<?php

/**
 * Multilingual Compatibility.
 *
 * @link    https://plugins360.com
 * @since   4.9.6
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AIOVG_Multilingual class.
 *
 * The logic shared by the WPML and Polylang integrations. Each subclass supplies its
 * plugin's primitives and bails out while its plugin is not active.
 *
 * @since 4.9.6
 */
abstract class AIOVG_Multilingual {

	/**
	 * The current category, tag, user or playlist page, once looked up.
	 *
	 * @since  4.9.6
	 * @access private
	 * @var    array|false|null
	 */
	private $page_context = null;

	/**
	 * The value to append for each language, once looked up.
	 *
	 * @since  4.9.6
	 * @access private
	 * @var    array
	 */
	private $translated_values = array();

	/**
	 * The archive page's path and its translations' paths, once looked up.
	 *
	 * @since  4.9.6
	 * @access private
	 * @var    array
	 */
	private $archive_paths = array();

	/**
	 * Plugin page URLs in other languages, once looked up.
	 *
	 * @since  4.9.6
	 * @access private
	 * @var    array
	 */
	private $page_urls = array();

	/**
	 * Add the current language to the admin-ajax.php URL.
	 *
	 * WPML and Polylang read "lang" from the request before falling back to a cookie.
	 *
	 * @since  4.9.6
	 * @param  string $url AJAX URL.
	 * @return string      Filtered URL.
	 */
	public function filter_ajax_url( $url ) {
		if ( ! $this->is_active() ) {
			return $url;
		}

		$language = $this->get_current_language();

		return empty( $language ) ? $url : add_query_arg( 'lang', $language, $url );
	}

	/**
	 * Use the published translation of a post in the current language.
	 *
	 * @since  4.9.6
	 * @param  int    $object_id   Post ID.
	 * @param  string $object_type Post type.
	 * @return int                 Translated post ID, or the given ID when there is none.
	 */
	public function filter_object_id( $object_id, $object_type ) {
		$object_id = (int) $object_id;

		if ( ! $this->is_active() || $object_id <= 0 ) {
			return $object_id;
		}

		$language = $this->get_current_language();

		if ( '' === $language ) {
			return $object_id;
		}

		$translations = $this->get_post_translations( $object_id );

		if ( ! empty( $translations[ $language ] ) && $translations[ $language ] != $object_id && 'publish' == get_post_status( $translations[ $language ] ) ) {
			return (int) $translations[ $language ];
		}

		return $object_id;
	}

	/**
	 * Use a page path that does not change with the request's language.
	 *
	 * @since  4.9.6
	 * @param  string $path    URL-decoded page path.
	 * @param  int    $page_id Page ID.
	 * @return string          Filtered path.
	 */
	public function filter_page_path( $path, $page_id ) {
		if ( ! $this->is_active() ) {
			return $path;
		}

		// The front page has no path in any language, as in core
		if ( 'page' == get_option( 'show_on_front' ) && in_array( (int) get_option( 'page_on_front' ), array_map( 'intval', $this->get_post_translations( $page_id ) ), true ) ) {
			return '';
		}

		return $this->get_page_path( $page_id );
	}

	/**
	 * Add the paths of a page's published translations to its rewrite bases.
	 *
	 * @since  4.9.6
	 * @param  array $bases   URL-decoded page paths keyed by page ID.
	 * @param  int   $page_id Page ID.
	 * @return array          Filtered paths. The given page comes first, and a path shared by two translations is listed once.
	 */
	public function filter_page_rewrite_bases( $bases, $page_id ) {
		if ( ! $this->is_active() ) {
			return $bases;
		}

		$translations = $this->get_post_translations( $page_id );
		$paths        = array();

		// Given page first, so it keeps any path it shares with a translation
		$ids = array_unique( array_merge( array( (int) $page_id ), array_values( $translations ) ) );

		foreach ( $ids as $id ) {
			$path = $this->get_page_path( $id );

			if ( '' === $path ) {
				continue;
			}

			$path = $this->get_rewrite_path( $path, (string) array_search( $id, $translations, true ) );

			if ( ! in_array( $path, $paths, true ) ) {
				$paths[ $id ] = $path;
			}
		}

		// Keep any path another callback added
		foreach ( (array) $bases as $id => $path ) {
			if ( ! isset( $paths[ $id ] ) && ! in_array( $path, $paths, true ) ) {
				$paths[ $id ] = $path;
			}
		}

		return $paths;
	}

	/**
	 * Filter the 'aiovg_page_settings' option to the current language's pages.
	 *
	 * @since  4.9.6
	 * @param  array $settings Default settings array.
	 * @return array $settings Filtered array of settings.
	 */
	public function filter_page_settings( $settings ) {
		// Checked here: this plugin registers its hooks before WPML and Polylang have loaded
		if ( ! $this->is_active() || ! aiovg_can_filter_page_settings() || ! is_array( $settings ) ) {
			return $settings;
		}

		foreach ( $settings as $key => $value ) {
			if ( $value > 0 ) {
				$id = $this->get_translated_page_id( $value );

				if ( ! empty( $id ) ) {
					$settings[ $key ] = $id;
				}
			}
		}

		return $settings;
	}

	/**
	 * Build category, tag and playlist links in the term's own language.
	 *
	 * @since  4.9.6
	 * @param  string  $url      Term link.
	 * @param  WP_Term $term     Term object.
	 * @param  string  $taxonomy Taxonomy slug.
	 * @return string            Filtered term link.
	 */
	public function filter_term_link( $url, $term, $taxonomy ) {
		$pages = array(
			'aiovg_categories' => array( 'category', 'aiovg_category', 'aiovg_category_page_url' ),
			'aiovg_tags'       => array( 'tag', 'aiovg_tag', 'aiovg_tag_page_url' ),
			'aiovg_playlists'  => array( 'playlist', 'aiovg_playlist', 'aiovg_playlist_page_url' )
		);

		if ( ! isset( $pages[ $taxonomy ] ) || ! $this->is_active() ) {
			return $url;
		}

		$language = $this->get_term_language( $term, $taxonomy );
		$default  = $this->get_default_language();

		// Same language (where page settings are translated, not in the admin), or a fallback original, keeps the current page
		$is_mapped = has_filter( 'option_aiovg_page_settings', array( $this, 'filter_page_settings' ) );

		if ( empty( $language ) || $language === $default || ( $is_mapped && $language === $this->get_current_language() ) ) {
			return $url;
		}

		list( $key, $query_var, $filter ) = $pages[ $taxonomy ];

		$page_settings = aiovg_get_page_settings();
		$page_url      = empty( $page_settings[ $key ] ) ? '' : $this->get_page_url_in_language( $page_settings[ $key ], $language );

		if ( empty( $page_url ) ) {
			return $url;
		}

		if ( '' == get_option( 'permalink_structure' ) ) {
			$url = add_query_arg( $query_var, $term->slug, $page_url );
		} else {
			$url = aiovg_add_path_to_url( $page_url, $term->slug );
		}

		return apply_filters( $filter, $url, $term );
	}

	/**
	 * Use the video archive page's translated path in video links, in the video's own language.
	 *
	 * @since  4.9.6
	 * @param  string  $post_link The video permalink.
	 * @param  WP_Post $post      The video post.
	 * @return string             Filtered permalink.
	 */
	public function filter_video_permalink( $post_link, $post ) {
		if ( 'aiovg_videos' != $post->post_type || ! $this->is_active() ) {
			return $post_link;
		}

		$permalink_settings = aiovg_get_option( 'aiovg_permalink_settings' );

		if ( empty( $permalink_settings['video_archive_page'] ) ) {
			return $post_link;
		}

		$language = $this->get_post_language( $post->ID, 'aiovg_videos' );
		$page_id  = (int) $permalink_settings['video_archive_page'];

		// Once per request, as every video link needs the same lookups. Unprefixed paths: the link already carries the language directory.
		if ( ! isset( $this->archive_paths[ $page_id ] ) ) {
			$paths = array();

			foreach ( $this->get_post_translations( $page_id ) as $code => $id ) {
				$paths[ $code ] = $this->get_page_path( $id );
			}

			$this->archive_paths[ $page_id ] = array( $this->get_page_path( $page_id ), $paths );
		}

		list( $archive_path, $paths ) = $this->archive_paths[ $page_id ];

		if ( empty( $language ) || empty( $paths[ $language ] ) ) {
			return $post_link;
		}

		$from = '' === $archive_path ? '' : '/' . $archive_path . '/';
		$to   = '/' . $paths[ $language ] . '/';

		if ( '' === $from || '' === $to || $from === $to ) {
			return $post_link;
		}

		$pos = strpos( $post_link, $from );

		return false === $pos ? $post_link : substr_replace( $post_link, $to, $pos, strlen( $from ) );
	}

	/**
	 * Flag rewrite rules for flushing when a translation of an assigned page is updated.
	 *
	 * Core handles the assigned pages themselves.
	 *
	 * @since 4.9.6
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post update.
	 */
	public function force_rewrite_rules_on_page_update( $post_id, $post, $update ) {
		if ( ! $this->is_active() || ! $update || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		foreach ( $this->get_assigned_page_ids() as $page_id ) {
			if ( in_array( (int) $post_id, array_map( 'intval', $this->get_post_translations( $page_id ) ), true ) ) {
				delete_option( 'aiovg_rewrite_rules_flushed' );
				return;
			}
		}
	}

	/**
	 * Append the current item's value in the given language to a translated page URL.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  string    $url      Page URL in that language.
	 * @param  string    $language Language code.
	 * @return string              URL with the value, or unchanged when there is none.
	 */
	protected function add_translated_value( $url, $language ) {
		$value = $this->get_page_context() ? $this->get_translated_value( $language ) : false;

		return false === $value ? $url : $this->append_value( $url, $value );
	}

	/**
	 * Append a category, tag, user or playlist value to a page URL, keeping any query string.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  string    $url   Page URL.
	 * @param  string    $value Slug to append.
	 * @return string           URL with the value.
	 */
	protected function append_value( $url, $value ) {
		$context = $this->get_page_context();

		if ( '' == get_option( 'permalink_structure' ) ) {
			return add_query_arg( $context['query_var'], $value, $url );
		}

		return aiovg_add_path_to_url( $url, $value );
	}

	/**
	 * Get the IDs of the pages assigned in the plugin settings, the video archive page included.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return array     Page IDs.
	 */
	protected function get_assigned_page_ids() {
		$page_settings      = aiovg_get_page_settings();
		$permalink_settings = aiovg_get_option( 'aiovg_permalink_settings' );

		$ids = array_map( 'intval', (array) $page_settings );

		if ( ! empty( $permalink_settings['video_archive_page'] ) ) {
			$ids[] = (int) $permalink_settings['video_archive_page'];
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Get the current language code.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return string    The language code, or empty when all languages are shown.
	 */
	protected function get_current_language() {
		$language = $this->get_plugin_language();

		return empty( $language ) || 'all' == $language ? '' : (string) $language;
	}

	/**
	 * Get the current category, tag, user or playlist page.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return array|false Keys "page_id", "query_var", "taxonomy" and "value", or false on any other page.
	 */
	protected function get_page_context() {
		if ( null !== $this->page_context ) {
			return $this->page_context;
		}

		// Not until the main query has run
		if ( ! did_action( 'wp' ) ) {
			return false;
		}

		$this->page_context = false;

		$page_id = get_queried_object_id();

		if ( empty( $page_id ) || ! is_page() ) {
			return false;
		}

		$page_settings = aiovg_get_option( 'aiovg_page_settings' );

		$pages = array(
			'category'    => array( 'aiovg_category', 'aiovg_categories' ),
			'tag'         => array( 'aiovg_tag', 'aiovg_tags' ),
			'user_videos' => array( 'aiovg_user', '' ),
			'playlist'    => array( 'aiovg_playlist', 'aiovg_playlists' )
		);

		foreach ( $pages as $key => $vars ) {
			if ( empty( $page_settings[ $key ] ) || $page_id != $page_settings[ $key ] ) {
				continue;
			}

			$value = get_query_var( $vars[0] );

			if ( '' !== $value && ( '' === $vars[1] || taxonomy_exists( $vars[1] ) ) ) {
				$this->page_context = array(
					'page_id'   => $page_id,
					'query_var' => $vars[0],
					'taxonomy'  => $vars[1],
					'value'     => $value
				);
			}

			break;
		}

		return $this->page_context;
	}

	/**
	 * Get the path of a published page from its slugs.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int       $page_id Page ID.
	 * @return string             URL-decoded page path, or empty when the page is not published.
	 */
	protected function get_page_path( $page_id ) {
		$post = (int) $page_id > 0 ? get_post( (int) $page_id ) : null;

		if ( ! $post || 'publish' !== $post->post_status ) {
			return '';
		}

		// A permalink would follow the request's language
		return trim( urldecode( get_page_uri( $post ) ), '/' );
	}

	/**
	 * Get the translations of a post.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int       $post_id Post ID.
	 * @return array              Post IDs keyed by language code, or the post itself keyed by an empty string when it has no translations.
	 */
	protected function get_post_translations( $post_id ) {
		$post_id   = (int) $post_id;
		$post_type = $post_id > 0 ? get_post_type( $post_id ) : false;

		if ( empty( $post_type ) ) {
			return array();
		}

		$translations = $this->get_translations( $post_id, $post_type );

		if ( empty( $translations ) ) {
			return array( '' => $post_id );
		}

		// One query for the posts that callers read next
		_prime_post_caches( array_values( $translations ), false, false );

		return $translations;
	}

	/**
	 * Get the value to append for the given language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  string       $language Language code.
	 * @return string|false           Slug to append, or false when that language has no matching item.
	 */
	protected function get_translated_value( $language ) {
		if ( array_key_exists( $language, $this->translated_values ) ) {
			return $this->translated_values[ $language ];
		}

		$context = $this->get_page_context();
		$value   = false;

		// The page itself must exist in that language
		$pages = $this->get_post_translations( $context['page_id'] );

		if ( ! empty( $pages[ $language ] ) ) {
			// A user has the same slug in every language
			$value = '' === $context['taxonomy'] ? $context['value'] : $this->get_translated_term_slug( $context, $language );
		}

		$this->translated_values[ $language ] = $value;

		return $value;
	}

	/**
	 * Get the URL of a plugin page's translation in the given language.
	 *
	 * @since  4.9.6
	 * @access private
	 * @param  int     $page_id  Page ID, as saved in settings.
	 * @param  string  $language Language code.
	 * @return string            Page URL, or empty when that translation is not published.
	 */
	private function get_page_url_in_language( $page_id, $language ) {
		$key = (int) $page_id . '|' . $language;

		if ( isset( $this->page_urls[ $key ] ) ) {
			return $this->page_urls[ $key ];
		}

		$translations = $this->get_post_translations( $page_id );
		$url          = '';

		if ( ! empty( $translations[ $language ] ) && 'publish' == get_post_status( $translations[ $language ] ) ) {
			$url = $this->get_permalink_in_language( $translations[ $language ], $language );
		}

		$this->page_urls[ $key ] = (string) $url;

		return $this->page_urls[ $key ];
	}

	/**
	 * Get the slug of the current term in the given language.
	 *
	 * @since  4.9.6
	 * @access private
	 * @param  array        $context  Page context.
	 * @param  string       $language Language code.
	 * @return string|false           Term slug, or false when the term has no translation.
	 */
	private function get_translated_term_slug( $context, $language ) {
		$term = get_term_by( 'slug', sanitize_title( $context['value'] ), $context['taxonomy'] );

		if ( ! $term ) {
			return false;
		}

		$term_id = $this->get_translated_term_id( $term->term_id, $context['taxonomy'], $language );

		if ( empty( $term_id ) ) {
			// Fallback mode shows the original term on the translated page
			return $this->is_display_as_translated( $context['taxonomy'] ) ? $term->slug : false;
		}

		$translated = $this->get_term_in_language( (int) $term_id, $context['taxonomy'], $language );

		return $translated instanceof WP_Term ? $translated->slug : false;
	}

	/**
	 * Get the default language code.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return string|false Language code.
	 */
	abstract protected function get_default_language();

	/**
	 * Get a post's permalink as seen in the given language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int          $post_id  Post ID.
	 * @param  string       $language Language code.
	 * @return string|false           Permalink.
	 */
	abstract protected function get_permalink_in_language( $post_id, $language );

	/**
	 * Get the current language as the plugin reports it.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return string|false|null Language code.
	 */
	abstract protected function get_plugin_language();

	/**
	 * Get a post's language code.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int               $post_id   Post ID.
	 * @param  string            $post_type Post type.
	 * @return string|false|null            Language code.
	 */
	abstract protected function get_post_language( $post_id, $post_type );

	/**
	 * Get the path a page answers on in the rewrite rules.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  string    $path     URL-decoded page path.
	 * @param  string    $language The page's language code, or empty when unknown.
	 * @return string              Path for the rewrite rules.
	 */
	abstract protected function get_rewrite_path( $path, $language );

	/**
	 * Get a term as seen in the given language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int                   $term_id  Term ID.
	 * @param  string                $taxonomy Taxonomy slug.
	 * @param  string                $language Language code.
	 * @return WP_Term|WP_Error|null           Term.
	 */
	abstract protected function get_term_in_language( $term_id, $taxonomy, $language );

	/**
	 * Get a term's language code.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  WP_Term           $term     Term object.
	 * @param  string            $taxonomy Taxonomy slug.
	 * @return string|false|null           Language code.
	 */
	abstract protected function get_term_language( $term, $taxonomy );

	/**
	 * Get a page's translation in the current language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int|string $page_id Page ID.
	 * @return int                 Translated page ID, or empty to keep the given page.
	 */
	abstract protected function get_translated_page_id( $page_id );

	/**
	 * Get a term's translation in the given language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int       $term_id  Term ID.
	 * @param  string    $taxonomy Taxonomy slug.
	 * @param  string    $language Language code.
	 * @return int|null            Translated term ID, or empty when there is none.
	 */
	abstract protected function get_translated_term_id( $term_id, $taxonomy, $language );

	/**
	 * Get the translations of a post as the plugin records them.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int       $post_id   Post ID.
	 * @param  string    $post_type Post type.
	 * @return array                Post IDs keyed by language code, or empty when there are none.
	 */
	abstract protected function get_translations( $post_id, $post_type );

	/**
	 * Check whether the integration's plugin is active.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return bool      True if active.
	 */
	abstract protected function is_active();

	/**
	 * Check whether a taxonomy shows untranslated terms in every language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  string    $taxonomy Taxonomy slug.
	 * @return bool                True in fallback mode.
	 */
	abstract protected function is_display_as_translated( $taxonomy );

}
