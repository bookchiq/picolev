<?php
/**
 * Plugin Name: Picolev
 * Description: Productivity through public accountability
 * Version:     0.1.0
 * Author:      Sarah Lewis
 * Author URI:  http://wpmoxie.com/
 * License:     GPLv2+
 * Text Domain: picolev
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 Sarah Lewis (email : sarah@wpmoxie.com)
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
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

// Useful global constants
define( 'PICOLEV_VERSION', '0.1.0' );
define( 'PICOLEV_URL',     plugin_dir_url( __FILE__ ) );
define( 'PICOLEV_PATH',    dirname( __FILE__ ) . '/' );

/**
 * Default initialization for the plugin:
 * - Registers the default textdomain.
 */
function picolev_init() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'picolev' );
	load_textdomain( 'picolev', WP_LANG_DIR . '/picolev/picolev-' . $locale . '.mo' );
	load_plugin_textdomain( 'picolev', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/**
 * Activate the plugin
 */
function picolev_activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	picolev_init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'picolev_activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function picolev_deactivate() {

}
register_deactivation_hook( __FILE__, 'picolev_deactivate' );

// Wireup actions
add_action( 'init', 'picolev_init' );

// Wireup filters

// Wireup shortcodes
