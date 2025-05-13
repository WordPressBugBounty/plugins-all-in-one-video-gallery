<?php

/**
 * Video Thumbnail.
 *
 * @link    https://plugins360.com
 * @since   1.0.0
 *
 * @package All_In_One_Video_Gallery
 */

$general_settings = get_option( 'aiovg_general_settings' );
$images_settings  = get_option( 'aiovg_images_settings' );

$post_meta = get_post_meta( $post->ID );

$image_size = ! empty( $images_settings['size'] ) ? $images_settings['size'] : 'large';
$image_data = aiovg_get_image( $post->ID, $image_size, 'post', true );
$image = $image_data['src'];
$image_alt = ! empty( $image_data['alt'] ) ? $image_data['alt'] : $post->post_title;

$has_access = aiovg_current_user_can( 'play_aiovg_video', $post->ID );

$lazyloading = ! empty( $general_settings['lazyloading'] ) ? 'loading="lazy" ' : '';
?>

<div class="aiovg-thumbnail aiovg-thumbnail-style-image-top">
    <a href="<?php the_permalink(); ?>" class="aiovg-responsive-container" style="padding-bottom: <?php echo esc_attr( $attributes['ratio'] ); ?>;">
        <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" class="aiovg-responsive-element" <?php echo $lazyloading; ?>/>                    
        
        <?php if ( $attributes['show_duration'] && ! empty( $post_meta['duration'][0] ) ) : ?>
            <div class="aiovg-duration">
                <?php echo esc_html( $post_meta['duration'][0] ); ?>
            </div>
        <?php endif; ?>

        <?php if ( $has_access ) : ?>
            <svg xmlns="http://www.w3.org/2000/svg" fill="white" width="40" height="40" viewBox="0 0 24 24" class="aiovg-svg-icon-play aiovg-flex-shrink-0">
                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm14.024-.983a1.125 1.125 0 0 1 0 1.966l-5.603 3.113A1.125 1.125 0 0 1 9 15.113V8.887c0-.857.921-1.4 1.671-.983l5.603 3.113Z" clip-rule="evenodd" />
            </svg>
        <?php else : ?>
            <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="32" height="32" viewBox="0 0 50 50" class="aiovg-svg-icon-locked aiovg-flex-shrink-0">
                <path d="M 25 3 C 18.363281 3 13 8.363281 13 15 L 13 20 L 9 20 C 7.300781 20 6 21.300781 6 23 L 6 47 C 6 48.699219 7.300781 50 9 50 L 41 50 C 42.699219 50 44 48.699219 44 47 L 44 23 C 44 21.300781 42.699219 20 41 20 L 37 20 L 37 15 C 37 8.363281 31.636719 3 25 3 Z M 25 5 C 30.566406 5 35 9.433594 35 15 L 35 20 L 15 20 L 15 15 C 15 9.433594 19.433594 5 25 5 Z M 25 30 C 26.699219 30 28 31.300781 28 33 C 28 33.898438 27.601563 34.6875 27 35.1875 L 27 38 C 27 39.101563 26.101563 40 25 40 C 23.898438 40 23 39.101563 23 38 L 23 35.1875 C 22.398438 34.6875 22 33.898438 22 33 C 22 31.300781 23.300781 30 25 30 Z"></path>
            </svg>
        <?php endif; ?>
    </a>    	
    
    <div class="aiovg-caption">
        <?php if ( $attributes['show_title'] ) : ?>
            <div class="aiovg-title">
                <?php
                $filtered_title  = '<a href="' . esc_url( get_permalink() ) . '" class="aiovg-link-title">';
                $filtered_title .= wp_kses_post( aiovg_truncate( get_the_title(), $attributes['title_length'] ) );
                $filtered_title .= '</a>';
                $filtered_title = apply_filters( 'aiovg_the_title', $filtered_title, $post->ID );

                echo $filtered_title;
                ?>
            </div>
        <?php endif; ?>

        <?php
        // Labels
        if ( ! $has_access ) {
            $restrictions_settings = get_option( 'aiovg_restrictions_settings' );

			if ( ! empty( $restrictions_settings['show_restricted_label'] ) && ! empty( $restrictions_settings['restricted_label_text'] ) ) {
				$styles = array();				

				if ( $restricted_label_bg_color = $restrictions_settings['restricted_label_bg_color'] ) {
					$styles[] = sprintf( 'background-color: %s', $restricted_label_bg_color );
				}

				if ( $restricted_label_text_color = $restrictions_settings['restricted_label_text_color'] ) {
                    $styles[] = sprintf( 'color: %s', $restricted_label_text_color );
				}

				printf( 
					'<div class="aiovg-labels"><span class="aiovg-restricted-label" style="%s">%s</span></div>',
					esc_attr( implode( '; ', $styles ) ),
					esc_html( $restrictions_settings['restricted_label_text'] )
				);
			}
		}
        ?>

        <?php
        $meta = array();					

        // Date
        if ( $attributes['show_date'] ) {
            $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
            </svg>';

            $meta[] = sprintf(
                '<div class="aiovg-date aiovg-flex aiovg-gap-1 aiovg-items-center">%s<time>%s</time></div>',
                $icon,
                esc_html( aiovg_get_the_date() )
            );
        }
                
        // Author
        if ( $attributes['show_user'] ) {
            $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>';

            $author_url = aiovg_get_user_videos_page_url( $post->post_author );

            $meta[] = sprintf( 
                '<div class="aiovg-user aiovg-flex aiovg-gap-1 aiovg-items-center">%s<a href="%s" class="aiovg-link-author">%s</a></div>', 
                $icon,
                esc_url( $author_url ), 
                esc_html( get_the_author() ) 
            );			
        }

        // Views
        if ( $attributes['show_views'] ) {
            $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>';

            $meta[] = sprintf(
                '<div class="aiovg-views aiovg-flex aiovg-gap-1 aiovg-items-center">%s<span class="aiovg-views-count">%s</span></div>',
                $icon,
                sprintf( 
                    esc_html__( '%s views', 'all-in-one-video-gallery' ), 
                    isset( $post_meta['views'] ) ? number_format_i18n( $post_meta['views'][0] ) : 0
                )
            );
        }

        // Likes
        if ( $attributes['show_likes'] ) {           
            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
            </svg>';

            $meta[] = sprintf(
                '<div class="aiovg-likes aiovg-flex aiovg-gap-1 aiovg-items-center">%s<span class="aiovg-likes-count">%d</span><span class="aiovg-likes-label">%s</span></div>',
                $icon,
                ( isset( $post_meta['likes'] ) ? (int) $post_meta['likes'][0] : 0 ),
                esc_html__( 'likes', 'all-in-one-video-gallery' )
            );
        }

        // Dislikes
        if ( $attributes['show_dislikes'] ) {           
            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.498 15.25H4.372c-1.026 0-1.945-.694-2.054-1.715a12.137 12.137 0 0 1-.068-1.285c0-2.848.992-5.464 2.649-7.521C5.287 4.247 5.886 4 6.504 4h4.016a4.5 4.5 0 0 1 1.423.23l3.114 1.04a4.5 4.5 0 0 0 1.423.23h1.294M7.498 15.25c.618 0 .991.724.725 1.282A7.471 7.471 0 0 0 7.5 19.75 2.25 2.25 0 0 0 9.75 22a.75.75 0 0 0 .75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 0 0 2.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384m-10.253 1.5H9.7m8.075-9.75c.01.05.027.1.05.148.593 1.2.925 2.55.925 3.977 0 1.487-.36 2.89-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398-.306.774-1.086 1.227-1.918 1.227h-1.053c-.472 0-.745-.556-.5-.96a8.95 8.95 0 0 0 .303-.54" />
            </svg>';

            $meta[] = sprintf(
                '<div class="aiovg-dislikes aiovg-flex aiovg-gap-1 aiovg-items-center">%s<span class="aiovg-dislikes-count">%d</span><span class="aiovg-dislikes-label">%s</span></div>',
                $icon,
                ( isset( $post_meta['dislikes'] ) ? (int) $post_meta['dislikes'][0] : 0 ),
                esc_html__( 'dislikes', 'all-in-one-video-gallery' )
            );
        }

        // ...
        if ( count( $meta ) ) {
            echo '<div class="aiovg-meta aiovg-flex aiovg-flex-wrap aiovg-gap-1 aiovg-items-center aiovg-text-small">';
            echo implode( '<span class="aiovg-text-separator">/</span>', $meta );
            echo '</div>';
        }
        ?>       
        
        <?php
        // Categories
        if ( $attributes['show_category'] ) {
            $categories = wp_get_object_terms( get_the_ID(), 'aiovg_categories', array(
                'orderby' => sanitize_text_field( $attributes['categories_orderby'] ),
                'order'   => sanitize_text_field( $attributes['categories_order'] )
            ));

            if ( ! empty( $categories ) ) {
                $meta = array();

                foreach ( $categories as $category ) {
                    $category_url = aiovg_get_category_page_url( $category );

                    $meta[] = sprintf( 
                        '<a href="%s" class="aiovg-link-category">%s</a>', 
                        esc_url( $category_url ), 
                        esc_html( $category->name ) 
                    );
                }

                echo '<div class="aiovg-category aiovg-flex aiovg-flex-wrap aiovg-gap-1 aiovg-items-center aiovg-text-small">';
                echo '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                </svg>';
                echo '<div class="aiovg-item-category">' . implode( '<span class="aiovg-separator">,</span></div><div class="aiovg-item-category">', $meta ) . '</div>';
                echo '</div>';
            }
        }
        ?>

        <?php
        // Tags
        if ( $attributes['show_tag'] ) {
            $tags = wp_get_object_terms( get_the_ID(), 'aiovg_tags', array(
                'orderby' => sanitize_text_field( $attributes['categories_orderby'] ),
                'order'   => sanitize_text_field( $attributes['categories_order'] )
            ));

            if ( ! empty( $tags ) ) {
                $meta = array();

                foreach ( $tags as $tag ) {
                    $tag_url = aiovg_get_tag_page_url( $tag );

                    $meta[] = sprintf( 
                        '<a href="%s" class="aiovg-link-tag">%s</a>', 
                        esc_url( $tag_url ), 
                        esc_html( $tag->name ) 
                    );
                }

                echo '<div class="aiovg-tag aiovg-flex aiovg-flex-wrap aiovg-gap-1 aiovg-items-center aiovg-text-small">';
                echo '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008Z" />
                </svg>';
                echo '<div class="aiovg-item-tag">' . implode( '<span class="aiovg-separator">,</span></div><div class="aiovg-item-tag">', $meta ) . '</div>';
                echo '</div>';
            }
        }
        ?>       

        <?php if ( $attributes['show_excerpt'] ) : ?>
            <div class="aiovg-excerpt aiovg-hide-if-empty"><?php the_aiovg_excerpt( $attributes['excerpt_length'] ); ?></div>
        <?php endif; ?> 
        
        <!-- After Thumbnail -->
        <?php the_aiovg_content_after_thumbnail( $attributes ); ?>
    </div>    
</div>