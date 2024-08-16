<?php


add_action( 'wp_enqueue_scripts', 'twentytwentyfour_child_scripts' );
function twentytwentyfour_child_scripts() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
  wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ) );
}



// add_filter('acf/shortcode/allow_in_block_themes_outside_content', '__return_true');
// add_filter('the_content', 'do_shortcode');

// function acf_custom_field_shortcode($atts) {
// 	$atts = shortcode_atts(array(
// 			'field' => '',
// 			'post_id' => null,
// 	), $atts, 'acf');

// 	if (!empty($atts['field'])) {
// 			return get_field($atts['field'], $atts['post_id']);
// 	}

// 	return '';
// }
// add_shortcode('acf', 'acf_custom_field_shortcode');

// function register_acf_blocks() {
// 	acf_register_block_type(array(
// 			'name'              => 'custom-block',
// 			'title'             => __('Custom Block'),
// 			'render_callback'   => 'my_acf_block_render_callback',
// 	));
// }
// add_action('acf/init', 'register_acf_blocks');

// function my_acf_block_render_callback($block) {
// 	$field_value = get_field('field_name');
// 	echo '<div class="custom-block">'.$field_value.'</div>';
// }


// Display list of custom taxonomies in a template file or shortcode

// Register the shortcode with the proper callback function
// Register the shortcode
add_shortcode('display-taxes', 'get_all_taxonomy_terms_for_post_type');


function get_all_taxonomy_terms_for_post_type(  ) {

	$post_type = 'project';
	// Get all taxonomies registered for the custom post type
	$taxonomies = get_object_taxonomies( $post_type, 'objects' );
	$html = ''; // Initialize an empty string for HTML

	if ( !empty($taxonomies) ) {
			$html .= '<ul>';

			// Loop through each taxonomy and get the terms that are used in the custom post type
			foreach ( $taxonomies as $taxonomy ) {
					$terms = get_terms( array(
							'taxonomy'   => $taxonomy->name,
							'hide_empty' => true,  // Only show terms that are assigned to posts
					));

					if ( !empty($terms) && !is_wp_error($terms) ) {
							foreach ( $terms as $term ) {
									// Generate the link to filter posts by this taxonomy term
									$term_link = get_term_link( $term );
									if ( !is_wp_error( $term_link ) ) {
											$html .= '<li><a href="' . esc_url( $term_link ) . '">' . esc_html( $term->name ) . '</a></li>';
									}
							}
					}
			}

			$html .= '</ul>';
	} else {
			$html .= '<p>No taxonomies found for this post type.</p>';
	}

	return $html; // Return the concatenated HTML
}

