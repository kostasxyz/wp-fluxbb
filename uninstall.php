<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   WPFluxBB
 * @author    Charlie MERLAND <charlie.merland@gmail.com>
 * @license   GPL-3.0+
 * @link      http://charliemerland.me
 * @copyright 2013 Charlie MERLAND
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// @TODO: Define uninstall functionality here