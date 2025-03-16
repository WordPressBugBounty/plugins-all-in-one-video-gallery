<?php

/**
 * Plugin Dashboard.
 *
 * @link    https://plugins360.com
 * @since   1.6.5
 *
 * @package All_In_One_Video_Gallery
 */
?>

<div id="aiovg-dashboard" class="aiovg aiovg-dashboard wrap about-wrap full-width-layout">
	<h1><?php esc_html_e( 'All-in-One Video Gallery', 'all-in-one-video-gallery' ); ?></h1>
    
    <p class="about-text">
        <?php esc_html_e( 'An ultimate video player and video gallery plugin – no coding required. Suitable for YouTubers, Video Bloggers, Course Creators, Podcasters, Sales & Marketing Professionals, and anyone using video on a website.', 'all-in-one-video-gallery' ); ?>
    </p>

    <p class="about-description">
        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=aiovg_videos' ) ); ?>" class="button button-primary button-hero">
            <span class="dashicons dashicons-format-video"></span>
            <?php esc_html_e( 'Add New Video', 'all-in-one-video-gallery' ); ?>
        </a>
        <a href="https://plugins360.com/all-in-one-video-gallery/getting-started/" class="button button-hero" target="_blank" rel="noopener noreferrer">
            <span class="dashicons dashicons-external"></span>
            <?php esc_html_e( 'Learn How to Use', 'all-in-one-video-gallery' ); ?>
        </a>
    </p>
        
	<div class="wp-badge">
        <?php printf( esc_html__( 'Version %s', 'all-in-one-video-gallery' ), AIOVG_PLUGIN_VERSION ); ?>
    </div>
    
    <h2 class="nav-tab-wrapper wp-clearfix">
		<?php		
        foreach ( $tabs as $tab => $title ) {
            $url = add_query_arg( 'tab', $tab, 'admin.php?page=all-in-one-video-gallery' );
            $url = admin_url( $url );

            $classes = array( 'nav-tab' );
            if ( $tab == $active_tab ) $classes[] = 'nav-tab-active';
            
            if ( 'issues' == $tab ) {
                $classes[] = 'aiovg-text-error';
                $title .= sprintf( ' <span class="count">(%d)</span>', count( $issues['found'] ) );
            }

            printf( 
                '<a href="%s" class="%s">%s</a>', 
                esc_url( $url ), 
                implode( ' ', $classes ), 
                $title 
            );
        }
        ?>
    </h2>

    <?php require_once AIOVG_PLUGIN_DIR . "admin/partials/{$active_tab}.php"; ?>    
</div>