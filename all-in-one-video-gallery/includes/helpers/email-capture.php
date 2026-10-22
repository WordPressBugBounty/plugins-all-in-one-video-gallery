<?php

/**
 * Email & Lead Capture helper functions.
 *
 * @link    https://plugins360.com
 * @since   4.7.6
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Whether the email capture form may be shown to the current visitor.
 *
 * A logged-out visitor always qualifies. A logged-in user qualifies only when the
 * "Logged-in Users" setting is on, which is off by default and reproduces the
 * pre-4.9.4 behaviour of never showing the form to logged-in users.
 *
 * Both the render path ( aiovg_get_email_capture_config() ) and the AJAX submit
 * handler ( AIOVG_Public::save_email_lead() ) call this, so the form can never
 * render for a visitor whose submission would then be rejected.
 *
 * @since  4.9.4
 * @return bool True when the form may be shown and a submission accepted.
 */
function aiovg_can_show_email_capture_to_user() {
	if ( ! is_user_logged_in() ) {
		return true;
	}

	$email_capture_settings = aiovg_get_option( 'aiovg_email_capture_settings' );

	// Added in 4.9.4, so an install that has not saved these settings since upgrading
	// has no such key. Absent must mean 0 — the pre-4.9.4 behaviour.
	$show_to_logged_in_users = isset( $email_capture_settings['show_to_logged_in_users'] ) ? (int) $email_capture_settings['show_to_logged_in_users'] : 0;

	/**
	 * Filter whether the email capture form is shown to a logged-in user.
	 *
	 * Applied to logged-in users only — a logged-out visitor always qualifies and
	 * never reaches this filter. Intended for role-level control, for example to
	 * show the form to subscribers but not to editors.
	 *
	 * @since 4.9.4
	 * @param bool $show    Whether to show the form, from the "Logged-in Users" setting.
	 * @param int  $user_id ID of the logged-in user.
	 */
	return (bool) apply_filters( 'aiovg_email_capture_show_to_user', ! empty( $show_to_logged_in_users ), get_current_user_id() );
}

/**
 * Create the aiovg_email_leads database table using dbDelta().
 *
 * Safe to call on every activation or upgrade — dbDelta() only creates the
 * table if it does not exist, or adds any missing columns.
 *
 * @since 4.7.6
 */
function aiovg_email_capture_create_table() {
	global $wpdb;

	$table_name      = $wpdb->prefix . 'aiovg_email_leads';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		email VARCHAR(255) NOT NULL,
		name VARCHAR(255) NOT NULL DEFAULT '',
		phone VARCHAR(50) NOT NULL DEFAULT '',
		video_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		video_url VARCHAR(2083) NOT NULL DEFAULT '',
		page_url VARCHAR(2083) NOT NULL DEFAULT '',
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_email (email),
		KEY idx_video_id (video_id),
		KEY idx_created_at (created_at)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Output email leads as a downloadable CSV file and exit.
 *
 * Sets the appropriate HTTP headers, writes the header row and all lead rows
 * to the output stream, then terminates execution.
 *
 * @since 4.7.6
 * @param array  $leads     Array of lead row objects from aiovg_email_capture_get_leads().
 * @param string $file_name Suggested download filename.
 */
function aiovg_email_capture_generate_csv( $leads, $file_name = 'aiovg-emails.csv' ) {
	// Clear any buffered output before streaming the CSV.
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file_name ) . '"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	$output = fopen( 'php://output', 'w' );

	// UTF-8 BOM so Excel opens the file correctly.
	fputs( $output, "\xEF\xBB\xBF" );

	// Pass $escape explicitly; PHP 8.4 deprecates relying on its default.
	fputcsv( $output, array(
		__( 'Email Address', 'all-in-one-video-gallery' ),
		__( 'Name', 'all-in-one-video-gallery' ),
		__( 'Phone Number', 'all-in-one-video-gallery' ),
		__( 'Video Title', 'all-in-one-video-gallery' ),
		__( 'Video ID', 'all-in-one-video-gallery' ),
		__( 'Page URL', 'all-in-one-video-gallery' ),
		__( 'Date', 'all-in-one-video-gallery' )
	), ',', '"', '\\' );

	foreach ( $leads as $lead ) {
		$video_id = absint( $lead->video_id );

		if ( $video_id > 0 ) {
			$video_title = get_the_title( $video_id );
		} elseif ( ! empty( $lead->video_url ) ) {
			$video_title = $lead->video_url;
		} else {
			$video_title = '';
		}

		fputcsv( $output, array(
			$lead->email,
			$lead->name,
			$lead->phone,
			$video_title,
			$video_id,
			$lead->page_url,
			$lead->created_at
		), ',', '"', '\\' );
	}

	fclose( $output );
	exit;
}

/**
 * Query the email leads table with optional filters.
 *
 * @since  4.7.6
 * @param  array $args Optional filter arguments.
 * @return array       Array of lead row objects.
 */
function aiovg_email_capture_get_leads( $args = array() ) {
	global $wpdb;

	$table = $wpdb->prefix . 'aiovg_email_leads';

	$video_id    = isset( $args['video_id'] ) ? absint( $args['video_id'] ) : 0;
	$date_from   = isset( $args['date_from'] ) ? sanitize_text_field( $args['date_from'] ) : '';
	$date_to     = isset( $args['date_to'] ) ? sanitize_text_field( $args['date_to'] ) : '';

	$where_parts = array();
	$values      = array();

	if ( $video_id > 0 ) {
		$where_parts[] = 'video_id = %d';
		$values[]      = $video_id;
	}

	if ( '' !== $date_from ) {
		$where_parts[] = 'created_at >= %s';
		$values[]      = $date_from . ' 00:00:00';
	}

	if ( '' !== $date_to ) {
		$where_parts[] = 'created_at <= %s';
		$values[]      = $date_to . ' 23:59:59';
	}

	$where_clause = '';
	if ( ! empty( $where_parts ) ) {
		$where_clause = ' WHERE ' . implode( ' AND ', $where_parts );
	}

	$sql = "SELECT * FROM {$table}{$where_clause} ORDER BY id ASC";

	if ( ! empty( $values ) ) {
		$query_args = array_merge( array( $sql ), $values );
		$sql        = call_user_func_array( array( $wpdb, 'prepare' ), $query_args );
	}

	return $wpdb->get_results( $sql );
}

/**
 * Resolve who should be emailed about a captured lead.
 *
 * A per-video address replaces the site-wide one rather than adding to it, so a
 * client's video routes only to that client. The video's author, when the setting
 * is on, is *added* to whichever of those won — the realistic pattern is "the
 * author and a central inbox", which a fallback could not express.
 *
 * A video with no post ID — a shortcode, block or widget playing a bare URL — has
 * nowhere to hold a per-video address and no author, so it always falls through to
 * the site-wide address, or to nobody when that is empty.
 *
 * @since  4.9.4
 * @param  int   $video_id ID of the video the lead was captured on. 0 when unknown.
 * @return array           Validated, de-duplicated email addresses. Empty means send nothing.
 */
function aiovg_email_capture_get_notification_recipients( $video_id = 0 ) {
	$settings = aiovg_get_option( 'aiovg_email_lead_captured_settings' );

	// Added in 4.9.4, so an install that has not saved these settings since upgrading
	// has no such key. Absent must mean "notify nobody" — never a fallback address.
	$global_email        = isset( $settings['notification_email'] ) ? $settings['notification_email'] : '';
	$notify_video_author = isset( $settings['notify_video_author'] ) ? (int) $settings['notify_video_author'] : 0;

	$video_id = absint( $video_id );
	$is_video = ( $video_id > 0 && 'aiovg_videos' == get_post_type( $video_id ) );

	$configured = '';

	if ( $is_video ) {
		$configured = (string) get_post_meta( $video_id, 'email_capture_notification_email', true );
	}

	if ( '' === trim( $configured ) ) {
		$configured = (string) $global_email;
	}

	$recipients = array();
	$seen       = array();

	// Split on whitespace as well as commas. Both addresses are stored with
	// sanitize_text_field(), which collapses newlines to spaces, so a list entered one
	// per line arrives space separated rather than comma separated.
	$entries = preg_split( '/[\s,]+/', $configured, -1, PREG_SPLIT_NO_EMPTY );

	if ( ! is_array( $entries ) ) {
		$entries = array();
	}

	foreach ( $entries as $entry ) {
		$email = sanitize_email( $entry );

		// Nothing has checked these addresses before now — they are stored as typed, so
		// that a mistake stays visible and correctable in the settings field. One bad
		// entry must never cost the rest of the list.
		if ( empty( $email ) || ! is_email( $email ) ) {
			continue;
		}

		$key = strtolower( $email );

		if ( isset( $seen[ $key ] ) ) {
			continue;
		}

		$seen[ $key ] = true;
		$recipients[] = $email;
	}

	if ( $notify_video_author && $is_video ) {
		$author_id = (int) get_post_field( 'post_author', $video_id );

		// Author id 0 is an import that never had one; get_userdata() is false when the
		// account has since been deleted. Neither is an error worth failing the send for.
		if ( $author_id > 0 ) {
			$author = get_userdata( $author_id );

			if ( false !== $author && ! empty( $author->user_email ) ) {
				$key = strtolower( $author->user_email );

				if ( ! isset( $seen[ $key ] ) ) {
					$seen[ $key ] = true;
					$recipients[] = $author->user_email;
				}
			}
		}
	}

	/**
	 * Filter the addresses a captured lead is emailed to.
	 *
	 * Runs after the per-video, site-wide and author addresses have been resolved,
	 * validated and de-duplicated. Return an empty array to send nothing.
	 *
	 * @since 4.9.4
	 * @param array $recipients Resolved email addresses.
	 * @param int   $video_id   ID of the video the lead was captured on, 0 when unknown.
	 */
	$recipients = apply_filters( 'aiovg_email_capture_notification_recipients', $recipients, $video_id );

	return is_array( $recipients ) ? array_values( $recipients ) : array();
}

/**
 * Insert a lead record into the email leads table.
 *
 * @since  4.7.6
 * @param  array $data Sanitized lead data (email, name, phone, video_id, video_url, page_url).
 * @return int|false   Inserted row ID on success, false on failure.
 */
function aiovg_email_capture_insert_lead( $data ) {
	global $wpdb;

	$table  = $wpdb->prefix . 'aiovg_email_leads';
	$result = $wpdb->insert(
		$table,
		$data,
		array( '%s', '%s', '%s', '%d', '%s', '%s' )
	);

	return $result ? $wpdb->insert_id : false;
}

/**
 * Email the configured recipients about a captured lead.
 *
 * Called from AIOVG_Public::save_email_lead() once the lead row is stored. The
 * lead is already saved by this point and playback must not depend on SMTP, so
 * this never throws and its return value is ignored by the caller.
 *
 * @since  4.9.4
 * @param  int   $lead_id ID of the stored lead row.
 * @param  array $data    Sanitized lead data, as passed to aiovg_email_capture_insert_lead().
 * @return bool           True when a mail was handed to wp_mail() successfully.
 */
function aiovg_email_capture_notify( $lead_id, $data ) {
	$video_id   = isset( $data['video_id'] ) ? absint( $data['video_id'] ) : 0;
	$recipients = aiovg_email_capture_get_notification_recipients( $video_id );

	/**
	 * Filter whether to send the lead notification email at all.
	 *
	 * Return false to suppress it — useful when another integration, hooked to
	 * "aiovg_email_capture_lead_saved", already notifies the same people.
	 *
	 * @since 4.9.4
	 * @param bool  $send       Whether to send. False by default when no recipient resolved.
	 * @param int   $lead_id    ID of the stored lead row.
	 * @param array $data       Sanitized lead data.
	 * @param array $recipients Resolved email addresses.
	 */
	$send = apply_filters( 'aiovg_email_capture_send_notification', ! empty( $recipients ), $lead_id, $data, $recipients );

	if ( empty( $send ) || empty( $recipients ) ) {
		return false;
	}

	$settings = aiovg_get_option( 'aiovg_email_lead_captured_settings' );

	// Seeded by AIOVG_Admin::insert_missing_options(). Empty subject or body means
	// aiovg_send_mail() declines to send, which is the correct reading of an admin
	// who deliberately cleared the template.
	$subject = isset( $settings['subject'] ) ? $settings['subject'] : '';
	$body    = isset( $settings['body'] ) ? $settings['body'] : '';

	if ( '' === trim( $subject ) || '' === trim( $body ) ) {
		return false;
	}

	$site_name    = get_bloginfo( 'name' );
	$site_url     = get_bloginfo( 'url' );
	$date_format  = get_option( 'date_format' );
	$time_format  = get_option( 'time_format' );
	$current_time = current_time( 'timestamp' );

	$video_url = isset( $data['video_url'] ) ? $data['video_url'] : '';
	$page_url  = isset( $data['page_url'] ) ? $data['page_url'] : '';

	// Same title fallback aiovg_email_capture_generate_csv() uses, so the email and the
	// CSV export name a URL-only video the same way.
	if ( $video_id > 0 ) {
		$video_title = get_the_title( $video_id );
		$permalink   = get_permalink( $video_id );

		if ( ! empty( $permalink ) ) {
			$video_url = $permalink;
		}
	} elseif ( ! empty( $video_url ) ) {
		$video_title = $video_url;
	} else {
		$video_title = '';
	}

	$placeholders = array(
		'{email}'       => isset( $data['email'] ) ? $data['email'] : '',
		'{name}'        => isset( $data['name'] ) ? $data['name'] : '',
		'{phone}'       => isset( $data['phone'] ) ? $data['phone'] : '',
		'{video_title}' => $video_title,
		'{video_link}'  => ! empty( $video_url ) ? sprintf( '<a href="%s">%s</a>', esc_url( $video_url ), $video_title ) : $video_title,
		'{video_url}'   => ! empty( $video_url ) ? sprintf( '<a href="%s">%s</a>', esc_url( $video_url ), $video_url ) : '',
		'{page_url}'    => ! empty( $page_url ) ? sprintf( '<a href="%s">%s</a>', esc_url( $page_url ), $page_url ) : '',
		'{site_name}'   => $site_name,
		'{site_link}'   => sprintf( '<a href="%s">%s</a>', esc_url( $site_url ), $site_name ),
		'{today}'       => date_i18n( $date_format, $current_time ),
		'{now}'         => date_i18n( $date_format . ' ' . $time_format, $current_time )
	);

	$subject = apply_filters( 'aiovg_translate_strings', strtr( $subject, $placeholders ), 'email_lead_captured_settings_subject' );
	$message = strtr( $body, $placeholders );
	$message = apply_filters( 'aiovg_translate_strings', nl2br( $message ), 'email_lead_captured_settings_body' );

	// wp_mail() catches PHPMailer's own exceptions, but an SMTP plugin hooked to
	// "phpmailer_init" can throw its own. The lead is already stored and the visitor is
	// waiting on the response, so a broken mail configuration must not surface as a
	// failed submission.
	try {
		$sent = aiovg_send_mail( $recipients, $subject, $message );
	} catch ( Exception $e ) {
		$sent = false;
	}

	return $sent;
}

/**
 * Resolve whether the email capture form should be shown, and with what settings.
 *
 * Single source of truth for the email capture decision, shared by the JavaScript
 * player ( includes/player/base.php ) and the three iframe player templates. It
 * answers only what is per-video and cacheable: the master switch, the per-video
 * "email_capture" meta, the per-instance override, and the roll positions the
 * caller is able to render.
 *
 * It deliberately does not test the "aiovg_email_captured" cookie. That decision
 * belongs to the caller, which knows whether it is swapping a whole template or
 * rendering an overlay inside a player.
 *
 * @since  4.9.4
 * @param  array $args {
 *     Optional. Arguments used to resolve the settings.
 *
 *     @type int    $post_id   ID of the video being played. Default 0.
 *     @type string $post_type Post type of the video being played. Default "".
 *     @type array  $post_meta Pre-fetched get_post_meta() array. Omit to read the meta directly.
 *     @type mixed  $override  Per-instance "email_capture" value. 1 forces the form on, 0 forces
 *                             it off, and -1 ( "Default" ) states no preference and defers to the
 *                             video. Omit when the caller has no such control at all.
 *     @type string $rolls     Roll positions the caller can render: "all", "pre" or "midpost". Default "all".
 *     @type array  $atts      Per-instance attributes merged over the saved settings. Default array().
 *     @type string $video_url Video URL, for the capture key when there is no "aiovg_videos"
 *                             post. Omit when the caller cannot know it yet — see the note on
 *                             "capture_key" below.
 * }
 * @return array|false         Resolved email capture settings, or false if the form must not be shown.
 */
function aiovg_get_email_capture_config( $args = array() ) {
	$email_capture_settings = aiovg_get_option( 'aiovg_email_capture_settings' );

	if ( empty( $email_capture_settings['enable_email_capture'] ) ) {
		return false;
	}

	if ( ! aiovg_can_show_email_capture_to_user() ) {
		return false;
	}

	$post_id   = isset( $args['post_id'] ) ? (int) $args['post_id'] : 0;
	$post_type = isset( $args['post_type'] ) ? $args['post_type'] : '';
	$post_meta = ( isset( $args['post_meta'] ) && is_array( $args['post_meta'] ) ) ? $args['post_meta'] : null;
	$rolls     = isset( $args['rolls'] ) ? $args['rolls'] : 'all';
	$atts      = ( isset( $args['atts'] ) && is_array( $args['atts'] ) ) ? $args['atts'] : array();

	// Per-video setting. Start at the "Default" sentinel rather than at 1, so a video
	// that never states a preference — including one with no aiovg_videos post at all,
	// such as a shortcode or block playing a bare URL — is resolved through the
	// site-wide mode below instead of being forced on.
	$show_email_capture = -1;

	if ( 'aiovg_videos' == $post_type ) {
		if ( null !== $post_meta ) {
			// The caller already fetched every meta value for this video.
			if ( isset( $post_meta['email_capture'] ) ) {
				$show_email_capture = (int) $post_meta['email_capture'][0];
			}
		} elseif ( $post_id > 0 ) {
			$email_capture = get_post_meta( $post_id, 'email_capture', true );

			if ( '' !== $email_capture ) {
				$show_email_capture = (int) $email_capture;
			}
		}
	}

	// Per-instance override, from a shortcode, block, widget or the player query string.
	if ( isset( $args['override'] ) ) {
		$override = (int) $args['override'];

		// -1 is "Default", which on a per-instance control means "state no preference".
		// It must fall through to whatever the video itself says — overwriting the meta
		// with -1 would send an explicitly enabled video back to the site-wide mode.
		if ( -1 !== $override ) {
			$show_email_capture = $override;
		}
	}

	if ( $show_email_capture === -1 ) { // Global
		$show_email_capture = aiovg_is_email_capture_global_enabled() ? 1 : 0;
	}

	if ( empty( $show_email_capture ) ) {
		return false;
	}

	$email_capture_settings = shortcode_atts( $email_capture_settings, $atts );

	$pre_roll  = ! empty( $email_capture_settings['pre_roll'] ) ? 1 : 0;
	$mid_roll  = ! empty( $email_capture_settings['mid_roll'] ) ? (int) $email_capture_settings['mid_roll'] : 0;
	$post_roll = ! empty( $email_capture_settings['post_roll'] ) ? 1 : 0;

	// Nothing to show unless a roll position the caller can render is enabled.
	switch ( $rolls ) {
		case 'pre':
			$has_roll = ! empty( $pre_roll );
			break;

		case 'midpost':
			$has_roll = ! empty( $mid_roll ) || ! empty( $post_roll );
			break;

		default:
			$has_roll = ! empty( $pre_roll ) || ! empty( $mid_roll ) || ! empty( $post_roll );
	}

	if ( empty( $has_roll ) ) {
		return false;
	}

	$header_text  = ! empty( $email_capture_settings['header_text'] ) ? $email_capture_settings['header_text'] : __( 'Enter your email to continue watching', 'all-in-one-video-gallery' );
	$footer_text  = ! empty( $email_capture_settings['footer_text'] ) ? $email_capture_settings['footer_text'] : '';
	$button_label = ! empty( $email_capture_settings['button_label'] ) ? $email_capture_settings['button_label'] : __( 'Submit', 'all-in-one-video-gallery' );
	$skip_text    = ! empty( $email_capture_settings['skip_text'] ) ? $email_capture_settings['skip_text'] : __( 'Skip', 'all-in-one-video-gallery' );

	$settings = array();

	// What the markup carries, for the scripts to scope their clearing by: this video's
	// key in the per-video modes, and empty in the site-wide ones, where one capture
	// clears every player on the page.
	//
	// Also empty when the caller passed no URL and there is no post — the JavaScript
	// player resolves its sources after this runs and through a filter, so it fills the
	// key in at markup time from the same helper rather than having the rule guess at a
	// source list it cannot see.
	//
	// Safe to hand straight to aiovg_should_display_email_capture() as well: in the
	// site-wide modes that test answers from the scope before the key is looked at.
	$settings['capture_key'] = aiovg_get_email_capture_markup_key(
		$post_id,
		$post_type,
		isset( $args['video_url'] ) ? $args['video_url'] : ''
	);

	// Informational — the resolved mode, for anything filtering this config. Nothing in
	// the markup or the scripts reads it: the scope is already baked into capture_key.
	$settings['display_frequency'] = aiovg_get_email_capture_display_frequency();

	if ( 'midpost' != $rolls ) {
		$settings['pre_roll'] = $pre_roll;
	}

	if ( 'pre' != $rolls ) {
		$settings['mid_roll']  = $mid_roll;
		$settings['post_roll'] = $post_roll;
	}

	$settings['collect_name']  = ! empty( $email_capture_settings['collect_name'] ) ? 1 : 0;
	$settings['collect_phone'] = ! empty( $email_capture_settings['collect_phone'] ) ? 1 : 0;
	$settings['allow_skip']    = ! empty( $email_capture_settings['allow_skip'] ) ? 1 : 0;
	$settings['header_text']   = apply_filters( 'aiovg_translate_strings', $header_text, 'header_text' );
	$settings['footer_text']   = apply_filters( 'aiovg_translate_strings', $footer_text, 'footer_text' );
	$settings['button_label']  = apply_filters( 'aiovg_translate_strings', $button_label, 'button_label' );
	$settings['skip_text']     = apply_filters( 'aiovg_translate_strings', $skip_text, 'skip_text' );

	return $settings;
}

/**
 * Get the "Display Frequency" mode.
 *
 * The four remembering modes are two independent questions crossed: how widely a
 * capture is remembered, and whether skipping counts as having been asked. Every
 * mode name states both, so the grid can be read straight off the stored value.
 *
 *                       | a skip counts    | a skip is forgiven
 *     ------------------+------------------+----------------------------------
 *      every video      | once_per_visitor | once_per_visitor_until_submitted
 *      the one video    | once_per_video   | once_per_video_until_submitted
 *
 * "always" remembers nothing at all, so neither question applies to it.
 *
 * Read the scope through aiovg_is_email_capture_memory_site_wide() rather than
 * comparing against a mode name, so adding a mode does not mean hunting for
 * comparisons.
 *
 * Whitelisted on read rather than on write, so a value written straight to the option
 * by an import or a snippet still resolves to something sane.
 *
 * @since  4.9.4
 * @return string One of "once_per_visitor", "once_per_visitor_until_submitted",
 *                "once_per_video", "once_per_video_until_submitted", "always".
 */
function aiovg_get_email_capture_display_frequency() {
	$email_capture_settings = aiovg_get_option( 'aiovg_email_capture_settings' );

	// Added in 4.9.4, so an install that has not saved these settings since upgrading has
	// no such key. Absent must mean "once_per_visitor" — the pre-4.9.4 behaviour.
	$frequency = isset( $email_capture_settings['display_frequency'] ) ? $email_capture_settings['display_frequency'] : 'once_per_visitor';

	$modes = array(
		'once_per_visitor',
		'once_per_visitor_until_submitted',
		'once_per_video',
		'once_per_video_until_submitted',
		'always'
	);

	if ( ! in_array( $frequency, $modes, true ) ) {
		$frequency = 'once_per_visitor';
	}

	return $frequency;
}

/**
 * Derive the key that identifies one video for email capture purposes.
 *
 * Per-video scoping cannot key on the post ID alone, because a shortcode, block or
 * widget may play a bare URL with no "aiovg_videos" post behind it. A real post uses
 * its ID; anything else uses a short hash of the normalised URL.
 *
 * This is the **only** definition of the rule. Never reimplement it in JavaScript —
 * the markup carries the result as a data attribute, and the AJAX handler recomputes
 * it from the posted video_id / video_url, so the two always agree without the client
 * being trusted to supply a key.
 *
 * The returned key is never purely numeric unless it is a post ID, so a hashed key can
 * never collide with one.
 *
 * @since  4.9.4
 * @param  int    $post_id   ID of the video being played. 0 when there is no post.
 * @param  string $post_type Post type of the video being played.
 * @param  string $video_url Video URL, used only when there is no "aiovg_videos" post.
 * @return string            The capture key, or an empty string when neither is available.
 */
function aiovg_get_email_capture_key( $post_id = 0, $post_type = '', $video_url = '' ) {
	$post_id = (int) $post_id;

	if ( $post_id > 0 && 'aiovg_videos' == $post_type ) {
		return (string) $post_id;
	}

	$video_url = trim( (string) $video_url );

	if ( '' === $video_url ) {
		return '';
	}

	// Escape before hashing, so the markup and the AJAX handler hash the same string.
	// The markup writes the URL through esc_url() and the handler reads it back through
	// esc_url_raw(), which percent-encodes characters a raw URL may carry literally — a
	// space being the common one. Hashing the raw value here would give a video whose
	// URL contains a space a key the handler could never reproduce, so its capture would
	// be recorded under a key that never matches again. esc_url_raw() is idempotent, so
	// a caller that has already escaped loses nothing.
	$video_url = esc_url_raw( $video_url );

	if ( '' === $video_url ) {
		return '';
	}

	// Normalise so the same video yields the same key whether it was linked over http
	// or https, with or without a trailing slash, or with the host in a different case.
	// The query string is kept, because for several providers it carries the video ID.
	$parts = wp_parse_url( $video_url );

	$host  = isset( $parts['host'] ) ? strtolower( $parts['host'] ) : '';
	$path  = isset( $parts['path'] ) ? rtrim( $parts['path'], '/' ) : '';
	$query = isset( $parts['query'] ) ? $parts['query'] : '';

	$normalized = $host . $path;

	if ( '' !== $query ) {
		$normalized .= '?' . $query;
	}

	// A URL wp_parse_url() could make nothing of — fall back to the raw value rather
	// than hashing an empty string and giving every such video the same key.
	if ( '' === $normalized ) {
		$normalized = $video_url;
	}

	return 'u' . substr( md5( $normalized ), 0, 12 );
}

/**
 * The capture key to put in the markup, for the scripts to scope their clearing by.
 *
 * Empty in the site-wide modes, where one capture clears every player on the page.
 * Emitting the answer rather than the mode keeps the scope rule in PHP: every script
 * can then use the key it is given unconditionally, instead of each re-deciding what
 * the mode means — which is how the two engines drift apart.
 *
 * This is only about what the page does after a capture. It never affects what gets
 * stored: AIOVG_Public::save_email_lead() recomputes the real key server-side from
 * the posted video id or URL.
 *
 * @since  4.9.4
 * @param  int    $post_id   ID of the video being played. 0 when there is no post.
 * @param  string $post_type Post type of the video being played.
 * @param  string $video_url Video URL, used only when there is no "aiovg_videos" post.
 * @return string            The capture key, or "" when the mode is site-wide.
 */
function aiovg_get_email_capture_markup_key( $post_id = 0, $post_type = '', $video_url = '' ) {
	if ( aiovg_is_email_capture_memory_site_wide() ) {
		return '';
	}

	return aiovg_get_email_capture_key( $post_id, $post_type, $video_url );
}

/**
 * Read this visitor's email capture memory.
 *
 * Kept in the "aiovg_email_captured" cookie for a logged-out visitor, and in user meta
 * of the same name for a logged-in one — a logged-in visitor who can see the form should
 * not be re-prompted merely because they switched browser.
 *
 * @since  4.9.4
 * @return string Raw stored value: "" for nothing, "1" for the pre-4.9.4 site-wide marker,
 *                or a comma-separated list of capture keys.
 */
function aiovg_get_email_capture_memory() {
	if ( is_user_logged_in() ) {
		$memory = get_user_meta( get_current_user_id(), 'aiovg_email_captured', true );

		return is_string( $memory ) ? $memory : '';
	}

	if ( ! isset( $_COOKIE['aiovg_email_captured'] ) ) {
		return '';
	}

	return sanitize_text_field( wp_unslash( $_COOKIE['aiovg_email_captured'] ) );
}

/**
 * Get the values to pre-fill the email capture form with.
 *
 * Only a logged-in user has anything to pre-fill, and the form reaches a logged-in
 * user only when the "Logged-in Users" setting is on, so every logged-out visitor
 * gets empty strings and sees the form exactly as before.
 *
 * WordPress profiles hold no phone number, so "phone" is always empty here. It is
 * part of the returned array so the filter below can supply one from wherever a
 * site actually stores it.
 *
 * @since  4.9.4
 * @return array Associative array of "email", "name" and "phone". Any may be an empty string.
 */
function aiovg_get_email_capture_prefill() {
	$empty = array(
		'email' => '',
		'name'  => '',
		'phone' => ''
	);

	if ( ! is_user_logged_in() ) {
		return $empty;
	}

	$user = wp_get_current_user();

	if ( empty( $user->ID ) ) {
		return $empty;
	}

	$prefill = $empty;

	$prefill['email'] = $user->user_email;

	// Prefer a real name. display_name is often the login name, which is not what
	// someone means when a form asks for their name.
	$name = trim( $user->first_name . ' ' . $user->last_name );

	if ( '' === $name ) {
		$name = $user->display_name;
	}

	$prefill['name'] = $name;

	/**
	 * Filter the values used to pre-fill the email capture form.
	 *
	 * Only applied for a logged-in user who is allowed to see the form. Use it to
	 * supply a phone number, which WordPress itself does not store, or to override
	 * the name with one from elsewhere.
	 *
	 * @since 4.9.4
	 * @param array $prefill Associative array of "email", "name" and "phone".
	 * @param int   $user_id ID of the logged-in user.
	 */
	$prefill = apply_filters( 'aiovg_email_capture_prefill', $prefill, $user->ID );

	// A filter may return a partial array, or something that is not an array at all.
	// Five templates index every key, so restore the shape before handing it back.
	if ( ! is_array( $prefill ) ) {
		$prefill = array();
	}

	$prefill = array_merge( $empty, array_intersect_key( $prefill, $empty ) );

	return $prefill;
}

/**
 * Whether this visitor has already dealt with the form for a given video.
 *
 * @since  4.9.4
 * @param  string $capture_key Key from aiovg_get_email_capture_key(). "" when unknown.
 * @return bool                True when the form should stay hidden.
 */
function aiovg_has_captured_email( $capture_key = '' ) {
	$frequency = aiovg_get_email_capture_display_frequency();

	// Nothing is ever recorded, so nothing ever suppresses the form.
	if ( 'always' === $frequency ) {
		return false;
	}

	$memory = aiovg_get_email_capture_memory();

	if ( '' === $memory ) {
		return false;
	}

	// The pre-4.9.4 value means "captured, site-wide". Honour it in every mode until it
	// expires, so nobody who already submitted is re-prompted on the day this ships.
	if ( '1' === $memory ) {
		return true;
	}

	if ( aiovg_is_email_capture_memory_site_wide( $frequency ) ) {
		return true;
	}

	// Per-video modes. A video whose key could not be derived cannot be remembered, so it
	// shows again rather than being silenced by some other video's key.
	if ( '' === $capture_key ) {
		return false;
	}

	return in_array( $capture_key, explode( ',', $memory ), true );
}

/**
 * Whether a video left on "Default" resolves to enabled.
 *
 * The single source of truth for reading the site-wide mode, shared by the
 * resolver, the metabox description and the All Videos list column.
 *
 * Note the per-video "Default" and the per-instance "Default" are not the same
 * question: on a video it means "follow the site-wide setting", which is what
 * this answers; on a shortcode, block or widget it means "apply no override",
 * which is handled in aiovg_get_email_capture_config() and never reaches here.
 *
 * @since  4.9.4
 * @return bool True when a video that states no preference shows the form.
 */
function aiovg_is_email_capture_global_enabled() {
	$email_capture_settings = aiovg_get_option( 'aiovg_email_capture_settings' );

	if ( empty( $email_capture_settings['enable_email_capture'] ) ) {
		return false;
	}

	// Mode 2 shows the form only on videos that opted in, so "Global" is off.
	// Any other truthy value — a legacy 1, or something written straight to the
	// option by an import or a snippet — keeps the pre-4.9.4 behaviour, which is
	// what every other "! empty( enable_email_capture )" gate assumes.
	return ( 2 != $email_capture_settings['enable_email_capture'] );
}

/**
 * Whether a capture is remembered for the whole site rather than for one video.
 *
 * The single source of truth for the scope half of the "Display Frequency" modes —
 * see aiovg_get_email_capture_display_frequency() for the grid. Everything that has
 * to know the scope asks this, rather than comparing against a mode name, so adding
 * a mode is one edit here instead of a hunt through PHP and three scripts.
 *
 * @since  4.9.4
 * @param  string $frequency Mode to test. Omit to read the site's current mode.
 * @return bool              True when one capture silences every video.
 */
function aiovg_is_email_capture_memory_site_wide( $frequency = '' ) {
	if ( '' === $frequency ) {
		$frequency = aiovg_get_email_capture_display_frequency();
	}

	return in_array( $frequency, array( 'once_per_visitor', 'once_per_visitor_until_submitted' ), true );
}

/**
 * Record that this visitor has dealt with the form for one video.
 *
 * What gets written is decided entirely by the "Display Frequency" mode, so no separate
 * skip/submit marker is stored.
 *
 * @since 4.9.4
 * @param string $capture_key Key from aiovg_get_email_capture_key().
 * @param bool   $skipped     True when the visitor skipped rather than submitted.
 * @return bool               True when the capture is now remembered.
 */
function aiovg_record_email_capture( $capture_key = '', $skipped = false ) {
	$frequency = aiovg_get_email_capture_display_frequency();

	// Never remembered. Note the GDPR consent cookie is written separately and is not
	// affected, or the consent overlay would return on every play.
	if ( 'always' === $frequency ) {
		return false;
	}

	// The forgiving modes: only a submission silences it, so a skip leaves it asking.
	if ( $skipped && in_array( $frequency, array( 'once_per_video_until_submitted', 'once_per_visitor_until_submitted' ), true ) ) {
		return false;
	}

	if ( aiovg_is_email_capture_memory_site_wide( $frequency ) ) {
		// Keep writing the pre-4.9.4 value, so the one meaning of "1" is preserved and a
		// site switching modes does not leave two kinds of site-wide marker behind.
		$value = '1';
	} else {
		// Nothing to remember it by — see aiovg_get_email_capture_key().
		if ( '' === $capture_key ) {
			return false;
		}

		$memory = aiovg_get_email_capture_memory();
		$keys   = ( '' === $memory || '1' === $memory ) ? array() : explode( ',', $memory );

		if ( in_array( $capture_key, $keys, true ) ) {
			return true;
		}

		$keys[] = $capture_key;

		// FIFO cap. A large gallery must not push a multi-kilobyte cookie into every
		// request header; the oldest entries are the ones a visitor is least likely to
		// meet again.
		$limit = 50;

		if ( count( $keys ) > $limit ) {
			$keys = array_slice( $keys, - $limit );
		}

		$value = implode( ',', $keys );
	}

	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), 'aiovg_email_captured', $value );
		return true;
	}

	setcookie( 'aiovg_email_captured', $value, time() + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );

	return true;
}

/**
 * Sanitize the blocked email domains setting.
 *
 * Normalises the newline-separated list: strips http(s)://, trailing slashes,
 * and leading/trailing whitespace from each line. Blank lines are removed.
 *
 * @since  4.7.6
 * @param  string $value Raw textarea input.
 * @return string        Sanitized newline-separated domain list.
 */
function aiovg_sanitize_email_domains( $value ) {
	$value = sanitize_textarea_field( $value );
	$lines = explode( "\n", $value );
	$clean = array();

	foreach ( $lines as $line ) {
		$line = trim( $line );
		$line = preg_replace( '#^https?://#i', '', $line );
		$line = rtrim( $line, '/' );
		$line = trim( $line );

		if ( '' !== $line ) {
			$clean[] = $line;
		}
	}

	return implode( "\n", $clean );
}

/**
 * Sanitize the mid-roll percentage setting.
 *
 * Accepts an integer between 1 and 99. Anything outside that range —
 * including 0 — is stored as an empty string (feature disabled).
 *
 * @since  4.7.6
 * @param  string|int $value Raw input value.
 * @return string|int        Sanitized value: int 1–99, or '' if disabled.
 */
function aiovg_sanitize_mid_roll_percentage( $value ) {
	$value = intval( $value );

	if ( $value >= 1 && $value <= 99 ) {
		return $value;
	}

	return '';
}

/**
 * Whether to display the email capture form for a video this visitor may have seen before.
 *
 * The last word on the decision, called from all four resolution sites so that a single
 * override reaches the JavaScript player, the iframe pre-roll page and both iframe
 * mid/post-roll templates alike.
 *
 * @since  4.9.4
 * @param  string $capture_key Key from aiovg_get_email_capture_key(). "" when unknown.
 * @return bool                True to display the form.
 */
function aiovg_should_display_email_capture( $capture_key = '' ) {
	$should_display = ! aiovg_has_captured_email( $capture_key );

	/**
	 * Filter whether the email capture form is displayed.
	 *
	 * Runs after the per-video memory has been consulted, so returning true shows the
	 * form to a visitor who has already submitted, and returning false hides it from one
	 * who has not. Useful where a page cache cannot be made to vary on the
	 * "aiovg_email_captured" cookie and the site would rather decide for itself.
	 *
	 * @since 4.9.4
	 * @param bool   $should_display Whether to display the form.
	 * @param string $capture_key    Key identifying the video, or "" when it has none.
	 */
	return (bool) apply_filters( 'aiovg_email_capture_should_display', $should_display, $capture_key );
}
