<?php

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

// Wrapper function to register the shortcode
function register_display_project_features_shortcode() {
  add_shortcode( 'project_features', 'display_project_features' );
}

// Hook into WordPress's init action to register the shortcode after WordPress has loaded
add_action('init', 'register_display_project_features_shortcode');