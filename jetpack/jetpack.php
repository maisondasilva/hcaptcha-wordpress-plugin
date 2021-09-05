<?php
/**
 * Jetpack form file.
 *
 * @package hcaptcha-wp
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	// @codeCoverageIgnoreStart
	exit;
	// @codeCoverageIgnoreEnd
}

if ( ! function_exists( 'hcap_hcaptcha_jetpack_form' ) ) {

	/**
	 * Add hCaptcha to Jetpack contact form.
	 *
	 * @param string $content Content.
	 *
	 * @return string|string[]|null
	 */
	function hcap_hcaptcha_jetpack_form( $content ) {
		// Jetpack classic form.
		$content = preg_replace_callback(
			'~(\[contact-form(?:[\s\S]*)?][\s\S]*)(\[/contact-form])~U',
			'hcaptcha_jetpack_cf_classic_callback',
			$content
		);

		// Jetpack block form.
		return preg_replace_callback(
			'~(?:<form (?:[\s\S]*)?wp-block-jetpack-contact-form[\s\S]*)?(<button [\s\S]*type="submit"[\s\S]*</button>)[\s\S]*</form>~U',
			'hcaptcha_jetpack_cf_block_callback',
			$content
		);
	}
}

add_filter( 'the_content', 'hcap_hcaptcha_jetpack_form' );
add_filter( 'widget_text', 'hcap_hcaptcha_jetpack_form', 0 );

add_filter( 'widget_text', 'shortcode_unautop' );
add_filter( 'widget_text', 'do_shortcode' );

if ( ! function_exists( 'hcaptcha_jetpack_cf_classic_callback' ) ) {

	/**
	 * Add hCaptcha shortcode to the provided shortcode for Jetpack classic contact form.
	 *
	 * @param array $matches Matches.
	 *
	 * @return string
	 */
	function hcaptcha_jetpack_cf_classic_callback( $matches ) {
		if ( preg_match( '~\[hcaptcha]~', $matches[0] ) ) {
			return (
				$matches[0] .
				wp_nonce_field( 'hcaptcha_jetpack', 'hcaptcha_jetpack_nonce', true, false )
			);
		}

		return (
			$matches[1] . '[hcaptcha]' .
			wp_nonce_field( 'hcaptcha_jetpack', 'hcaptcha_jetpack_nonce', true, false ) .
			$matches[2]
		);
	}
}

if ( ! function_exists( 'hcaptcha_jetpack_cf_block_callback' ) ) {

	/**
	 * Add hCaptcha shortcode to the provided shortcode for Jetpack block contact form.
	 *
	 * @param array $matches Matches.
	 *
	 * @return string
	 */
	function hcaptcha_jetpack_cf_block_callback( $matches ) {
		$replace = $matches[1] . wp_nonce_field( 'hcaptcha_jetpack', 'hcaptcha_jetpack_nonce', true, false );

		if ( ! preg_match( '~\[hcaptcha]~', $matches[0] ) ) {
			$replace = '[hcaptcha]' . $replace;
		}

		return str_replace(
			$matches[1],
			$replace,
			$matches[0]
		);
	}
}

if ( ! function_exists( 'hcap_hcaptcha_jetpack_verify' ) ) {

	/**
	 * Verify hCaptcha answer from the Jetpack Contact Form.
	 *
	 * @param bool $is_spam Is spam.
	 *
	 * @return bool|WP_Error
	 */
	function hcap_hcaptcha_jetpack_verify( $is_spam = false ) {
		$error_message = hcaptcha_get_verify_message( 'hcaptcha_jetpack_nonce', 'hcaptcha_jetpack' );

		if ( null === $error_message ) {
			return $is_spam;
		}

		$error = new WP_Error();
		$error->add( 'invalid_hcaptcha', $error_message );
		add_filter( 'hcap_hcaptcha_content', 'hcap_hcaptcha_error_message', 10, 1 );

		return $error;
	}
}

add_filter( 'jetpack_contact_form_is_spam', 'hcap_hcaptcha_jetpack_verify', 11, 2 );
