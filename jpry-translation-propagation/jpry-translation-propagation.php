<?php
/**
 * Plugin Name: JPry Translation Propagation
 * Plugin URI:  http://jeremypry.com
 * Description: Propagate English posts to each Language site.
 * Version:     0.2.0
 * Author:      JPry
 * Author URI:  http://jeremypry.com
 * Donate link: http://jeremypry.com
 * License:     GPLv2
 * Text Domain: jpry-translation-propagation
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 JPry
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using generator-plugin-wp
 */

/**
 * CHANGELOG
 *
 * 0.1.0
 *
 * - Initial plugin creation.
 *
 * 0.2.0
 *
 * - Added new JPry_Async_Delete_Post class.
 * - Added logging to JPry_Wiki_Propagation class.
 * - Added deletion logic to JPry_Wiki_Propagation class.
 */


/**
 * Our autoloader.
 *
 * @since 0.1.0
 *
 * @param string $class The class name to autoload.
 */
function jpry_translation_propagation( $class ) {
	if ( 0 !== strpos( $class, 'JPry_' ) ) {
		return;
	}

	$file_name = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
	$file = __DIR__ . '/includes/' . $file_name;

	if ( file_exists( $file ) ) {
		require_once( $file );
	}
}
spl_autoload_register( 'jpry_translation_propagation' );

define( 'JPRY_TRANSLATION_PROPAGATION', __FILE__ );

$instance = JPry_Translation_Propagation::get_instance();
add_action( 'plugins_loaded', array( $instance, 'do_hooks' ) );
