<?php

function project_link_button_shortcode() {
	// Get the ACF field value for 'project_link'
	$project_link = get_field('project_link');

	// Check if the project_link is not empty
	if ($project_link) {
			// Return button HTML
			return '<a href="' . esc_url($project_link) . '"
			class="wp-block-button__link has-text-color project-link-btn has-link-color wp-element-button"
			target="_blank"
			rel="noopener noreferrer">Open Project<img src="https://www.sigurdwatt.com/wp-content/themes/sigurd-child/assets/svgs/open1.svg" /></a>';
	}
}


// Wrapper function to register the shortcode
function register_project_link_button_shortcode() {
	add_shortcode('project_link_button', 'project_link_button_shortcode');
}

// Hook into WordPress's init action to register the shortcode after WordPress has loaded
add_action('init', 'register_project_link_button_shortcode');