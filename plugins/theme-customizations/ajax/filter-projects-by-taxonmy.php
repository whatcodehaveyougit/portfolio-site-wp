<?php

include_once plugin_dir_path(__FILE__) . '../utils/display_all_projects.php';

function filter_projects_by_taxonomy_ajax() {

  // This checks if the AJAX request is valid using a nonce (filter_nonce). A nonce is a security token used to verify that the request
  // is coming from an authorized source, preventing CSRF (Cross-Site Request Forgery) attacks.
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

// Ajax Actions

add_action('wp_ajax_filter_projects_by_taxonomy', 'filter_projects_by_taxonomy_ajax');
add_action('wp_ajax_nopriv_filter_projects_by_taxonomy', 'filter_projects_by_taxonomy_ajax');