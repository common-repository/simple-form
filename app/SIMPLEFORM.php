<?php
/**
 * Represents as plugin base file.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */

namespace SIMPLEFORM { //phpcs:ignore

	// If direct access than exit the file.
	defined( 'ABSPATH' ) || exit;

	/**
	 * Represents as plugin base file.
	 *
	 * @since 2.12.15
	 */
	class SIMPLEFORM {

		/**
		 * Holds the instance of the plugin currently in use.
		 *
		 * @var SIMPLEFORM\SIMPLEFORM
		 */
		private static $instance = null;

		/**
		 * Contains the helpers methods.
		 *
		 * @var SIMPLEFORM\Helpers
		 */
		public $helpers;

		/**
		 * Contains plugin notices.
		 *
		 * @var SIMPLEFORM\Notices
		 */
		public $notices;

		/**
		 * Contains the plugin assets.
		 *
		 * @var SIMPLEFORM\Assets
		 */
		public $assets;

		/**
		 * Contains the plugin multisite functionalities.
		 *
		 * @var SIMPLEFORM\Multisite
		 */
		public $multisite;

		/**
		 * Contains the admin functionalities.
		 *
		 * @var SIMPLEFORM\Admin
		 */
		public $admin;

		/**
		 * Contains the plugin settings.
		 *
		 * @var SIMPLEFORM\Settings
		 */
		public $settings;

		/**
		 * Contains the plugin settings api.
		 *
		 * @var SIMPLEFORM\SettingsApi
		 */
		public $settingsApi; //phpcs:ignore

		/**
		 * Contains the plugin shortcode.
		 *
		 * @var SIMPLEFORM\Shortcode
		 */
		public $shortcode;

		/**
		 * Contains the admin functionalities.
		 *
		 * @var SIMPLEFORM\FloatingWidgets
		 */
		public $floatingWidgets; //phpcs:ignore

		/**
		 * Contains the admin functionalities.
		 *
		 * @var SIMPLEFORM\Contactform7
		 */
		public $contactform7;

		/**
		 * Contains the plugin database helpers.
		 *
		 * @var SIMPLEFORM\Database
		 */
		public $database;

		/**
		 * Contains the plugin ajax endpoints.
		 *
		 * @var SIMPLEFORM\Ajax
		 */
		public $ajax;

		/**
		 * Main Plugin Instance.
		 *
		 * Insures that only one instance of the addon exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since  1.0.0
		 * @return SIMPLEFORM\SIMPLEFORM
		 */
		public static function getInstance() {
			if ( null === self::$instance || ! self::$instance instanceof self ) {
				self::$instance = new self();

				self::$instance->init();
			}

			return self::$instance;
		}

		/**
		 * Class constructor.
		 *
		 * @since 2.12.15
		 */
		public function init() {
			$this->includes();
			$this->loader();

			if ( SIMPLEFORM()->helpers->version_check() ) {
				return;
			}
		}

		/**
		 * Instantiate plugin available classes.
		 *
		 * @since 2.12.15
		 */
		public function includes() {
			$dependencies = [
				'/vendor/autoload.php',
			];

			foreach ( $dependencies as $path ) {
				if ( ! file_exists( SIMPLEFORM_BASE_PATH . $path ) ) {
					status_header( 500 );
					wp_die( esc_html__( 'Plugin is missing required dependencies. Please contact support for more information.', 'simpleform' ) );
				}

				require SIMPLEFORM_BASE_PATH . $path;
			}
		}

		/**
		 * Load plugin classes.
		 *
		 * @since  2.12.15
		 * @return void
		 */
		private function loader() {
			add_action( 'admin_init', [ $this, 'redirection' ] );
			add_filter( 'plugin_action_links_' . plugin_basename( SIMPLEFORM_PLUGIN_FILE ), [ $this, 'add_action_links' ] );

			register_activation_hook( SIMPLEFORM_PLUGIN_FILE, [ $this, 'register_active_deactive_hooks' ] );

			$this->helpers          = new \SIMPLEFORM\Helpers();
			$this->multisite        = new \SIMPLEFORM\Multisite();
			$this->assets           = new \SIMPLEFORM\Assets();
			$this->admin            = new \SIMPLEFORM\Admin();
			$this->shortcode        = new \SIMPLEFORM\Shortcode();
			$this->contactform7     = new \SIMPLEFORM\Contactformseven();
			$this->floatingWidgets  = new \SIMPLEFORM\FloatingWidget(); // phpcs:ignore
			$this->database         = new \SIMPLEFORM\Database();
			$this->ajax             = new \SIMPLEFORM\Ajax();
		}



		/**
		 * Add plugin action links.
		 *
		 * @param array $links The plugin links.
		 * @return array
		 */
		public function add_action_links( $links ) {
			$plugin = [
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'admin.php?page=simpleform-dashboard' ) ),
					esc_html__( 'Dashboard', 'simpleform' )
				),

			];

			return array_merge( $links, $plugin );
		}

		/**
		 * Redirect to admin page on plugin activation
		 *
		 * @since 1.0.0
		 */
		public function redirection() {
			$redirect_to_admin_page = absint( get_option( 'simpleform_activation_redirect', 0 ) );

			if ( 1 === $redirect_to_admin_page ) {
				delete_option( 'simpleform_activation_redirect' );
				wp_safe_redirect( admin_url( 'admin.php?page=simpleform-dashboard' ) );
				exit;
			}
		}

		/**
		 * Registering activation and deactivation Hooks.
		 *
		 * @param int $network_wide The network site ID.
		 * @return void
		 */
		public function register_active_deactive_hooks( $network_wide ) {
			SIMPLEFORM()->database->migration->run( $network_wide );

			$admin_email = get_option('admin_email');
			// Check if the admin email is set, otherwise use a default value.
			$admin_email = isset($admin_email) ? $admin_email : '';

			$form_settings = [
				'selectedTable' => null,
				'storeleds' => true,
				'metadata' => true,
				'leadsinMail' => false,
				'recipiantmail' => $admin_email ? $admin_email : '',
				'selectedWhatsapp' => null,
				'whatsappRedirection' => false,
				'formCustomization' => false,
				'floatingwidgets' => false,

				'whatsappNumber' => '',
				'openInNewTab' => false,

				'submitbtntext' => 'Send Message',
				'formheader' => 'Have question? - Submit the Form',
				'formcta' => 'Have queries?',

				'submitbtnbgcolor' => '#FFA500',
				'submitbtntextcolor' => '#FFFFFF',
				'submitbtntexthovercolor' => '#3F98D2',

				'headerbackgroundcolor' => '#293239',
				'headertextcolor' => '#FFFFFF',

				'formfieldtextcolor' => '#293239',
				'formbackgroundcolor' => '#F7F7F7',

				'flotingwidgetsbgcolor' => '#0065A0',
				'selectedFont' => 'Arial',
			];

			add_option( 'form_settings', json_encode($form_settings) );

			add_option( 'simpleform_activation_redirect', 1 );

			if ( ! get_option( 'simpleformActivationTime' ) ) {
				add_option( 'simpleformActivationTime', time() );
			}

			flush_rewrite_rules();
		}
	}
}

namespace { //phpcs:ignore
	// if direct access than exit the file.
	defined( 'ABSPATH' ) || exit;

	/**
	 * This function is responsible for running the main plugin.
	 *
	 * @since  2.12.15
	 * @return object SIMPLEFORM\SIMPLEFORM The plugin instance.
	 */
	function SIMPLEFORM() { //phpcs:ignore
		return \SIMPLEFORM\SIMPLEFORM::getInstance();
	}
}
