<?php


add_action( 'wp_enqueue_scripts', 'twentytwentyfour_child_scripts' );
function twentytwentyfour_child_scripts() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
  wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ) );

	// wp_enqueue_style( 'minified-child-theme-css', get_stylesheet_directory_uri() . '/dist/css/style.css', array(), 1.4 );
	// wp_enqueue_script('minified-child-theme-js',  get_stylesheet_directory_uri() . '/dist/js/scripts.js', [], 1.0, true);

	wp_enqueue_style( 'parcel', get_stylesheet_directory_uri() . '/dist/styles/style.css', array(), '1.0' );
	wp_enqueue_script( 'parcel', get_stylesheet_directory_uri() . '/dist/scripts/scripts.js', array(), '1.0', true );
}

function enqueue_taxonomy_filter_script() {
	// Localize the script with admin-ajax.php and a nonce
	wp_localize_script( 'taxonomy-filter', 'ajax_obj', array(
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


function display_all_projects() {
	$args = array(
			'post_type' => 'project',
			'posts_per_page' => -1 // Display all posts
	);

	$query = new WP_Query($args);
	$html = '';

	if ($query->have_posts()) {
			while ($query->have_posts()) {
					$query->the_post();
					$thumbnail_html = get_the_post_thumbnail(get_the_ID(), 'medium'); // Get the thumbnail HTML
					$title = get_the_title();
					$escaped_title = esc_attr($title);
					$permalink = get_the_permalink(); // Get the URL of the project

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



// function modify_navigation_block_output($block_content, $block) {
// 	// Check if the block is a navigation block
// 	// if ($block['blockName'] === 'core/navigation' && $block['attrs']['className'] === 'primary-nav--mobile') {
// 	 if ($block['blockName'] === 'core/navigation') {
// 			// Convert block attributes to HTML
// 			$block_attributes = '';
// 			if (!empty($block['attrs']) && is_array($block['attrs'])) {
// 					foreach ($block['attrs'] as $attr_key => $attr_value) {
// 							$block_attributes .= ' ' . esc_attr($attr_key) . '="' . esc_attr($attr_value) . '"';
// 					}
// 			}

// 		 // Your custom HTML
// 		 $custom_html = '<div class="mobile-menu-top-bar">
// 		 <div></div>
// 		 <button aria-label="Close menu" class="wp-block-navigation__responsive-container-close" data-wp-on--click="actions.closeMenuOnClick" style="display: block;">
// 		 <img src="https://echo-3.co.uk/wp-content/uploads/2024/05/close-icon-mobile-menu.webp"/>
// 		 </button>
// 		 </div>';

// 		 // Find the position of the first occurrence of <ul> tag
// 		 $ul_position = strpos($block_content, '<ul');

// 		 // Insert custom HTML before the <ul> tag
// 		 if ($ul_position !== false) {
// 				 $block_content = substr_replace($block_content, $custom_html, $ul_position, 0);
// 		 } else {
// 				 // Log if <ul> tag is not found
// 				 error_log('Navigation Block: <ul> tag not found');
// 		 }
// 	}

// 	return $block_content;
// }
// add_filter('render_block', 'modify_navigation_block_output', 10, 2);

// function register_project_features_taxonomy() {
// 	// Add new taxonomy, make it hierarchical like categories
// 	$labels = array(
// 			'name'              => _x('Features', 'taxonomy general name', 'textdomain'),
// 			'singular_name'     => _x('Feature', 'taxonomy singular name', 'textdomain'),
// 			'search_items'      => __('Search Features', 'textdomain'),
// 			'all_items'         => __('All Features', 'textdomain'),
// 			'parent_item'       => __('Parent Feature', 'textdomain'),
// 			'parent_item_colon' => __('Parent Feature:', 'textdomain'),
// 			'edit_item'         => __('Edit Feature', 'textdomain'),
// 			'update_item'       => __('Update Feature', 'textdomain'),
// 			'add_new_item'      => __('Add New Feature', 'textdomain'),
// 			'new_item_name'     => __('New Feature Name', 'textdomain'),
// 			'menu_name'         => __('Features', 'textdomain'),
// 	);

// 	$args = array(
// 			'hierarchical'      => true, // Make it hierarchical (like categories)
// 			'labels'            => $labels,
// 			'show_ui'           => true,
// 			'show_admin_column' => true,
// 			'query_var'         => true,
// 			'rewrite'           => array('slug' => 'features'),
// 	);

// 	// Register the taxonomy for the 'project' post type
// 	register_taxonomy('features', array('project'), $args);
// }

// add_action('init', 'register_project_features_taxonomy');




// function display_project_features_shortcode($atts) {
// 	// Ensure the global $post object is available
// 	global $post;

// 	// Debugging: Check if the post object is available
// 	if (empty($post)) {
// 			return '<p>No global post object available.</p>';
// 	}

// 	// Debugging: Output the post type
// 	$output = '<p>Post Type: ' . get_post_type($post) . '</p>';

// 	// Verify that we're inside a project post type
// 	if (get_post_type($post) != 'project') {
// 			return '<p>This shortcode is only applicable to the project post type.</p>';
// 	}

// 	// Define the taxonomy you want to display
// 	$taxonomy = 'features'; // Update to your actual taxonomy slug

// 	// Initialize output
// 	$output .= '<p>Displaying taxonomy terms for: ' . esc_html($taxonomy) . '</p>';

// 	// Get terms for the current post
// 	$terms = wp_get_post_terms($post->ID, $taxonomy);

// 	// Check if terms is a WP_Error object
// 	if (is_wp_error($terms)) {
// 			$error_message = $terms->get_error_message();
// 			$output .= '<p>Error fetching terms for taxonomy: ' . esc_html($taxonomy) . ' - ' . esc_html($error_message) . '</p>';
// 			return $output; // Return the error message and stop execution
// 	}

// 	// Check if there are terms available
// 	if (!empty($terms)) {
// 			// Get the taxonomy object to retrieve its labels
// 			$taxonomy_obj = get_taxonomy($taxonomy);
// 			var_export($taxonomy_obj);
// 			$output .= '<h3>' . esc_html($taxonomy_obj->labels->singular_name) . '</h3>';
// 			$output .= '<ul class="project-taxonomy-list">';

// 			// Loop through each term and display it
// 			foreach ($terms as $term) {
// 					$output .= '<li>' . esc_html($term->name) . '</li>';
// 			}

// 			$output .= '</ul>';
// 	} else {
// 			$output .= '<p>No terms found for taxonomy: ' . esc_html($taxonomy) . '</p>';
// 	}

// 	return $output;
// }

// // Register the shortcode with WordPress
// add_shortcode('project_features', 'display_project_features_shortcode');
