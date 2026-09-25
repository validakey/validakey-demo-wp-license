<?php

/**
 * Fired during plugin deactivation.
 *
 * @link       https://validakey.com
 * @since      1.0.0
 *
 * @package    Validakey_Demo_License
 * @subpackage Validakey_Demo_License/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * Clears Validakey license revalidation cron for this plugin slug.
 *
 * @since      1.0.0
 * @package    Validakey_Demo_License
 * @subpackage Validakey_Demo_License/includes
 * @author     Art Harvey <hello@validakey.com>
 */
class Validakey_Demo_License_Deactivator {

	/**
	 * Unschedule Validakey license revalidation for this plugin.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		$autoload = plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';
		if ( is_readable( $autoload ) ) {
			require_once $autoload;
		}

		if ( class_exists( '\Validakey\WordPress\LicenseBootstrap' ) ) {
			\Validakey\WordPress\LicenseBootstrap::deactivate( 'validakey_demo_license' );
		}
	}

}
