<?php

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
// 			$html .= '<p>No projects found.</p>';
	}

	wp_reset_postdata();

	return $html;
}
