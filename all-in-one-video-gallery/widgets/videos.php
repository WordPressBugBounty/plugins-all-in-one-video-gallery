<?php

/**
 * Videos Widget.
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
 * AIOVG_Widget_Videos class.
 *
 * @since 1.0.0
 */
class AIOVG_Widget_Videos extends WP_Widget {
	
	/**
     * Unique identifier for the widget.
     *
     * @since  1.0.0
	 * @access protected
     * @var    string
     */
    protected $widget_slug;
	
	/**
     * Widget fields.
     *
     * @since  1.0.0
	 * @access private
     * @var    array
     */
	private $fields;

	/**
     * Excluded widget fields.
     *
     * @since  2.5.8
	 * @access private
     * @var    array
     */
	private $excluded_fields = array();
	
	/**
     * Default settings.
     *
     * @since  1.0.0
	 * @access private
     * @var    array
     */
    private $defaults;
	
	/**
	 * Get things started.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {		
		$this->widget_slug = 'aiovg-widget-videos';		
		$this->fields = aiovg_get_shortcode_fields();
		$this->excluded_fields = array( 'ratio', 'title_length', 'excerpt_length' );
		$this->defaults = $this->get_defaults();
		
		parent::__construct(
			$this->widget_slug,
			__( 'AIOVG - Video Gallery', 'all-in-one-video-gallery' ),
			array(
				'classname'   => $this->widget_slug,
				'description' => __( 'Display a video gallery.', 'all-in-one-video-gallery' )
			)
		);		
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @since 1.0.0
	 * @param array	$args	  The array of form elements.
	 * @param array $instance The current instance of the widget.
	 */
	public function widget( $args, $instance ) {		
		// Merge incoming $instance array with $defaults
		if ( count( $instance ) ) {
			foreach ( $this->excluded_fields as $excluded_field ) {
				if ( isset( $instance[ $excluded_field ] ) ) {
					unset( $instance[ $excluded_field ] ); // Always get this value from the global settings
				}
			}
			
			$attributes = array_merge( $this->defaults, $instance );
		} else {
			$attributes = $this->defaults;
		}

		$attributes = $this->prepare_attributes( $attributes );		

		$orderby = sanitize_text_field( $attributes['orderby'] );
		$order   = sanitize_text_field( $attributes['order'] );
		
		// Added for backward compatibility (version < 1.5.7)
		if ( isset( $instance['image_position'] ) && 'left' == $instance['image_position'] ) {
			$attributes['thumbnail_style'] = 'image-left';
		}

		// Define the query
		global $post;	

		$query = array(				
			'post_type'      => 'aiovg_videos',
			'posts_per_page' => ! empty( $attributes['limit'] ) ? (int) $attributes['limit'] : -1,
			'post_status'    => array( 'publish' )
		);
		
		if ( isset( $attributes['search_query'] ) && ! empty( $attributes['search_query'] ) ) { // Search
			$query['s'] = sanitize_text_field( $attributes['search_query'] );
		}

		if ( isset( $attributes['exclude'] ) && ! empty( $attributes['exclude'] ) ) { // Exclude video IDs
			$query['post__not_in'] = is_array( $attributes['exclude'] ) ? array_map( 'intval', $attributes['exclude'] ) : array_map( 'intval', explode( ',', $attributes['exclude'] ) );
		}
		
		// Taxonomy Parameters
		$tax_queries = array();

		if ( ! empty( $attributes['category'] ) ) { // Category
			$tax_queries[] = array(
				'taxonomy'         => 'aiovg_categories',
				'field'            => 'term_id',
				'terms'            => is_array( $attributes['category'] ) ? array_map( 'intval', $attributes['category'] ) : array_map( 'intval', explode( ',', $attributes['category'] ) ),
				'include_children' => false
			);
		}

		if ( isset( $attributes['category_exclude'] ) && ! empty( $attributes['category_exclude'] ) ) { // Exclude categories
			$tax_queries[] = array(
				'taxonomy'         => 'aiovg_categories',
				'field'            => 'term_id',
				'terms'            => is_array( $attributes['category_exclude'] ) ? array_map( 'intval', $attributes['category_exclude'] ) : array_map( 'intval', explode( ',', $attributes['category_exclude'] ) ),
				'include_children' => false,
				'operator'         => 'NOT IN'
			);
		}
		
		if ( ! empty( $attributes['tag'] ) ) { // Tag
			$tax_queries[] = array(
				'taxonomy'         => 'aiovg_tags',
				'field'            => 'term_id',
				'terms'            => is_array( $attributes['tag'] ) ? array_map( 'intval', $attributes['tag'] ) : array_map( 'intval', explode( ',', $attributes['tag'] ) ),
				'include_children' => false
			);
		}

		$count_tax_queries = count( $tax_queries );
		if ( $count_tax_queries ) {
			$tax_relation = ! empty( $attributes['related'] ) ? 'OR' : 'AND';
			$query['tax_query'] = ( $count_tax_queries > 1 ) ? array_merge( array( 'relation' => $tax_relation ), $tax_queries ) : $tax_queries;
		}
	
		// Custom Field (post meta) Parameters
		$meta_queries = array();		

		if ( 'likes' == $orderby ) { // Likes			
			$meta_queries['likes'] = array(
				'relation' => 'OR',
				array(
					'key'     => 'likes',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key'     => 'likes',
					'compare' => 'EXISTS'
				)
			);				
		}

		if ( 'dislikes' == $orderby ) { // Dislikes			
			$meta_queries['dislikes'] = array(
				'key'     => 'dislikes',
				'value'   => 0,
				'compare' => '>'
			);				
		}

		if ( ! empty( $attributes['featured'] ) ) {			
			$meta_queries['featured'] = array(
				'key'     => 'featured',
				'value'   => 1,
				'compare' => '='
			);				
		}

		$count_meta_queries = count( $meta_queries );
		if ( $count_meta_queries ) {
			$query['meta_query'] = ( $count_meta_queries > 1 ) ? array_merge( array( 'relation' => 'AND' ), $meta_queries ) : $meta_queries;
		}		
	
		// Order & Orderby Parameters
		switch ( $orderby ) {
			case 'likes':
				$query['orderby']  = 'meta_value_num';				
				$query['order']    = $order;
				break;

			case 'dislikes':
				$query['meta_key'] = $orderby;
				$query['orderby']  = 'meta_value_num';				
				$query['order']    = $order;
				break;

			case 'views':
				$query['meta_key'] = $orderby;
				$query['orderby']  = 'meta_value_num';				
				$query['order']    = $order;
				break;

			case 'rand':
				$seed = aiovg_get_orderby_rand_seed();
				$query['orderby']  = 'RAND(' . $seed . ')';
				break;
				
			default:
				$query['orderby'] = $orderby;
				$query['order']   = $order;
		}
		
		$query = apply_filters( 'aiovg_query_args', $query, $attributes );
		$aiovg_query = new WP_Query( $query );
		
		// Enqueue dependencies
		wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-public' );
		
		// Process output
		echo $args['before_widget'];
		
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		
		if ( $aiovg_query->have_posts() ) {			
			unset( $attributes['title'] );
				
			ob_start();
			include apply_filters( 'aiovg_load_template', AIOVG_PLUGIN_DIR . 'public/templates/videos-template-classic.php', $attributes );		
			$content = ob_get_clean();			
		} else {		
			$content = sprintf(
				'<div class="aiovg-videos aiovg-widget-videos aiovg-no-items-found">%s</div>',
				esc_html( aiovg_get_message( 'videos_empty' ) )
			);
		}

		$content = aiovg_wrap_with_filters( $content, $attributes );
		echo $content;
		
		echo $args['after_widget'];
	}
	
	/**
	 * Processes the widget's options to be saved.
	 *
	 * @since 1.0.0
	 * @param array	$new_instance The new instance of values to be generated via the update.
	 * @param array $old_instance The previous instance of values before the update.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;		

		foreach ( $this->fields['videos']['sections'] as $section ) {
			foreach ( $section['fields'] as $field ) {
				$field_name = $field['name'];

				if ( in_array( $field_name, $this->excluded_fields ) ) {
					continue;
				}

				if ( 'categories' == $field['type'] ) {
					$instance['category'] = isset( $new_instance['category'] ) ? array_map( 'intval', $new_instance['category'] ) : array();
				} elseif ( 'tags' == $field['type'] ) {
					$instance['tag'] = isset( $new_instance['tag'] ) ? array_map( 'intval', $new_instance['tag'] ) : array();
				} elseif ( 'number' == $field['type'] ) {
					if ( ! empty( $new_instance[ $field_name ] ) ) {
						$instance[ $field_name ] = false === strpos( $new_instance[ $field_name ], '.' ) ? (int) $new_instance[ $field_name ] : (float) $new_instance[ $field_name ];
					} else {
						$instance[ $field_name ] = 0;
					}
				} elseif ( 'url' == $field['type'] ) {
					$instance[ $field_name ] = ! empty( $new_instance[ $field_name ] ) ? aiovg_sanitize_url( $new_instance[ $field_name ] ) : '';
				} elseif ( 'checkbox' == $field['type'] ) {
					$instance[ $field_name ] = isset( $new_instance[ $field_name ] ) ? (int) $new_instance[ $field_name ] : 0;
				} else {
					$instance[ $field_name ] = ! empty( $new_instance[ $field_name ] ) ? sanitize_text_field( $new_instance[ $field_name ] ) : '';
				}
			}
		}
		
		return $instance;
	}
	
	/**
	 * Generates the administration form for the widget.
	 *
	 * @since 1.0.0
	 * @param array $instance The array of keys and values for the widget.
	 */
	public function form( $instance ) {
		// Parse incoming $instance into an array and merge it with $defaults
		$instance = wp_parse_args(
			(array) $instance,
			$this->defaults
		);
		
		// Added for backward compatibility (version < 1.5.7)
		if ( isset( $instance['image_position'] ) && 'left' == $instance['image_position'] ) {
			$instance['thumbnail_style'] = 'image-left';
		}
			
		// Display the admin form
		include AIOVG_PLUGIN_DIR . 'widgets/forms/videos.php';
	}

	/**
	 * Prepares and processes the complete attributes array for the widget.
	 *
	 * @since  4.0.1
	 * @param  array $attributes Raw widget attributes.
	 * @return array        	 Fully prepared attributes array, ready for use.
	 */
	public function prepare_attributes( $attributes ) {
		$attributes['uid'] = aiovg_get_uniqid();
		$attributes['show_count'] = 0;

		// Related
		if ( ! empty( $attributes['related'] ) ) {
			if ( is_singular( 'aiovg_videos' ) ) {
				global $wp_the_query;				
				$post_id = $wp_the_query->get_queried_object_id();

				$categories = wp_get_object_terms( $post_id, 'aiovg_categories', array( 'fields' => 'ids' ) );
				$attributes['category'] = ! empty( $categories ) ? $categories : '';

				$tags = wp_get_object_terms( $post_id, 'aiovg_tags', array( 'fields' => 'ids' ) );
				$attributes['tag'] = ! empty( $tags ) ? $tags : '';
				
				$attributes['exclude'] = array( $post_id );
			} else {
				// Category page
				if ( $term_slug = get_query_var( 'aiovg_category' ) ) {         
					$term = get_term_by( 'slug', sanitize_text_field( $term_slug ), 'aiovg_categories' );
					$attributes['category'] = $term->term_id;
				}

				// Tag page
				if ( $term_slug = get_query_var( 'aiovg_tag' ) ) {         
					$term = get_term_by( 'slug', sanitize_text_field( $term_slug ), 'aiovg_tags' );
					$attributes['tag'] = $term->term_id;
				}
			}			
		}

		// Search
		if ( 
			! empty( $attributes['filters_keyword'] ) ||
			! empty( $attributes['filters_category'] ) ||
			! empty( $attributes['filters_tag'] ) ||
			! empty( $attributes['filters_sort'] )
		) {
			if ( isset( $_GET['vi'] ) ) {
				$attributes['search_query'] = $_GET['vi'];
			}
			
			if ( isset( $_GET['ca'] ) ) {
				$attributes['category'] = $_GET['ca'];
			}
	
			if ( ! isset( $_GET['ca'] ) || ( isset( $_GET['ca'] ) && empty( $_GET['ca'] ) ) ) {
				$categories_excluded = get_terms( array(
					'taxonomy'   => 'aiovg_categories',
					'hide_empty' => false,
					'fields'     => 'ids',
					'meta_key'   => 'exclude_search_form',
					'meta_value' => 1
				) );
	
				if ( ! empty( $categories_excluded ) && ! is_wp_error( $categories_excluded ) ) {
					$attributes['category_exclude'] = $categories_excluded;
				}
			}
	
			if ( isset( $_GET['ta'] ) ) {
				$attributes['tag'] = $_GET['ta'];
			}
			
			if ( isset( $_GET['sort'] ) ) {
				$sort = array_filter( array_map( 'trim', explode( '-', $_GET['sort'] ) ) );
	
				if ( ! empty( $sort ) ) {
					$attributes['orderby'] = $sort[0];
					
					if ( count( $sort ) > 1 ) {
						$attributes['order'] = $sort[1];
					}
				}
			}
		}

		// Pagination
		if ( empty( $attributes['more_label'] ) ) {
			$attributes['more_label'] = __( 'Show More', 'all-in-one-video-gallery' );
		}

		if ( 'ajax' == $attributes['filters_mode'] ) {
			$attributes['pagination_ajax'] = 1;
		}

		return $attributes;
	}

	/**
	 * Get the default attribute values.
	 *
	 * @since  1.0.0
	 * @return array An associative array of attributes.
	 */
	public function get_defaults() {
		$pagination_settings = get_option( 'aiovg_pagination_settings', array() ); 

		$defaults = array();

		foreach ( $this->fields['videos']['sections'] as $section ) {
			foreach ( $section['fields'] as $field ) {
				$defaults[ $field['name'] ] = $field['value'];
			}
		}

		foreach ( $this->fields['categories']['sections']['general']['fields'] as $field ) {
			if ( 'orderby' == $field['name'] || 'order' == $field['name'] ) {
				$defaults[ 'categories_' . $field['name'] ] = $field['value'];
			}
		}

		foreach ( $this->fields['video']['sections']['general']['fields'] as $field ) {
			if ( 'autoplay' == $field['name'] || 'loop' == $field['name'] || 'muted' == $field['name'] ) {
				$defaults[ 'player_' . $field['name'] ] = $field['value'];
			}
		}

		foreach ( $this->fields['video']['sections']['controls']['fields'] as $field ) {
			$defaults[ 'player_' . $field['name'] ] = $field['value'];
		}

		$defaults['filters_keyword'] = 0;
		$defaults['filters_category'] = 0;
		$defaults['filters_tag'] = 0;
		$defaults['filters_sort'] = 0;
		$defaults['filters_template'] = 'horizontal';
		$defaults['filters_mode'] = 'live';
		$defaults['filters_position'] = 'top';
		
		$defaults['source'] = 'videos';
		$defaults['count'] = 0;
		$defaults['paged'] = 1;
		$defaults['pagination_ajax'] = isset( $pagination_settings['ajax'] ) && ! empty( $pagination_settings['ajax'] ) ? 1 : 0;

		$defaults = array_merge(
			$defaults,
			array(
				'title'              => __( 'Video Gallery', 'all-in-one-video-gallery' ),
				'columns'            => 1,
				'thumbnail_style'    => 'image-left',				
				'ratio'              => ! empty( $defaults['ratio'] ) ? (float) $defaults['ratio'] . '%' : '56.25%',
				'show_pagination'    => 0			
			)
		);

		return $defaults;
	}
	
}