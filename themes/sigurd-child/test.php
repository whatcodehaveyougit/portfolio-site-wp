<?php register_post_type('my_custom_post',
  array(
    'labels' => array(
      'name' => __('My custom posts', 'textdomain'),
      'singular_name' => __('Custom post', 'textdomain'),
    ),
    'public' => true,
    'publicly_queryable' => true,
    'has_archive' => true,
    'rewrite' => array( 'slug' => 'mypost' ),
    'supports' => array( 'title' ),
    'show_in_rest' => true
  )
);