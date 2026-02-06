<?php

/**
 * Import / Export.
 *
 * @link    https://plugins360.com
 * @since   4.5.2
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AIOVG_Admin_Import_Export class.
 *
 * @since 4.5.2
 */
class AIOVG_Admin_Import_Export {

	/**
	 * Add a settings menu for the plugin.
	 *
	 * @since 4.5.2
	 */
	public function admin_menu() {	
		add_submenu_page(
			'all-in-one-video-gallery',
			__( 'All-in-One Video Gallery - Import / Export', 'all-in-one-video-gallery' ),
			__( 'Bulk Import / Export', 'all-in-one-video-gallery' ),
			'manage_aiovg_options',
			'aiovg_import_export',
			array( $this, 'display_page' )
		);	

		if ( aiovg_fs()->is_not_paying() ) {
			add_submenu_page(
				'all-in-one-video-gallery',
				'',
				'<span class="aiovg-menu-separator"></span>',
				'manage_aiovg_options',
				'all-in-one-video-gallery-separator-2',
				'__return_null'
			);
		}
	}
	
	/**
	 * Display import / export page.
	 *
	 * @since 4.5.2
	 */
	public function display_page() {
		require_once AIOVG_PLUGIN_DIR . 'admin/partials/import-export.php';		
	}	

	/**
	 * AJAX callback to import videos from a folder.
	 *
	 * @since 4.5.2
	 */
	public function ajax_callback_import_folder() {
		@set_time_limit( 1200 );
		ignore_user_abort( true );

		check_ajax_referer( 'aiovg_ajax_nonce', 'security' ); // Verify the nonce for security

		if ( ! current_user_can( 'manage_aiovg_options' ) ) {
			wp_send_json_error( array( 'error' => esc_html__( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) ) );
		}

		$response = array();

		// Sanitize and extract folder path
		$folder = isset( $_POST['folder'] ) ? sanitize_text_field( wp_unslash( $_POST['folder'] ) ) : '';
		
		$resolved_folder_path = $this->resolve_folder_path( $folder );
		if ( is_wp_error( $resolved_folder_path ) ) {
    		wp_send_json_error( array( 'error' => $resolved_folder_path->get_error_message() ) );
		}

		$base_folder_path = $resolved_folder_path;

		// Sanitize options
		$include_subfolders = isset( $_POST['include_subfolders'] ) ? (int) $_POST['include_subfolders'] : 0;
		$slug_strategy      = isset( $_POST['slug_strategy'] ) ? sanitize_key( $_POST['slug_strategy'] ) : 'filename';
		$set_featured_image = isset( $_POST['set_featured_image'] ) ? (int) $_POST['set_featured_image'] : 0;
		$enable_downloads   = isset( $_POST['enable_downloads'] ) ? (int) $_POST['enable_downloads'] : 0;
		$enable_comments    = isset( $_POST['enable_comments'] ) ? (int) $_POST['enable_comments'] : 0;
		$access_control     = isset( $_POST['access_control'] ) ? (int) $_POST['access_control'] : -1;
		$restricted_roles   = isset( $_POST['restricted_roles'] ) ? array_map( 'sanitize_text_field', $_POST['restricted_roles'] ) : array();
		$offset             = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;
		$limit              = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 20;

		// Taxonomies
		$categories = array();
		if ( isset( $_POST['tax_input'] ) && isset( $_POST['tax_input']['aiovg_categories'] ) ) {
			$categories = array_map( 'intval', $_POST['tax_input']['aiovg_categories'] );
		}

		$tags = array();
		if ( isset( $_POST['tax_input'] ) && isset( $_POST['tax_input']['aiovg_tags'] ) ) {
			$tags = array_map( 'intval', $_POST['tax_input']['aiovg_tags'] );
		}

		// Unique ID for this import
		$import_id = md5( $folder );

		// Find video files
		$videos           = array();
		$matches          = array();
		$video_extensions = array( 'mp4', 'webm', 'ogv', 'm4v', 'mov' );
		$image_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
		$current_index    = 0;
		$processed        = 0;

		try {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $base_folder_path, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $iterator as $file ) {
				if ( ! $file->isFile() ) {
					continue;
				}

				// Normalize path once per file
				$current_folder_path = wp_normalize_path( $file->getPath() );
				$file_path           = $file->getPathname();

				// Skip files from subfolders if not allowed
				if ( ! $include_subfolders && $current_folder_path !== $base_folder_path ) {
					continue;
				}				

				// Skip early if not a video
				$extension = strtolower( $file->getExtension() );
				if ( ! in_array( $extension, $video_extensions, true ) ) {
					continue;
				}				

				// Respect offset (counting videos only)
				if ( $current_index++ < $offset ) {
					continue;
				}

				// Stop when limit is reached
				if ( $processed >= $limit ) {
					break;
				}

				// Build base name and title
				$file_name  = pathinfo( $file_path, PATHINFO_FILENAME ); 
				$post_title = ucwords( preg_replace( '/[-_]+/', ' ', $file_name ) );

				// Add video entry
				$matches[ $file_name ] = array(
					'title' => $post_title,
					'video' => $this->make_absolute_url( $file_path )
				);					

				// Try to find matching image in the same folder
				foreach ( $image_extensions as $image_extension ) {
					$image_path = $current_folder_path. '/' . $file_name . '.' . $image_extension;

					if ( @file_exists( $image_path ) ) {
						$matches[ $file_name ]['image']      = $this->make_absolute_url( $image_path );
						$matches[ $file_name ]['image_path'] = $image_path; // Already normalized
						break;
					}
				}

				$processed++;
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

		// Prepare videos for import
		foreach ( $matches as $name => $data ) {
			if ( empty( $data['video'] ) ) {
				continue;
			}

			$videos[] = array(
				'title'      => $data['title'],				
				'src'        => $data['video'],
				'image'      => isset( $data['image'] ) ? $data['image'] : '',
				'image_path' => isset( $data['image_path'] ) ? $data['image_path'] : ''				
			);
		}
		
		// Import videos
		$imported_data = $this->import_videos_from_folder( $videos, array(
			'slug_strategy'      => $slug_strategy,
			'categories'         => $categories,
			'tags'               => $tags,
			'comment_status'     => ( ! empty( $enable_comments ) ? 'open' : 'closed' ),
			'set_featured_image' => $set_featured_image,
			'enable_downloads'   => $enable_downloads,
			'access_control'     => $access_control,
			'restricted_roles'   => $restricted_roles,
			'import_id'          => $import_id
		) );

		// Calculate new offset
		$new_offset = $offset + $processed;

		// Batch processing
		if ( $current_index > $new_offset ) {
			$message = sprintf(
				esc_html__( '%s videos imported, %s excluded as duplicates, and %s failed to import.', 'all-in-one-video-gallery' ),
				'%%imported%%',
				'%%skipped%%',
				'%%failed%%'
			);

			$response = array(
				'step'     => 'process',
				'message'  => $message,
				'offset'   => $new_offset,		
				'imported' => $imported_data['imported'],
				'skipped'  => $imported_data['skipped'],
				'failed'   => $imported_data['failed']						
			);

			wp_send_json_success( $response );
		}

		// Build the final response
		$message = sprintf(
			esc_html__( '%s videos imported, %s excluded as duplicates, and %s failed to import.', 'all-in-one-video-gallery' ),
			'%%imported%%',
			'%%skipped%%',
			'%%failed%%'
		);

		$message .= sprintf(
			' <a href="%s" target="_blank">%s</a>',
			esc_url( admin_url( 'edit.php?post_type=aiovg_videos&aiovg_filter=imported&import_id=' . $import_id ) ),
			esc_html__( 'Click here to view the imported videos', 'all-in-one-video-gallery' )
		);

		$response = array(
			'step'     => 'done',
			'message'  => $message,
			'offset'   => $new_offset,		
			'imported' => $imported_data['imported'],
			'skipped'  => $imported_data['skipped'],
			'failed'   => $imported_data['failed']			
		);
		
		wp_send_json_success( $response ); // Send successful JSON response
	}

	/**
	 * AJAX callback to import videos from a csv file.
	 *
	 * @since 4.5.2
	 */
	public function ajax_callback_import_csv() {
		@set_time_limit( 1200 );
		ignore_user_abort( true );

		check_ajax_referer( 'aiovg_ajax_nonce', 'security' ); // Verify the nonce for security

		if ( ! current_user_can( 'manage_aiovg_options' ) ) {
			wp_send_json_error( array( 'error' => esc_html__( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) ) );
		}

		// Sanitize and extract parameters
		$csv_file           = isset( $_POST['csv_file'] ) ? esc_url_raw( $_POST['csv_file'] ) : '';
		$zip_file           = isset( $_POST['zip_file'] ) ? sanitize_text_field( wp_unslash( $_POST['zip_file'] ) ) : '';
		$folder             = isset( $_POST['folder'] ) ? sanitize_text_field( wp_unslash( $_POST['folder'] ) ) : '';
		$columns_separator  = isset( $_POST['columns_separator'] ) ? sanitize_text_field( $_POST['columns_separator'] ) : ',';	
		$values_separator   = isset( $_POST['values_separator'] ) ? sanitize_text_field( $_POST['values_separator'] ) : ';';	
		$add_new_term       = isset( $_POST['add_new_term'] ) ? (int) $_POST['add_new_term'] : 0;
		$add_new_user       = isset( $_POST['add_new_user'] ) ? (int) $_POST['add_new_user'] : 0;
		$set_featured_image = isset( $_POST['set_featured_image'] ) ? (int) $_POST['set_featured_image'] : 0;
		$csv_columns        = isset( $_POST['csv_columns'] ) ? array_map( 'sanitize_text_field', $_POST['csv_columns'] ) : array();
		$offset             = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;
		$limit              = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 20;

		$response = $this->parse_csv( $csv_file, array(
			'csv_columns'       => $csv_columns,
			'columns_separator' => $columns_separator,
			'offset'            => $offset,
			'limit'             => $limit
		) );		

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'error' => $response->get_error_message() ) );
		}

		$videos     = $response['videos'];
		$has_more   = $response['has_more'];
		$new_offset = $response['new_offset'];

		if ( ! empty( $folder ) ) {
			$folder = $this->resolve_folder_path( $folder );

			if ( is_wp_error( $folder ) ) {
				wp_send_json_error( array( 'error' => $folder->get_error_message() ) );
			}
		} elseif ( ! empty( $zip_file ) ) {
			$folder = $this->resolve_import_directory( $zip_file );

			if ( is_wp_error( $folder ) ) {
				wp_send_json_error( array( 'error' => $folder->get_error_message() ) );
			}
		}

		// Unique ID for this import
		$import_id = md5( $csv_file );

		// Import videos
		$video_settings = aiovg_get_option( 'aiovg_video_settings' );

		$imported_data = $this->import_videos_from_csv( $videos, array(
			'folder'             => $folder,
			'offset'             => $offset,
			'values_separator'   => $values_separator,
			'add_new_term'       => $add_new_term,
			'add_new_user'       => $add_new_user,
			'set_featured_image' => $set_featured_image,
			'comment_status'     => ( (int) $video_settings['has_comments'] > 0 ) ? 'open' : 'closed',
			'import_id'          => $import_id	
		) );

		// Batch processing
		if ( $has_more ) {
			if ( count( $imported_data['updated'] ) > 0 ) {
				$message = sprintf(
					esc_html__( '%s videos imported, %s updated, %s excluded as duplicates, and %s failed to import.', 'all-in-one-video-gallery' ),
					'%%imported%%',
					'%%updated%%',
					'%%skipped%%',
					'%%failed%%'
				);
			} else {
				$message = sprintf(
					esc_html__( '%s videos imported, %s excluded as duplicates, and %s failed to import.', 'all-in-one-video-gallery' ),
					'%%imported%%',
					'%%skipped%%',
					'%%failed%%'
				);
			}

			$response = array(
				'step'     => 'process',
				'message'  => $message,
				'folder'   => $folder,	
				'offset'   => $new_offset,				
				'imported' => $imported_data['imported'],
				'updated'  => $imported_data['updated'],
				'skipped'  => $imported_data['skipped'],
				'failed'   => $imported_data['failed'],
				'errors'   => $imported_data['errors']						
			);

			wp_send_json_success( $response );
		}

		// Delete the attachments before returning the final response
		$maybe_attachments = array( $csv_file, $zip_file );

		foreach ( $maybe_attachments as $file_path ) {
			if ( empty( $file_path ) ) continue;

			// Only delete if it's an attachment
			$attachment_id = attachment_url_to_postid( $file_path );
			if ( $attachment_id ) {
				wp_delete_attachment( $attachment_id, true );
			}
		}

		// Build the final response
		if ( count( $imported_data['updated'] ) > 0 ) {
			$message = sprintf(
				esc_html__( '%s videos imported, %s updated, %s excluded as duplicates, and %s failed to import.', 'all-in-one-video-gallery' ),
				'%%imported%%',
				'%%updated%%',
				'%%skipped%%',
				'%%failed%%'
			);

			$message .= sprintf(
				' <a href="%s" target="_blank">%s</a>',
				esc_url( admin_url( 'edit.php?post_type=aiovg_videos&aiovg_filter=imported&import_id=' . $import_id ) ),
				esc_html__( 'Click here to view the imported videos', 'all-in-one-video-gallery' )
			);
		} else {
			$message = sprintf(
				esc_html__( '%s videos imported, %s excluded as duplicates, and %s failed to import.', 'all-in-one-video-gallery' ),
				'%%imported%%',
				'%%skipped%%',
				'%%failed%%'
			);

			$message .= sprintf(
				' <a href="%s" target="_blank">%s</a>',
				esc_url( admin_url( 'edit.php?post_type=aiovg_videos&aiovg_filter=imported&import_id=' . $import_id ) ),
				esc_html__( 'Click here to view the imported videos', 'all-in-one-video-gallery' )
			);
		}

		$response = array(
			'step'     => 'done',
			'message'  => $message,
			'folder'   => $folder,
			'offset'   => $new_offset,				
			'imported' => $imported_data['imported'],
			'updated'  => $imported_data['updated'],
			'skipped'  => $imported_data['skipped'],
			'failed'   => $imported_data['failed'],
			'errors'   => $imported_data['errors']			
		);
		
		wp_send_json_success( $response ); // Send successful JSON response
	}

	/**
	 * AJAX callback to get CSV column mapping fields.
	 *
	 * @since 4.5.2
	 */
	public function ajax_callback_get_csv_columns() {
		check_ajax_referer( 'aiovg_ajax_nonce', 'security' ); // Verify the nonce for security

		if ( ! current_user_can( 'manage_aiovg_options' ) ) {
			wp_send_json_error( array( 'error' => esc_html__( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) ) );
		}

		// Sanitize and extract parameters.
		$csv_file          = isset( $_POST['csv_file'] ) ? esc_url_raw( $_POST['csv_file'] ) : '';
		$columns_separator = isset( $_POST['columns_separator'] ) ? sanitize_text_field( $_POST['columns_separator'] ) : ',';

		$response = $this->parse_csv( $csv_file, array(
			'columns_separator' => $columns_separator,
			'headers_only'      => true
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'error' => $response->get_error_message() ) );
		}

		$csv_headers = $response['headers'];
		$csv_columns = $this->get_csv_column_mapping_fields();
		$html        = '';

		foreach ( $csv_headers as $index => $header ) {
			$html .= '<div class="aiovg-form-control">';

			$html .= sprintf(
				'<label for="aiovg-csv_columns-%s" class="aiovg-form-label">%s</label>',
				esc_attr( $index ),
				esc_html( $header )
			);

			$html .= sprintf(
				'<select name="csv_columns[%s]" id="aiovg-csv_columns-%s" class="widefat">',
				esc_attr( $index ),
				esc_attr( $index )
			);

			$html .= sprintf(
				'<option value="">— %s —</option>',
				esc_html__( 'Select Field', 'all-in-one-video-gallery' )
			);

			foreach ( $csv_columns as $value => $label ) {
				if ( in_array( $value, array( 'post_title', 'video' ), true ) ) {
					$label .= ' *'; // Add asterisk for required fields.
				}

				$html .= sprintf(
					'<option value="%s">%s</option>',
					esc_attr( $value ),
					esc_html( $label )
				);
			}

			$html .= '</select>';
			$html .= '</div>';
		}

		// Send successful JSON response
		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX callback to export videos to a CSV file.
	 *
	 * @since 4.5.2
	 */
	public function ajax_callback_export_csv() {
		@set_time_limit( 1200 );
		ignore_user_abort( true );

		check_ajax_referer( 'aiovg_ajax_nonce', 'security' ); // Verify the nonce for security

		if ( ! current_user_can( 'manage_aiovg_options' ) ) {
			wp_send_json_error( array( 'error' => esc_html__( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) ) );
		}

		// Sanitize and extract parameters
		$offset    = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;
		$limit     = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 200;
		$total     = isset( $_POST['total'] ) ? (int) $_POST['total'] : 0;
		$timestamp = isset( $_POST['timestamp'] ) ? sanitize_text_field( $_POST['timestamp'] ) : date( 'YmdHis' );
		$file_name = isset( $_POST['file_name'] ) ? sanitize_file_name( wp_unslash( $_POST['file_name'] ) ) : '';

		$csv_columns = $this->get_csv_column_mapping_fields();
		$csv_rows    = array();

		// Ensure export directory exists
		$upload_dir = wp_upload_dir();
		
		$upload_basedir = trailingslashit( $upload_dir['basedir'] );
		$upload_baseurl = trailingslashit( $upload_dir['baseurl'] );

		$export_dir = $upload_basedir . 'all-in-one-video-gallery/exports/';
		$export_url = $upload_baseurl . 'all-in-one-video-gallery/exports/';

		if ( ! file_exists( $export_dir ) ) {
			if ( ! wp_mkdir_p( $export_dir ) ) {
				wp_send_json_error( array( 'error' => __( 'Sorry, we were unable to create the export directory. Please check folder permissions and try again.', 'all-in-one-video-gallery' ) ) );
			}
		}		

		// Query videos
		$args = array(
			'post_type'      => 'aiovg_videos',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'orderby'        => 'ID',
			'order'          => 'ASC'
		);

		if ( $offset > 0 ) {
			$args['no_found_rows'] = true; // Only first batch needs total count
		}

		$aiovg_query = new WP_Query( $args );

		if ( ! $aiovg_query->have_posts() ) {
			wp_send_json_error( array( 'error' => __( 'Sorry, no videos were found to export.', 'all-in-one-video-gallery' ) ) );
		}

		// Get total count on first batch
		if ( $offset === 0 ) {
			$total = $aiovg_query->found_posts;
		}

		// Loop through videos and build CSV rows
		global $post;

		while ( $aiovg_query->have_posts() ) {
			$aiovg_query->the_post();

			$post_id   = get_the_ID();
			$post_meta = get_post_meta( $post_id );

			$row = array();

			foreach ( $csv_columns as $field => $column ) {
				switch ( $field ) {
					case 'post_id':
						$row[] = $post_id;
						break;
					case 'post_title':
						$row[] = $post->post_title;
						break;
					case 'post_content':
						$row[] = $post->post_content;
						break;
					case 'post_excerpt':
						$row[] = $post->post_excerpt;
						break;
					case 'aiovg_categories':
					case 'aiovg_tags':
						$terms = array();
						$terms_ids = wp_get_object_terms( get_the_ID(), $field, array( 'fields' => 'ids' ) );
						
						foreach ( $terms_ids as $term_id ) {
							$term_parents_list = get_term_parents_list( 
								$term_id, 
								$field, 
								array( 
									'separator' => '>', 
									'link'      => false 
								) 
							);

							$terms[] = rtrim( $term_parents_list, '>' );
						}

						$row[] = implode( ';', $terms );
						break;
					case 'video':
						$type = isset( $post_meta['type'] ) ? sanitize_text_field( $post_meta['type'][0] ) : 'default';
						
						if ( in_array( $type, array( 'youtube', 'vimeo', 'dailymotion', 'rumble', 'facebook' ) ) ) {
							$row[] = isset( $post_meta[ $type ] ) ? aiovg_sanitize_url( $post_meta[ $type ][0] ) : '';
						} elseif ( 'adaptive' == $type ) { 
							$src = isset( $post_meta['hls'] ) ? aiovg_sanitize_url( $post_meta['hls'][0] ) : '';
							if ( empty( $src ) ) {
								$src = isset( $post_meta['dash'] ) ? aiovg_sanitize_url( $post_meta['dash'][0] ) : '';
							}

							$row[] = $src;
						} elseif ( 'embedcode' == $type ) {
							try {
								add_filter( 'wp_kses_allowed_html', 'aiovg_allow_iframe_script_tags' );
								$row[] = isset( $post_meta['embedcode'] ) ? wp_kses_post( $post_meta['embedcode'][0] ) : '';
							} finally {
								remove_filter( 'wp_kses_allowed_html', 'aiovg_allow_iframe_script_tags' );
							}
						} else {
							$mp4     = isset( $post_meta['mp4'] ) ? aiovg_sanitize_url( $post_meta['mp4'][0] ) : '';
							$sources = isset( $post_meta['sources'] ) ? maybe_unserialize( $post_meta['sources'][0] ) : array();
							
							if ( empty( $sources ) ) {
								$row[] = wp_json_encode( array(
									'src'  => $mp4,
									'file' => $this->get_exported_filename( $mp4, $post_id )
								) );
							} else {
								$videos = array();

								$videos[] = array(
									'src'     => $mp4,
									'file'    => $this->get_exported_filename( $mp4, $post_id ),
									'quality' => ( isset( $post_meta['quality_level'] ) ? sanitize_text_field( $post_meta['quality_level'][0] ) : '' )
								);

								foreach ( $sources as $source ) {
									if ( ! empty( $source['src'] ) && ! empty( $source['quality'] ) ) {
										$src = aiovg_sanitize_url( $source['src'] );

										$videos[] = array(
											'src'     => $src,
											'file'    => $this->get_exported_filename( $src, $post_id ),
											'quality' => sanitize_text_field( $source['quality'] )
										);
									}
								}

								$row[] = wp_json_encode( $videos );
							}													
						}
						break;
					case 'image':
						$image     = isset( $post_meta['image'] ) ? aiovg_sanitize_url( $post_meta['image'][0] ) : '';
						$image_alt = isset( $post_meta['image_alt'] ) ? sanitize_text_field( $post_meta['image_alt'][0] ) : '';
							
						$row[] = wp_json_encode( array(
							'src'  => $image,
							'file' => $this->get_exported_filename( $image, $post_id ),
							'alt'  => $image_alt
						) );
						break;
					case 'track':
						$tracks = array();

						if ( ! empty( $post_meta['track'] ) ) {
							foreach ( $post_meta['track'] as $track ) {
								$track = maybe_unserialize( $track );

								if ( ! empty( $track['src'] ) ) {
									$src = aiovg_sanitize_url( $track['src'] );

									$tracks[] = array(
										'src'     => $src,
										'file'    => $this->get_exported_filename( $src, $post_id ),
										'label'   => isset( $track['label'] ) ? sanitize_text_field( $track['label'] ) : '',
										'srclang' => isset( $track['srclang'] ) ? sanitize_text_field( $track['srclang'] ) : ''
									);
								}
							}
						}

						$row[] = wp_json_encode( $tracks );
						break;
					case 'chapter':
						$chapters = array();

						if ( ! empty( $post_meta['chapter'] ) ) {
							foreach ( $post_meta['chapter'] as $chapter ) {
								$chapters[] = maybe_unserialize( $chapter );
							}
						}

						$row[] = wp_json_encode( $chapters );
						break;
					case 'duration':
						$row[] = isset( $post_meta['duration'] ) ? sanitize_text_field( $post_meta['duration'][0] ) : '';
						break;
					case 'views':
					case 'likes':
					case 'dislikes':
					case 'download':
					case 'featured':
						$row[] = isset( $post_meta[ $field ] ) ? (int) $post_meta[ $field ][0] : 0;
						break;
					case 'access_control':
						$row[] = isset( $post_meta['access_control'] ) ? (int) $post_meta['access_control'][0] : -1;
						break;
					case 'restricted_roles':
						$restricted_roles = isset( $post_meta['restricted_roles'] ) ? maybe_unserialize( $post_meta['restricted_roles'][0] ) : array();
						$restricted_roles = is_array( $restricted_roles ) ? array_map( 'sanitize_text_field', $restricted_roles ) : array();
						$row[] = implode( ';', $restricted_roles );
						break;
					case 'post_date':
						$row[] = get_the_date( 'Y-m-d H:i:s' );
						break;
					case 'post_status':
						$row[] = $post->post_status;
						break;
					case 'comment_status':
						$row[] = $post->comment_status;
						break;
					case 'post_author':
						$row[] = get_the_author_meta( 'user_email' );
						break;
					default:
						$row[] = isset( $post_meta[ $field ] ) ? sanitize_text_field( $post_meta[ $field ][0] ) : '';
				}
			}

			$csv_rows[] = $row;
		}

		wp_reset_postdata();

		// Calculate new offset
		$new_offset = $offset + count( $aiovg_query->posts );

		// Generate file name on first batch
		if ( empty( $file_name ) ) {
			$file_name = 'aiovg-videos-' . $timestamp . '.csv';
		}

		$file_path = trailingslashit( $export_dir ) . $file_name;
		$file_url  = trailingslashit( $export_url ) . $file_name;

		// Open file in append mode (or write mode if first batch)
		$handle = fopen( $file_path, $offset === 0 ? 'w' : 'a' );
		if ( false === $handle ) {
			wp_send_json_error( array( 'error' => __( 'Sorry, we were unable to open the CSV file for writing. Please check file permissions and try again.', 'all-in-one-video-gallery' ) ) );
		}

		// Write header row only for first batch
		if ( $offset === 0 ) {
			fputcsv( $handle, array_values( $csv_columns ) );
		}

		// Write CSV rows
		foreach ( $csv_rows as $row ) {
			fputcsv( $handle, $row );
		}

		fclose( $handle );

		// Batch processing
		if ( $new_offset < $total ) {
			$message = sprintf(
				__( '%d out of %d videos processed', 'all-in-one-video-gallery' ),
				$new_offset,
				$total
			);

			$response = array(
				'step'      => 'process',
				'message'   => $message,
				'offset'    => $new_offset,
				'total'     => $total,
				'file_name' => $file_name
			);

			wp_send_json_success( $response );
		}

		// Schedule cleanup
		$this->schedule_export_directory_cleanup();

		// Build the final response
		$message = sprintf(
			__( 'Your CSV export is complete. %s video records processed successfully. <a href="%s" target="_blank">Click here to download the CSV file</a>.', 'all-in-one-video-gallery' ),
			$new_offset,
			esc_url( $file_url )
		);

		$response = array(
			'step'      => 'done',
			'message'   => $message,
			'offset'    => $new_offset,
			'total'     => $total,
			'file_name' => $file_name
		);

		wp_send_json_success( $response ); // Send successful JSON response
	}

	/**
	 * AJAX callback to export videos to ZIP files.
	 *
	 * @since 4.5.2
	 */
	public function ajax_callback_export_zip() {
		@set_time_limit( 1200 );
		ignore_user_abort( true );

		check_ajax_referer( 'aiovg_ajax_nonce', 'security' ); // Verify the nonce for security

		if ( ! current_user_can( 'manage_aiovg_options' ) ) {
			wp_send_json_error( array( 'error' => esc_html__( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) ) );
		}

		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_send_json_error( array( 'error' => __( 'ZIP creation is not supported on your server. This feature requires the PHP "ZipArchive" class. Please ask your hosting provider to enable the PHP ZIP extension, or upgrade PHP to a version that includes it.', 'all-in-one-video-gallery' ) ) );		
		}

		// Sanitize and extract parameters
		$offset    = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;
		$limit     = isset( $_POST['limit'] ) ? (int) $_POST['limit'] : 50;
		$total     = isset( $_POST['total'] ) ? (int) $_POST['total'] : 0;
		$zip_index = isset( $_POST['zip_index'] ) ? (int) $_POST['zip_index'] : 1;
		$timestamp = isset( $_POST['timestamp'] ) ? sanitize_text_field( $_POST['timestamp'] ) : date( 'YmdHis' );
				
		$max_size  = apply_filters( 'aiovg_export_max_zip_size', 1024 * 1024 * 1024 ); // 1GB default
		$zip_files = array();
		$skipped   = array();
		
		// Ensure export directory exists
		$upload_dir = wp_upload_dir();
		
		$upload_basedir = trailingslashit( $upload_dir['basedir'] );
		$upload_baseurl = trailingslashit( $upload_dir['baseurl'] );

		$export_dir = $upload_basedir . 'all-in-one-video-gallery/exports/';
		$export_url = $upload_baseurl . 'all-in-one-video-gallery/exports/';

		if ( ! file_exists( $export_dir ) ) {
			if ( ! wp_mkdir_p( $export_dir ) ) {
				wp_send_json_error( array( 'error' => __( 'Sorry, we were unable to create the export directory. Please check folder permissions and try again.', 'all-in-one-video-gallery' ) ) );
			}
		}		

		// Query videos
		$args = array(
			'post_type'              => 'aiovg_videos',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'offset'                 => $offset,
			'fields'                 => 'ids',
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false
		);

		if ( $offset > 0 ) {
			$args['no_found_rows'] = true; // Only first batch needs total count
		}

		$aiovg_query = new WP_Query( $args );

		if ( ! $aiovg_query->have_posts() ) {
			wp_send_json_error( array( 'error' => __( 'Sorry, no videos were found to export. Please check your selection and try again.', 'all-in-one-video-gallery' ) ) );
		}

		// Get total count on first batch
		if ( $offset === 0 ) {
			$total = $aiovg_query->found_posts;
		}

		// Process posts and create ZIPs
		$added_count = 0;
		$added_size  = 0;

		$zip_name = 'aiovg-videos-' . $timestamp . '-' . $zip_index . '.zip';
		$zip_path = trailingslashit( $export_dir ) . $zip_name;
		
		$zip = new ZipArchive();

		if ( $zip->open( $zip_path, ZipArchive::CREATE ) !== TRUE ) {
			wp_send_json_error( array( 'error' => __( 'Sorry, we were unable to create the ZIP file. Please check folder permissions and try again.', 'all-in-one-video-gallery' ) ) );
		}

		foreach ( $aiovg_query->posts as $post_id ) {
			$files = array();

			// Get main video file(s)
			$type = get_post_meta( $post_id, 'type', true );
			if ( 'default' == $type ) {
				$mp4 = get_post_meta( $post_id, 'mp4', true );	
				if ( ! empty( $mp4 ) && $this->is_file_local( $mp4 ) ) {
					$files[] = $mp4;
				}

				$sources = maybe_unserialize( get_post_meta( $post_id, 'sources', true ) );
				if ( ! empty( $sources ) && is_array( $sources ) ) {
					foreach ( $sources as $source ) {
						if ( ! empty( $source['src'] ) && $this->is_file_local( $source['src'] ) ) {
							$files[] = $source['src'];
						}
					}
				}
			}

			// Get image file
			$image = get_post_meta( $post_id, 'image', true );
			if ( ! empty( $image ) && $this->is_file_local( $image ) ) {
				$files[] = $image;
			}

			// Get subtitle files
			$tracks = get_post_meta( $post_id, 'track' );
			if ( ! empty( $tracks ) && is_array( $tracks ) ) {
				foreach ( $tracks as $track ) {
					$track = maybe_unserialize( $track );

					if ( ! empty( $track['src'] ) && $this->is_file_local( $track['src'] ) ) {
						$files[] = $track['src'];
					}
				}
			}

			// No files found for this video
			if ( empty( $files ) ) {
				continue;
			}

			// Move subtitle, image files to the top of the list and video files to the bottom for efficient ZIP compression.
			// Remove duplicate entries just in case.
			$files = array_reverse( array_unique( $files ) );

			// Process each file
			foreach ( $files as $file_url ) {
				// Convert URL to absolute path				
				if ( substr( $file_url, 0, strlen( $upload_baseurl ) ) === $upload_baseurl ) {
					$file_path = $upload_basedir . substr( $file_url, strlen( $upload_baseurl ) );
				} else {
					$file_path = ABSPATH . str_replace( site_url( '/' ), '', $file_url );
				}

				if ( ! file_exists( $file_path ) ) {
					continue;
				}

				$file_size = filesize( $file_path );

				// Skip single files larger than max size
				if ( $file_size > $max_size ) {
					$skipped[] = $file_url;
					continue;
				}

				// If adding this file exceeds max ZIP size, close current ZIP and start a new one
				if ( ( $added_size + $file_size ) > $max_size ) {
					$zip->close();
					$zip_files[] = trailingslashit( $export_url ) . $zip_name;

					// Schedule cleanup
					$this->schedule_export_directory_cleanup();

					// Start new ZIP
					$zip_index++;

					$added_size = 0;

					$zip_name = 'aiovg-videos-' . $timestamp . '-' . $zip_index . '.zip';
					$zip_path = trailingslashit( $export_dir ) . $zip_name;
					
					$zip = new ZipArchive();

					if ( $zip->open( $zip_path, ZipArchive::CREATE ) !== TRUE ) {
						wp_send_json_error( array( 'error' => __( 'Sorry, we were unable to create the ZIP file. Please check folder permissions and try again.', 'all-in-one-video-gallery' ) ) );
					}				
				}

				$zip->addFile( $file_path, $this->get_exported_filename( $file_path, $post_id, true ) );

				$added_size += $file_size;
				$added_count++;
			}
		}

		// Close last ZIP if files were added
		if ( $added_count > 0 ) {
			$zip->close();
			$zip_files[] = trailingslashit( $export_url ) . $zip_name;

			// Schedule cleanup
			$this->schedule_export_directory_cleanup();
		}

		wp_reset_postdata();

		// Calculate new offset
		$new_offset = $offset + count( $aiovg_query->posts );

		// Batch processing
		if ( $new_offset < $total ) {
			$message = sprintf(
				__( '%d out of %d videos processed', 'all-in-one-video-gallery' ),
				$new_offset,
				$total
			);

			$response = array(
				'step'      => 'process',
				'message'   => $message,
				'offset'    => $new_offset,
				'total'     => $total,
				'zip_index' => $zip_index,
				'zip_files' => $zip_files,
				'skipped'   => $skipped
			);

			wp_send_json_success( $response );
		}

		// Build the final response
		$message = sprintf(
			__( 'Your ZIP export is complete. %s video records processed, and %s skipped.', 'all-in-one-video-gallery' ),
			'%%exported%%',
			'%%skipped%%'
		);

		$response = array(
			'step'      => 'done',
			'message'   => $message,
			'offset'    => $new_offset,
			'total'     => $total,
			'zip_index' => $zip_index,
			'zip_files' => $zip_files,
			'skipped'   => $skipped
		);

		wp_send_json_success( $response ); // Send successful JSON response
	}

	/**
	 * Hooked cleanup function to delete all files inside the export directory.
	 *
	 * @since 4.5.2
	 */
	public function cleanup_export_directory() {
		// Ensure WP_Filesystem is available
		if ( ! isset( $GLOBALS['wp_filesystem'] ) || ! is_object( $GLOBALS['wp_filesystem'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		global $wp_filesystem;

		// Path to export directory
		$uploads    = wp_upload_dir();
		$export_dir = trailingslashit( $uploads['basedir'] ) . 'all-in-one-video-gallery/exports/';

		if ( $wp_filesystem->is_dir( $export_dir ) ) {
			// Get all items (files and subdirectories) inside the folder
			$items = $wp_filesystem->dirlist( $export_dir );

			foreach ( $items as $item ) {
				$path = trailingslashit( $export_dir ) . $item['name'];
				// Recursive delete: removes files and subfolders
				$wp_filesystem->delete( $path, true );
			}
		}
	}	

	/**
	 * Parse CSV data with mapping, offset, and limit support.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  string         $csv_file   Path or attachment URL of the CSV file.
	 * @param  array          $attributes Import settings.
	 * @return array|WP_Error             Parsed CSV data.
	 */
	private function parse_csv( $csv_file, $attributes = array() ) {
		if ( empty( $csv_file ) ) {
			return new WP_Error( 'invalid_csv', __( 'Sorry, the selected file is not a valid CSV. Please upload a valid CSV file.', 'all-in-one-video-gallery' ) );
		}

		// Default arguments.
		$attributes = wp_parse_args( $attributes, array(
			'columns_separator' => ',',
			'csv_columns'       => array(),
			'headers_only'      => false,
			'offset'            => 0,
			'limit'             => 0
		) );

		$response = array(
			'headers'    => array(),
			'videos'     => array(),
			'has_more'   => false,
			'new_offset' => $attributes['offset']
		);

		$csv_row_number = 0;

		// Resolve CSV file path.
		$attachment_id  = attachment_url_to_postid( $csv_file );
		$csv_file_path  = $attachment_id ? get_attached_file( $attachment_id ) : '';

		if ( ! $csv_file_path || ! file_exists( $csv_file_path ) ) {
			return new WP_Error( 'invalid_csv', __( 'Sorry, the selected file is not a valid CSV. Please upload a valid CSV file.', 'all-in-one-video-gallery' ) );
		}

		if ( ! is_readable( $csv_file_path ) ) {
			@chmod( $csv_file_path, 0744 );
			if ( ! is_readable( $csv_file_path ) ) {
				return new WP_Error( 'csv_not_readable', __( 'Sorry, we could not read the selected CSV file. Please check the file and try again.', 'all-in-one-video-gallery' ) );
			}
		}

		if ( function_exists( 'wp_check_filetype' ) ) {
			$filetype = wp_check_filetype( $csv_file_path );
			if ( empty( $filetype['ext'] ) || 'csv' !== strtolower( $filetype['ext'] ) ) {
				return new WP_Error( 'invalid_file_type', __( 'Sorry, this file type is not supported. Please upload a valid CSV file.', 'all-in-one-video-gallery' ) );
			}
		}

		if ( false === ( $handle = fopen( $csv_file_path, 'r' ) ) ) {
			return new WP_Error( 'csv_open_failed', __( 'Sorry, we were unable to open the selected CSV file. Please check the file and try again.', 'all-in-one-video-gallery' ) );
		}

		// Get headers
		$headers = fgetcsv( $handle, 0, $attributes['columns_separator'] );
		$csv_row_number++;

		if ( empty( $headers ) || ! is_array( $headers ) ) {
			fclose( $handle );
			return new WP_Error( 'invalid_csv_headers', __( 'Sorry, the CSV file does not contain the required column headers. Please check the file and try again.', 'all-in-one-video-gallery' ) );
		}

		$response['headers'] = array_map( 'trim', $headers );

		if ( true === $attributes['headers_only'] ) {
			fclose( $handle );
			return $response;
		}

		// Skip until offset
		for ( $i = 0; $i < $attributes['offset']; $i++ ) {
			if ( feof( $handle ) ) break;

			fgetcsv( $handle, 0, $attributes['columns_separator'] );
			$csv_row_number++;
		}

		// Read CSV rows
		while ( ! feof( $handle ) ) {
			if ( $attributes['limit'] > 0 && count( $response['videos'] ) >= $attributes['limit'] ) {
				// Reached limit, stop reading further rows
				break;
			}

			$row = fgetcsv( $handle, 0, $attributes['columns_separator'] );
			$csv_row_number++;

			if ( $row === false ) {
				if ( ! feof( $handle ) ) {
					// Malformed row
					$response['videos'][] = array(
						'number' => $csv_row_number,
						'error'  => __( 'Malformed CSV row', 'all-in-one-video-gallery' )
					);

					continue;
				} else {
					break;
				}
			}

			$post = array( 'number' => $csv_row_number );

			// Include empty rows
			if ( ! array_filter( $row, 'strlen' ) ) {
				$response['videos'][] = $post;
				continue;
			}

			// Map columns
			foreach ( $attributes['csv_columns'] as $i => $field ) {
				if ( ! empty( $field ) && isset( $row[ $i ] ) ) {
					$value = trim( $row[ $i ] );

					$decoded = json_decode( $value, true );
					if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
						$value = $decoded;
					} else {
						$value = htmlspecialchars_decode( $value );
					}

					$post[ $field ] = $value;
				}
			}

			$post = apply_filters( 'aiovg_parse_csv_row', $post, $attributes );
			$response['videos'][] = $post;
		}

		// Peek one extra row to set has_more if needed
		if ( ! feof( $handle ) && $attributes['limit'] > 0 ) {
			while ( ( $next_row = fgetcsv( $handle, 0, $attributes['columns_separator'] ) ) !== false ) {
				if ( array_filter( $next_row, 'strlen' ) ) {
					$response['has_more'] = true;
					break;
				}
			}
		}

		fclose( $handle );

		// Calculate next offset for batch import
		$response['new_offset'] = $csv_row_number - 1;

		return $response;
	}

	/**
	 * Resolve import directory from a ZIP file or a folder path.
	 * 
	 * @since  4.5.2
	 * @access private
	 * @param  string          $zip_file Path or attachment URL of the ZIP file, or folder path.
	 * @return string|WP_Error           Extracted directory path or validated folder path, or WP_Error on failure.
	 */
	private function resolve_import_directory( $zip_file = '' ) {
		if ( empty( $zip_file ) ) {
			return new WP_Error( 'invalid_zip', __( 'Sorry, no ZIP file or folder path was provided. Please select a ZIP file or enter a folder path to continue.', 'all-in-one-video-gallery' ) );
		}

		// CASE 1: Folder path
		$folder_path = $this->resolve_folder_path( $zip_file );

		if ( ! is_wp_error( $folder_path ) ) {
			// Valid folder
			return $folder_path;
		}

		// If folder validation failed with 'invalid_folder', treat it as ZIP
		if ( $folder_path->get_error_code() !== 'invalid_folder' ) {
			// Any other error (e.g., permissions, invalid location) — stop
			return $folder_path;
		}

		// CASE 2: ZIP attachment
		$attachment_id = attachment_url_to_postid( $zip_file );
		$zip_file_path = $attachment_id ? get_attached_file( $attachment_id ) : '';

		if ( empty( $zip_file_path ) || ! is_file( $zip_file_path ) ) {
			return new WP_Error( 'invalid_zip', __( 'Sorry, the selected ZIP file could not be found or read. Please check the file and try again.', 'all-in-one-video-gallery' ) );
		}

		// Ensure WP_Filesystem is available
		if ( ! isset( $GLOBALS['wp_filesystem'] ) || ! is_object( $GLOBALS['wp_filesystem'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$uploads       = wp_upload_dir();
		$zip_file_name = basename( $zip_file_path );
		$folder_name   = substr( $zip_file_name, 0, strrpos( $zip_file_name, '.' ) );
		$default_path  = trailingslashit( $uploads['basedir'] ) . 'all-in-one-video-gallery/imports/' . sanitize_file_name( $folder_name );

		$extract_path = apply_filters( 'aiovg_import_directory_path', $default_path, array(	'zip_file' => $zip_file	) );

		if ( ! wp_mkdir_p( $extract_path ) ) {
			return new WP_Error( 'mkdir_failed', __( 'Sorry, we were unable to create the import directory. Please check your folder permissions and try again.', 'all-in-one-video-gallery' ) );
		}

		// Create protection files safely inside the extract path
		$htaccess_file   = trailingslashit( $extract_path ) . '.htaccess';
		$webconfig_file  = trailingslashit( $extract_path ) . 'web.config';
		$htaccess_rules  = "Deny from all\n";
		$webconfig_rules = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
			. "<configuration>\n"
			. "\t<system.webServer>\n"
			. "\t\t<authorization>\n"
			. "\t\t\t<deny users=\"*\" />\n"
			. "\t\t</authorization>\n"
			. "\t</system.webServer>\n"
			. "</configuration>";

		if ( false === @file_put_contents( $htaccess_file, $htaccess_rules ) ) {
			return new WP_Error( 'file_write_failed', __( 'We were unable to complete a required file operation. Please make sure your server allows writing files.', 'all-in-one-video-gallery' ) );
		}

		if ( false === @file_put_contents( $webconfig_file, $webconfig_rules ) ) {
			return new WP_Error( 'file_write_failed', __( 'We were unable to complete a required file operation. Please make sure your server allows writing files.', 'all-in-one-video-gallery' ) );
		}

		// Unzip
		$unzip_result = unzip_file( $zip_file_path, $extract_path );
		
		if ( is_wp_error( $unzip_result ) ) {
			// Remove protection files before returning
			@unlink( $htaccess_file );
			@unlink( $webconfig_file );

			return $unzip_result;
		}

		// Keep only files allowed by WordPress MIME validation
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $extract_path, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				$filetype = wp_check_filetype( $file->getFilename() );
				if ( empty( $filetype['type'] ) ) {
					@unlink( $file->getPathname() );
				}
			}
		}

		// Security cleanup
		@unlink( $htaccess_file );
		@unlink( $webconfig_file );

		return untrailingslashit( $extract_path );
	}

	/**
	 * Validate a folder path inside the WordPress directory.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  string          $folder The folder path to validate (relative or absolute).
	 * @return string|WP_Error         Validated and normalized absolute folder path, or WP_Error on failure.
	 */
	private function resolve_folder_path( $folder ) {
		if ( empty( $folder ) ) {
			return new WP_Error( 'invalid_folder', __( 'Sorry, the folder path provided is invalid. Please check the path and try again.', 'all-in-one-video-gallery' ) );
		}

		// Resolve the absolute path
		$folder_path = realpath( trailingslashit( ABSPATH ) . ltrim( $folder, '/' ) );

		if ( ! $folder_path || ! is_dir( $folder_path ) ) {
			return new WP_Error( 'invalid_folder', __( 'Sorry, the folder path provided is invalid. Please check the path and try again.', 'all-in-one-video-gallery' ) );
		}

		// Normalize and remove trailing slash
		$folder_path = untrailingslashit( wp_normalize_path( $folder_path ) );

		// Ensure folder is inside the WordPress directory
		if ( strpos( $folder_path, wp_normalize_path( ABSPATH ) ) !== 0 ) {
			return new WP_Error( 'invalid_folder_location', __( 'Sorry, the folder must be located inside your WordPress directory. Please choose a valid folder and try again.', 'all-in-one-video-gallery' ) );
		}

		return $folder_path;
	}

	/**
	 * Import videos from folder.
	 * 
	 * @since  4.5.2
	 * @access private
	 * @param  array   $videos     Array of videos to import.
	 * @param  array   $attributes Import settings.
	 * @return array   $response   Array containing import results.
     */
	private function import_videos_from_folder( $videos, $attributes = array() ) {
		$response = array(
			'total'    => count( $videos ),
			'imported' => array(),
			'skipped'  => array(),
			'failed'   => array()
		);

		if ( empty( $videos ) ) {
			return $response;
		}

		// Pre import
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		
		// Import videos
		foreach ( $videos as $video ) {
			// Check if the video post already exists			
			$post_id   = $this->is_video_exists( $video['src'] );
			$video_src = aiovg_sanitize_url( $video['src'] );

			if ( ! empty( $post_id ) ) {
				$response['skipped'][] = $video_src;
				continue;
			}
			
			// Insert post
			$args = array(
				'post_type'      => 'aiovg_videos',
				'post_title'     => wp_strip_all_tags( $video['title'] ),
				'post_status'    => 'publish',
				'comment_status' => $attributes['comment_status']					
			);

			if ( 'random' === $attributes['slug_strategy'] ) {
				$args['post_name'] = $this->generate_random_video_slug( $video_src );
			}

			$post_id = wp_insert_post( $args );

			// Insert post meta
			if ( ! is_wp_error( $post_id ) ) {
				// Categories
				if ( ! empty( $attributes['categories'] ) ) {
					wp_set_object_terms( $post_id, $attributes['categories'], 'aiovg_categories' );
				}
				
				// Tags
				if ( ! empty( $attributes['tags'] ) ) {
					wp_set_object_terms( $post_id, $attributes['tags'], 'aiovg_tags' );
				}

				// Meta
				$meta = array(
					'type'             => 'default',
					'mp4'              => $video_src,
					'duration'         => '',
					'featured'         => 0,
					'views'            => 0,
					'likes'			   => 0,
					'dislikes'         => 0,
					'download'         => $attributes['enable_downloads'],
					'access_control'   => $attributes['access_control'],
					'restricted_roles' => $attributes['restricted_roles'],
					'import_id'        => $attributes['import_id']
				);

				// Image
				if ( ! empty( $video['image'] ) ) {
					$meta['image'] = aiovg_sanitize_url( $video['image'] );

					if ( ! empty( $attributes['set_featured_image'] ) && ! empty( $video['image_path'] ) ) {
						$image_id = $this->create_attachment( $post_id, sanitize_text_field( $video['image_path'] ), $meta['image'] );

						if ( ! empty( $image_id ) ) {
							$meta['image_id'] = $image_id;
							set_post_thumbnail( $post_id, $image_id ); 
						}
					}	
				}			

				// Insert post meta in bulk
				$this->add_post_meta_bulk( $post_id, $meta );
				$response['imported'][] = $video_src;

				// Hook for developers
				do_action( 'aiovg_video_imported', $post_id, $attributes['import_id'] );				
			} else {
				$response['failed'][] = $video_src;
			}
		}

		// Post Import
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
		
		return $response;
	}

	/**
	 * Import videos from CSV.
	 * 
	 * @since  4.5.2
	 * @access private
	 * @param  array   $videos     Array of videos to import.
	 * @param  array   $attributes Import settings.
	 * @return array   $response   Array containing import results.
     */
	private function import_videos_from_csv( $videos, $attributes = array() ) {
		$response = array(
			'total'    => count( $videos ),
			'imported' => array(),
			'updated'  => array(),
			'skipped'  => array(),
			'failed'   => array(),
			'errors'   => array()
		);

		if ( empty( $videos ) ) {
			return $response;
		}

		// Pre import
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		
		// Import videos
		foreach ( $videos as $row ) {	
			$csv_row_number = (int) $row['number'];

			if ( isset( $row['error'] ) && ! empty( $row['error'] ) ) {
				$response['failed'][] = $csv_row_number;

				$response['errors'][] = sprintf(
					__( '<strong>Line #%d:</strong> %s', 'all-in-one-video-gallery' ),
					$csv_row_number,
					esc_html( $row['error'] )
				);

				continue;
			}

			if ( empty( $row['post_title'] ) || empty( $row['video'] ) ) {
				$response['failed'][] = $csv_row_number;

				$response['errors'][] = sprintf(
					__( '<strong>Line #%d:</strong> Invalid video title or source.', 'all-in-one-video-gallery' ),
					$csv_row_number
				);

				continue;
			}

			// Parse video data
			$post_title = wp_strip_all_tags( $row['post_title'] );

			$video   = $row['video'];
			$sources = array();			

			if ( is_array( $video ) ) {
				// Multiple videos array
				if ( ! isset( $video['src'] ) ) {
					$sources = $video;
					$video   = array_shift( $sources );
				}							
			} else {	
				$video = array( 'src' => $video );			
			}

			if ( empty( $video['src'] ) ) {
				$response['failed'][] = $csv_row_number;

				$response['errors'][] = sprintf(
					__( '<strong>Line #%d:</strong> %s - Invalid video source.', 'all-in-one-video-gallery' ),
					$csv_row_number,
					$post_title
				);

				continue;
			}	

			// $video['src'] is a simple filename (e.g. video.mp4)
			if ( ! empty( $attributes['folder'] ) && preg_match( '/^[a-zA-Z0-9_\-]+\.[a-zA-Z0-9]+$/i', $video['src'] ) ) {				
				$local_file_path = $attributes['folder'] . '/' . sanitize_file_name( $video['src'] );
				if ( @file_exists( $local_file_path ) ) {
					$video['src'] = $this->make_absolute_url( $local_file_path );
				}
			}

			// Check if the video post already exists
			$type    = $this->resolve_video_type( $video['src'] );
			$post_id = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;

			if ( $post_id > 0 ) {
				unset( $row['post_id'] );

				$post = get_post( $post_id );
				if ( $post && 'aiovg_videos' === $post->post_type ) {
					$row['post_id'] = $post_id;
				}
			}

			if ( ! isset( $row['post_id'] ) ) {
				$post_id = $this->is_video_exists( $video['src'], $type );

				if ( ! empty( $post_id ) ) {
					$response['skipped'][] = $csv_row_number;

					$response['errors'][] = sprintf(
						__( '<strong>Line #%d:</strong> %s - Video already exists.', 'all-in-one-video-gallery' ),
						$csv_row_number,
						$post_title
					);
					
					continue;
				}	
			}

			// Insert / Update post
			$is_existing_post = false;

			$args = array(
				'post_type'  => 'aiovg_videos',
				'post_title' => $post_title				
			);

			if ( isset( $row['post_content'] ) && ! empty( $row['post_content'] ) ) {
				$args['post_content'] = wp_kses_post( $row['post_content'] );
			}

			if ( isset( $row['post_excerpt'] ) && ! empty( $row['post_excerpt'] ) ) {
				$args['post_excerpt'] = wp_kses_post( $row['post_excerpt'] );
			}

			if ( isset( $row['post_date'] ) && ! empty( $row['post_date'] ) ) {
				$args['post_date'] = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $row['post_date'] ) ) );
			}

			if ( isset( $row['post_status'] ) && ! empty( $row['post_status'] ) ) {
				$args['post_status'] = sanitize_text_field( $row['post_status'] );
			}			

			if ( isset( $row['post_author'] ) && ! empty( $row['post_author'] ) ) {
				$post_author = $this->resolve_user_id( $row['post_author'], $attributes );
				
				if ( ! is_wp_error( $post_author ) ) {
					$args['post_author'] = (int) $post_author;
				} else {
					$response['errors'][] = sprintf(
						__( '<strong>Line #%d:</strong> %s - %s', 'all-in-one-video-gallery' ),
						$csv_row_number,
						$post_title,
						$post_author->get_error_message()
					);
				}
			}

			if ( isset( $row['comment_status'] ) && 'open' === strtolower( $row['comment_status'] ) ) {
				$args['comment_status'] = 'open';
			}

			if ( isset( $row['post_id'] ) ) {
				// Update the existing video post
				$post_id = $row['post_id'];

				$args['ID'] = $row['post_id'];
				wp_update_post( $args );

				$is_existing_post = true;
			} else {
				if ( ! isset( $row['post_status'] ) || empty( $row['post_status'] ) ) {
					$args['post_status'] = 'publish';
				}

				if ( ! isset( $row['comment_status'] ) || empty( $row['comment_status'] ) ) {
					$args['comment_status'] = $attributes['comment_status'];
				}

				$post_id = wp_insert_post( $args );
			}

			// Insert post meta
			if ( ! is_wp_error( $post_id ) ) {
				// Categories
				if ( isset( $row['aiovg_categories'] ) ) {
					wp_set_object_terms( $post_id, null, 'aiovg_categories' );

					if ( ! empty( $row['aiovg_categories'] ) ) {
						$categories = $this->resolve_term_ids( $row['aiovg_categories'], 'aiovg_categories', $attributes );				
						
						if ( ! is_wp_error( $categories ) ) {
							if ( ! empty( $categories ) ) {
								wp_set_object_terms( $post_id, $categories, 'aiovg_categories' );
							}
						} else {
							$response['errors'][] = sprintf(
								__( '<strong>Line #%d:</strong> %s - %s', 'all-in-one-video-gallery' ),
								$csv_row_number,
								$post_title,
								$categories->get_error_message()
							);
						}
					}
				}
				
				// Tags
				if ( isset( $row['aiovg_tags'] ) ) {
					wp_set_object_terms( $post_id, null, 'aiovg_tags' );

					if ( ! empty( $row['aiovg_tags'] ) ) {
						$tags = $this->resolve_term_ids( $row['aiovg_tags'], 'aiovg_tags', $attributes );				
						
						if ( ! is_wp_error( $tags ) ) {
							if ( ! empty( $tags ) ) {
								wp_set_object_terms( $post_id, $tags, 'aiovg_tags' );
							}
						} else {
							$response['errors'][] = sprintf(
								__( '<strong>Line #%d:</strong> %s - %s', 'all-in-one-video-gallery' ),
								$csv_row_number,
								$post_title,
								$tags->get_error_message()
							);
						}
					}
				}

				// Subtitles
				if ( isset( $row['track'] ) ) {
					delete_post_meta( $post_id, 'track' );

					if ( is_array( $row['track'] ) ) {
						foreach ( $row['track'] as $source ) {
							if ( ! empty( $source['src'] ) ) {
								$src = $source['src'];

								if ( ! empty( $attributes['folder'] ) && preg_match( '/^[a-zA-Z0-9_\-]+\.[a-zA-Z0-9]+$/i', $src ) ) {
									// $src is a simple filename (e.g. captions.vtt)
									$local_file_path = $attributes['folder'] . '/' . sanitize_file_name( $src );
									if ( @file_exists( $local_file_path ) ) {
										$src = $this->make_absolute_url( $local_file_path );
									}
								}

								if ( ! empty( $attributes['folder'] ) && isset( $source['file'] ) && ! empty( $source['file'] ) ) {
									$local_file_path = $attributes['folder'] . '/' . sanitize_file_name( $source['file'] );
									if ( @file_exists( $local_file_path ) ) {
										$src = $this->make_absolute_url( $local_file_path );
									}
								}

								$track = array(
									'src'     => aiovg_sanitize_url( $src ),
									'label'   => '',
									'srclang' => ''
								);

								if ( isset( $source['label'] ) && ! empty( $source['label'] ) ) {
									$track['label'] = sanitize_text_field( $source['label'] );
								}

								if ( isset( $source['srclang'] ) && ! empty( $source['srclang'] ) ) {
									$track['srclang'] = sanitize_text_field( $source['srclang'] );
								}

								add_post_meta( $post_id, 'track', $track );
							}
						}
					}
				}

				// Chapters
				if ( isset( $row['chapter'] ) ) {
					delete_post_meta( $post_id, 'chapter' );

					if ( is_array( $row['chapter'] ) ) {
						foreach ( $row['chapter'] as $chapter ) {
							if ( ! empty( $chapter['time'] ) && ! empty( $chapter['label'] ) ) {
								$chapter = array(
									'time'  => sanitize_text_field( $chapter['time'] ),
									'label' => sanitize_text_field( $chapter['label'] )
								);

								add_post_meta( $post_id, 'chapter', $chapter );
							}
						}					
					}
				}

				// Import ID
				$import_id = sanitize_text_field( $attributes['import_id'] );

				if ( ! metadata_exists( 'post', $post_id, 'import_id' ) ) {
					add_post_meta( $post_id, 'import_id', $import_id );
				} else {
					// Meta exists — check if same value
					$existing = get_post_meta( $post_id, 'import_id' );

					if ( ! in_array( $import_id, $existing ) ) {
						add_post_meta( $post_id, 'import_id', $import_id );
					}
				}

				// Meta
				$meta = array();

				// Type
				$meta['type'] = $type;

				// Video
				if ( in_array( $type, array( 'youtube', 'vimeo', 'dailymotion', 'rumble', 'facebook' ) ) ) {
					$meta[ $type ] = aiovg_sanitize_url( $video['src'] );
				} elseif ( in_array( $type, array( 'hls', 'dash' ) ) ) {
					$meta['type']  = 'adaptive';
					$meta[ $type ] = aiovg_sanitize_url( $video['src'] );
				} elseif ( 'embedcode' == $type ) {
					try {
						add_filter( 'wp_kses_allowed_html', 'aiovg_allow_iframe_script_tags' );
						$meta[ $type ] = wp_kses_post( $video['src'] );
					} finally {
						remove_filter( 'wp_kses_allowed_html', 'aiovg_allow_iframe_script_tags' );
					}
				} else {
					$src = $video['src'];

					if ( ! empty( $attributes['folder'] ) && isset( $video['file'] ) && ! empty( $video['file'] ) ) {
						$local_file_path = $attributes['folder'] . '/' . sanitize_file_name( $video['file'] );
						if ( @file_exists( $local_file_path ) ) {
							$src = $this->make_absolute_url( $local_file_path );
						}
					}

					$meta['mp4'] = aiovg_sanitize_url( $src );

					// Quality level
					if ( isset( $video['quality'] ) && ! empty( $video['quality'] ) ) {
						$meta['quality_level'] = sanitize_text_field( $video['quality'] );
					}
				}

				// Sources
				$videos = array();

				foreach ( $sources as $source ) {
					if ( ! empty( $source['src'] ) && ! empty( $source['quality'] ) ) {
						$src = $source['src'];

						if ( ! empty( $attributes['folder'] ) && preg_match( '/^[a-zA-Z0-9_\-]+\.[a-zA-Z0-9]+$/i', $src ) ) {
							// $src is a simple filename (e.g. video.mp4)
							$local_file_path = $attributes['folder'] . '/' . sanitize_file_name( $src );
							if ( @file_exists( $local_file_path ) ) {
								$src = $this->make_absolute_url( $local_file_path );
							}
						}

						if ( ! empty( $attributes['folder'] ) && isset( $source['file'] ) && ! empty( $source['file'] ) ) {
							$local_file_path = $attributes['folder'] . '/' . sanitize_file_name( $source['file'] );
							if ( @file_exists( $local_file_path ) ) {
								$src = $this->make_absolute_url( $local_file_path );
							}
						}

						$videos[] = array(
							'src'     => aiovg_sanitize_url( $src ),
							'quality' => sanitize_text_field( $source['quality'] )
						);
					}
				}

				$meta['sources'] = $videos;

				// Parse image data
				$image      = '';
				$image_path = '';
				$image_id   = 0;
				$image_alt  = '';

				if ( isset( $row['image'] ) ) {
					if ( is_array( $row['image'] ) ) {
						if ( isset( $row['image']['src'] ) && ! empty( $row['image']['src'] ) ) {
							$image = $row['image']['src'];
						}

						if ( ! empty( $attributes['folder'] ) && isset( $row['image']['file'] ) && ! empty( $row['image']['file'] ) ) {
							$local_file_path = $attributes['folder'] . '/' . sanitize_file_name( $row['image']['file'] );
							if ( @file_exists( $local_file_path ) ) {
								$image      = $this->make_absolute_url( $local_file_path );
								$image_path = $local_file_path;
							}
						}

						if ( isset( $row['image']['alt'] ) && ! empty( $row['image']['alt'] ) ) {
							$image_alt = $row['image']['alt'];
						}
					} else {
						$image = $row['image'];
					}

					// $image is a simple filename (e.g. image.jpg)
					if ( ! empty( $image ) && ! empty( $attributes['folder'] ) && preg_match( '/^[a-zA-Z0-9_\-]+\.[a-zA-Z0-9]+$/i', $image ) ) {
						$local_file_path = $attributes['folder'] . '/' . sanitize_file_name( $image );
						if ( @file_exists( $local_file_path ) ) {
							$image      = $this->make_absolute_url( $local_file_path );
							$image_path = $local_file_path;
						}
					}
				}

				$meta['image']     = ! empty( $image ) ? aiovg_sanitize_url( $image ) : '';
				$meta['image_alt'] = ! empty( $image_alt ) ? sanitize_text_field( $image_alt ) : '';

				// Featured Image
				if ( ! empty( $attributes['set_featured_image'] ) ) {
					if ( ! empty( $meta['image'] ) ) {
						if ( ! empty( $image_path ) ) {
							$image_id = $this->create_attachment( $post_id, $image_path, $meta['image'] );
						} else {
							$image_id = aiovg_create_attachment_from_external_image_url( $meta['image'], $post_id );
						}
					}

					if ( ! empty( $image_id ) ) {
						$meta['image_id'] = $image_id;
						set_post_thumbnail( $post_id, $image_id ); 
					}
				}

				// Duration
				if ( isset( $row['duration'] ) ) {
					$meta['duration'] = '';

					if ( ! empty( $row['duration'] ) ) {
						$meta['duration'] = sanitize_text_field( $row['duration'] );
					}
				}

				// Views
				if ( isset( $row['views'] ) ) {
					$meta['views'] = 0;

					if ( ! empty( $row['views'] ) ) {
						$meta['views'] = (int) $row['views'];
					}
				}

				// Likes
				if ( isset( $row['likes'] ) ) {
					$meta['likes'] = 0;

					if ( ! empty( $row['likes'] ) ) {
						$meta['likes'] = (int) $row['likes'];
					}
				}

				// Dislikes
				if ( isset( $row['dislikes'] ) ) {
					$meta['dislikes'] = 0;

					if ( ! empty( $row['dislikes'] ) ) {
						$meta['dislikes'] = (int) $row['dislikes'];
					}
				}

				// Download
				if ( isset( $row['download'] ) ) {
					$meta['download'] = 0;

					if ( ! empty( $row['download'] ) ) {
						$meta['download'] = (int) $row['download'];
					}
				}

				// Featured
				if ( isset( $row['featured'] ) ) {
					$meta['featured'] = 0;

					if ( ! empty( $row['featured'] ) ) {
						$meta['featured'] = (int) $row['featured'];
					}
				}

				// Access Control
				if ( isset( $row['access_control'] ) ) {
					$meta['access_control'] = -1;

					if ( ! empty( $row['access_control'] ) ) {
						$meta['access_control'] = (int) $row['access_control'];
					}
				}

				// Restricted Roles
				if ( isset( $row['restricted_roles'] ) ) {
					$meta['restricted_roles'] = array();

					if ( ! empty( $row['restricted_roles'] ) ) {
						$separator = ! empty( $attributes['values_separator'] ) ? $attributes['values_separator'] : ';';
						$restricted_roles = array_filter( array_map( 'trim', explode( $separator, $row['restricted_roles'] ) ) );
						$meta['restricted_roles'] = array_map( 'sanitize_text_field', $restricted_roles );
					}
				}

				// Insert / Update post meta in bulk
				if ( $is_existing_post ) {
					$this->update_post_meta_bulk( $post_id, $meta );
					$response['updated'][] = $csv_row_number;
				} else {
					$this->add_post_meta_bulk( $post_id, $meta );
					$response['imported'][] = $csv_row_number;
				}

				// Hook for developers
				do_action( 'aiovg_video_imported', $post_id, $attributes['import_id'] );					
			} else {
				$response['failed'][] = $csv_row_number;

				$response['errors'][] = sprintf(
					__( '<strong>Line #%d:</strong> %s - %s', 'all-in-one-video-gallery' ),
					$csv_row_number,
					$post_title,
					$post_id->get_error_message()
				);
			}
		}

		// Post Import
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
		
		return $response;
	}

	/**
	 * Check if the video already exists.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  string  $src  The video source (URL or embed code).
	 * @param  string  $type The video source type.
	 * @return mixed         Video post ID if exists, false if not.
	 */
	private function is_video_exists( $src, $type = 'default' ) {
		if ( empty( $src ) || empty( $type ) ) {
			return false;
		}

		$type = sanitize_key( $type );

		$args = array(
			'post_type'              => 'aiovg_videos',
			'post_status'            => 'any',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false
		);

		$meta_queries = array();

		// Match by video type
		$meta_queries[] = array(
			'key'     => 'type',
			'value'   => ( in_array( $type, array( 'hls', 'dash' ) ) ? 'adaptive' : $type ),
			'compare' => '='
		);

		// Match by source URL
		if ( 'embedcode' === $type ) {
			try {
				add_filter( 'wp_kses_allowed_html', 'aiovg_allow_iframe_script_tags' );

				$meta_queries[] = array(
					'key'     => 'embedcode',
					'value'   => wp_kses_post( $src ),
					'compare' => '='
				);
			} finally {
				remove_filter( 'wp_kses_allowed_html', 'aiovg_allow_iframe_script_tags' );
			}
		} else {
			$meta_queries[] = array(
				'key'     => ( 'default' == $type ? 'mp4' : $type ),
				'value'   => aiovg_sanitize_url( $src ),
				'compare' => '='
			);
		}

		// Assign the meta query with 'AND' relation if multiple clauses exist.
		if ( count( $meta_queries ) > 1 ) {
			$meta_queries['relation'] = 'AND';
		}
		$args['meta_query'] = $meta_queries;

		$aiovg_query = new WP_Query( $args );

		if ( $aiovg_query->have_posts() ) {
			// Since 'fields' is 'ids', $aiovg_query->posts is an array of post IDs.
			return $aiovg_query->posts[0];
		}

		return false;
	}

	/**
	 * Generate a secure, deterministic video post slug.
	 *
	 * @since  4.7.0
	 * @access private
	 * @param  string  $video_src Absolute or relative video file path.
	 * @return string
	 */
	private function generate_random_video_slug( $video_src ) {
		// Normalize path for consistency across environments
		$normalized = wp_normalize_path( $video_src );

		// Add site-specific salt
		$hash = hash( 'sha256', $normalized . '|' . wp_salt( 'aiovg_video_slug' ) );

		// Short, URL-safe slug
		return substr( $hash, 0, 18 );
	}

	/**
	 * Resolve video type.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  string  $src Video source (URL, relative path, or embed code).
	 * @return string       Video type ('default', 'youtube', 'vimeo', etc., or 'embedcode').
	 */
	private function resolve_video_type( $src ) {
		if ( empty( $src ) ) {
			return 'default';
		}

		// Normalize source
		$src = trim( $src );

		// Check for any HTML markup
		if ( preg_match( '/<\s*\w+(?:\b[^>]*)?>/i', $src ) ) {
			return 'embedcode';
		}

		// Check known video platforms
		if ( false !== stripos( $src, 'youtube.com' ) || false !== stripos( $src, 'youtu.be' ) ) {
			return 'youtube';
		} elseif ( false !== stripos( $src, 'vimeo.com' ) ) {
			return 'vimeo';
		} elseif ( false !== stripos( $src, 'dailymotion.com' ) ) {
			return 'dailymotion';
		} elseif ( false !== stripos( $src, 'rumble.com' ) ) {
			return 'rumble';
		} elseif ( false !== stripos( $src, 'facebook.com' ) ) {
			return 'facebook';
		} else {
			$file_type = wp_check_filetype( $src );

			if ( 'm3u8' == $file_type['ext'] ) {
				return 'hls';
			}

			if ( 'mpd' == $file_type['ext'] ) {
				return 'dash';
			}
		}

		// Default fallback
		return 'default';
	}

	/**
	 * Resolve term IDs from imported data for a given taxonomy.
	 *
	 * Supports hierarchical chains like "Parent > Child > Subchild".
	 * Can optionally create new terms if they don't exist.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  array           $terms      Imported terms array.
	 * @param  string          $taxonomy   Taxonomy name.
	 * @param  array           $attributes Import settings (expects 'values_separator' and 'add_new_term').
	 * @return array|WP_Error              Array of term IDs, or WP_Error on failure.
	 */
	private function resolve_term_ids( $terms, $taxonomy, $attributes ) {
		$term_ids = array();

		if ( empty( $terms ) ) {
			return $term_ids; // No terms provided, return empty array
		}

		$separator = ! empty( $attributes['values_separator'] ) ? $attributes['values_separator'] : ';';
		$terms_raw = array_filter( array_map( 'trim', explode( $separator, $terms ) ) );

		foreach ( $terms_raw as $term_item ) {
			// Numeric term IDs
			if ( is_numeric( $term_item ) && get_term( (int) $term_item, $taxonomy ) ) {
				$term_ids[] = (int) $term_item;
				continue;
			}

			// Hierarchical chain
			$chain = array_filter( array_map( 'trim', explode( '>', $term_item ) ) );
			if ( empty( $chain ) ) {
				continue;
			}

			$parent_id = 0; // For hierarchy

			foreach ( $chain as $term_name ) {
				$term_name = htmlspecialchars( sanitize_text_field( $term_name ) ); // Handle &amp; etc.

				// Check if term exists
				$term = term_exists( $term_name, $taxonomy, $parent_id );

				if ( $term ) {
					$parent_id = (int) $term['term_id'];
				} else {
					if ( ! empty( $attributes['add_new_term'] ) ) {
						$new_term = wp_insert_term( $term_name, $taxonomy, array( 'parent' => $parent_id ) );
						
						if ( is_wp_error( $new_term ) ) {
							return new WP_Error(
								'term_insert_failed',
								sprintf(
									__( 'Failed to create term "%s" in taxonomy "%s".', 'all-in-one-video-gallery' ),
									$term_name,
									$taxonomy
								)
							);
						}

						$parent_id = (int) $new_term['term_id'];
					} else {
						return new WP_Error(
							'term_not_found',
							sprintf(
								__( 'Term "%s" not found in taxonomy "%s" and adding new terms is disabled.', 'all-in-one-video-gallery' ),
								$term_name,
								$taxonomy
							)
						);
					}
				}

				// Append each term in the chain if not already added
				if ( $parent_id && ! in_array( $parent_id, $term_ids, true ) ) {
					$term_ids[] = $parent_id;
				}
			}
		}

		return $term_ids;
	}

	/**
	 * Resolve user ID from a username or email, optionally creating a new user.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  string       $post_author WordPress username or email address.
	 * @param  array        $attributes  Import settings (expects 'add_new_user' boolean).
	 * @return int|WP_Error              User ID on success, WP_Error on failure.
	 */
	private function resolve_user_id( $post_author, $attributes = array() ) {
		if ( empty( $post_author ) ) {
			return new WP_Error( 'invalid_user', __( 'No username or email provided.', 'all-in-one-video-gallery' ) );
		}

		$post_author = sanitize_text_field( $post_author );

		// Case 1: Numeric user ID
		if ( is_numeric( $post_author ) ) {
			$user = get_user_by( 'ID', (int) $post_author );

			if ( $user ) {
				return $user->ID;
			}
				
			return new WP_Error( 'invalid_user', __( 'User ID does not exist.', 'all-in-one-video-gallery' ) );
		}

		// Case 2: Email
		if ( is_email( $post_author ) ) {
			$email = sanitize_email( $post_author );
			$user  = get_user_by( 'email', $email );

			if ( $user ) {
				return $user->ID;
			}

			if ( ! empty( $attributes['add_new_user'] ) ) {
				$password    = wp_generate_password( 12, true );
				$new_user_id = wp_create_user( $email, $password, $email );

				if ( is_wp_error( $new_user_id ) ) {
					return $new_user_id;
				}

				return $new_user_id;
			}

			return new WP_Error( 'invalid_user', sprintf( __( 'User with email "%s" does not exist.', 'all-in-one-video-gallery' ), $email ) );
		}

		// Case 3: Username
		$user_id = username_exists( $post_author );

		if ( $user_id ) {
			return $user_id;
		}

		return new WP_Error( 'invalid_user', sprintf( __( 'User "%s" does not exist.', 'all-in-one-video-gallery' ), $post_author ) );
	}

	/**
	 * Create a WordPress attachment from a local file if it doesn't already exist.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  int       $post_id   The post ID to attach the media to (e.g., a video post).
	 * @param  string    $file_path Absolute path to the local file.
	 * @param  string    $file_url  Optional. Public URL of the file (for checking duplicates by URL).
	 * @return int|false            Attachment ID on success, false on failure.
	 */
	private function create_attachment( $post_id, $file_path, $file_url = '' ) {
		if ( ! file_exists( $file_path ) ) {
			return false; // File doesn't exist
		}
		
		if ( $file_url ) {
			// Check via WordPress native function (uploads directory)
			if ( $existing_id = attachment_url_to_postid( $file_url ) ) {
				return $existing_id;
			}

			// Check via plugin meta (for external or imported files)
			$existing = get_posts( array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'meta_key'       => 'aiovg_source_url',
				'meta_value'     => $file_url,
				'fields'         => 'ids',
				'posts_per_page' => 1
			) );

			if ( ! empty( $existing ) ) {
				return $existing[0];
			}
		}

		// Include required WordPress files
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		// Prepare attachment data
		$file_name = basename( $file_path );
		$file_type = wp_check_filetype( $file_name, null );
		
		// Default to a safe image MIME type if detection fails
		$mime_type = ! empty( $file_type['type'] ) ? $file_type['type'] : 'image/jpeg';

		$attachment = array(
			'guid'           => $file_url,
			'post_mime_type' => $mime_type,
			'post_title'     => sanitize_file_name( preg_replace( '/\.[^.]+$/', '', $file_name ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		// Insert attachment into WordPress
		$attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );

		if ( ! $attach_id || is_wp_error( $attach_id ) ) {
			return false;
		}

		// Generate metadata (thumbnails, sizes)
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Save source URL for plugin-specific imports
		if ( $file_url ) {
			update_post_meta( $attach_id, 'aiovg_source_url', esc_url_raw( $file_url ) );
		}

		return $attach_id;
	}

	/**
	 * Insert multiple post meta at once.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  int     $post_id Post ID.
	 * @param  array   $data    Associative array of meta keys and values.
	 */
	private function add_post_meta_bulk( $post_id, $data ) {
		global $wpdb;

		$meta_table = _get_meta_table( 'post' );

		// Basic sanity check.
		if ( empty( $post_id ) || empty( $data ) ) {
			return;
		}

		// Bulk insert all new meta entries.
		$placeholders = array();
		$values       = array();

		foreach ( $data as $key => $value ) {
			$placeholders[] = "(%d, %s, %s)";
			$values[]       = $post_id;
			$values[]       = $key;
			$values[]       = maybe_serialize( $value );
		}

		$sql  = "INSERT INTO $meta_table (`post_id`, `meta_key`, `meta_value`) VALUES ";
		$sql .= implode( ',', $placeholders );

		$wpdb->query( $wpdb->prepare( $sql, $values ) );
	}

	/**
	 * Update multiple post meta entries at once.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  int     $post_id Post ID.
	 * @param  array   $data    Associative array of meta keys and values.
	 * @return void
	 */
	private function update_post_meta_bulk( $post_id, $data ) {
		global $wpdb;

		$meta_table = _get_meta_table( 'post' );

		// Basic sanity check.
		if ( empty( $post_id ) || empty( $data ) ) {
			return;
		}

		// Determine if the DB engine supports transactions (InnoDB).
		$supports_transactions = false;

		$engine_result = $wpdb->get_row(
			$wpdb->prepare( "SHOW TABLE STATUS WHERE Name = %s", $meta_table )
		);

		if ( $engine_result && isset( $engine_result->Engine ) && strtoupper( $engine_result->Engine ) === 'INNODB' ) {
			$supports_transactions = true;
		}

		// Begin transaction if supported.
		if ( $supports_transactions ) {
			$wpdb->query( 'START TRANSACTION' );
		}

		try {
			// Step 1: Delete existing meta for the given keys.
			$meta_keys = array_keys( $data );

			$placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
			$sql_delete   = "DELETE FROM {$meta_table} WHERE post_id = %d AND meta_key IN ($placeholders)";
			$wpdb->query( $wpdb->prepare( $sql_delete, array_merge( array( $post_id ), $meta_keys ) ) );

			// Step 2: Bulk insert all new meta entries.
			$placeholders = array();
			$values       = array();

			foreach ( $data as $key => $value ) {
				$placeholders[] = "(%d, %s, %s)";
				$values[]       = $post_id;
				$values[]       = $key;
				$values[]       = maybe_serialize( $value );
			}

			$sql_insert  = "INSERT INTO {$meta_table} (`post_id`, `meta_key`, `meta_value`) VALUES ";
			$sql_insert .= implode( ',', $placeholders );

			$wpdb->query( $wpdb->prepare( $sql_insert, $values ) );

			// Commit if all went well.
			if ( $supports_transactions ) {
				$wpdb->query( 'COMMIT' );
			}
		} catch ( Exception $e ) {
			// Rollback only works if engine supports transactions.
			if ( $supports_transactions ) {
				$wpdb->query( 'ROLLBACK' );
			}
		}
	}

	/**
	 * Get CSV column mapping fields.
	 *
	 * @since  4.5.2
	 * @access private
	 * @return array   $fields CSV column mapping fields.
	 */
	private function get_csv_column_mapping_fields() {
		$fields = array(
			'post_id'          => __( 'Video ID (existing video)', 'all-in-one-video-gallery' ),
			'post_title'       => __( 'Video Title', 'all-in-one-video-gallery' ),
			'video'            => __( 'Video File', 'all-in-one-video-gallery' ),
			'image'            => __( 'Poster Image', 'all-in-one-video-gallery' ),
			'post_content'     => __( 'Video Description', 'all-in-one-video-gallery' ),
			'post_excerpt'     => __( 'Video Excerpt', 'all-in-one-video-gallery' ),					
			'aiovg_categories' => __( 'Video Categories', 'all-in-one-video-gallery' ),			
			'aiovg_tags'       => __( 'Video Tags', 'all-in-one-video-gallery' ),
			'track'            => __( 'Subtitles', 'all-in-one-video-gallery' ),
			'chapter'          => __( 'Chapters', 'all-in-one-video-gallery' ),
			'duration'         => __( 'Video Duration', 'all-in-one-video-gallery' ),					
			'views'            => __( 'Views Count', 'all-in-one-video-gallery' ),
			'likes'            => __( 'Likes Count', 'all-in-one-video-gallery' ),
			'dislikes'         => __( 'Dislikes Count', 'all-in-one-video-gallery' ),
			'download'         => __( 'Enable Download', 'all-in-one-video-gallery' ),
			'featured'         => __( 'Featured', 'all-in-one-video-gallery' ),
			'access_control'   => __( 'Restrict Access', 'all-in-one-video-gallery' ),
			'restricted_roles' => __( 'Restricted User Roles', 'all-in-one-video-gallery' ),
			'comment_status'   => __( 'Comment Status', 'all-in-one-video-gallery' ),
			'post_status'      => __( 'Video Status', 'all-in-one-video-gallery' ),
			'post_author'      => __( 'Video Author', 'all-in-one-video-gallery' ),
			'post_date'        => __( 'Published Date', 'all-in-one-video-gallery' )
		);

		return $fields;
	}

	/**
	 * Check if a given URL is local to the WordPress site.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  string  $url The URL to check.
	 * @return bool         True if local, false if external.
	 */
	private function is_file_local( $url ) {
		// Get the hostname of the WordPress site.
		$site_host = wp_parse_url( get_home_url(), PHP_URL_HOST );

		// Get the hostname from the URL to be checked.
		$file_host = wp_parse_url( $url, PHP_URL_HOST );

		// If there is no host, it's a relative path and thus local.
		if ( null === $file_host ) {
			return true;
		}

		// Compare the hostnames (case-insensitive).
		return strcasecmp( $site_host, $file_host ) === 0;
	}

	/**
	 * Generate a filename for exported local files.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  string       $file_url The file URL.
	 * @param  int          $post_id  The post ID.
	 * @param  bool|null    $is_local Optional. Whether the file is local. If null, it will be determined automatically.
	 * @return string|false           Generated filename or false if external URL.
	 */
	private function get_exported_filename( $file_url, $post_id, $is_local = null ) {
		if ( $is_local === null ) {
			$is_local = $this->is_file_local( $file_url );
		}

		if ( $is_local ) {
			return "$post_id-" . basename( $file_url );
		}

		return false;
	}

	/**
	 * Converts an absolute file system path to a site-relative URL.
	 *
	 * @since  4.5.2
	 * @access private
	 * @param  string  $absolute_path Absolute file path.
	 * @return string                 File URL or empty string if path is invalid.
	 */
	private function make_absolute_url( $absolute_path ) {
		$absolute_path = wp_normalize_path( $absolute_path );
		$abspath       = wp_normalize_path( ABSPATH );

		if ( stripos( $absolute_path, $abspath ) === 0 ) {
			$relative_path = ltrim( substr( $absolute_path, strlen( $abspath ) ), '/' );
			return site_url( $relative_path );
		}

		return '';
	}

	/**
	 * Schedule cleanup of the export directory contents.
	 *
	 * @since  4.5.2
	 * @access private
	 */
	private function schedule_export_directory_cleanup() {
		// Unschedule previous cleanup if exists
		$timestamp = wp_next_scheduled( 'aiovg_cleanup_export_directory' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'aiovg_cleanup_export_directory' );
		}

		// Schedule new single-event cleanup
		$cleanup_after = apply_filters( 'aiovg_cleanup_export_directory_time', DAY_IN_SECONDS );
		wp_schedule_single_event( time() + $cleanup_after, 'aiovg_cleanup_export_directory' );
	}
	
}
