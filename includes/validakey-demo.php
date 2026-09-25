<?php
namespace validakey_demo_license;

if ( ! defined( 'WPINC' ) ) { die; } // If this file is called directly, abort.

require plugin_dir_path(__FILE__) . '../vendor/autoload.php';
require plugin_dir_path(__FILE__) . 'validakey-defines.php';

use Validakey\Exception\ApiException;
use Validakey\Exception\PaymentRequiredException;
use Validakey\Exception\ValidakeyException;
use Validakey\Request\CreateTokenRequest;
use Validakey\WordPress\LicenseBootstrap;
use Validakey\WordPress\LicensePanel;

const THIS_PLUGIN_NAME = 'validakey_demo_license'; // const is in namespace
const SETTINGS_PAGE = THIS_PLUGIN_NAME; // add_options_page() slug; must match settings_page / redirect_url
const CREATE_LICENSE_ACTION = 'validakey_demo_create_license';
const CREATE_LICENSE_ERROR_TRANSIENT = 'validakey_demo_create_license_last_error';
const GATE_BANNER_OPTION = 'validakey_demo_license_show_gate_banner';
const GATE_BANNER_SETTINGS_ACTION = 'validakey_demo_save_gate_banner';

// =======================================================================================
// BOOTSTRAP LOADING 
function register(){
	LicenseBootstrap::register(array(
	    'plugin_slug' => THIS_PLUGIN_NAME,            // optional; /plugins/{plugin_slug} if omitted
	    'constants_namespace' => __NAMESPACE__,
	    'settings_page' => SETTINGS_PAGE,             // $_GET['page'] for handleRequest scoping
	    'redirect_url' => admin_url('options-general.php?page='.SETTINGS_PAGE),
	    'token_spec' => CreateTokenRequest::free(),
	    'admin_notice_message' => __( 'Validakey Demo has no license. Open Settings → Validakey Demo to request one.', 'validakey-demo-license' ),
	));
}
add_action('init','validakey_demo_license\register'); // register at the WP 'init' hook.
// =======================================================================================



// =======================================================================================
// ADD AN ADMIN SETTINGS MENU 
function my_plugin_menu() {
	add_options_page(
		__( 'Validakey Demo License', 'validakey-demo-license' ),
		__( 'Validakey Demo', 'validakey-demo-license' ),
		'manage_options',
		SETTINGS_PAGE,
		'validakey_demo_license\my_plugin_options'
	);
}

/** DISPLAY THE SETTINGS OPTIONS */
function my_plugin_options() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'validakey-demo-license' ) );
	}
	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Validakey Demo License', 'validakey-demo-license' ) . '</h1>';
	settings_errors();
	render_gate_banner_settings();

	if ( LicenseBootstrap::isConfigured() && ! LicenseBootstrap::allows() ) {
		render_create_license_form();
	} else {
		LicenseBootstrap::renderPanel();
	}

	echo '</div>';
}
add_action( 'admin_menu', 'validakey_demo_license\my_plugin_menu' );

/**
 * Whether the front-end license-gate banner should render.
 *
 * Disable via Settings checkbox, wp-config
 * `define( 'VALIDAKEY_DEMO_LICENSE_DISABLE_GATE_BANNER', true );`, or
 * `add_filter( 'validakey_demo_license_show_gate_banner', '__return_false' )`.
 */
function should_show_gate_banner(): bool {
	if ( defined( 'VALIDAKEY_DEMO_LICENSE_DISABLE_GATE_BANNER' ) && VALIDAKEY_DEMO_LICENSE_DISABLE_GATE_BANNER ) {
		return false;
	}

	$enabled = '0' !== (string) get_option( GATE_BANNER_OPTION, '1' );

	return (bool) apply_filters( 'validakey_demo_license_show_gate_banner', $enabled );
}

/**
 * Settings UI: toggle the front-end LicenseBootstrap::allows() demo banner.
 */
function render_gate_banner_settings(): void {
	$locked_off = defined( 'VALIDAKEY_DEMO_LICENSE_DISABLE_GATE_BANNER' ) && VALIDAKEY_DEMO_LICENSE_DISABLE_GATE_BANNER;
	$option_on  = '0' !== (string) get_option( GATE_BANNER_OPTION, '1' );
	$checked    = $option_on && ! $locked_off;

	echo '<div class="validakey-demo-gate-settings" style="margin:1em 0 1.5em;">';
	echo '<h2 class="title">' . esc_html__( 'License gate demo', 'validakey-demo-license' ) . '</h2>';
	echo '<p>' . esc_html__(
		'The front-end banner is a teaching sample: it calls LicenseBootstrap::allows() and shows licensed vs unlicensed state. Copy that pattern to gate real features (shortcodes, REST, admin UI). It is not part of Validakey itself.',
		'validakey-demo-license'
	) . '</p>';

	if ( $locked_off ) {
		echo '<p><em>' . esc_html__(
			'Currently forced off by VALIDAKEY_DEMO_LICENSE_DISABLE_GATE_BANNER in wp-config.php.',
			'validakey-demo-license'
		) . '</em></p>';
	}

	echo '<form method="post">';
	wp_nonce_field( GATE_BANNER_SETTINGS_ACTION, GATE_BANNER_SETTINGS_ACTION . '_nonce' );
	echo '<label for="validakey_demo_show_gate_banner">';
	printf(
		'<input type="checkbox" name="validakey_demo_show_gate_banner" id="validakey_demo_show_gate_banner" value="1"%s%s /> ',
		checked( $checked, true, false ),
		disabled( $locked_off, true, false )
	);
	echo esc_html__( 'Show the front-end license gate banner', 'validakey-demo-license' );
	echo '</label> ';
	submit_button( __( 'Save', 'validakey-demo-license' ), 'secondary', GATE_BANNER_SETTINGS_ACTION, false );
	echo '</form></div>';
}

/**
 * Persist the gate-banner checkbox.
 */
function handle_gate_banner_settings(): void {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! isset( $_POST[ GATE_BANNER_SETTINGS_ACTION ] ) ) {
		return;
	}
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
	if ( SETTINGS_PAGE !== $page ) {
		return;
	}
	$nonce_field = GATE_BANNER_SETTINGS_ACTION . '_nonce';
	if (
		! isset( $_POST[ $nonce_field ] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( (string) $_POST[ $nonce_field ] ) ),
			GATE_BANNER_SETTINGS_ACTION
		)
	) {
		return;
	}

	if ( defined( 'VALIDAKEY_DEMO_LICENSE_DISABLE_GATE_BANNER' ) && VALIDAKEY_DEMO_LICENSE_DISABLE_GATE_BANNER ) {
		return;
	}

	$show = isset( $_POST['validakey_demo_show_gate_banner'] ) ? '1' : '0';
	update_option( GATE_BANNER_OPTION, $show, false );

	add_settings_error(
		'validakey_license_messages',
		'validakey_demo_gate_banner_saved',
		__( 'License gate demo setting saved.', 'validakey-demo-license' ),
		'success'
	);
	set_transient( 'settings_errors', get_settings_errors(), 30 );
	wp_safe_redirect( add_query_arg( 'settings-updated', 'true', admin_url( 'options-general.php?page=' . SETTINGS_PAGE ) ) );
	exit;
}
add_action( 'admin_init', 'validakey_demo_license\handle_gate_banner_settings' );

/**
 * Request form shown when the site has no valid grant.
 */
function render_create_license_form() {
	$action = CREATE_LICENSE_ACTION;
	$nonce_field = $action . '_nonce';

	echo '<div class="validakey-demo-create-license">';
	echo '<h2 class="title">' . esc_html__( 'Request License', 'validakey-demo-license' ) . '</h2>';

	$last_error = get_transient( CREATE_LICENSE_ERROR_TRANSIENT );
	if ( is_string( $last_error ) && '' !== $last_error ) {
		echo '<div class="notice notice-error inline"><p>' . esc_html( $last_error ) . '</p></div>';
	}

	echo '<form method="post" class="validakey-demo-create-license-form">';
	wp_nonce_field( $action, $nonce_field );

	echo '<table class="form-table" role="presentation"><tbody>';

	echo '<tr><th scope="row"><label for="validakey_demo_token_type">'
		. esc_html__( 'Token type', 'validakey-demo-license' ) . '</label></th><td>';
	echo '<select name="validakey_demo_token_type" id="validakey_demo_token_type">';
	$token_types = array(
		'1' => __( '1 — Transactional (never expires)', 'validakey-demo-license' ),
		'2' => __( '2 — Limited (optional time and/or uses)', 'validakey-demo-license' ),
		'3' => __( '3 — Subscription time', 'validakey-demo-license' ),
		'4' => __( '4 — Subscription use', 'validakey-demo-license' ),
	);
	foreach ( $token_types as $value => $label ) {
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $value ),
			selected( $value, '1', false ),
			esc_html( $label )
		);
	}
	echo '</select></td></tr>';

	echo '<tr class="validakey-demo-field-duration"><th scope="row"><label for="validakey_demo_duration">'
		. esc_html__( 'Duration (seconds)', 'validakey-demo-license' ) . '</label></th><td>';
	echo '<input type="number" class="regular-text" name="validakey_demo_duration" id="validakey_demo_duration" value="" min="1" step="1" />';
	echo '<p class="description validakey-demo-duration-hint-limited">'
		. esc_html__( 'Optional for Limited. Leave blank for no time limit.', 'validakey-demo-license' )
		. '</p>';
	echo '</td></tr>';

	echo '<tr class="validakey-demo-field-uses"><th scope="row"><label for="validakey_demo_uses">'
		. esc_html__( 'Uses', 'validakey-demo-license' ) . '</label></th><td>';
	echo '<input type="number" class="regular-text" name="validakey_demo_uses" id="validakey_demo_uses" value="" min="1" step="1" />';
	echo '<p class="description validakey-demo-uses-hint-limited">'
		. esc_html__( 'Optional for Limited. Leave blank for no use quota. Set duration and/or uses.', 'validakey-demo-license' )
		. '</p>';
	echo '</td></tr>';

	echo '<tr class="validakey-demo-field-recurrence"><th scope="row"><label for="validakey_demo_recurrence">'
		. esc_html__( 'Recurrence', 'validakey-demo-license' ) . '</label></th><td>';
	echo '<select name="validakey_demo_recurrence" id="validakey_demo_recurrence">';
	foreach ( array( 'daily', 'weekly', 'monthly', 'yearly' ) as $recurrence ) {
		printf(
			'<option value="%1$s"%2$s>%1$s</option>',
			esc_attr( $recurrence ),
			selected( $recurrence, 'monthly', false )
		);
	}
	echo '</select></td></tr>';

	echo '<tr class="validakey-demo-field-cost"><th scope="row"><label for="validakey_demo_cost_usd">'
		. esc_html__( 'Cost (USD)', 'validakey-demo-license' ) . '</label></th><td>';
	echo '<input type="number" class="regular-text" name="validakey_demo_cost_usd" id="validakey_demo_cost_usd" value="" min="0" step="0.01" />';
	echo '<p class="description">' . esc_html__( 'Leave blank for a free token.', 'validakey-demo-license' ) . '</p>';
	echo '</td></tr>';

	echo '<tr class="validakey-demo-field-tax"><th scope="row"><label for="validakey_demo_tax_usd">'
		. esc_html__( 'Tax (USD)', 'validakey-demo-license' ) . '</label></th><td>';
	echo '<input type="number" class="regular-text" name="validakey_demo_tax_usd" id="validakey_demo_tax_usd" value="" min="0" step="0.01" />';
	echo '</td></tr>';

	echo '<tr class="validakey-demo-field-total"><th scope="row">'
		. esc_html__( 'Total (USD)', 'validakey-demo-license' ) . '</th><td>';
	echo '<input type="text" class="regular-text" id="validakey_demo_total_usd" value="" readonly />';
	echo '<p class="description">' . esc_html__( 'Cost + tax (server also computes total).', 'validakey-demo-license' ) . '</p>';
	echo '</td></tr>';

	echo '</tbody></table>';

	submit_button( __( 'Request', 'validakey-demo-license' ), 'primary', $action, false );
	echo '</form></div>';
}

/**
 * Map posted create-form fields to a CreateTokenRequest.
 *
 * @throws \InvalidArgumentException When Limited has neither duration nor uses.
 */
function build_create_token_request_from_post(): CreateTokenRequest {
	$token_type = isset( $_POST['validakey_demo_token_type'] )
		? sanitize_text_field( wp_unslash( (string) $_POST['validakey_demo_token_type'] ) )
		: '1';

	$duration = parse_optional_positive_int( $_POST['validakey_demo_duration'] ?? null );
	$uses = parse_optional_positive_int( $_POST['validakey_demo_uses'] ?? null );
	$recurrence = isset( $_POST['validakey_demo_recurrence'] )
		? sanitize_text_field( wp_unslash( (string) $_POST['validakey_demo_recurrence'] ) )
		: 'monthly';
	if ( ! in_array( $recurrence, array( 'daily', 'weekly', 'monthly', 'yearly' ), true ) ) {
		$recurrence = 'monthly';
	}

	$cost_usd = parse_optional_usd( $_POST['validakey_demo_cost_usd'] ?? null );
	$tax_usd = parse_optional_usd( $_POST['validakey_demo_tax_usd'] ?? null );

	return match ( $token_type ) {
		'2' => ( static function () use ( $duration, $uses, $cost_usd, $tax_usd ): CreateTokenRequest {
			if ( null === $duration && null === $uses ) {
				throw new \InvalidArgumentException(
					__( 'Limited tokens need a duration, a use quota, or both.', 'validakey-demo-license' )
				);
			}

			return new CreateTokenRequest(
				duration: $duration,
				uses: $uses,
				costUsd: $cost_usd,
				taxUsd: $tax_usd,
			);
		} )(),
		'3' => new CreateTokenRequest(
			duration: $duration ?? 3600,
			recurrence: $recurrence,
			autoRenew: true,
			costUsd: $cost_usd,
			taxUsd: $tax_usd,
		),
		'4' => new CreateTokenRequest(
			uses: $uses ?? 1,
			recurrence: $recurrence,
			autoRenew: true,
			costUsd: $cost_usd,
			taxUsd: $tax_usd,
		),
		default => ( null === $cost_usd && null === $tax_usd )
			? CreateTokenRequest::free()
			: new CreateTokenRequest(
				duration: 0,
				costUsd: $cost_usd,
				taxUsd: $tax_usd,
				noExpiry: true,
			),
	};
}

/**
 * @param mixed $raw
 */
function parse_optional_positive_int( $raw ): ?int {
	if ( null === $raw ) {
		return null;
	}
	$value = trim( (string) wp_unslash( $raw ) );
	if ( '' === $value || ! is_numeric( $value ) ) {
		return null;
	}
	$int = (int) $value;

	return $int >= 1 ? $int : null;
}

/**
 * @param mixed $raw
 */
function parse_optional_usd( $raw ): ?float {
	if ( null === $raw ) {
		return null;
	}
	$value = trim( (string) wp_unslash( $raw ) );
	if ( '' === $value ) {
		return null;
	}
	if ( ! is_numeric( $value ) ) {
		return null;
	}

	return (float) $value;
}

/**
 * Handle the demo create-license POST before headers are sent.
 */
function handle_create_license_request() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_POST[ CREATE_LICENSE_ACTION ] ) ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
	if ( SETTINGS_PAGE !== $page ) {
		return;
	}

	$nonce_field = CREATE_LICENSE_ACTION . '_nonce';
	if (
		! isset( $_POST[ $nonce_field ] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( (string) $_POST[ $nonce_field ] ) ),
			CREATE_LICENSE_ACTION
		)
	) {
		return;
	}

	$redirect = admin_url( 'options-general.php?page=' . SETTINGS_PAGE );

	if ( ! LicenseBootstrap::isConfigured() ) {
		add_settings_error(
			'validakey_license_messages',
			'validakey_demo_unconfigured',
			__( 'Validakey is not configured.', 'validakey-demo-license' ),
			'error'
		);
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( add_query_arg( 'settings-updated', 'true', $redirect ) );
		exit;
	}

	try {
		$spec = build_create_token_request_from_post();
		LicenseBootstrap::license()->request( $spec );
		delete_transient( CREATE_LICENSE_ERROR_TRANSIENT );
		add_settings_error(
			'validakey_license_messages',
			'validakey_demo_license_granted',
			__( 'License granted.', 'validakey-demo-license' ),
			'success'
		);
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( add_query_arg( 'settings-updated', 'true', $redirect ) );
		exit;
	} catch ( \InvalidArgumentException $e ) {
		remember_create_error( $e->getMessage() );
	} catch ( PaymentRequiredException $e ) {
		remember_create_error( LicensePanel::paymentNotice( $e ) );
	} catch ( ApiException $e ) {
		remember_create_error( LicensePanel::apiNotice( $e ) );
	} catch ( ValidakeyException $e ) {
		remember_create_error( $e->getMessage() );
	}

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_init', 'validakey_demo_license\handle_create_license_request' );

function remember_create_error( string $message ): void {
	$ttl = defined( 'DAY_IN_SECONDS' ) ? (int) DAY_IN_SECONDS : 86400;
	set_transient( CREATE_LICENSE_ERROR_TRANSIENT, $message, $ttl );
}

/**
 * Load create-form assets only on the Validakey Demo settings page.
 */
function enqueue_create_license_assets( string $hook_suffix ): void {
	if ( 'settings_page_' . SETTINGS_PAGE !== $hook_suffix ) {
		return;
	}

	$admin_dir = plugin_dir_url( dirname( __FILE__ ) ) . 'admin/';
	$version = defined( 'VALIDAKEY_DEMO_LICENSE_VERSION' ) ? VALIDAKEY_DEMO_LICENSE_VERSION : '1.0.0';

	wp_enqueue_style(
		'validakey-demo-create-license',
		$admin_dir . 'css/validakey-demo-license-admin.css',
		array(),
		$version
	);
	wp_enqueue_script(
		'validakey-demo-create-license',
		$admin_dir . 'js/validakey-demo-license-admin.js',
		array( 'jquery' ),
		$version,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'validakey_demo_license\enqueue_create_license_assets' );

/**
 * Front-end feature-gate demo.
 *
 * Real products would wrap premium shortcodes, REST routes, or admin UI the
 * same way: trust LicenseBootstrap::allows() (local snapshot, no network).
 *
 * Prefer wp_body_open (top of <body>). Themes that omit that hook only fire
 * wp_footer — then the banner is position:fixed to the top of the viewport
 * so it still reads as a header gate, not a footer notice.
 */
function render_license_gate_banner(): void {
	static $printed = false;
	if ( $printed || is_admin() || ! should_show_gate_banner() ) {
		return;
	}
	$printed = true;

	$settings_url = admin_url( 'options-general.php?page=' . SETTINGS_PAGE );
	$from_footer  = ( 'wp_footer' === current_filter() );

	if ( ! LicenseBootstrap::isConfigured() ) {
		$status = esc_html__( 'Validakey Demo is not configured (missing license-path constants).', 'validakey-demo-license' );
		$class  = 'validakey-demo-gate validakey-demo-gate--unconfigured';
	} elseif ( LicenseBootstrap::allows() ) {
		$status = esc_html__( 'Validakey Demo Plugin is licensed — gated features may run.', 'validakey-demo-license' );
		$class  = 'validakey-demo-gate validakey-demo-gate--licensed';
	} else {
		$status = esc_html__( 'Validakey Demo Plugin has no license.', 'validakey-demo-license' )
			. ' <a href="' . esc_url( $settings_url ) . '">'
			. esc_html__( 'Request one in Settings', 'validakey-demo-license' )
			. '</a>.';
		$class = 'validakey-demo-gate validakey-demo-gate--unlicensed';
	}

	$style = 'padding:0.5rem 1rem;margin:0;font:14px/1.4 sans-serif;border-bottom:1px solid #c3c4c7;background:#fff;color:#1d2327;';
	if ( $from_footer ) {
		$style .= 'position:fixed;top:0;left:0;right:0;z-index:100000;';
	}

	echo '<div class="' . esc_attr( $class ) . '" role="status" style="' . esc_attr( $style ) . '">';
	echo '<strong>' . esc_html__( 'License gate demo:', 'validakey-demo-license' ) . '</strong> ' . $status;
	echo '</div>';
}
add_action( 'wp_body_open', 'validakey_demo_license\render_license_gate_banner', 5 );
add_action( 'wp_footer', 'validakey_demo_license\render_license_gate_banner', 5 );
