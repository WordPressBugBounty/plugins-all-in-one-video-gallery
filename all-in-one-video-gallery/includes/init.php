<?php

/**
 * The file that defines the core plugin class.
 *
 * @link    https://plugins360.com
 * @since   1.0.0
 *
 * @package All_In_One_Video_Gallery
 */
 
// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AIOVG_Init - The main plugin class.
 *
 * @since 1.0.0
 */
class AIOVG_Init {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    AIOVG_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Get things started.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->blocks_init();
		$this->widgets_init();
		$this->set_meta_caps();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once AIOVG_PLUGIN_DIR . 'includes/loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once AIOVG_PLUGIN_DIR . 'includes/i18n.php';
		
		/**
		 * The class responsibe for defining custom capabilities.
		 */
		 require_once AIOVG_PLUGIN_DIR . 'includes/roles.php';
		
		/**
		 * The custom walker class for the 'wp_terms_checklist' function.
		 */
		require_once AIOVG_PLUGIN_DIR . 'includes/walker-terms-checklist.php';

		/**
		 * The custom walker classes for the 'wp_dropdown_categories' function.
		 */
		require_once AIOVG_PLUGIN_DIR . 'includes/walker-terms-dropdown.php';
		require_once AIOVG_PLUGIN_DIR . 'includes/walker-terms-multiselect.php';
		
		/**
		 * The class responsibe for the video player related functionalities.
		 */
		require_once AIOVG_PLUGIN_DIR . 'includes/player.php';

		/**
		 * The base class of the WPML and Polylang integrations.
		 */
		require_once AIOVG_PLUGIN_DIR . 'includes/multilingual.php';

		/**
		 * The files with the helper functions.
		 */		
		require_once AIOVG_PLUGIN_DIR . 'includes/helpers/functions.php';
		require_once AIOVG_PLUGIN_DIR . 'includes/helpers/providers.php';
		require_once AIOVG_PLUGIN_DIR . 'includes/helpers/render.php';
		require_once AIOVG_PLUGIN_DIR . 'includes/helpers/urls.php';
		require_once AIOVG_PLUGIN_DIR . 'includes/helpers/email-capture.php';

		/**
		 * The classes responsible for defining all actions that occur in the admin area.
		 */
		require_once AIOVG_PLUGIN_DIR . 'admin/admin.php';
		require_once AIOVG_PLUGIN_DIR . 'admin/videos.php';
		require_once AIOVG_PLUGIN_DIR . 'admin/categories.php';
		require_once AIOVG_PLUGIN_DIR . 'admin/tags.php';
		require_once AIOVG_PLUGIN_DIR . 'admin/settings.php';
		require_once AIOVG_PLUGIN_DIR . 'admin/import-export.php';

		/**
		 * The classes responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once AIOVG_PLUGIN_DIR . 'public/public.php';		
		require_once AIOVG_PLUGIN_DIR . 'public/yoast.php';
		require_once AIOVG_PLUGIN_DIR . 'public/rank-math.php';			
		require_once AIOVG_PLUGIN_DIR . 'public/pmpro.php';
		require_once AIOVG_PLUGIN_DIR . 'public/wpml.php';
		require_once AIOVG_PLUGIN_DIR . 'public/polylang.php';
		require_once AIOVG_PLUGIN_DIR . 'public/categories.php';
		require_once AIOVG_PLUGIN_DIR . 'public/videos.php';		
		require_once AIOVG_PLUGIN_DIR . 'public/video.php';
		require_once AIOVG_PLUGIN_DIR . 'public/search.php';
		require_once AIOVG_PLUGIN_DIR . 'public/likes.php';
		require_once AIOVG_PLUGIN_DIR . 'public/bunny-stream.php';
		require_once AIOVG_PLUGIN_DIR . 'public/conflict.php';		
		
		/**
		 * The class responsible for defining actions that occur in the gutenberg blocks.
		 */
		require_once AIOVG_PLUGIN_DIR. 'blocks/blocks.php';

		/**
		 * The classes responsible for defining all actions that occur in the widgets.
		 */
		require_once AIOVG_PLUGIN_DIR . 'widgets/categories.php';
		require_once AIOVG_PLUGIN_DIR . 'widgets/videos.php';				
		require_once AIOVG_PLUGIN_DIR . 'widgets/video.php';
		require_once AIOVG_PLUGIN_DIR . 'widgets/search.php';			

		$this->loader = new AIOVG_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {
		$i18n = new AIOVG_i18n();
		$this->loader->add_action( 'plugins_loaded', $i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		// Hooks common to all admin pages
		$admin = new AIOVG_Admin();
				
		$this->loader->add_action( 'admin_init', $admin, 'insert_missing_options', 1 );		
		$this->loader->add_action( 'admin_init', $admin, 'handle_form_actions' );
		$this->loader->add_action( 'admin_init', $admin, 'restrict_publish_caps' );
		$this->loader->add_action( 'admin_menu', $admin, 'admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
		$this->loader->add_action( 'elementor/editor/after_enqueue_styles', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'elementor/editor/after_enqueue_scripts', $admin, 'enqueue_scripts' );	
		$this->loader->add_action( 'updated_option', $admin, 'force_rewrite_rules_on_settings_update', 10, 3 );
		$this->loader->add_action( 'save_post_page', $admin, 'force_rewrite_rules_on_page_update', 10, 3 );
		$this->loader->add_action( 'wp_ajax_aiovg_store_user_meta', $admin, 'ajax_callback_store_user_meta' );

		$this->loader->add_filter( 'display_post_states', $admin, 'add_display_post_states', 10, 2 );
		$this->loader->add_filter( 'plugin_action_links_' . AIOVG_PLUGIN_FILE_NAME, $admin, 'plugin_action_links' );
		$this->loader->add_filter( 'upload_mimes', $admin, 'add_mime_types' );
		$this->loader->add_filter( 'wp_check_filetype_and_ext', $admin, 'add_filetype_and_ext', 10, 4 );
		$this->loader->add_filter( 'wp_handle_upload_prefilter', $admin, 'wp_handle_upload_prefilter' );	
		
		// Hooks specific to the videos page
		$videos = new AIOVG_Admin_Videos();
		
		$this->loader->add_action( 'admin_menu', $videos, 'admin_menu' );
		$this->loader->add_action( 'init', $videos, 'register_post_type' );
		$this->loader->add_action( 'init', $videos, 'register_post_meta' );
		$this->loader->add_action( 'before_delete_post', $videos, 'before_delete_post' );

		if ( is_admin() ) {		
			$this->loader->add_action( 'post_submitbox_misc_actions', $videos, 'post_submitbox_misc_actions' );
			$this->loader->add_action( 'add_meta_boxes', $videos, 'add_meta_boxes' );
			$this->loader->add_action( 'add_meta_boxes', $videos, 'remove_legacy_meta_boxes', 99999 );
			$this->loader->add_action( 'save_post', $videos, 'save_meta_data', 10, 2 );
			$this->loader->add_action( 'restrict_manage_posts', $videos, 'restrict_manage_posts' );
			$this->loader->add_action( 'manage_aiovg_videos_posts_custom_column', $videos, 'custom_column_content', 10, 2 );
			$this->loader->add_action( 'admin_print_footer_scripts', $videos, 'print_footer_scripts', 99 );
			$this->loader->add_action( 'admin_print_footer_scripts', $videos, 'print_editor_placeholder_script', 99 );

			$this->loader->add_filter( 'parent_file', $videos, 'parent_file' );
			$this->loader->add_filter( 'parse_query', $videos, 'parse_query' );
			$this->loader->add_filter( 'post_row_actions', $videos, 'row_actions', 10, 2 );
			$this->loader->add_filter( 'manage_edit-aiovg_videos_columns', $videos, 'get_columns' );
			$this->loader->add_filter( 'manage_edit-aiovg_videos_sortable_columns', $videos, 'sortable_columns' );
			$this->loader->add_filter( 'use_block_editor_for_post_type', $videos, 'disable_gutenberg', 10, 2 );
			$this->loader->add_filter( 'gutenberg_can_edit_post_type', $videos, 'disable_gutenberg', 10, 2 );			
		}
		
		// Hooks specific to the categories page
		$categories = new AIOVG_Admin_Categories();
		
		$this->loader->add_action( 'admin_menu', $categories, 'admin_menu' );
		$this->loader->add_action( 'init', $categories, 'register_taxonomy' );
		$this->loader->add_action( 'aiovg_categories_add_form_fields', $categories, 'add_form_fields' );		
		$this->loader->add_action( 'aiovg_categories_edit_form_fields', $categories, 'edit_form_fields' );
		$this->loader->add_action( 'created_aiovg_categories', $categories, 'save_form_fields' );
		$this->loader->add_action( 'edited_aiovg_categories', $categories, 'save_form_fields' );		
		$this->loader->add_action( 'pre_delete_term', $categories, 'pre_delete_term', 10, 2 );	
		
		$this->loader->add_filter( 'parent_file', $categories, 'parent_file' );
		$this->loader->add_filter( "manage_edit-aiovg_categories_columns", $categories, 'get_columns' );
		$this->loader->add_filter( "manage_edit-aiovg_categories_sortable_columns", $categories, 'get_columns' );
		$this->loader->add_filter( "manage_aiovg_categories_custom_column", $categories, 'custom_column_content', 10, 3 );		
		
		// Hooks specific to the tags page
		$tags = new AIOVG_Admin_Tags();
		
		$this->loader->add_action( 'admin_menu', $tags, 'admin_menu' );
		$this->loader->add_action( 'init', $tags, 'register_taxonomy' );
		
		$this->loader->add_filter( 'parent_file', $tags, 'parent_file' );
		$this->loader->add_filter( "manage_edit-aiovg_tags_columns", $tags, 'get_columns' );
		$this->loader->add_filter( "manage_edit-aiovg_tags_sortable_columns", $tags, 'get_columns' );
		$this->loader->add_filter( "manage_aiovg_tags_custom_column", $tags, 'custom_column_content', 10, 3 );
		
		// Hooks specific to the settings page
		$settings = new AIOVG_Admin_Settings();
		
		$this->loader->add_action( 'admin_menu', $settings, 'admin_menu' );
		$this->loader->add_action( 'admin_init', $settings, 'admin_init' );

		// Hooks specific to the import / export page
		$import_export = new AIOVG_Admin_Import_Export();
		
		$this->loader->add_action( 'admin_menu', $import_export, 'admin_menu' );
		$this->loader->add_action( 'wp_ajax_aiovg_import_folder', $import_export, 'ajax_callback_import_folder' );
		$this->loader->add_action( 'wp_ajax_aiovg_import_csv', $import_export, 'ajax_callback_import_csv' );
		$this->loader->add_action( 'wp_ajax_aiovg_get_csv_columns', $import_export, 'ajax_callback_get_csv_columns' );
		$this->loader->add_action( 'wp_ajax_aiovg_export_csv', $import_export, 'ajax_callback_export_csv' );
		$this->loader->add_action( 'wp_ajax_aiovg_export_zip', $import_export, 'ajax_callback_export_zip' );
		$this->loader->add_action( 'wp_ajax_aiovg_export_email_leads_csv', $import_export, 'ajax_callback_export_email_leads_csv' );
		$this->loader->add_action( 'aiovg_cleanup_export_directory', $import_export, 'cleanup_export_directory' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		// Hooks common to all public pages
		$public = new AIOVG_Public();

		$this->loader->add_action( 'template_redirect', $public, 'template_redirect', 0 );
		$this->loader->add_action( 'init', $public, 'init' );
		$this->loader->add_action( 'init', $public, 'register_styles' );
		$this->loader->add_action( 'init', $public, 'register_scripts' );
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_assets', 99 );
		$this->loader->add_action( 'enqueue_block_assets', $public, 'enqueue_block_assets', 99 );	
		$this->loader->add_action( 'elementor/editor/after_enqueue_scripts', $public, 'enqueue_editor_assets', 99 );
		$this->loader->add_action( 'elementor/preview/enqueue_scripts', $public, 'enqueue_editor_assets', 99 );
		$this->loader->add_action( 'query_vars', $public, 'query_vars' );	
		$this->loader->add_action( 'wp', $public, 'set_mysql_rand_seed_value' );	
		$this->loader->add_action( 'wp_loaded', $public, 'maybe_flush_rules', 11 );				
		$this->loader->add_action( 'wp_head', $public, 'wp_head' );
		$this->loader->add_action( 'wp_print_footer_scripts', $public, 'wp_print_footer_scripts', 99 );
		$this->loader->add_action( 'wp_ajax_aiovg_set_cookie', $public, 'set_gdpr_cookie' );
		$this->loader->add_action( 'wp_ajax_nopriv_aiovg_set_cookie', $public, 'set_gdpr_cookie' );
		$this->loader->add_action( 'wp_ajax_aiovg_save_email_lead', $public, 'save_email_lead' );
		$this->loader->add_action( 'wp_ajax_nopriv_aiovg_save_email_lead', $public, 'save_email_lead' );
		
		if ( ! aiovg_is_yoast_or_rank_math_active() ) {
			$this->loader->add_filter( 'wp_title', $public, 'wp_title', 99, 3 );
			$this->loader->add_filter( 'document_title_parts', $public, 'document_title_parts' );
		}

		$this->loader->add_filter( 'the_title', $public, 'the_title', 99, 2 );
		$this->loader->add_filter( 'single_post_title', $public, 'the_title', 99 );
		$this->loader->add_filter( 'has_post_thumbnail', $public, 'has_post_thumbnail', 10, 3 );
		$this->loader->add_filter( 'post_thumbnail_html', $public, 'post_thumbnail_html', 10, 5 );
		$this->loader->add_filter( 'term_link', $public, 'term_link', 10, 3 );
		$this->loader->add_filter( 'get_canonical_url', $public, 'get_canonical_url', 10, 2 );
		
		// Hooks specific to the Yoast SEO plugin
		$yoast = new AIOVG_Public_Yoast();

		$this->loader->add_filter( 'wpseo_title', $yoast, 'wpseo_title' );
		$this->loader->add_filter( 'wpseo_opengraph_title', $yoast, 'wpseo_title' );
		$this->loader->add_filter( 'wpseo_metadesc', $yoast, 'wpseo_metadesc' );
		$this->loader->add_filter( 'wpseo_opengraph_desc', $yoast, 'wpseo_metadesc' );
		$this->loader->add_filter( 'wpseo_canonical', $yoast, 'wpseo_canonical' );
		$this->loader->add_filter( 'wpseo_opengraph_url', $yoast, 'wpseo_canonical' );
		$this->loader->add_filter( 'wpseo_opengraph_image', $yoast, 'wpseo_opengraph_image' );
		$this->loader->add_filter( 'wpseo_twitter_image', $yoast, 'wpseo_twitter_image' );
		$this->loader->add_filter( 'wpseo_add_opengraph_additional_images', $yoast, 'wpseo_add_opengraph_additional_images' );
		$this->loader->add_filter( 'wpseo_breadcrumb_links', $yoast, 'wpseo_breadcrumb_links' );
		$this->loader->add_filter( 'wpseo_schema_graph', $yoast, 'wpseo_schema_graph', 10, 2 );
		$this->loader->add_filter( 'wpseo_video_custom_field_details', $yoast, 'wpseo_video_sitemap_entry' );
		$this->loader->add_filter( 'wpseo_video_youtube_details', $yoast, 'wpseo_video_sitemap_entry' );
		$this->loader->add_filter( 'wpseo_video_vimeo_details', $yoast, 'wpseo_video_sitemap_entry' );

		// Hooks specific to the Rank Math SEO plugin
		$rank_math = new AIOVG_Public_Rank_Math();

		$this->loader->add_filter( 'rank_math/frontend/title', $rank_math, 'meta_title' );
		$this->loader->add_filter( 'rank_math/frontend/description', $rank_math, 'meta_description' );
		$this->loader->add_filter( 'rank_math/frontend/canonical', $rank_math, 'canonical_url' );
		$this->loader->add_filter( 'rank_math/opengraph/facebook/image', $rank_math, 'opengraph_image_url' );
		$this->loader->add_filter( 'rank_math/opengraph/twitter/image', $rank_math, 'opengraph_image_url' );
		$this->loader->add_filter( 'rank_math/frontend/breadcrumb/items', $rank_math, 'breadcrumb_items' );
		$this->loader->add_filter( 'rank_math/snippet/rich_snippet_videoobject_entity', $rank_math, 'videoobject_entity' );

		// Hooks specific to the Paid Memberships Pro plugin
		$pmpro = new AIOVG_Public_PMPro();

		$this->loader->add_filter( 'aiovg_current_user_can', $pmpro, 'filter_user_access', 10, 3 );
		$this->loader->add_filter( 'aiovg_restricted_message_html', $pmpro, 'filter_restricted_message_html', 10, 4 );
		$this->loader->add_filter( 'aiovg_player_overlay_link', $pmpro, 'filter_player_overlay_link', 10, 2 );
		$this->loader->add_filter( 'pmpro_membership_content_filter', $pmpro, 'filter_membership_content', 10, 3 );
		$this->loader->add_filter( 'pmprocpt_redirect_to', $pmpro, 'filter_cpt_redirect_to' );

		// Hooks specific to the WPML plugin
		$wpml = new AIOVG_Public_WPML();

		$this->loader->add_action( 'wpml_translation_update', $wpml, 'force_rewrite_rules_on_translation_update' );
		$this->loader->add_action( 'wpml_pro_translation_completed', $wpml, 'force_rewrite_rules_on_translation_job', 10, 3 );
		$this->loader->add_action( 'save_post_page', $wpml, 'force_rewrite_rules_on_page_update', 10, 3 );

		$this->loader->add_filter( 'aiovg_ajax_url', $wpml, 'filter_ajax_url' );
		$this->loader->add_filter( 'aiovg_object_id', $wpml, 'filter_object_id', 10, 2 );
		$this->loader->add_filter( 'aiovg_page_path', $wpml, 'filter_page_path', 10, 2 );
		$this->loader->add_filter( 'aiovg_page_rewrite_bases', $wpml, 'filter_page_rewrite_bases', 10, 2 );
		$this->loader->add_filter( 'post_type_link', $wpml, 'filter_video_permalink', 1, 2 );
		$this->loader->add_filter( 'term_link', $wpml, 'filter_term_link', 11, 3 );

		if ( ! is_admin() || wp_doing_ajax() ) {
			$this->loader->add_filter( 'option_aiovg_page_settings', $wpml, 'filter_page_settings' );
		}

		if ( ! is_admin() ) {
			$this->loader->add_filter( 'wpml_ls_language_url', $wpml, 'filter_language_switcher_url', 10, 2 );
			$this->loader->add_filter( 'wpml_head_langs', $wpml, 'filter_head_langs' );
		}

		// Hooks specific to the Polylang plugin
		$polylang = new AIOVG_Public_Polylang();

		$this->loader->add_action( 'pll_save_post', $polylang, 'force_rewrite_rules_on_translation_save', 10, 3 );
		$this->loader->add_action( 'save_post_page', $polylang, 'force_rewrite_rules_on_page_update', 10, 3 );

		$this->loader->add_filter( 'aiovg_ajax_url', $polylang, 'filter_ajax_url' );
		$this->loader->add_filter( 'aiovg_object_id', $polylang, 'filter_object_id', 10, 2 );
		$this->loader->add_filter( 'aiovg_page_path', $polylang, 'filter_page_path', 10, 2 );
		$this->loader->add_filter( 'aiovg_page_rewrite_bases', $polylang, 'filter_page_rewrite_bases', 10, 2 );
		$this->loader->add_filter( 'post_type_link', $polylang, 'filter_video_permalink', 1, 2 );
		$this->loader->add_filter( 'term_link', $polylang, 'filter_term_link', 11, 3 );

		if ( ! is_admin() || wp_doing_ajax() ) {
			$this->loader->add_filter( 'option_aiovg_page_settings', $polylang, 'filter_page_settings' );
		}

		if ( ! is_admin() ) {
			$this->loader->add_filter( 'pll_translation_url', $polylang, 'filter_translation_url', 10, 2 );
		}

		// Hooks specific to the categories page
		$categories = new AIOVG_Public_Categories();
		
		$this->loader->add_action( 'wp_ajax_aiovg_load_categories', $categories, 'ajax_callback_load_categories' );
		$this->loader->add_action( 'wp_ajax_nopriv_aiovg_load_categories', $categories, 'ajax_callback_load_categories' );
		
		// Hooks specific to the videos page
		$videos = new AIOVG_Public_Videos();

		$this->loader->add_action( 'wp_ajax_aiovg_load_videos', $videos, 'ajax_callback_load_videos' );
		$this->loader->add_action( 'wp_ajax_nopriv_aiovg_load_videos', $videos, 'ajax_callback_load_videos' );
		
		// Hooks specific to the single video page
		$video = new AIOVG_Public_Video();
		
		$this->loader->add_action( 'init', $video, 'download_video', 1 );		
		$this->loader->add_action( 'wp_ajax_aiovg_update_views_count', $video, 'ajax_callback_update_views_count' );
		$this->loader->add_action( 'wp_ajax_nopriv_aiovg_update_views_count', $video, 'ajax_callback_update_views_count' );	

		$this->loader->add_filter( 'template_include', $video, 'template_include', 999 );
		$this->loader->add_filter( 'show_admin_bar', $video, 'remove_admin_bar' );
		$this->loader->add_filter( 'aiovg_iframe_videojs_player_sources', $video, 'filter_player_sources', 10, 2 );
		$this->loader->add_filter( 'aiovg_iframe_vidstack_player_sources', $video, 'filter_player_sources', 10, 2 );
		$this->loader->add_filter( 'aiovg_videojs_player_sources', $video, 'filter_player_sources', 10, 2 );
		$this->loader->add_filter( 'aiovg_vidstack_player_sources', $video, 'filter_player_sources', 10, 2 );
		$this->loader->add_filter( 'aiovg_the_content', $video, 'wrap_timestamps_with_links' );
		$this->loader->add_filter( 'aiovg_the_content', $video, 'hide_password_protected_description', 99, 2 );
		$this->loader->add_filter( 'the_content', $video, 'the_content', 11 );
		$this->loader->add_filter( 'comments_open', $video, 'comments_open', 10, 2 );		
		
		// Hooks specific to the search form
		$search = new AIOVG_Public_Search();

		// Hooks specific to the like / dislike button
		$likes_settings = aiovg_get_option( 'aiovg_likes_settings' );

		if ( ! empty( $likes_settings['like_button'] ) || ! empty( $likes_settings['dislike_button'] ) ) {
			$likes = new AIOVG_Public_Likes();
			
			$this->loader->add_action( 'wp_ajax_aiovg_get_likes_dislikes_info', $likes, 'ajax_callback_get_likes_dislikes_info' );
			$this->loader->add_action( 'wp_ajax_nopriv_aiovg_get_likes_dislikes_info', $likes, 'ajax_callback_get_likes_dislikes_info' );
			$this->loader->add_action( 'wp_ajax_aiovg_toggle_likes', $likes, 'ajax_callback_toggle_likes' );
			$this->loader->add_action( 'wp_ajax_nopriv_aiovg_toggle_likes', $likes, 'ajax_callback_toggle_likes' );
			$this->loader->add_action( 'wp_ajax_aiovg_toggle_dislikes', $likes, 'ajax_callback_toggle_dislikes' );
			$this->loader->add_action( 'wp_ajax_nopriv_aiovg_toggle_dislikes', $likes, 'ajax_callback_toggle_dislikes' );
		}

		// Hooks specific to the Bunny Stream integration
		$bunny_stream = new AIOVG_Public_Bunny_Stream();

		if ( is_admin() ) {
			$this->loader->add_action( 'save_post', $bunny_stream, 'save_post', 10, 2 );
		}

		$this->loader->add_action( 'aiovg_save_video', $bunny_stream, 'save_bunny_stream_data' );
		$this->loader->add_action( 'wp_ajax_aiovg_create_bunny_stream_video', $bunny_stream, 'ajax_callback_create_bunny_stream_video' );
		$this->loader->add_action( 'wp_ajax_aiovg_get_bunny_stream_video', $bunny_stream, 'ajax_callback_get_bunny_stream_video' );
		$this->loader->add_action( 'wp_ajax_aiovg_delete_bunny_stream_video', $bunny_stream, 'ajax_callback_delete_bunny_stream_video' );
		$this->loader->add_action( 'before_delete_post', $bunny_stream, 'before_delete_post', 1 );

		$this->loader->add_filter( 'aiovg_get_image', $bunny_stream, 'filter_image_url', 10, 4 );
		$this->loader->add_filter( 'aiovg_iframe_videojs_player_sources', $bunny_stream, 'filter_player_sources', 10, 2 );
		$this->loader->add_filter( 'aiovg_iframe_vidstack_player_sources', $bunny_stream, 'filter_player_sources', 10, 2 );
		$this->loader->add_filter( 'aiovg_videojs_player_sources', $bunny_stream, 'filter_player_sources', 10, 2 );
		$this->loader->add_filter( 'aiovg_vidstack_player_sources', $bunny_stream, 'filter_player_sources', 10, 2 );

		// Fixes for third-party plugin/theme conflict
		$conflict = new AIOVG_Public_Conflict();

		$this->loader->add_filter( 'autoptimize_filter_noptimize', $conflict, 'noptimize' );
		$this->loader->add_filter( 'smush_skip_iframe_from_lazy_load', $conflict, 'smush', 999, 2 );
	}

	/**
	 * Initialize Blocks.
	 *
	 * @since  1.5.6
	 * @access private
	 */
	private function blocks_init() {
		if ( is_admin() ) {
			global $pagenow;
			if ( 'widgets.php' === $pagenow ) return;
		}

		global $wp_version;

		$blocks = new AIOVG_Blocks();

		$this->loader->add_action( 'init', $blocks, 'register_block_types' );
		$this->loader->add_action( 'enqueue_block_editor_assets', $blocks, 'enqueue_block_editor_assets' );

		if ( version_compare( $wp_version, '5.8', '>=' ) ) {
			$this->loader->add_filter( 'block_categories_all', $blocks, 'block_categories' );
		} else {
			$this->loader->add_filter( 'block_categories', $blocks, 'block_categories' );
		}
	}
	
	/**
	 * Add hook to register widgets.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function widgets_init() {
		$this->loader->add_action( 'widgets_init', $this, 'register_widgets' );
	}
	
	/**
	 * Register widgets.
	 *
	 * @since 1.0.0
	 */
	public function register_widgets() {		
		register_widget( "AIOVG_Widget_Categories" );
		register_widget( "AIOVG_Widget_Videos" );		
		register_widget( "AIOVG_Widget_Video" );
		register_widget( "AIOVG_Widget_Search" );		
	}
	
	/**
	 * Map meta caps to primitive caps.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_meta_caps() {
		$roles = new AIOVG_Roles();
		$this->loader->add_filter( 'map_meta_cap', $roles, 'meta_caps', 10, 4 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

}
