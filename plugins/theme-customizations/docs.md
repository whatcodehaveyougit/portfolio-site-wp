3. WordPress Initialization Process:
WordPress loads its core and third-party code in stages:
Core WordPress files are loaded.
Plugins are loaded.
Themes are loaded.
Actions like init, wp_loaded, or plugins_loaded are fired.
If your shortcode registration happens before key parts of WordPress are initialized, it may not behave as expected. Using a wrapper function attached to an appropriate action ensures that your shortcode is registered at the right moment in the lifecycle.