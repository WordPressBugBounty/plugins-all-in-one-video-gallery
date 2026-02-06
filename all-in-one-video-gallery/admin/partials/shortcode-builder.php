<?php

/**
 * Dashboard: Shortcode Builder.
 *
 * @link    https://plugins360.com
 * @since   1.6.5
 *
 * @package All_In_One_Video_Gallery
 */

$fields = aiovg_get_shortcode_fields();

// Videos
$is_video_found = 0;

$args = array(				
    'post_type' => 'aiovg_videos',			
    'posts_per_page' => 1,
    'fields' => 'ids',
    'no_found_rows' => true,
    'update_post_term_cache' => false,
    'update_post_meta_cache' => false
);

$aiovg_query = new WP_Query( $args );

if ( $aiovg_query->have_posts() ) {
    $is_video_found = 1;
}

// Categories
$is_category_found = 0;

$args = array(
    'taxonomy'	 => 'aiovg_categories',		
    'parent'     => 0,
    'hide_empty' => false
);

$terms = get_terms( $args );			

if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
    $is_category_found = 1;
}
?>

<!-- Shortcode Builder -->
<div id="aiovg-shortcode-builder"> 
    <!-- Shortcode Selector -->
    <div id="aiovg-shortcode-selector">
        <p class="about-description aiovg-no-margin">
            <?php esc_html_e( 'Select a shortcode type', 'all-in-one-video-gallery' ); ?>
        </p>

        <div class="aiovg-flex aiovg-flex-wrap aiovg-gap-3">
            <?php
            foreach ( $fields as $shortcode => $params ) {
                printf( 
                    '<label><input type="radio" name="shortcode" value="%s"%s/>%s</label>', 
                    esc_attr( $shortcode ), 
                    checked( $shortcode, 'videos', false ), 
                    esc_html( $params['title'] ) 
                );
            }
            ?>
        </div>    
    </div>

    <!-- Shortcode Forms -->
    <div id="aiovg-shortcode-forms"> 
        <!-- Left Column -->  
        <div class="aiovg-left-col">
            <?php 
            foreach ( $fields as $shortcode => $params ) :
                $classes = array( 'aiovg-shortcode-form' );
                $error = '';

                if ( 'video' == $shortcode ) {
                    $classes[] = 'aiovg-type-default';
                } elseif ( 'videos' == $shortcode ) {
                    $classes[] = 'aiovg-template-classic'; 
                    
                    if ( ! $is_video_found ) {
                        $error = sprintf( 
                            __( 'No videos found. <a href="%s">Add your first video</a>', 'all-in-one-video-gallery' ),
                            esc_url( admin_url( 'post-new.php?post_type=aiovg_videos' ) )
                        );
                    }  
                } elseif ( 'categories' == $shortcode ) {
                    $classes[] = 'aiovg-template-grid';

                    if ( ! $is_category_found ) {
                        $error = sprintf( 
                            __( 'No categories found. <a href="%s">Add your first category</a>', 'all-in-one-video-gallery' ),
                            esc_url( admin_url( 'edit-tags.php?taxonomy=aiovg_categories&post_type=aiovg_videos' ) )
                        );
                    }
                }
                ?>
                <div id="aiovg-shortcode-form-<?php echo esc_attr( $shortcode ); ?>" class="<?php echo implode( ' ', $classes ); ?>"<?php if ( 'videos' != $shortcode ) echo ' style="display: none;"'; ?>>
                    <?php 
                    if ( ! empty( $error ) ) {
                        printf( '<div class="aiovg-notice aiovg-notice-error aiovg-margin-bottom">%s</div>', $error );
                    }
        
                    foreach ( $params['sections'] as $key => $section ) : ?>                         
                        <details<?php if ( 'general' == $key ) echo ' open'; ?>> 
                            <summary>            
                                <?php echo esc_html( $section['title'] ); ?>
                            </summary>  
                                                    
                            <div class="aiovg-shortcode-controls">
                                <?php foreach ( $section['fields'] as $field ) : ?>
                                    <div class="aiovg-shortcode-control aiovg-shortcode-control-<?php echo esc_attr( $field['name'] ); ?>"> 
                                        <?php if ( 'header' == $field['type'] ) : ?>    
                                            <label class="aiovg-shortcode-label aiovg-font-bold"><?php echo esc_html( $field['label'] ); ?></label>                                                 
                                        <?php elseif ( 'text' == $field['type'] || 'url' == $field['type'] || 'number' == $field['type'] ) : ?>                                        
                                            <label class="aiovg-shortcode-label"><?php echo esc_html( $field['label'] ); ?></label>
                                            <input type="text" name="<?php echo esc_attr( $field['name'] ); ?>" class="aiovg-shortcode-field widefat" value="<?php echo esc_attr( $field['value'] ); ?>" data-default="<?php echo esc_attr( $field['value'] ); ?>" />
                                        <?php elseif ( 'textarea' == $field['type'] ) : ?>
                                            <label class="aiovg-shortcode-label"><?php echo esc_html( $field['label'] ); ?></label>
                                            <textarea name="<?php echo esc_attr( $field['name'] ); ?>" class="aiovg-shortcode-field widefat" rows="8" data-default="<?php echo esc_attr( $field['value'] ); ?>"><?php echo esc_textarea( $field['value'] ); ?></textarea>
                                        <?php elseif ( 'select' == $field['type'] || 'radio' == $field['type'] ) : ?>
                                            <label class="aiovg-shortcode-label"><?php echo esc_html( $field['label'] ); ?></label> 
                                            <select name="<?php echo esc_attr( $field['name'] ); ?>" class="aiovg-shortcode-field widefat" data-default="<?php echo esc_attr( $field['value'] ); ?>">
                                                <?php
                                                foreach ( $field['options'] as $value => $label ) {
                                                    printf( 
                                                        '<option value="%s"%s>%s</option>', 
                                                        esc_attr( $value ), 
                                                        selected( $value, $field['value'], false ), 
                                                        esc_html( $label ) 
                                                    );
                                                }
                                                ?>
                                            </select>                                                                               
                                        <?php elseif ( 'checkbox' == $field['type'] ) : ?>                                        
                                            <label>				
                                                <input type="checkbox" name="<?php echo esc_attr( $field['name'] ); ?>" class="aiovg-shortcode-field" value="1" data-default="<?php echo esc_attr( $field['value'] ); ?>" <?php checked( $field['value'] ); ?> />
                                                <?php echo esc_html( $field['label'] ); ?>
                                            </label>                                            
                                        <?php elseif ( 'color' == $field['type'] ) : ?>                                        
                                            <label class="aiovg-shortcode-label"><?php echo esc_html( $field['label'] ); ?></label>
                                            <input type="text" name="<?php echo esc_attr( $field['name'] ); ?>" class="aiovg-shortcode-field aiovg-color-picker widefat" value="<?php echo esc_attr( $field['value'] ); ?>" data-default="<?php echo esc_attr( $field['value'] ); ?>" />
                                        <?php elseif ( 'media' == $field['type'] ) : ?>
                                            <label class="aiovg-shortcode-label"><?php echo esc_html( $field['label'] ); ?></label>
                                            <div class="aiovg-media-uploader">                                                
                                                <input type="text" name="<?php echo esc_attr( $field['name'] ); ?>" class="aiovg-shortcode-field widefat" value="<?php echo esc_attr( $field['value'] ); ?>" data-default="<?php echo esc_attr( $field['value'] ); ?>" />
                                                <button type="button" id="aiovg-upload-<?php echo esc_attr( $field['name'] ); ?>" class="aiovg-upload-media button" data-format="<?php echo esc_attr( $field['name'] ); ?>">
                                                    <?php esc_html_e( 'Upload File', 'all-in-one-video-gallery' ); ?>
                                                </button>
                                            </div>                                            
                                        <?php elseif ( 'parent' == $field['type'] ) : ?>
                                            <label class="aiovg-shortcode-label"><?php echo esc_html( $field['label'] ); ?></label> 
                                            <?php
                                            $args = array(
                                                'show_option_none'  => '— ' . esc_html__( 'Select Parent', 'all-in-one-video-gallery' ) . ' —',
                                                'option_none_value' => 0,
                                                'taxonomy'          => 'aiovg_categories',
                                                'name' 			    => esc_attr( $field['name'] ),
                                                'class'             => 'aiovg-shortcode-field widefat',
                                                'orderby'           => 'name',
                                                'selected'          => 0,
                                                'hierarchical'      => true,
                                                'depth'             => 10,
                                                'show_count'        => false,
                                                'hide_empty'        => false
                                            );                           
                                            
                                            wp_dropdown_categories( $args );
                                        elseif ( 'categories' == $field['type'] ) : ?>
                                            <label class="aiovg-shortcode-label"><?php echo esc_html( $field['label'] ); ?></label> 
                                            <ul name="<?php echo esc_attr( $field['name'] ); ?>" class="aiovg-shortcode-field aiovg-checklist widefat" data-default="">
                                                <?php
                                                $args = array(
                                                    'taxonomy'      => 'aiovg_categories',
                                                    'walker'        => null,
                                                    'checked_ontop' => false
                                                ); 
                                            
                                                wp_terms_checklist( 0, $args );
                                                ?>
                                            </ul>
                                        <?php elseif ( 'tags' == $field['type'] ) : ?>
                                            <label class="aiovg-shortcode-label"><?php echo esc_html( $field['label'] ); ?></label> 
                                            <ul name="<?php echo esc_attr( $field['name'] ); ?>" class="aiovg-shortcode-field aiovg-checklist widefat" data-default="">
                                                <?php
                                                $args = array(
                                                    'taxonomy'      => 'aiovg_tags',
                                                    'walker'        => null,
                                                    'checked_ontop' => false
                                                ); 
                                            
                                                wp_terms_checklist( 0, $args );
                                                ?>
                                            </ul>
                                        <?php endif; ?>
                                        
                                        <?php if ( ! empty( $field['description'] ) ) : ?>    
                                            <!-- Description -->                        
                                            <span class="description aiovg-text-muted"><?php echo wp_kses_post( $field['description'] ); ?></span>                        
                                        <?php endif; ?>
                                    </div>    
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <a href="#aiovg-shortcode-modal" id="aiovg-generate-shortcode" class="aiovg-modal-button aiovg-margin-top button button-primary button-hero">
                <?php esc_attr_e( 'Generate Shortcode', 'all-in-one-video-gallery' ); ?>
            </a>
        </div>

        <!-- Right Column -->
        <div class="aiovg-right-col">
            <div id="aiovg-shortcode-instructions-video" class="aiovg-shortcode-instructions" style="display: none;">
               <p class="about-description">
                    <?php esc_html_e( 'How to Add a Single Video', 'all-in-one-video-gallery' ); ?>
                </p>

                <p>
                    <?php esc_html_e( 'You can add a single video to any post or page using one of the following methods:', 'all-in-one-video-gallery' ); ?>
                </p>

                <ul>
                    <li>
                        <?php 
                        printf( 
                            __( 'Go to <a href="%s">Add New Video</a> under the "All Videos" menu, upload or embed your video, copy its shortcode, and paste it into your post or page.', 'all-in-one-video-gallery' ),
                            esc_url( admin_url( 'post-new.php?post_type=aiovg_videos' ) )
                        ); 
                        ?>
                    </li>

                    <li>
                        <h4><?php esc_html_e( 'Shortcode Builder:', 'all-in-one-video-gallery' ); ?></h4>
                        <?php esc_html_e( 'Use the form on this page to build your shortcode, then copy and paste it wherever you want the video to appear.', 'all-in-one-video-gallery' ); ?>
                    </li>

                    <li>
                        <h4><?php esc_html_e( 'Gutenberg Block:', 'all-in-one-video-gallery' ); ?></h4>
                        <?php 
                        printf( 
                            __( 'Use the <a href="%s">AIOVG - Video Player</a> block directly in the WordPress block editor to embed a video in your post or page.', 'all-in-one-video-gallery' ), 
                            esc_url( admin_url( 'post-new.php?post_type=page' ) ) 
                        ); 
                        ?>
                    </li>

                    <li>
                        <h4><?php esc_html_e( 'Widget:', 'all-in-one-video-gallery' ); ?></h4>
                        <?php 
                        printf( 
                            __( 'Go to "Appearance → Widgets" and add the <a href="%s">AIOVG - Video Player</a> widget to your sidebar or footer area.', 'all-in-one-video-gallery' ), 
                            esc_url( admin_url( 'widgets.php' ) ) 
                        ); 
                        ?>
                    </li>
                </ul>
            </div>

            <div id="aiovg-shortcode-instructions-videos" class="aiovg-shortcode-instructions">
                <p class="about-description">
                    <?php esc_html_e( 'How to Create or Add a Video Gallery', 'all-in-one-video-gallery' ); ?>
                </p>

                <p>
                    <?php esc_html_e( 'Follow these simple steps to create and display your video gallery:', 'all-in-one-video-gallery' ); ?>
                </p>

                <h4>1. <?php esc_html_e( 'Add Videos', 'all-in-one-video-gallery' ); ?></h4>

                <ul>
                    <li>
                        <?php
                        printf(
                            __( 'Go to <a href="%s">Add New Video</a> to upload or embed videos from YouTube, Vimeo, Dailymotion, or any external sources.', 'all-in-one-video-gallery' ),
                            esc_url( admin_url( 'post-new.php?post_type=aiovg_videos' ) )
                        );
                        ?>
                    </li>

                    <li>
                        <?php
                        printf(
                            __( 'Use the <a href="%s">Bulk Import / Export</a> option to quickly import videos from a "CSV file" or a "Folder".', 'all-in-one-video-gallery' ),
                            esc_url( admin_url( 'admin.php?page=aiovg_import_export' ) )
                        );
                        ?>
                    </li>
                </ul>

                <h4>2. <?php esc_html_e( 'Display Your Videos', 'all-in-one-video-gallery' ); ?></h4>

                <p>
                    <?php esc_html_e( 'Choose one of the following methods to display your gallery on your website:', 'all-in-one-video-gallery' ); ?>
                </p>

                <ul>
                    <li>
                        <h4><?php esc_html_e( 'Shortcode Builder:', 'all-in-one-video-gallery' ); ?></h4>
                        <?php esc_html_e( 'Use the form on this page to build your shortcode, then copy and paste it into any "post", "page", or "template".', 'all-in-one-video-gallery' ); ?>
                    </li>

                    <li>
                        <h4><?php esc_html_e( 'Gutenberg Block:', 'all-in-one-video-gallery' ); ?></h4>

                        <?php 
                        printf( 
                            __( 'Use the <a href="%s">AIOVG - Video Gallery</a> block directly in the WordPress block editor.', 'all-in-one-video-gallery' ), 
                            esc_url( admin_url( 'post-new.php?post_type=page' ) ) 
                        ); 
                        ?>
                    </li>

                    <li>
                        <h4><?php esc_html_e( 'Widget:', 'all-in-one-video-gallery' ); ?></h4>

                        <?php 
                        printf( 
                            __( 'Go to "Appearance → Widgets" and add the <a href="%s">AIOVG - Video Gallery</a> widget to your sidebar or footer area.', 'all-in-one-video-gallery' ), 
                            esc_url( admin_url( 'widgets.php' ) ) 
                        ); 
                        ?>
                    </li>
                </ul>
            </div>

            <div id="aiovg-shortcode-instructions-categories" class="aiovg-shortcode-instructions" style="display: none;">
                <p class="about-description">
                    <?php esc_html_e( 'How to Create and Display Video Categories', 'all-in-one-video-gallery' ); ?>
                </p>

                <p>
                    <?php esc_html_e( 'Follow these simple steps to create and display your video categories:', 'all-in-one-video-gallery' ); ?>
                </p>

                <h4>1. <?php esc_html_e( 'Add Categories', 'all-in-one-video-gallery' ); ?></h4>

                <ul>
                    <li>
                        <?php
                        printf(
                            __( 'Go to <a href="%s">Video Categories</a> under the main "Video Gallery" menu to create and manage your categories.', 'all-in-one-video-gallery' ),
                            esc_url( admin_url( 'edit-tags.php?taxonomy=aiovg_categories&post_type=aiovg_videos' ) )
                        );
                        ?>
                    </li>
                </ul>

                <h4>2. <?php esc_html_e( 'Display Video Categories', 'all-in-one-video-gallery' ); ?></h4>

                <p>
                    <?php esc_html_e( 'Choose one of the following methods to display your video categories on your website:', 'all-in-one-video-gallery' ); ?>
                </p>

                <ul>
                    <li>
                        <h4><?php esc_html_e( 'Shortcode Builder:', 'all-in-one-video-gallery' ); ?></h4>
                        <?php esc_html_e( 'Use the form on this page to build your shortcode, then copy and paste it into any "post", "page", or "template".', 'all-in-one-video-gallery' ); ?>
                    </li>

                    <li>
                        <h4><?php esc_html_e( 'Gutenberg Block:', 'all-in-one-video-gallery' ); ?></h4>
                        <?php 
                        printf( 
                            __( 'Use the <a href="%s">AIOVG - Video Categories</a> block directly in the WordPress block editor.', 'all-in-one-video-gallery' ), 
                            esc_url( admin_url( 'post-new.php?post_type=page' ) ) 
                        ); 
                        ?>
                    </li>

                    <li>
                        <h4><?php esc_html_e( 'Widget:', 'all-in-one-video-gallery' ); ?></h4>
                        <?php 
                        printf( 
                            __( 'Go to "Appearance → Widgets" and add the <a href="%s">AIOVG - Video Categories</a> widget to your sidebar or footer area.', 'all-in-one-video-gallery' ), 
                            esc_url( admin_url( 'widgets.php' ) ) 
                        ); 
                        ?>
                    </li>
                </ul>
            </div>

            <div id="aiovg-shortcode-instructions-search_form" class="aiovg-shortcode-instructions" style="display: none;">
                <p class="about-description">
                    <?php esc_html_e( 'How to Create and Add a Video Search Form', 'all-in-one-video-gallery' ); ?>
                </p>

                <p>
                    <?php esc_html_e( 'Choose one of the following methods to add the video search form to your website:', 'all-in-one-video-gallery' ); ?>
                </p>

                <ul>
                    <li>
                        <h4><?php esc_html_e( 'Shortcode Builder:', 'all-in-one-video-gallery' ); ?></h4>
                        <?php esc_html_e( 'Use the form on this page to build your shortcode, then copy and paste it into any "post", "page", or "template".', 'all-in-one-video-gallery' ); ?>
                    </li>

                    <li>
                        <h4><?php esc_html_e( 'Gutenberg Block:', 'all-in-one-video-gallery' ); ?></h4>
                        <?php 
                        printf( 
                            __( 'Use the <a href="%s">AIOVG - Search Form</a> block directly in the WordPress block editor.', 'all-in-one-video-gallery' ), 
                            esc_url( admin_url( 'post-new.php?post_type=page' ) ) 
                        ); 
                        ?>
                    </li>

                    <li>
                        <h4><?php esc_html_e( 'Widget:', 'all-in-one-video-gallery' ); ?></h4>
                        <?php 
                        printf( 
                            __( 'Go to "Appearance → Widgets" and add the <a href="%s">AIOVG - Search Form</a> widget to your sidebar or footer area.', 'all-in-one-video-gallery' ), 
                            esc_url( admin_url( 'widgets.php' ) ) 
                        ); 
                        ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Shortcode Modal -->
    <div id="aiovg-shortcode-modal" class="aiovg aiovg-modal mfp-hide">
        <div class="aiovg-modal-body">
            <p><?php esc_html_e( 'Congrats! copy the shortcode below and paste it in your POST or PAGE where you need the gallery,', 'all-in-one-video-gallery' ); ?></p>
            <textarea id="aiovg-shortcode" class="widefat code" rows="3" autofocus="autofocus" onfocus="this.select()"></textarea>
        </div>
    </div>
</div>