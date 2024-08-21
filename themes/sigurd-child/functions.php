<?php


add_action( 'wp_enqueue_scripts', 'twentytwentyfour_child_scripts' );
function twentytwentyfour_child_scripts() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
  wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ) );
}

function enqueue_taxonomy_filter_script() {
	wp_enqueue_script( 'taxonomy-filter', get_template_directory_uri() . '/js/taxonomy-filter.js', array('jquery'), null, true );
	// Localize the script with admin-ajax.php and a nonce
	wp_localize_script( 'taxonomy-filter', 'ajax_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'filter_nonce' )
	));
}
add_action( 'wp_enqueue_scripts', 'enqueue_taxonomy_filter_script' );




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
// add_shortcode('display-taxes', 'get_all_taxonomy_terms_for_post_type');


// This code did work but I want it to be AJAX powered
function get_all_taxonomy_terms_for_post_type() {
	$post_type = 'project';
	$taxonomies = get_object_taxonomies( $post_type, 'objects' );
	$html = '';

	if ( !empty($taxonomies) ) {
			$html .= '<ul class="taxonomy-pills">';

			foreach ( $taxonomies as $taxonomy ) {
					$terms = get_terms( array(
							'taxonomy'   => $taxonomy->name,
							'hide_empty' => true,
					));

					if ( !empty($terms) && !is_wp_error($terms) ) {
							foreach ( $terms as $term ) {
									$html .= '<li><a href="#" class="taxonomy-term-link" data-term-id="' . esc_attr( $term->term_id ) . '" data-taxonomy="' . esc_attr( $taxonomy->name ) . '">' . esc_html( $term->name ) . '</a></li>';
							}
					}
			}

			$html .= '</ul>';
	} else {
			$html .= '<p>No taxonomies found for this post type.</p>';
	}

	return $html;
}

// Function to handle the AJAX request (already defined)
function filter_projects_by_taxonomy_ajax() {
	check_ajax_referer( 'filter_nonce', 'nonce' );

	$term_id = intval( $_POST['term_id'] );
	$taxonomy = sanitize_text_field( $_POST['taxonomy'] );

	$args = array(
			'post_type' => 'project',
			'tax_query' => array(
					array(
							'taxonomy' => $taxonomy,
							'field'    => 'term_id',
							'terms'    => $term_id,
					),
			),
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
					$query->the_post();
					// Customize the output as needed
					echo '<div class="project">';
					echo '<h2>' . get_the_title() . '</h2>';
					echo '<div>' . get_the_post_thumbnail( get_the_ID(), 'medium' ) . '</div>';
					echo '</div>';
			}
	} else {
			echo '<p>No projects found for this term.</p>';
	}

	wp_reset_postdata();

	wp_die(); // Required to terminate immediately and return a proper response
}
add_action( 'wp_ajax_filter_projects_by_taxonomy', 'filter_projects_by_taxonomy_ajax' );
add_action( 'wp_ajax_nopriv_filter_projects_by_taxonomy', 'filter_projects_by_taxonomy_ajax' );

// Shortcode function to display taxonomy terms and filtered projects
function filter_projects_by_taxonomy_shortcode() {
	// Get the taxonomy terms
	$terms_html = get_all_taxonomy_terms_for_post_type();

	// Create the container for the filtered projects
	$html = '<div class="taxonomy-terms">';
	$html .= $terms_html;
	$html .= '</div>';

	// Display all posts by default
	$html .= '<div class="projects-container">';
	$html .= display_all_projects(); // Function to display all projects
	$html .= '</div>';

	// Include the AJAX script
	$html .= '<script type="text/javascript">
	jQuery(document).ready(function($) {
			$(".taxonomy-term-link").on("click", function(e) {
					e.preventDefault(); // Prevent the default link behavior

					var term_id = $(this).data("term-id");
					var taxonomy = $(this).data("taxonomy");

					$.ajax({
							url: "' . admin_url( 'admin-ajax.php' ) . '",
							type: "POST",
							data: {
									action: "filter_projects_by_taxonomy",
									term_id: term_id,
									taxonomy: taxonomy,
									nonce: "' . wp_create_nonce('filter_nonce') . '"
							},
							beforeSend: function() {
									$(".projects-container").html("<p>Loading...</p>"); // Show a loading message or spinner
							},
							success: function(response) {
									$(".projects-container").html(response); // Update the content with the filtered posts
							},
							error: function(xhr, status, error) {
									console.log("AJAX Error:", error);
									$(".projects-container").html("<p>There was an error loading the projects. Please try again.</p>");
							}
					});
			});
	});
	</script>';

	return $html;
}
function display_all_projects() {
	$args = array(
			'post_type' => 'project',
			'posts_per_page' => -1 // Display all posts
	);

	$query = new WP_Query( $args );
	$html = '';

	if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
					$query->the_post();
					$html .= '<div class="project">';
					$html .= '<h2>' . get_the_title() . '</h2>';

					// Display the featured image
					if ( has_post_thumbnail() ) {
							$html .= '<div>' . get_the_post_thumbnail( get_the_ID(), 'medium' ) . '</div>';
					} else {
							$html .= '<div><p>No image available</p></div>';
					}

					$html .= '</div>';
			}
	} else {
			$html .= '<p>No projects found.</p>';
	}

	wp_reset_postdata();

	return $html;
}

add_shortcode( 'filter_projects', 'filter_projects_by_taxonomy_shortcode' );
