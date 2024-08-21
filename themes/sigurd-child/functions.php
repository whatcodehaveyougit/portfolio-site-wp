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


// Function to handle the AJAX request (already defined)
function filter_projects_by_taxonomy_ajax() {
	check_ajax_referer( 'filter_nonce', 'nonce' );

	$term_id = intval( $_POST['term_id'] );
	$taxonomy = sanitize_text_field( $_POST['taxonomy'] );

	$args = array(
			'post_type' => 'project',
	);

	// If term_id and taxonomy are provided, filter by them
	if ( !empty($term_id) && !empty($taxonomy) ) {
			$args['tax_query'] = array(
					array(
							'taxonomy' => $taxonomy,
							'field'    => 'term_id',
							'terms'    => $term_id,
					),
			);
	}

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
			echo '<p>No projects found.</p>';
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
							url: "' . admin_url( 'admin-ajax.php' ) . '",
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
							url: "' . admin_url( 'admin-ajax.php' ) . '",
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
