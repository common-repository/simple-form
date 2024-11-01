<?php
/**
 * Plugin Name: FormFlow
 *
 * @author            wpxpertise, devsabbirahmed
 * @copyright         2024- wpxpertise
 * @license           GPL-2.0-or-later
 * @package           simple-form
 *
 * @wordpress-plugin
 * Plugin Name: FormFlow
 * Plugin URI: https://wpxperties.com/simple-form-pricing/
 * Description: FormFlow- Experience revolutionized WhatsApp and social form building with an elegant WhatsApp form builder, allowing for quick WhatsApp chat and traditional WordPress forms.
 * Version:           3.1.1
 * Requires at least: 5.9 or higher
 * Requires PHP:      5.4 or higher
 * Author:            WPXpertise
 * Author URI:        https://wpxperties.com/
 * Text Domain:       simpleform
 * Domain Path: /languages/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! function_exists( 'sf_fs' ) ) {
	/**
	 * Create a helper function for easy SDK access.
	 */
	function sf_fs() {
		global $sf_fs;

		if ( ! isset( $sf_fs ) ) {
			// Include Freemius SDK.
			require_once __DIR__ . '/freemius/start.php';

			$sf_fs = fs_dynamic_init( array(
				'id'                  => '14829',
				'slug'                => 'simple-form',
				'type'                => 'plugin',
				'public_key'          => 'pk_eab6f1b2452e1079f53f6c315f3ce',
				'is_premium'          => false,
				// If your plugin is a serviceware, set this option to false.
				'has_premium_version' => true,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				// 'has_affiliation'     => 'selected',
				'menu'                => array(
					'slug'           => 'simpleform-dashboard',
					'first-path'     => 'admin.php?page=simpleform-dashboard',
					'contact'    => false,
					'support'        => false,
					'account'        => false,
				),
			) );
		}

		return $sf_fs;
	}

	// Init Freemius.
	sf_fs();
	// Signal that SDK was initiated.
	do_action( 'sf_fs_loaded' );
}

defined( 'ABSPATH' ) || exit;

define( 'SIMPLEFORM_VERSION', '3.1.1' );
define( 'SIMPLEFORM_BASE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIMPLEFORM_BASE_URL', plugin_dir_url( __FILE__ ) );
define( 'SIMPLEFORM_PLUGIN_FILE', __FILE__ );
define( 'SIMPLEFORM_PLUGIN_NAME', 'FormFlow' );

// Define the class and the function.
require_once __DIR__ . '/app/SIMPLEFORM.php';

// Run the plugin.
SIMPLEFORM();
