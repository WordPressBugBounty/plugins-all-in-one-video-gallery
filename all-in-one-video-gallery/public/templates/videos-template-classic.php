<?php

/**
 * Videos: Classic Template.
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
?>

<div id="aiovg-<?php echo esc_attr( $attributes['uid'] ); ?>" class="aiovg aiovg-videos aiovg-videos-template-classic">
    <?php                    
    // Title
    if ( ! empty( $attributes['title'] ) ) : ?>
        <h2 class="aiovg-header">
            <?php echo esc_html( $attributes['title'] ); ?>
        </h2>
    <?php endif;

    // Videos count
    if ( ! empty( $attributes['show_count'] ) ) : ?>
        <div class="aiovg-count">
            <?php 
            $videos_count = (int) $attributes['count'];
            printf( esc_html( /* translators: %s: Number of videos found. */ _n( '%s video found', '%s videos found', $videos_count, 'all-in-one-video-gallery' ) ), esc_html( number_format_i18n( $videos_count ) ) );
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Videos -->
    <div class="aiovg-section-videos aiovg-grid aiovg-row">   
        <?php   
        // The loop
        $columns = (int) $attributes['columns'];
        
        while ( $aiovg_query->have_posts() ) :        
            $aiovg_query->the_post();  
            
            $classes = array();
            $classes[] = 'aiovg-item-video';
            $classes[] = 'aiovg-item-video-' . $post->ID;
            $classes[] = 'aiovg-col';
            $classes[] = 'aiovg-col-' . $columns;
            if ( $columns > 3 ) $classes[] = 'aiovg-col-sm-3';
            if ( $columns > 2 ) $classes[] = 'aiovg-col-xs-2';
            ?>            
            <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-id="<?php echo esc_attr( $post->ID ); ?>">
                <?php aiovg_the_video_thumbnail( $post, $attributes ); ?>            
            </div>                
            <?php 
        endwhile;
            
        // Use reset postdata to restore orginal query
        wp_reset_postdata(); 
        ?>
    </div>
    
    <?php    
    if ( ! empty( $attributes['show_pagination'] ) ) { // Pagination        
        aiovg_the_pagination( $aiovg_query->max_num_pages, '', $attributes['paged'], $attributes );
    } elseif ( ! empty( $attributes['show_more'] ) ) { // More button         
        aiovg_the_more_button( $aiovg_query->max_num_pages, $attributes );
    }
    ?>
</div>