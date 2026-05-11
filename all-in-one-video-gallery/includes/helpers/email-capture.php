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

	fputcsv( $output, array(
		__( 'Email Address', 'all-in-one-video-gallery' ),
		__( 'Name', 'all-in-one-video-gallery' ),
		__( 'Phone Number', 'all-in-one-video-gallery' ),
		__( 'Video Title', 'all-in-one-video-gallery' ),
		__( 'Video ID', 'all-in-one-video-gallery' ),
		__( 'Page URL', 'all-in-one-video-gallery' ),
		__( 'Date', 'all-in-one-video-gallery' )
	) );

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
		) );
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
