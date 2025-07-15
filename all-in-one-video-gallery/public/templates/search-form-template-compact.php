<?php

/**
 * Search Form: Compact Layout.
 *
 * @link    https://plugins360.com
 * @since   3.0.0
 *
 * @package All_In_One_Video_Gallery
 */

$is_form_submitted = false;
if ( isset( $_GET['vi'] ) ) {
	$is_form_submitted = true;
}

$search_form_mode = 'search';
if ( isset( $attributes['filters_mode'] ) ) {
	$search_form_mode = $attributes['filters_mode'];
}

$search_page_id  = (int) $attributes['search_page_id'];
$search_page_url = aiovg_get_search_page_url( $search_page_id );
?>

<div class="aiovg aiovg-search-form aiovg-search-form-template-compact aiovg-search-form-mode-<?php echo esc_attr( $search_form_mode ); ?>">
	<form method="get" action="<?php echo esc_url( $search_page_url ); ?>">
    	<?php if ( ! get_option( 'permalink_structure' ) ) : ?>
       		<input type="hidden" name="page_id" value="<?php echo $search_page_id; ?>" />
    	<?php endif; ?>

		<div class="aiovg-form-group aiovg-field-keyword">
			<input type="text" name="vi" class="aiovg-form-control" placeholder="<?php esc_attr_e( 'Search Videos', 'all-in-one-video-gallery' ); ?>" value="<?php echo isset( $_GET['vi'] ) ? esc_attr( stripslashes( $_GET['vi'] ) ) : ''; ?>" />
			<button type="submit" class="aiovg-button aiovg-button-submit"<?php if ( $is_form_submitted ) echo ' hidden'; ?>> 
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">
					<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
				</svg>
			</button>
			<button type="button" class="aiovg-button aiovg-button-reset" onclick="location.href='<?php echo esc_url( $search_page_url ); ?>';"<?php if ( ! $is_form_submitted ) echo ' hidden'; ?>> 
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
				</svg>
			</button>
		</div>
		
		<!-- Hook for developers to add new fields -->
        <?php do_action( 'aiovg_search_form_fields', $attributes ); ?>
	</form> 
</div>
