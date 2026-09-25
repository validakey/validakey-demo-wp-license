<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://validakey.com
 * @since      1.0.0
 *
 * @package    Validakey_Demo_License
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$plugin_slug = 'validakey_demo_license';

delete_option( $plugin_slug . '_validakey_instances' );
delete_option( $plugin_slug . '_validakey_tokens' );
delete_option( $plugin_slug . '_validakey_license_checks' );
delete_option( 'validakey_demo_license_show_gate_banner' );

delete_transient( 'validakey_demo_create_license_last_error' );
delete_transient( $plugin_slug . '_validakey_license_last_error' );
