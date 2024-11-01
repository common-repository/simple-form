<?php
/**
 * Responsible for enqueuing assets.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */

namespace SIMPLEFORM;

// If direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Responsible for enqueuing assets.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */
class Assets {

	/**
	 * Class constructor.
	 *
	 * @since 2.12.15
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'fe_scripts' ] );
		add_action('wp_enqueue_scripts', [ $this, 'cf7_all_scripts' ]);
	}



	/**
	 * Enqueue backend files.
	 *
	 * @since 2.12.15
	 */
	public function admin_scripts() {
		$current_screen = get_current_screen();

		wp_enqueue_style(
			'formychat-cf7-css',
			SIMPLEFORM_BASE_URL . 'assets/public/cf7/formflow-cf7.css',
			'',
			time(),
			'all'
		);

		if ( 'toplevel_page_simpleform-dashboard' === $current_screen->id ) {
			// We don't want any plugin adding notices to our screens. Let's clear them out here.
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );

			wp_enqueue_script('formflow_freemius_checkout', '//checkout.freemius.com/checkout.min.js', [ 'jquery' ], '1.0.0', true);

			wp_enqueue_media();

			$this->formTableScripts();

			$dependencies = require_once SIMPLEFORM_BASE_PATH . 'react/build/index.asset.php';
			$dependencies['dependencies'][] = 'wp-util';

			wp_enqueue_style(
				'SIMPLEFORM-admin',
				SIMPLEFORM_BASE_URL . 'assets/admin.css',
				'',
				time(),
				'all'
			);

			if ( !wp_style_is( 'font-awesome', 'enqueued' ) ) {
				wp_enqueue_style( 'font-awesome', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3' );
			}
			
			

			// if ( ! SIMPLEFORM()->helpers->is_pro_active() ) {}.

			wp_enqueue_style(
				'SIMPLEFORM-app',
				SIMPLEFORM_BASE_URL . 'react/build/index.css',
				'',
				time(),
				'all'
			);

			wp_enqueue_script(
				'SIMPLEFORM-app',
				SIMPLEFORM_BASE_URL . 'react/build/index.js',
				$dependencies['dependencies'],
				time(),
				true
			);

			$icons = apply_filters( 'export_buttons_logo_backend', false );

			$localize = [
				'nonce'            => wp_create_nonce( 'SIMPLEFORM-admin-app-nonce-action' ),
				'icons'            => $icons,
				'isPro'           => SIMPLEFORM()->database->table->is_pro_active(),
				'cf7_all_forms'  => SIMPLEFORM()->database->table->cf7_all_forms(),
				'turnsTile'       => SIMPLEFORM()->database->table->getKeys(),
				'tables'           => SIMPLEFORM()->database->table->get_all(),
				'formsettings'     => SIMPLEFORM()->database->table->get_settings(),
				'ran_setup_wizard' => wp_validate_boolean( get_option( 'SIMPLEFORM_ran_setup_wizard', false ) ),
			];

			wp_localize_script(
				'SIMPLEFORM-app',
				'SIMPLEFORM_APP',
				$localize
			);

			wp_enqueue_script(
				'SIMPLEFORM-admin-js',
				SIMPLEFORM_BASE_URL . 'assets/public/scripts/backend/admin.min.js',
				[ 'jquery' ],
				time(),
				true
			);

		}
	}

	/**
	 * Load assets frontend
	 *
	 * @return mixed
	 */
	public function fe_scripts() {
		$this->frontend_scripts();
	}

	/**
	 * Enqueue frontend files.
	 *
	 * @since 2.12.15
	 */
	public function frontend_scripts() {

		wp_enqueue_style(
			'simpleform-frontend-minified',
			SIMPLEFORM_BASE_URL . 'assets/public/styles/frontendcss.min.css',
			[],
			time(),
			'all'
		);

		if ( !wp_style_is( 'font-awesome', 'enqueued' ) ) {
			wp_enqueue_style( 'font-awesome', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3' );
		}
		
		

		wp_enqueue_script(
			'simpleform-frontend-js',
			SIMPLEFORM_BASE_URL . 'assets/public/scripts/frontend/frontend.min.js',
			[ 'jquery', 'jquery-ui-draggable' ],
			time(),
			true
		);

		wp_enqueue_script(
			'simpleform-sweet-alert-js',
			SIMPLEFORM_BASE_URL . 'assets/public/library/sweetalert2@11.js',
			[ 'jquery' ],
			time(),
			true
		);

		$iconsURLs = apply_filters( 'export_buttons_logo_frontend', false );

		wp_localize_script('simpleform-frontend-js', 'front_end_data', [
			'admin_ajax'           => esc_url( admin_url( 'admin-ajax.php' ) ),
			'isProActive'          => SIMPLEFORM()->database->table->is_pro_active(),
			'iconsURL'             => $iconsURLs,
			'nonce'                => wp_create_nonce( 'simpleform_sheet_nonce_action' ),
		]);
	}


	/**
	 * Enqueue data tables scripts.
	 *
	 * @since 2.12.15
	 */
	public function formTableScripts() {
		wp_enqueue_script(
			'simpleform-sweet-alert-js',
			SIMPLEFORM_BASE_URL . 'assets/public/library/sweetalert2@11.js',
			[ 'jquery' ],
			time(),
			true
		);
	}


	/**
	 * Enqueue CF7 tables scripts.
	 *
	 * @param  mixed $hook The page id.
	 *
	 * @since 2.12.15
	 */
	public function cf7_all_scripts( $hook = '' ) {

		wp_enqueue_script('jquery');

		wp_enqueue_script(
			'formychat-cf7-fe-js',
			SIMPLEFORM_BASE_URL . 'assets/public/cf7/formflow-cf7-fe.js',
			[ 'jquery' ],
			time(),
			true
		);
	}
}
