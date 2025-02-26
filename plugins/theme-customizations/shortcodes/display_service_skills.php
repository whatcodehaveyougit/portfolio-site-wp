<?php

// function display_service_skills() {
// 	// Ensure this is a single project page
// 	if ( ! is_singular( 'service' ) ) {
// 			return '<p>This is not a service page.</p>';
// 	}
// 	// Get the current post ID
// 	global $post;
// 	$post_id = $post->ID;
// 	// Verify taxonomy and post type
// 	$taxonomies = get_object_taxonomies( 'service', 'names' );
// 	if ( ! in_array( 'skill', $taxonomies ) ) {
// 			return '<p>The taxonomy "skills" is not registered for this post typeeee.</p>';
// 	}
// 	// Get terms from the 'features' taxonomy for the current project
// 	$skills = get_the_terms( $post_id, 'skill' );

// 	if ( $skills && ! is_wp_error( $skills ) && ! empty( $skills ) ) {
// 			$html = '<div>';
// 			$html .= '<ul class="pills">';

// 			foreach ( $skills as $skill ) {
// 					// Output each term
// 					$html .= '<li class="pill-outline">';
// 					$html .= esc_html( $skill->name );
// 					$html .= '</li>';
// 			}

// 			$html .= '</ul>';
// 			$html .= '</div>';
// 	} else {
// 			$html = '<p>No features found.</p>';
// 	}
// 	return $html;
// }


function display_service_hero() {
    // Get the current post ID
    $post_id = get_the_ID();

    // Fetch ACF fields
    $service_name = get_field('service_name', $post_id);
    $service_description = get_field('service_description', $post_id);

    // Ensure we have values (fallback to default text if fields are empty)
    $service_name = !empty($service_name) ? esc_html($service_name) : 'Default Service';
    $service_description = !empty($service_description) ? esc_html($service_description) : 'Default Description';

    return '<div class="hero">
                <h1>' . $service_name . '</h1>
                <h4>' . $service_description . '</h4>
            </div>';
}



// function register_display_service_skills() {
//   add_shortcode( 'service_skills', 'display_service_skills' );
// }

// add_action('init', 'register_display_service_skills');



function register_display_service_hero() {
  add_shortcode( 'service_hero', 'display_service_hero' );
}

add_action('init', 'register_display_service_hero');