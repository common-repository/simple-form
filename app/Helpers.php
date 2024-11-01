<?php
/**
 * Responsible for managing helper methods.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */

namespace SIMPLEFORM; //phpcs:ignore

use WP_Error; //phpcs:ignore

// If direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Manages notices.
 *
 * @since 2.12.15
 */
class Helpers {

	/**
	 * Check if the pro plugin exists.
	 *
	 * @return boolean
	 */
	public function check_pro_plugin_exists(): bool {
		return file_exists( WP_PLUGIN_DIR . '/simple-form-pro/simple-form-pro.php' );
	}

	/**
	 * Check if pro plugin is active or not
	 *
	 * @return boolean
	 */
	public function is_pro_active(): bool {
		$license = function_exists( 'SIMPLEFORMpro' ) ? SIMPLEFORMpro()->license_status : false;

		return in_array( 'simple-form-pro/simple-form-pro.php', get_option( 'active_plugins', [] ), true ) && $license;
	}

	/**
	 * Checks for php versions.
	 *
	 * @return bool
	 */
	public function version_check(): bool {
		return version_compare( PHP_VERSION, '5.4' ) < 0;
	}

	/**
	 * Get nonce field.
	 *
	 * @param string $nonce_action The nonce action.
	 * @param string $nonce_name   The nonce input name.
	 */
	public function nonceField( $nonce_action, $nonce_name ) {
		wp_nonce_field( $nonce_action, $nonce_name );
	}


	/**
	 * Checks plugin version is greater than 2.13.4 (after revamp).
	 *
	 * @since 3.0.0
	 * @return bool
	 */
	public function is_latest_version(): bool {
		return version_compare( SIMPLEFORM_VERSION, '2.4.0', '>' );
	}
}
