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
			$html .= '<li class="pill-outline"><a href="#" class="taxonomy-term-link view-all" data-term-id="" data-taxonomy="">View All</a></li>';

			foreach ( $taxonomies as $taxonomy ) {
					$terms = get_terms( array(
							'taxonomy'   => $taxonomy->name,
							'hide_empty' => true,
					));

					if ( !empty($terms) && !is_wp_error($terms) ) {
							foreach ( $terms as $term ) {
									$html .= '<li class="pill-outline"><a href="#" class="taxonomy-term-link" data-term-id="' . esc_attr( $term->term_id ) . '" data-taxonomy="' . esc_attr( $taxonomy->name ) . '">' . esc_html( $term->name ) . '</a></li>';
							}
					}
			}

			$html .= '</ul>';
	} else {
			$html .= '<p>No taxonomies found for this post type.</p>';
	}

	return $html;
}

function render_project_card($post_id) {
	$thumbnail_html = get_the_post_thumbnail($post_id, 'medium');
	$title = get_the_title($post_id);
	$description = get_field('project_description', $post_id);
	$escaped_title = esc_attr($title);
	$permalink = get_the_permalink($post_id);
	$features = get_the_terms($post_id, 'feature'); // Replace 'feature' with your actual taxonomy slug

	$html = '<div class="project-card-container">';
	$html .= '  <a href="' . esc_url($permalink) . '" title="' . esc_attr($escaped_title) . '" class="project-card-link">';
	$html .= '    <div class="project-card">';
	$html .= '      <div class="card-content">';
	$html .= '        <h1 class="card-title">' . esc_html($title) . '</h1>';

	if ($features && !is_wp_error($features)) {
			$html .= '        <ul class="features-list">';
			foreach ($features as $feature) {
					$html .= '          <li class="pill-outline">' . esc_html($feature->name) . '</li>';
			}
			$html .= '        </ul>';
	}
	$html .= '<p>' . esc_html($description) . '</p>';
	$html .= '      </div>';
	$html .= '      <div class="card-image">';
	$html .= '        ' . $thumbnail_html;
	$html .= '      </div>';
	$html .= '    </div>';
	$html .= '  </a>';
	$html .= '</div>';

	return $html;
}

function display_all_projects($args = array()) {
	$default_args = array(
			'post_type' => 'project',
			'posts_per_page' => -1
	);
	$query_args = wp_parse_args($args, $default_args);

	// Define an array of post IDs to display first
	$specific_post_ids = array(133, 138); // Replace with the actual post IDs you want to prioritize

	// Query to get projects with the specific IDs first
	$specific_query_args = array_merge($query_args, array(
			'post__in' => $specific_post_ids, // Query for posts with specific IDs
			'orderby' => 'post__in', // Ensure the order of results matches the order of IDs in the array
	));
	$specific_query = new WP_Query($specific_query_args);

	// Collect IDs of projects with specific IDs
	$exclude_ids = array();
	if ($specific_query->have_posts()) {
			while ($specific_query->have_posts()) {
					$specific_query->the_post();
					$exclude_ids[] = get_the_ID(); // Collect IDs of projects with the specific IDs
			}
	}

	// Query to get all other projects excluding the specific ones
	$remaining_query_args = array_merge($query_args, array(
			'post__not_in' => $exclude_ids
	));
	$remaining_query = new WP_Query($remaining_query_args);

	$html = '';

	// Display projects with the specific IDs
	if ($specific_query->have_posts()) {
			while ($specific_query->have_posts()) {
					$specific_query->the_post();
					$html .= render_project_card(get_the_ID());
			}
	}

	// Display remaining projects
	if ($remaining_query->have_posts()) {
			while ($remaining_query->have_posts()) {
					$remaining_query->the_post();
					$html .= render_project_card(get_the_ID());
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
					console.log(this);

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
			$html = '<div class="">';
			$html .= '<ul>';

			foreach ( $terms as $term ) {
					// Output each term
					$html .= '<li class="pill-outline">';
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


function display_service_skills() {
	// Ensure this is a single project page
	if ( ! is_singular( 'service' ) ) {
			return '<p>This is not a service page.</p>';
	}
	// Get the current post ID
	global $post;
	$post_id = $post->ID;
	// Verify taxonomy and post type
	$taxonomies = get_object_taxonomies( 'service', 'names' );
	if ( ! in_array( 'skill', $taxonomies ) ) {
			return '<p>The taxonomy "skills" is not registered for this post typeeee.</p>';
	}
	// Get terms from the 'features' taxonomy for the current project
	$terms = get_the_terms( $post_id, 'skill' );

	if ( $terms && ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$html = '<div>';
			$html .= '<ul class="pills">';

			foreach ( $terms as $term ) {
					// Output each term
					$html .= '<li class="pill-outline">';
					$html .= esc_html( $term->name );
					$html .= '</li>';
			}

			$html .= '</ul>';
			$html .= '</div>';
	} else {
			$html = '<p>No features found.</p>';
	}
	return $html;
}

add_shortcode( 'service_skills', 'display_service_skills' );


function project_link_button_shortcode() {
	// Get the ACF field value for 'project_link'
	$project_link = get_field('project_link');

	// Check if the project_link is not empty
	if ($project_link) {
			// Return button HTML
			return '<a href="' . esc_url($project_link) . '"
			class="wp-block-button__link has-text-color project-link-btn has-link-color wp-element-button"
			target="_blank"
			rel="noopener noreferrer">Open Project<img src="http://localhost:10018/wp-content/themes/sigurd-child/assets/svgs/open1.svg" /></a>';
	}
}

// Register the shortcode with WordPress
add_shortcode('project_link_button', 'project_link_button_shortcode');


// Register block type
function register_acf_service_image_block() {
	register_block_type('my-plugin/acf-service-image', array(
			'render_callback' => 'render_acf_service_image_block',
	));
}
add_action('init', 'register_acf_service_image_block');

// Block rendering callback
function render_acf_service_image_block($attributes) {
	// Get current post ID
	$post_id = get_the_ID();

	// Get the ACF field
	$service_image = get_field('service_image', $post_id);

	if ($service_image) {
			if (is_array($service_image) && isset($service_image['url'])) {
					return '<img src="' . esc_url($service_image['url']) . '" alt="' . esc_attr($service_image['alt']) . '" />';
			}
	}

	return '<p>No service image found for this post.</p>';
}

// Register the shortcode
function display_acf_service_image_shortcode($atts) {
	$atts = shortcode_atts(array(
			'post_id' => get_the_ID(), // Default to current post ID
	), $atts, 'service_image');

	// Get ACF field for the given post ID
	$service_image = get_field('service_image', $atts['post_id']);

	if ($service_image) {
			if (is_array($service_image) && isset($service_image['url'])) {
					return '<img src="' . esc_url($service_image['url']) . '" alt="' . esc_attr($service_image['alt']) . '" />';
			}
	}

	return '<p>No service image found for post ID: ' . esc_html($atts['post_id']) . '.</p>';
}
add_shortcode('service_image', 'display_acf_service_image_shortcode');
