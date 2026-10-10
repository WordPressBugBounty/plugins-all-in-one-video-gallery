<?php

/**
 * WPML.
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
 * AIOVG_Public_WPML class.
 *
 * @since 4.9.6
 */
class AIOVG_Public_WPML extends AIOVG_Multilingual {

	/**
	 * Keep the category, tag, user or playlist in the hreflang links.
	 *
	 * @since  4.9.6
	 * @param  array $languages Languages with their URLs, keyed by language code.
	 * @return array            Filtered languages.
	 */
	public function filter_head_langs( $languages ) {
		if ( ! $this->is_active() || ! is_array( $languages ) || ! $this->get_page_context() ) {
			return $languages;
		}

		foreach ( $languages as $key => $language ) {
			if ( empty( $language['code'] ) || empty( $language['url'] ) ) {
				continue;
			}

			$value = $this->get_translated_value( $language['code'] );

			// hreflang must point to the same content, so drop languages without a translation
			if ( false === $value ) {
				unset( $languages[ $key ] );
				continue;
			}

			$languages[ $key ]['url'] = $this->append_value( $language['url'], $value );
		}

		return $languages;
	}

	/**
	 * Keep the category, tag, user or playlist in the language switcher links.
	 *
	 * @since  4.9.6
	 * @param  string $url  Language switcher URL.
	 * @param  array  $data Language data.
	 * @return string       Filtered URL.
	 */
	public function filter_language_switcher_url( $url, $data ) {
		if ( ! $this->is_active() || empty( $url ) || empty( $data['code'] ) ) {
			return $url;
		}

		// An untranslated item keeps the page URL, as before
		return $this->add_translated_value( $url, $data['code'] );
	}

	/**
	 * Flag rewrite rules for flushing when a translation job creates or updates a translation of an assigned page.
	 *
	 * @since  4.9.6
	 * @param  int    $post_id Translated post ID.
	 * @param  array  $fields  Translated fields.
	 * @param  object $job     Translation job.
	 */
	public function force_rewrite_rules_on_translation_job( $post_id, $fields = array(), $job = null ) {
		// Jobs link the page with a direct database update, so no "wpml_translation_update" event fires
		if ( ! $this->is_active() || 'page' != get_post_type( $post_id ) ) {
			return;
		}

		// The job's TRID first: WPML's cache may not know the new link yet in this request
		$trid = is_object( $job ) && ! empty( $job->trid ) ? $job->trid : apply_filters( 'wpml_element_trid', null, $post_id, 'post_page' );

		if ( empty( $trid ) ) {
			return;
		}

		foreach ( $this->get_assigned_page_ids() as $page_id ) {
			if ( (int) $trid === (int) apply_filters( 'wpml_element_trid', null, $page_id, 'post_page' ) ) {
				delete_option( 'aiovg_rewrite_rules_flushed' );
				return;
			}
		}
	}

	/**
	 * Flag rewrite rules for flushing when a page joins the translation group of an assigned page.
	 *
	 * @since  4.9.6
	 * @param  array $args Translation update details.
	 */
	public function force_rewrite_rules_on_translation_update( $args ) {
		if ( ! $this->is_active() || ! is_array( $args ) ) {
			return;
		}

		// "insert" for a page saved into the group, "update" for an existing page connected to it
		if ( ! isset( $args['type'], $args['element_type'] ) || ! in_array( $args['type'], array( 'insert', 'update' ), true ) || 'post_page' != $args['element_type'] || empty( $args['trid'] ) ) {
			return;
		}

		foreach ( $this->get_assigned_page_ids() as $page_id ) {
			$trid = apply_filters( 'wpml_element_trid', null, $page_id, 'post_page' );

			if ( (int) $trid === (int) $args['trid'] ) {
				delete_option( 'aiovg_rewrite_rules_flushed' );
				return;
			}
		}
	}

	/**
	 * Get the default language code.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return string|null Language code.
	 */
	protected function get_default_language() {
		return apply_filters( 'wpml_default_language', null );
	}

	/**
	 * Get a post's permalink as seen in the given language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int          $post_id  Post ID.
	 * @param  string       $language Language code.
	 * @return string|false           Permalink.
	 */
	protected function get_permalink_in_language( $post_id, $language ) {
		// "Adjust IDs" would return the current language's page, so read it in its own language
		do_action( 'wpml_switch_language', $language );

		try {
			return get_permalink( $post_id );
		} finally {
			do_action( 'wpml_switch_language', null );
		}
	}

	/**
	 * Get the current language as WPML reports it.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return string|null Language code.
	 */
	protected function get_plugin_language() {
		return apply_filters( 'wpml_current_language', null );
	}

	/**
	 * Get a post's language code.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int         $post_id   Post ID.
	 * @param  string      $post_type Post type.
	 * @return string|null            Language code.
	 */
	protected function get_post_language( $post_id, $post_type ) {
		return apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $post_id, 'element_type' => $post_type ) );
	}

	/**
	 * Get the path a page answers on in the rewrite rules.
	 *
	 * WPML strips its language directory before matching rules, so the page path is used as is.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  string    $path     URL-decoded page path.
	 * @param  string    $language The page's language code, or empty when unknown.
	 * @return string              Path for the rewrite rules.
	 */
	protected function get_rewrite_path( $path, $language ) {
		return $path;
	}

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
	protected function get_term_in_language( $term_id, $taxonomy, $language ) {
		// With "Adjust IDs" on, get_term() returns the current language's term
		do_action( 'wpml_switch_language', $language );

		try {
			return get_term( $term_id, $taxonomy );
		} finally {
			do_action( 'wpml_switch_language', null );
		}
	}

	/**
	 * Get a term's language code.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  WP_Term     $term     Term object.
	 * @param  string      $taxonomy Taxonomy slug.
	 * @return string|null           Language code.
	 */
	protected function get_term_language( $term, $taxonomy ) {
		return apply_filters( 'wpml_element_language_code', null, array( 'element_id' => (int) $term->term_taxonomy_id, 'element_type' => $taxonomy ) );
	}

	/**
	 * Get a page's translation in the current language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int|string $page_id Page ID.
	 * @return int                 Translated page ID.
	 */
	protected function get_translated_page_id( $page_id ) {
		// Falls back to the original page when there is no translation
		return apply_filters( 'wpml_object_id', (int) $page_id, 'page', true );
	}

	/**
	 * Get a term's translation in the given language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int       $term_id  Term ID.
	 * @param  string    $taxonomy Taxonomy slug.
	 * @param  string    $language Language code.
	 * @return int|null            Translated term ID, or null when there is none.
	 */
	protected function get_translated_term_id( $term_id, $taxonomy, $language ) {
		return apply_filters( 'wpml_object_id', $term_id, $taxonomy, false, $language );
	}

	/**
	 * Get the translations of a post as WPML records them.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int       $post_id   Post ID.
	 * @param  string    $post_type Post type.
	 * @return array                Post IDs keyed by language code, or empty when there are none.
	 */
	protected function get_translations( $post_id, $post_type ) {
		$element_type = apply_filters( 'wpml_element_type', $post_type );
		$trid         = apply_filters( 'wpml_element_trid', null, $post_id, $element_type );
		$found        = array();

		if ( empty( $trid ) ) {
			return $found;
		}

		// All statuses, so the result does not depend on who is viewing
		$elements = apply_filters( 'wpml_get_element_translations', null, $trid, $element_type, false, true );

		if ( is_array( $elements ) ) {
			foreach ( $elements as $language => $element ) {
				if ( ! empty( $element->element_id ) ) {
					$found[ $language ] = (int) $element->element_id;
				}
			}
		}

		return $found;
	}

	/**
	 * Check whether WPML is active.
	 *
	 * Checked on each call: WPML loads after this plugin. Polylang also answers the "wpml_*"
	 * filters, so WPML is detected by its own constant.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return bool      True if WPML is active.
	 */
	protected function is_active() {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	/**
	 * Check whether a taxonomy shows untranslated terms in every language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  string    $taxonomy Taxonomy slug.
	 * @return bool                True in WPML's "display as translated" mode.
	 */
	protected function is_display_as_translated( $taxonomy ) {
		global $sitepress;

		return is_object( $sitepress ) && method_exists( $sitepress, 'is_display_as_translated_taxonomy' ) && $sitepress->is_display_as_translated_taxonomy( $taxonomy );
	}

}
