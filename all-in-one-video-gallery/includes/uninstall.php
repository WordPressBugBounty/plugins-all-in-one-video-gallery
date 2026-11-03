<?php

/**
 * Fired during plugin uninstallation.
 *
 * @link    https://plugins360.com
 * @since   1.0.0
 *
 * @package All_In_One_Video_Gallery
 */
// Exit if accessed directly
if ( !defined( 'WPINC' ) ) {
    die;
}
/**
 * AIOVG_Uninstall class.
 *
 * @since 1.0.0
 */
class AIOVG_Uninstall {
    /**
     * Called when the plugin is uninstalled.
     *
     * @since 1.0.0
     */
    public static function uninstall() {
        if ( !defined( 'AIOVG_UNINSTALL_PLUGIN' ) ) {
            define( 'AIOVG_UNINSTALL_PLUGIN', true );
        }
        $general_settings = get_option( 'aiovg_general_settings', array() );
        if ( empty( $general_settings['delete_plugin_data'] ) ) {
            return;
        }
        global $wpdb;
        // Delete all the custom post types
        $aiovg_post_types = array('aiovg_videos');
        foreach ( $aiovg_post_types as $post_type ) {
            $items = get_posts( array(
                'post_type'   => $post_type,
                'post_status' => 'any',
                'numberposts' => -1,
                'fields'      => 'ids',
            ) );
            if ( count( $items ) ) {
                foreach ( $items as $item ) {
                    wp_delete_post( $item, true );
                }
            }
        }
        // Delete all the terms & taxonomies
        $aiovg_taxonomies = array('aiovg_categories', 'aiovg_tags');
        foreach ( $aiovg_taxonomies as $taxonomy ) {
            $terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.name ASC", $taxonomy ) );
            // Delete terms
            if ( count( $terms ) ) {
                foreach ( $terms as $term ) {
                    $wpdb->delete( $wpdb->term_taxonomy, array(
                        'term_taxonomy_id' => $term->term_taxonomy_id,
                    ) );
                    $wpdb->delete( $wpdb->terms, array(
                        'term_id' => $term->term_id,
                    ) );
                    $wpdb->delete( $wpdb->termmeta, array(
                        'term_id' => $term->term_id,
                    ) );
                }
            }
            // Delete taxonomies
            $wpdb->delete( $wpdb->term_taxonomy, array(
                'taxonomy' => $taxonomy,
            ), array('%s') );
        }
        // Delete the plugin pages
        // A missing option would fall back to the defaults, which create the pages
        $aiovg_created_pages = ( get_option( 'aiovg_page_settings' ) ? aiovg_get_page_settings() : array() );
        if ( is_array( $aiovg_created_pages ) ) {
            foreach ( $aiovg_created_pages as $page => $id ) {
                if ( $id > 0 ) {
                    wp_delete_post( $id, true );
                }
            }
        }
        // Delete all the plugin options
        $aiovg_settings = array(
            'aiovg_player_settings',
            'aiovg_email_capture_settings',
            'aiovg_email_lead_captured_settings',
            'aiovg_socialshare_settings',
            'aiovg_videos_settings',
            'aiovg_categories_settings',
            'aiovg_images_settings',
            'aiovg_featured_images_settings',
            'aiovg_likes_settings',
            'aiovg_pagination_settings',
            'aiovg_video_settings',
            'aiovg_related_videos_settings',
            'aiovg_brand_settings',
            'aiovg_permalink_settings',
            'aiovg_restrictions_settings',
            'aiovg_trailer_settings',
            'aiovg_privacy_settings',
            'aiovg_general_settings',
            'aiovg_api_settings',
            'aiovg_bunny_stream_settings',
            'aiovg_page_settings',
            'aiovg_issues',
            'aiovg_publish_caps_restricted',
            'aiovg_rewrite_rules_flushed',
            'aiovg_version'
        );
        foreach ( $aiovg_settings as $settings ) {
            delete_option( $settings );
        }
        // Delete the plugin transients
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_aiovg_' ) . '%', $wpdb->esc_like( '_transient_timeout_aiovg_' ) . '%' ) );
        // Delete the plugin user meta
        $aiovg_user_meta = array(
            'aiovg_videos_likes',
            'aiovg_videos_dislikes',
            'aiovg_email_captured',
            'aiovg_video_form_tour',
            'aiovg_automation_form_tour'
        );
        foreach ( $aiovg_user_meta as $meta_key ) {
            delete_metadata(
                'user',
                0,
                $meta_key,
                '',
                true
            );
        }
        // Delete the email leads table
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'aiovg_email_leads' ) );
        // Delete the scheduled events, in case deactivation didn't clear them
        wp_clear_scheduled_hook( 'aiovg_hourly_scheduled_events' );
        wp_clear_scheduled_hook( 'aiovg_schedule_every_five_minutes' );
        wp_clear_scheduled_hook( 'aiovg_cleanup_export_directory' );
        // Delete capabilities
        $roles = new AIOVG_Roles();
        $roles->remove_caps();
        // Delete the plugin's working folders; "imports" holds Media Library files, so it stays
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        $upload_dir = wp_upload_dir();
        $dir = trailingslashit( $upload_dir['basedir'] ) . 'all-in-one-video-gallery';
        $fileSystemDirect = new WP_Filesystem_Direct(false);
        foreach ( array('exports', 'temp') as $folder ) {
            $fileSystemDirect->rmdir( $dir . '/' . $folder, true );
        }
        // Remove folders left empty; rmdir() without recursion never deletes a file
        $import_dirs = glob( $dir . '/imports/*', GLOB_ONLYDIR );
        if ( is_array( $import_dirs ) ) {
            foreach ( $import_dirs as $import_dir ) {
                $fileSystemDirect->rmdir( $import_dir );
            }
        }
        $fileSystemDirect->rmdir( $dir . '/imports' );
        $fileSystemDirect->rmdir( $dir );
    }

}
