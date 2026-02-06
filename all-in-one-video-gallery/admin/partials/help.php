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
    <!-- FAQ Section -->
    <p class="about-description">
        <?php esc_html_e( 'Frequently Asked Questions', 'all-in-one-video-gallery' ); ?>
    </p>

    <details>
        <summary>
            <?php esc_html_e( 'Does the plugin work with page builders like Elementor, Divi, WPBakery, and others?', 'all-in-one-video-gallery' ); ?>
        </summary>

        <div>
            <?php 
            printf(
                __( 'Absolutely! Simply use our <a href="%s">Shortcode Builder</a> to generate your shortcode, then insert it into your page builder. All popular page builders fully support shortcodes, so you\'re good to go.', 'all-in-one-video-gallery' ),
                esc_url( admin_url( 'admin.php?page=all-in-one-video-gallery' ) )
            );
            ?>
        </div>
    </details>

    <details>
        <summary>
            <?php esc_html_e( 'The plugin isn\'t working for me. What should I do?', 'all-in-one-video-gallery' ); ?>
        </summary>

        <div>
            <?php 
            printf(
                __( 'No worries — we\'re just an email away! Please <a href="%s">contact us here</a> and share as many details as you can about the issue. If possible, also include a link to the page where we can see the problem directly. This helps us understand what\'s happening and get you the right solution faster.', 'all-in-one-video-gallery' ),
                esc_url( admin_url( 'admin.php?page=all-in-one-video-gallery-contact' ) )
            );
            ?>
        </div>
    </details>

    <br />

    <!-- Tutorials Section -->
    <p class="about-description">
        <?php esc_html_e( 'Looking for Detailed Tutorials?', 'all-in-one-video-gallery' ); ?>
    </p>

    <p>
        <?php 
        printf(
            __( 'Visit our <a href="%s" target="_blank" rel="noopener noreferrer">Official Documentation</a> for step-by-step tutorials and helpful tips.', 'all-in-one-video-gallery' ),
            'https://plugins360.com/all-in-one-video-gallery/documentation/'
        );
        ?>
    </p>
</div>
