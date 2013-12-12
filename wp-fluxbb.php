<?php
/**
 * The WordPress FluxBB Bridge Plugin.
 *
 * @package   WPFluxBB
 * @author    Charlie MERLAND <charlie.merland@gmail.com>
 * @license   GPL-3.0+
 * @link      http://charliemerland.me
 * @copyright 2013 Charlie MERLAND
 *
 * @wordpress-plugin
 * Plugin Name:       WPFluxBB
 * Plugin URI:        https://github.com/Askelon/WPFluxBB
 * Description:       Bridge between WordPress and FluxBB
 * Version:           1.0.0
 * Author:            Charlie MERLAND
 * Author URI:        http://charliemerland.me
 * Text Domain:       wp-fluxbb-locale
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/Askelon/WPFluxBB
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-wp-fluxbb.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'WPFluxBB', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPFluxBB', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'WPFluxBB', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-wp-fluxbb-admin.php' );
	add_action( 'plugins_loaded', array( 'WPFluxBB_Admin', 'get_instance' ) );

}
