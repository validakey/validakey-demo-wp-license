<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://validakey.com
 * @since      1.0.0
 *
 * @package    Validakey_Demo_License
 * @subpackage Validakey_Demo_License/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Validakey_Demo_License
 * @subpackage Validakey_Demo_License/includes
 * @author     Art Harvey <hello@validakey.com>
 */
class Validakey_Demo_License_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'validakey-demo-license',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
