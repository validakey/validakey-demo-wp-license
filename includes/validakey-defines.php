<?php
namespace validakey_demo_license;

if ( ! defined( 'WPINC' ) ) { die; } // If this file is called directly, abort.

/*
 * License-path credentials the Validakey client needs at runtime:
 *   VALIDAKEY_API_UUID, VALIDAKEY_USER_APP_ID, VALIDAKEY_BASE_URL
 * Optional: VALIDAKEY_TIMEOUT, VALIDAKEY_SUBJECT
 *
 * Provide them either:
 *   1) As namespaced constants in this file (uncomment the block below and fill
 *      real values when you ship a product plugin), or
 *   2) As global defines in wp-config.php (typical for this demo so secrets
 *      stay out of the plugin tree). PluginClientFactory accepts both.
 *
 * NEVER place VALIDAKEY_API_PKEY in shipped plugin code. That key is only for
 * automating your Validakey account in your own secure tooling. Token minting
 * never needs it.
 */

/* // EXAMPLE — uncomment and replace with your App / account values:
const VALIDAKEY_API_UUID    = '11111111-1111-1111-1111-111111111111';
const VALIDAKEY_USER_APP_ID = '22222222-2222-2222-2222-222222222222';
const VALIDAKEY_BASE_URL    = 'https://api.validakey.com/v1';
const VALIDAKEY_TIMEOUT     = 30.0;
*/
