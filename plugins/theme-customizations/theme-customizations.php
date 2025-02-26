<?php
/**
 * Plugin Name: Theme Customizations
 * Plugin URI: https://yourwebsite.com
 * Description: A plugin for SW's child theme
 * Version: 1.0
 * Author: Sigurd Watt
 * License: GPL2
 */

// Include the separated files
require_once plugin_dir_path( __FILE__ ) . '/shortcodes/display_project_features.php';
require_once plugin_dir_path( __FILE__ ) . '/shortcodes/display_service_skills.php';
require_once plugin_dir_path( __FILE__ ) . '/shortcodes/filter_projects_by_taxonomy.php';
require_once plugin_dir_path( __FILE__ ) . '/shortcodes/project_link_button.php';



require_once plugin_dir_path( __FILE__ ) . '/ajax/filter-projects-by-taxonmy.php';

