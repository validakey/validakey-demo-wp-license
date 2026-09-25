=== Validakey Demo License ===
Contributors: Art Harvey
Donate link: https://validakey.com/
Tags: license, licensing, validakey, monetization, demo
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Demo WordPress plugin: go from boilerplate to a Validakey-licensed product with the validakey-php client.

== Description ==

This plugin demonstrates how little glue is required to license a WordPress plugin with [Validakey](https://validakey.com) using the `validakey/validakey-php` library.

It ships as a near-standard WordPress Plugin Boilerplate layout, then adds:

* `LicenseBootstrap` wiring for handshake, cron revalidation, and admin notices
* A Settings → Validakey Demo screen to request a license (token type, cost, tax) or revoke an existing key
* A **Payment method** section with an embedded Square Web Payments form for the site’s Instance Entity card (priced mints)
* A front-end **license gate demo** banner that calls `LicenseBootstrap::allows()`

The point of the demo is the very short path from empty boilerplate to monetarily licensed software—not a polished product UI.

= Payment method (Instance Entity card) =

Priced tokens require a card on file for the **site** (Instance Entity), not your Validakey account PKey. This demo:

1. Calls `instancePaymentStatus()` (sealed). The reply includes public Square `application_id` / `location_id` — no private key.
2. Wires `Validakey\WordPress\InstancePaymentPanel` (enqueue, AJAX attach, detach, hosted link, render) which uses `InstanceCardForm` for the Square CDN embed.
3. Tokenizes in the browser and posts only a `source_id` to `attachInstanceCard()`.

Card numbers never reach WordPress or Validakey. If Square IDs are missing from status, use **Open hosted payment link** as a fallback.

= License gate demo =

`LicenseBootstrap::allows()` reads the last local license snapshot (no network call on every page). In a real product you wrap premium features the same way:

`if ( ! LicenseBootstrap::allows() ) { /* hide or degrade the feature */ }`

This demo shows that pattern as a site-wide banner (via `wp_body_open`, or fixed to the top of the viewport from `wp_footer` when the theme omits body_open). The banner is teaching material only; it is not required for Validakey to work.

**Turn the banner off** (any one of these):

1. Settings → Validakey Demo → uncheck **Show the front-end license gate banner** → Save
2. In `wp-config.php`: `define( 'VALIDAKEY_DEMO_LICENSE_DISABLE_GATE_BANNER', true );`
3. In code: `add_filter( 'validakey_demo_license_show_gate_banner', '__return_false' );`

== Installation ==

1. Place this folder under `wp-content/plugins/validakey-demo-license/` (or clone and run `composer install` so `vendor/` includes `validakey/validakey-php`).
2. Configure Validakey credentials. Prefer `wp-config.php` globals (keeps secrets out of the plugin tree), for example:

   `define( 'VALIDAKEY_API_UUID', '…' );`
   `define( 'VALIDAKEY_USER_APP_ID', '…' );`
   `define( 'VALIDAKEY_BASE_URL', 'https://api.validakey.com/v1' );`

   Or uncomment the namespaced constants in `includes/validakey-defines.php`. Never ship `VALIDAKEY_API_PKEY` in plugin code; minting does not need it.
3. Activate **Validakey Demo License** under Plugins.
4. Open **Settings → Validakey Demo**, request a license, and use Revoke & Delete Key when you need to mint again.

== Frequently Asked Questions ==

= Where do credentials live? =

In this development setup they usually live in `wp-config.php`. The plugin’s `validakey-defines.php` shows the namespaced constant shape you would ship with a real product (UUID, User App ID, base URL)—still without the account PKey.

= Why does the plugin still look like boilerplate? =

On purpose. The demo emphasizes simple Validakey integration steps, not replacing every wppb stub.

= How do I hide the front-end license banner? =

Uncheck it under Settings → Validakey Demo, or use the `VALIDAKEY_DEMO_LICENSE_DISABLE_GATE_BANNER` constant / `validakey_demo_license_show_gate_banner` filter described under **License gate demo** above.

= Why did Request say I need a payment card? =

That is `ie_card_required`: the vKey has a price and this site has no Instance Entity card yet. Open **Settings → Validakey Demo → Payment method**, save a card (or use the hosted link), then Request again.

== Changelog ==

= 1.0.0 =
* Initial demo: LicenseBootstrap, create-license form, IE Square card embed, revoke panel, license gate banner.
