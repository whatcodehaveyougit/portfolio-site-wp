<?php
include_once plugin_dir_path(__FILE__) . '../utils/display_all_projects.php';

function return_all_taxonomys_for_post_type( $post_type) {
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




function filter_projects_by_taxonomy_shortcode() {
	// Get the taxonomy terms
	$taxonomy_pills = return_all_taxonomys_for_post_type('project');

	// Create the container for the list of taxonomy terms - these will display as pill
	$html = '<div class="taxonomys-container">';
	$html .= $taxonomy_pills;
	$html .= '</div>';

	// Display all posts by default
	$html .= '<div class="projects-container">';
	$html .= display_all_projects(); // Use the function to display all projects
	$html .= '</div>';

	// Include the AJAX script with animations and active class management

	return $html;
}


// Wrapper function to register the shortcode
function register_filter_projects_by_taxonomy_shortcode() {
	add_shortcode('filter_projects', 'filter_projects_by_taxonomy_shortcode');

}

// Hook into WordPress's init action to register the shortcode after WordPress has loaded
add_action('init', 'register_filter_projects_by_taxonomy_shortcode');