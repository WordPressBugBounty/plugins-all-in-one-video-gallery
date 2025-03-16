<?php

/**
 * Dashboard: Help & Tutorials.
 *
 * @link    https://plugins360.com
 * @since   1.6.5
 *
 * @package All_In_One_Video_Gallery
 */
?>

<div id="aiovg-help">
    <p class="about-description"><?php esc_html_e( 'Frequently Asked Questions', 'all-in-one-video-gallery' ); ?></p>

    <details class="aiovg-accordion">
        <summary><?php esc_html_e( 'Does the plugin work with page builders like Elementor, Divi, WPBakery, and others?', 'all-in-one-video-gallery' ); ?></summary>
        <div>
            <?php 
            printf(
                __( 'Absolutely! Simply use our <a href="%s">Shortcode Builder</a> to generate your shortcode, then insert it into your page builder. All popular page builders fully support shortcodes, so you\'re good to go.', 'all-in-one-video-gallery' ),
                esc_url( admin_url( 'admin.php?page=all-in-one-video-gallery' ) )
            );
            ?>
        </div>
    </details>

    <details class="aiovg-accordion">
        <summary><?php esc_html_e( 'The plugin isn\'t working for me. What should I do?', 'all-in-one-video-gallery' ); ?></summary>
        <div>
            <?php 
            printf(
                __( 'No worries — we\'re just an email away! Please <a href="%s">contact us here</a> and share as many details as you can about the issue. If possible, also include a link to the page where we can see the problem directly. This helps us understand what\'s happening and get you the right solution faster.', 'all-in-one-video-gallery' ),
                esc_url( admin_url( 'admin.php?page=all-in-one-video-gallery-contact' ) )
            );
            ?>
        </div>
    </details>

    <p class="about-description"><?php esc_html_e( 'Tutorials', 'all-in-one-video-gallery' ); ?></p>

    <details class="aiovg-accordion">
        <summary><?php esc_html_e( 'Getting Started', 'all-in-one-video-gallery' ); ?></summary>
        <ol>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/getting-started/',
                    esc_html__( 'Quickstart Guide', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/installation-activation-and-updating/',
                    esc_html__( 'Installation, Activation & Updates', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/widgets-sidebar-content/',
                    esc_html__( 'Using the Shortcode Builder, Gutenberg Blocks & Widgets', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
        </ol>
    </details>

    <details class="aiovg-accordion">
        <summary><?php esc_html_e( 'Setting Up Your Gallery', 'all-in-one-video-gallery' ); ?></summary>
        <ol>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/add-your-first-video/',
                    esc_html__( 'Adding Videos', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/displaying-categories/',
                    esc_html__( 'Create & Show Video Categories', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/displaying-tags/',
                    esc_html__( 'Create & Show Video Tags', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/displaying-video-gallery/',
                    esc_html__( 'Create & Show Video Galleries', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
        </ol>
    </details>

    <details class="aiovg-accordion">
        <summary><?php esc_html_e( 'General Features', 'all-in-one-video-gallery' ); ?></summary>
        <ol>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/adding-chapters/',
                    esc_html__( 'Add Chapters to Videos', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/access-restrict-videos/',
                    esc_html__( 'Restrict Video Access', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/custom-logo-and-branding/',
                    esc_html__( 'Add Custom Logo & Branding — Pro', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/likes-and-dislikes/',
                    esc_html__( 'Add Likes / Dislikes to Videos', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/playlists-favourites/',
                    esc_html__( 'Enable Playlists & Favorites — Pro', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/gdpr-consent/',
                    esc_html__( 'Display GDPR Consent for Videos', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
        </ol>
    </details>

    <details class="aiovg-accordion">
        <summary><?php esc_html_e( 'User Features', 'all-in-one-video-gallery' ); ?></summary>
        <ol>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/front-end-user-submission/',
                    esc_html__( 'Allow Front-End Video Submissions — Pro', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/auto-thumbnail-generator/',
                    esc_html__( 'Use Auto Thumbnail Generator — Pro', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
        </ol>
    </details>

    <details class="aiovg-accordion">
        <summary><?php esc_html_e( 'Automations', 'all-in-one-video-gallery' ); ?></summary>
        <ol>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/auto-import-youtube-videos/',
                    esc_html__( 'Auto Import YouTube Videos — Pro', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/how-to-get-youtube-api-key/',
                    esc_html__( 'Get a YouTube API Key', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/auto-import-vimeo-videos/',
                    esc_html__( 'Auto Import Vimeo Videos — Pro', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/how-to-get-vimeo-access-token/',
                    esc_html__( 'Get a Vimeo Access Token', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
        </ol>
    </details>

    <details class="aiovg-accordion">
        <summary><?php esc_html_e( 'SEO & Optimization', 'all-in-one-video-gallery' ); ?></summary>
        <ol>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/video-seo/',
                    esc_html__( 'Optimize Videos for SEO — Pro', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://handbrake.fr/docs/',
                    esc_html__( 'Optimizing Video Encoding with Handbrake', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'http://www.wpbeginner.com/beginners-guide/how-to-upload-large-images-in-wordpress/',
                    esc_html__( 'Configure Your Server for Large File Uploads', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
        </ol>
    </details>

    <details class="aiovg-accordion">
        <summary><?php esc_html_e( 'Monetization', 'all-in-one-video-gallery' ); ?></summary>
        <ol>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/video-advertising/',
                    esc_html__( 'Set Up Video Ads with VAST/VPAID — Pro', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/ad-tag-variables-macros/',
                    esc_html__( 'Use Ad Tag Variables (Macros) — Pro', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
        </ol>
    </details>

    <details class="aiovg-accordion">
        <summary><?php esc_html_e( 'Translation & Multilingual', 'all-in-one-video-gallery' ); ?></summary>
        <ol>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/translate-to-your-language/',
                    esc_html__( 'Translate the Plugin into Your Language', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/multilingual-with-wpml/',
                    esc_html__( 'Using WPML for Multilingual Support', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/multilingual-with-polylang/',
                    esc_html__( 'Using Polylang for Multilingual Support', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
        </ol>
    </details>

    <details class="aiovg-accordion">
        <summary><?php esc_html_e( 'For Developers', 'all-in-one-video-gallery' ); ?></summary>
        <ol>
            <li>
                <?php 
                printf(
                    __( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>' ),
                    'https://plugins360.com/all-in-one-video-gallery/child-themes-and-templates/',
                    esc_html__( 'Customize the Plugin\'s Front-End Layouts', 'all-in-one-video-gallery' )
                );
                ?>
            </li>
        </ol>
    </details>

    <p class="about-description"><?php esc_html_e( 'Need More Help?', 'all-in-one-video-gallery' ); ?></p>
    <p><?php 
        printf( 
            __( 'If you couldn\'t find what you\'re looking for, <a href="%s">contact our support team</a> — w\'re happy to help!', 'all-in-one-video-gallery' ), 
            esc_url( admin_url( 'admin.php?page=all-in-one-video-gallery-contact' ) ) 
        );
    ?></p>
</div>
