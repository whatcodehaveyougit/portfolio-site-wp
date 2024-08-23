<?php


add_action( 'wp_enqueue_scripts', 'twentytwentyfour_child_scripts' );
function twentytwentyfour_child_scripts() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
  wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ) );

	// wp_enqueue_style( 'minified-child-theme-css', get_stylesheet_directory_uri() . '/dist/css/style.css', array(), 1.4 );
	// wp_enqueue_script('minified-child-theme-js',  get_stylesheet_directory_uri() . '/dist/js/scripts.js', [], 1.0, true);

	wp_enqueue_style( 'parcel', get_stylesheet_directory_uri() . '/dist/styles/style.css', array(), '1.0' );
	wp_enqueue_script( 'parcel-js', get_stylesheet_directory_uri() . '/dist/scripts/scripts.js', array(), '1.0', true );
}

function enqueue_taxonomy_filter_script() {
	// Localize the script with admin-ajax.php and a nonce
	wp_localize_script( 'parcel-js', 'ajax_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'filter_nonce' )
	));
}
add_action( 'wp_enqueue_scripts', 'enqueue_taxonomy_filter_script' );



// This code did work but I want it to be AJAX powered
function get_all_taxonomy_terms_for_post_type() {
	$post_type = 'project';
	$taxonomies = get_object_taxonomies( $post_type, 'objects' );
	$html = '';

	if ( !empty($taxonomies) ) {
			$html .= '<ul class="taxonomy-pills">';

			// Add "View All" pill at the start
			$html .= '<li><a href="#" class="taxonomy-term-link view-all" data-term-id="" data-taxonomy="">View All</a></li>';

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


function display_all_projects($args = array()) {
	$default_args = array(
			'post_type' => 'project',
			'posts_per_page' => -1
	);
	$query_args = wp_parse_args($args, $default_args);

	$query = new WP_Query($query_args);
	$html = '';

	if ($query->have_posts()) {
			while ($query->have_posts()) {
					$query->the_post();
					$thumbnail_html = get_the_post_thumbnail(get_the_ID(), 'medium');
					$title = get_the_title();
					$escaped_title = esc_attr($title);

					$html .= '<div class="project-tile-container">';
					$html .= '<a href="' . esc_url($permalink) . '" title="' . $escaped_title . '">';
					$html .= $thumbnail_html; // Include the thumbnail within the link
					$html .= '</a>';
					$html .= '<div class="overlay">';
					$html .= '<h2>' . esc_html($title) . '</h2>';
					$html .= '</div>';
					$html .= '</div>';
			}
	} else {
			$html .= '<p>No projects found.</p>';
	}

	wp_reset_postdata();

	return $html;
}


function filter_projects_by_taxonomy_ajax() {
	check_ajax_referer('filter_nonce', 'nonce');

	$term_id = intval($_POST['term_id']);
	$taxonomy = sanitize_text_field($_POST['taxonomy']);

	$args = array(
			'post_type' => 'project',
	);

	if (!empty($term_id) && !empty($taxonomy)) {
			$args['tax_query'] = array(
					array(
							'taxonomy' => $taxonomy,
							'field'    => 'term_id',
							'terms'    => $term_id,
					),
			);
	}

	// Use the same function to display the filtered projects
	echo display_all_projects($args);

	wp_die(); // Required to terminate immediately and return a proper response
}

add_action('wp_ajax_filter_projects_by_taxonomy', 'filter_projects_by_taxonomy_ajax');
add_action('wp_ajax_nopriv_filter_projects_by_taxonomy', 'filter_projects_by_taxonomy_ajax');

function filter_projects_by_taxonomy_shortcode() {
	// Get the taxonomy terms
	$terms_html = get_all_taxonomy_terms_for_post_type();

	// Create the container for the filtered projects
	$html = '<div class="taxonomy-terms">';
	$html .= $terms_html;
	$html .= '</div>';

	// Display all posts by default
	$html .= '<div class="projects-container">';
	$html .= display_all_projects(); // Use the function to display all projects
	$html .= '</div>';

	// Include the AJAX script with animations and active class management
	$html .= '<script type="text/javascript">
	jQuery(document).ready(function($) {
			$(".taxonomy-term-link").on("click", function(e) {
					e.preventDefault(); // Prevent the default link behavior

					var term_id = $(this).data("term-id");
					var taxonomy = $(this).data("taxonomy");

					// Remove active class from all taxonomy terms
					$(".taxonomy-term-link").removeClass("active");

					// Add active class to the clicked taxonomy term
					$(this).addClass("active");

					$.ajax({
							url: "' . admin_url('admin-ajax.php') . '",
							type: "POST",
							data: {
									action: "filter_projects_by_taxonomy",
									term_id: term_id,
									taxonomy: taxonomy,
									nonce: "' . wp_create_nonce('filter_nonce') . '"
							},
							beforeSend: function() {
									$(".projects-container").fadeOut(300, function() {
											$(this).html("<p>Loading...</p>").fadeIn(300); // Show a loading message with fade effect
									});
							},
							success: function(response) {
									$(".projects-container").fadeOut(300, function() {
											$(this).html(response).fadeIn(300); // Update the content with the filtered posts and add fade effect
									});
							},
							error: function(xhr, status, error) {
									console.log("AJAX Error:", error);
									$(".projects-container").fadeOut(300, function() {
											$(this).html("<p>There was an error loading the projects. Please try again.</p>").fadeIn(300);
									});
							}
					});
			});

			// Handle "View All" pill click
			$(".taxonomy-terms").on("click", ".view-all", function(e) {
					e.preventDefault();

					// Remove active class from all taxonomy terms
					$(".taxonomy-term-link").removeClass("active");

					$.ajax({
							url: "' . admin_url('admin-ajax.php') . '",
							type: "POST",
							data: {
									action: "filter_projects_by_taxonomy",
									term_id: "",
									taxonomy: "",
									nonce: "' . wp_create_nonce('filter_nonce') . '"
							},
							beforeSend: function() {
									$(".projects-container").fadeOut(300, function() {
											$(this).html("<p>Loading...</p>").fadeIn(300);
									});
							},
							success: function(response) {
									$(".projects-container").fadeOut(300, function() {
											$(this).html(response).fadeIn(300);
									});
							},
							error: function(xhr, status, error) {
									console.log("AJAX Error:", error);
									$(".projects-container").fadeOut(300, function() {
											$(this).html("<p>There was an error loading the projects. Please try again.</p>").fadeIn(300);
									});
							}
					});
			});
	});
	</script>';

	return $html;
}

add_shortcode('filter_projects', 'filter_projects_by_taxonomy_shortcode');



function display_project_features() {
	// Ensure this is a single project page
	if ( ! is_singular( 'project' ) ) {
			return '<p>This is not a project page.</p>';
	}

	// Get the current post ID
	global $post;
	$post_id = $post->ID;

	// Verify taxonomy and post type
	$taxonomies = get_object_taxonomies( 'project', 'names' );

	if ( ! in_array( 'feature', $taxonomies ) ) {
			return '<p>The taxonomy "features" is not registered for this post typeeee.</p>';
	}

	// Get terms from the 'features' taxonomy for the current project
	$terms = get_the_terms( $post_id, 'feature' );

	if ( $terms && ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$html = '<div class="project-features">';
			$html .= '<h3>Features:</h3>';
			$html .= '<ul>';

			foreach ( $terms as $term ) {
					// Output each term
					$html .= '<li>';
					$html .= '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
					$html .= '</li>';
			}

			$html .= '</ul>';
			$html .= '</div>';
	} else {
			$html = '<p>No features found.</p>';
	}

	return $html;
}

add_shortcode( 'project_features', 'display_project_features' );
