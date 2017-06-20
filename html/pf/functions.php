<?php

/**
 * PF Theme functions and definitions
 *
 * When using a child theme (see http://codex.wordpress.org/Theme_Development and
 * http://codex.wordpress.org/Child_Themes), you can override certain functions
 * (those wrapped in a function_exists() call) by defining them first in your child theme's
 * functions.php file. The child theme's functions.php file is included before the parent
 * theme's file, so the child theme functions would be used.
 *
 * @package PF Theme
 * @since 0.1.0
 */

// Useful global constants
define( 'PF_VERSION',      '0.1.0' );
define( 'PF_URL',          get_stylesheet_directory_uri() );
define( 'PF_TEMPLATE_URL', get_template_directory_uri() );
define( 'PF_PATH',         get_template_directory() . '/' );
define( 'PF_INC',          PF_PATH . 'includes/' );
define( 'PF_PREFIX',       '_pf_' );

// Include compartmentalized functions
require_once PF_INC . 'functions/core.php';

// Run the setup functions
PF_Theme\Core\setup();
