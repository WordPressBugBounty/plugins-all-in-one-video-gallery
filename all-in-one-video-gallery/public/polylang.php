<?php

/**
 * Polylang.
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
 * AIOVG_Public_Polylang class.
 *
 * @since 4.9.6
 */
class AIOVG_Public_Polylang extends AIOVG_Multilingual {

	/**
	 * Keep the category, tag, user or playlist in the language switcher and hreflang links.
	 *
	 * @since  4.9.6
	 * @param  string|null $url      Translation URL, or null when Polylang found none.
	 * @param  string      $language Language slug.
	 * @return string|null           Filtered URL.
	 */
	public function filter_translation_url( $url, $language ) {
		if ( empty( $url ) || ! $this->is_active() ) {
			return $url;
		}

		// One filter feeds both the switcher and hreflang, so an untranslated item keeps the page URL
		return $this->add_translated_value( $url, $language );
	}

	/**
	 * Flag rewrite rules for flushing when a page is saved in the translation group of an assigned page.
	 *
	 * @since  4.9.6
	 * @param  int     $post_id      Post ID.
	 * @param  WP_Post $post         Post object.
	 * @param  array   $translations Post IDs in the translation group, keyed by language.
	 */
	public function force_rewrite_rules_on_translation_save( $post_id, $post, $translations ) {
		if ( ! $this->is_active() || ! $post || 'page' != $post->post_type || empty( $translations ) || ! is_array( $translations ) ) {
			return;
		}

		if ( array_intersect( $this->get_assigned_page_ids(), array_map( 'intval', $translations ) ) ) {
			delete_option( 'aiovg_rewrite_rules_flushed' );
		}
	}

	/**
	 * Get the default language code.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return string|false Language code.
	 */
	protected function get_default_language() {
		return pll_default_language();
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
		// Polylang builds each post's link in its own language
		return get_permalink( $post_id );
	}

	/**
	 * Get the current language as Polylang reports it.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return string|false Language code.
	 */
	protected function get_plugin_language() {
		return pll_current_language();
	}

	/**
	 * Get a post's language code.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int          $post_id   Post ID.
	 * @param  string       $post_type Post type.
	 * @return string|false            Language code.
	 */
	protected function get_post_language( $post_id, $post_type ) {
		return pll_get_post_language( $post_id );
	}

	/**
	 * Get the path a page answers on in the rewrite rules.
	 *
	 * Polylang matches rules against the path with its language directory; WPML strips it first.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  string    $path     URL-decoded page path.
	 * @param  string    $language The page's language code, or empty when unknown.
	 * @return string              Path for the rewrite rules.
	 */
	protected function get_rewrite_path( $path, $language ) {
		if ( empty( $language ) ) {
			return $path;
		}

		$options = (array) get_option( 'polylang', array() );

		// Directory mode only, and not for a default language hidden from URLs
		if ( ! isset( $options['force_lang'] ) || 1 != $options['force_lang'] ) {
			return $path;
		}

		if ( ! empty( $options['hide_default'] ) && isset( $options['default_lang'] ) && $options['default_lang'] === $language ) {
			return $path;
		}

		return ( empty( $options['rewrite'] ) ? 'language/' : '' ) . $language . '/' . $path;
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
		return get_term( $term_id, $taxonomy );
	}

	/**
	 * Get a term's language code.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  WP_Term      $term     Term object.
	 * @param  string       $taxonomy Taxonomy slug.
	 * @return string|false           Language code.
	 */
	protected function get_term_language( $term, $taxonomy ) {
		return pll_get_term_language( $term->term_id );
	}

	/**
	 * Get a page's translation in the current language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int|string $page_id Page ID.
	 * @return int                 Translated page ID, or 0 when there is none or no current language.
	 */
	protected function get_translated_page_id( $page_id ) {
		$language = pll_current_language();

		if ( empty( $language ) ) {
			return 0;
		}

		return pll_get_post( $page_id );
	}

	/**
	 * Get a term's translation in the given language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int       $term_id  Term ID.
	 * @param  string    $taxonomy Taxonomy slug.
	 * @param  string    $language Language code.
	 * @return int|false           Translated term ID, or empty when there is none.
	 */
	protected function get_translated_term_id( $term_id, $taxonomy, $language ) {
		return pll_get_term( $term_id, $language );
	}

	/**
	 * Get the translations of a post as Polylang records them.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  int       $post_id   Post ID.
	 * @param  string    $post_type Post type.
	 * @return array                Post IDs keyed by language code, or empty when there are none.
	 */
	protected function get_translations( $post_id, $post_type ) {
		return array_map( 'intval', (array) pll_get_post_translations( $post_id ) );
	}

	/**
	 * Check whether Polylang is active.
	 *
	 * Checked on each call: Polylang loads after this plugin. WPML takes precedence when both
	 * are active.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @return bool      True if Polylang is active and WPML is not.
	 */
	protected function is_active() {
		return ! defined( 'ICL_SITEPRESS_VERSION' ) && function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post_translations' );
	}

	/**
	 * Check whether a taxonomy shows untranslated terms in every language.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  string    $taxonomy Taxonomy slug.
	 * @return bool                Always false: Polylang has no such mode.
	 */
	protected function is_display_as_translated( $taxonomy ) {
		return false;
	}

}
