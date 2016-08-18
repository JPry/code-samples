<?php
/**
 * Plugin Name: JPry Autoloader
 * Description: Autoload some classes in mu-plugins
 * Version: 1.0
 * Author: Jeremy Pry
 * Author URI: http://jeremypry.com/
 * License: GPL2
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	die( "You can't do anything by accessing this file directly." );
}

/**
 * An autoloader for mu-plugins classes.
 *
 * @param string $class The class name to autoload.
 */
function jpry_autoloader( $class ) {
	// Only attempt to load classes that are prefixed with 'JPry_'.
	if ( 'JPry_' !== substr( $class, 0, 5 ) ) {
		return;
	}

	$file = __DIR__ . "/autoloaded_classes/{$class}.php";
	if ( file_exists( $file ) ) {
		require_once( $file );
	}
}
spl_autoload_register( 'jpry_autoloader' );
