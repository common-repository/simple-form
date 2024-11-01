<?php
/**
 * Responsible for managing ajax endpoints.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */

namespace SIMPLEFORM\Ajax;

use SIMPLEFORMPRO\Database\DB;  //phpcs:ignore
use WPCF7_ContactForm; 		    // phpcs:ignore
use WP_Upgrader; 				// phpcs:ignore
use Plugin_Upgrader; 			// phpcs:ignore
use WP_Upgrader_Skin; 			// phpcs:ignore

// If direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Responsible for handling table operations.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */
class Tables {

	/**
	 * Class constructor.
	 *
	 * @since 2.12.15
	 */
	public function __construct() {
		add_action( 'wp_ajax_simpleform_create_form', [ $this, 'create' ] );
		add_action( 'wp_ajax_simpleform_save_settings', [ $this, 'save_settings' ] );

		add_action( 'wp_ajax_simpleform_get_tables', [ $this, 'get_all' ] );
		add_action( 'wp_ajax_simpleform_get_CF7_tables', [ $this, 'get_all_cf7' ] );

		add_action( 'wp_ajax_simpleform_get_leads', [ $this, 'get_all_leads' ] );
		add_action( 'wp_ajax_simpleform_get_CF7_leads', [ $this, 'get_all_CF7_leads' ] );
		add_action( 'wp_ajax_simpleform_IS_CF7_Installed', [ $this, 'IS_CF7_installed' ] );

		add_action( 'wp_ajax_simpleform_get_settings', [ $this, 'get_settings' ] );

		add_action( 'wp_ajax_simpleform_delete_table', [ $this, 'delete' ] );
		add_action( 'wp_ajax_simpleform_delete_leads', [ $this, 'delete_leads' ] );

		add_action( 'wp_ajax_simpleform_delete_cf7_leads', [ $this, 'delete_cf7_leads' ] );

		add_action( 'wp_ajax_simpleform_edit_table', [ $this, 'edit' ] );
		add_action( 'wp_ajax_simpleform_save_table', [ $this, 'save' ] );

		add_action( 'wp_ajax_simpleform_store_captcha', [ $this, 'storecaptchakeys' ] );
		add_action( 'wp_ajax_simpleform_connect_captcha', [ $this, 'connectcaptcha' ] );

		add_action( 'wp_ajax_simpleform_table_html', [ $this, 'rendertable' ] );
		add_action('wp_ajax_nopriv_simpleform_table_html', [ $this, 'rendertable' ] );

		add_action( 'wp_ajax_simpleform_get_submit_data', [ $this, 'get_submitdata' ] );
		add_action('wp_ajax_nopriv_simpleform_get_submit_data', [ $this, 'get_submitdata' ] );

		add_action('wp_ajax_activate_cf7_plugin', [ $this, 'activate_cf7_plugin' ]);
		add_action('wp_ajax_install_and_activate_cf7_plugin', [ $this, 'install_and_activate_cf7_plugin' ]);

		add_action( 'wp_ajax_all_cf7_forms', [ $this, 'all_cf7_forms' ] );
		add_action( 'wp_ajax_cf7_get_formwise_config', [ $this, 'cf7_get_formwise_config' ] );

		add_action( 'wp_ajax_cf7_get_preset', [ $this, 'cf7_get_preset' ] );
	}

	/**
	 * Create table on ajax request.
	 *
	 * @since 2.0.0
	 */
	public function create() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		/**
		 * Responsible for sanitize before save.
		 */
		function sanitize_text_or_array_field( $array_or_string ) {
			if ( is_string($array_or_string) ) {
				// Sanitize string field.
				return sanitize_text_field($array_or_string);
			} elseif ( is_array($array_or_string) ) {
				// Sanitize array of fields.
				foreach ( $array_or_string as $key => &$value ) {
					if ( is_array( $value ) ) {
						// Recursively sanitize nested arrays.
						$value = sanitize_text_or_array_field($value);
					} elseif ( is_string( $value ) && ( $key === 'href' || $key === 'src' ) ) {
						// Sanitize URLs.
						$value = filter_var($value, FILTER_SANITIZE_URL);
						$value = esc_url( $value, 'db' );
					} else {
						// Sanitize other string fields.
						$value = sanitize_text_field( $value );
					}
				}
				return $array_or_string;
			}
			return $array_or_string;
		}

		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash($_POST['name'] ) ) : __( 'Untitled', 'simpleform' );
		$from_data = isset( $_POST['formdata'] ) ? sanitize_text_or_array_field( wp_unslash($_POST['formdata'] ) ) : []; // phpcs:ignore

		$table = [
			'form_name'     => $name,
			'form_fields'     => $from_data,
			'time'     => current_time('mysql'),
		];

		$table_id = SIMPLEFORM()->database->table->insert( $table );

		wp_send_json_success([
			'id'      => absint( $table_id ),
			'form_name'      => $name,
			'form_fields'     => $from_data,
			'message' => esc_html__( 'Table created successfully', 'simpleform' ),
		]);
	}

	/**
	 * Save table settings for contact form 7.
	 *
	 * @since 2.0.0
	 */
	public function save_settings() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		/**
		 * Responsible for sanitize before save.
		 */
		function sanitize_text_or_array_field( $array_or_string ) {
			if ( is_string($array_or_string) ) {
				$array_or_string = sanitize_text_field($array_or_string);
			} elseif ( is_array($array_or_string) ) {
				foreach ( $array_or_string as $key => &$value ) {
					if ( is_array( $value ) ) {
						$value = sanitize_text_or_array_field($value);
					} else {
						$value = sanitize_text_field( $value );
					}
				}
			}
			return $array_or_string;
		}

		$from_data_settings = isset( $_POST['settings'] ) ? sanitize_text_or_array_field( wp_unslash( $_POST['settings'] ) ) : []; //phpcs:ignore
		$cf7_config_settings = isset( $_POST['settings']['cf7_config_settings'] ) ? sanitize_text_or_array_field( wp_unslash( $_POST['settings']['cf7_config_settings'] ) ) : []; //phpcs:ignore

		 // Extract CF7 config data.
		 $selectedCF7toconfigureID = isset( $cf7_config_settings['selectedCF7toconfigureID'] ) ? $cf7_config_settings['selectedCF7toconfigureID'] : null;
		 $disableCF7Mail = isset( $cf7_config_settings['disableCF7Mail'] ) ? $cf7_config_settings['disableCF7Mail'] : null;
		 $cf7formData = isset( $cf7_config_settings['cf7formData'] ) ? $cf7_config_settings['cf7formData'] : null;

		// Save the settings in the WordPress options table.
		update_option( 'form_settings', $from_data_settings );

		if ( false === get_option( 'form_settings' ) ) {
			wp_send_json_error([
				'message' => esc_html__( 'Failed to save settings.', 'simpleform' ),
			]);
		}

		/**
		 * Update to DB.
		 */
		global $wpdb;
		$table_name = $wpdb->prefix . 'formflow_cf7_config_data';

		// Check if selectedCF7toconfigureID already exists in the database.
		$existing_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE selectedCF7toconfigureID = %d", $selectedCF7toconfigureID ) ); //phpcs:ignore

		if ( $existing_row ) {
			// If it exists, update the row.
			$wpdb->update(
				$table_name,
				array(
					'disableCF7Mail' => $disableCF7Mail,
					'cf7formData' => $cf7formData,
				),
				array( 'selectedCF7toconfigureID' => $selectedCF7toconfigureID )
			);
		} else {
			// If it doesn't exist, insert a new row.
			$wpdb->insert(
				$table_name,
				array(
					'selectedCF7toconfigureID' => $selectedCF7toconfigureID,
					'disableCF7Mail' => $disableCF7Mail,
					'cf7formData' => $cf7formData,
				)
			);
		}

		wp_send_json_success([
			'settings'     => $from_data_settings,
			'CF7Configlist'     => $cf7_config_settings,
			'message' => esc_html__( 'Settings created successfully', 'simpleform' ),
		]);
	}


	/**
	 * Get all tables on ajax request.
	 *
	 * @since 2.0.0
	 */
	public function get_all() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$tables = SIMPLEFORM()->database->table->get_all();

		wp_send_json_success([
			'tables'       => $tables,
			'tables_count' => count( $tables ),
		]);
	}

	/**
	 * Get all contact form 7 forms.
	 *
	 * @since 2.0.0
	 */
	public function get_all_cf7() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		if ( is_plugin_active('contact-form-7/wp-contact-form-7.php') ) {
			$tables = SIMPLEFORM()->database->table->cf7_all_forms();

			wp_send_json_success([
				'tables'       => $tables,
				'status'       => $tables,
				'tables_count' => count( $tables ),
			]);
		}

		wp_send_json_error([
			'status' => __( 'CF7 Plugin not found', 'simpleform' ),
		]);
	}

	/**
	 * Get all leads of contact form 7.
	 *
	 * @since 2.0.0
	 */
	public function get_all_leads() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$table_id = ! empty( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $table_id ) {
			wp_send_json_error([
				'message' => __( 'Invalid table to edit.', 'simpleform' ),
			]);
		}

		$table = SIMPLEFORM()->database->table->getleads( $table_id );

		if ( ! $table ) {
			wp_send_json_error([
				'type'   => 'invalid_request',
				'output' => esc_html__( 'Request is invalid', 'simpleform' ),
			]);
		}

		wp_send_json_success([
			'tables'       => $table,
		]);
	}

	/**
	 * Get all contact form 7 leads.
	 *
	 * @since 2.0.0
	 */
	public function get_all_CF7_leads() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$table_id = ! empty( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $table_id ) {
			wp_send_json_error([
				'message' => __( 'Invalid table to edit.', 'simpleform' ),
			]);
		}

		$table = SIMPLEFORM()->database->table->getCF7leads( $table_id );

		if ( ! $table ) {
			wp_send_json_error([
				'type'   => 'invalid_request',
				'output' => esc_html__( 'Request is invalid', 'simpleform' ),
			]);
		}

		wp_send_json_success([
			'tables'       => $table,
		]);
	}

	/**
	 * Check if contact form 7 is installed.
	 *
	 * @since 2.0.0
	 */
	public function IS_CF7_installed() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		if ( is_plugin_active('contact-form-7/wp-contact-form-7.php') ) {
			// Get all the forms created in CF7.
			$forms = WPCF7_ContactForm::find([
				'orderby' => 'ID',
				'order' => 'ASC',
			]);

			// Format the forms data for the REST API response.
			$formatted_forms = [];
			foreach ( $forms as $form ) {
				$formatted_forms[] = [
					'id' => $form->id(),
					'title' => $form->title(),
				];
			}
			wp_send_json_success([
				'tables'       => $formatted_forms,
			]);

		} elseif ( file_exists(WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php') ) {
				// 'Installed but not activated' .
				wp_send_json_success([
					'tables'       => 'inactive',
				]);
		} else {
			wp_send_json_success([
				'tables'       => 'notinstalled',
			]);
		}
	}


	/**
	 * Settigns get for all forms.
	 *
	 * @since 2.0.0
	 */
	public function get_settings() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$settings = SIMPLEFORM()->database->table->get_settings();

		wp_send_json_success([
			'settings'       => $settings,
		]);
	}


	/**
	 * Delete and get all table by id.
	 */
	public function delete() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : false;
		$tables = SIMPLEFORM()->database->table->get_all();

		if ( $id ) {
			$response = SIMPLEFORM()->database->table->delete( $id );

			if ( $response ) {
				wp_send_json_success([
					'message'      => sprintf( __( '%s form deleted.', 'simpleform' ), $response ), //phpcs:ignore
					'tables'       => $tables,
					'tables_count' => count( SIMPLEFORM()->database->table->get_all() ),
				]);
			}

			wp_send_json_error([
				'message'      => sprintf( __( 'Failed to delete form with id %d', 'simpleform' ), $id ), //phpcs:ignore
				'tables'       => $tables,
				'tables_count' => count( SIMPLEFORM()->database->table->get_all() ),
			]);
		}

		wp_send_json_error([
			'message'      => sprintf( __( 'Invalid table to perform delete in id %d.' , 'simpleform' ), $id ), //phpcs:ignore
			'tables'       => $tables,
			'tables_count' => count( SIMPLEFORM()->database->table->get_all() ),
		]);
	}

	/**
	 * Delete leads by id.
	 */
	public function delete_leads() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : false;
		$tables = SIMPLEFORM()->database->table->get_all();

		if ( $id ) {
			$response = SIMPLEFORM()->database->table->deleteleads( $id );

			if ( $response ) {
				wp_send_json_success([
					'message'      => sprintf( __( '%s form deleted.', 'simpleform' ), $response ),  //phpcs:ignore
					'tables'       => $tables,
					'tables_count' => count( SIMPLEFORM()->database->table->get_all() ),
				]);
			}

			wp_send_json_error([
				'message'      => sprintf( __( 'Failed to delete form with id %d', 'simpleform' ), $id ), //phpcs:ignore
				'tables'       => $tables,
				'tables_count' => count( SIMPLEFORM()->database->table->get_all() ),
			]);
		}

		wp_send_json_error([
			'message'      => sprintf( __( 'Invalid table to perform delete with id %d.' , 'simpleform' ), $id ),  //phpcs:ignore
			'tables'       => $tables,
			'tables_count' => count( SIMPLEFORM()->database->table->get_all() ),
		]);
	}


	/**
	 * Delete table of contact form 7 by id.
	 */
	public function delete_cf7_leads() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : false;
		$tables = SIMPLEFORM()->database->table->cf7_all_forms();

		if ( $id ) {
			$response = SIMPLEFORM()->database->table->deletecf7leads( $id );

			if ( $response ) {
				wp_send_json_success([
					'message'      => sprintf( __( '%s form deleted.', 'simpleform' ), $response ), //phpcs:ignore
					'tables'       => $tables,
					'tables_count' => count( SIMPLEFORM()->database->table->cf7_all_forms() ),
				]);
			}

			wp_send_json_error([
				'message'      => sprintf( __( 'Failed to delete form with id %d', 'simpleform' ), $id ), //phpcs:ignore
				'tables'       => $tables,
				'tables_count' => count( SIMPLEFORM()->database->table->cf7_all_forms() ),
			]);
		}

		wp_send_json_error([
			'message'      => sprintf( __( 'Invalid table to perform delete.' ), $id ), //phpcs:ignore
			'tables'       => $tables,
			'tables_count' => count( SIMPLEFORM()->database->table->cf7_all_forms() ),
		]);
	}


	/**
	 * Edit table on ajax request.
	 *
	 * @since 2.0.0
	 */
	public function edit() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$table_id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $table_id ) {
			wp_send_json_error([
				'message' => __( 'Invalid table to edit.', 'simpleform' ),
			]);
		}

		$table = SIMPLEFORM()->database->table->get( $table_id );

		if ( ! $table ) {
			wp_send_json_error([
				'type'   => 'invalid_request',
				'output' => esc_html__( 'Request is invalid', 'simpleform' ),
			]);
		}

		$settings   = json_decode( $table['form_fields'], true );

		wp_send_json_success([
			'form_name'     => esc_attr( $table['form_name'] ),
			'table_settings' => $settings,
		]);
	}


	/**
	 * Save table by id.
	 */

	public function save() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', '' ),
			]);
		}

		function sanitize_text_or_array_field( $array_or_string ) {
			if ( is_string($array_or_string) ) {
				// Sanitize string field.
				return sanitize_text_field($array_or_string);
			} elseif ( is_array($array_or_string) ) {
				// Sanitize array of fields.
				foreach ( $array_or_string as $key => &$value ) {
					if ( is_array( $value ) ) {
						// Recursively sanitize nested arrays.
						$value = sanitize_text_or_array_field($value);
					} elseif ( is_string( $value ) && ( $key === 'href' || $key === 'src' ) ) {
						// Sanitize URLs.
						$value = filter_var($value, FILTER_SANITIZE_URL);
						$value = esc_url( $value, 'simpleform' );
					} else {
						// Sanitize other string fields.
						$value = sanitize_text_field( $value );
					}
				}
				return $array_or_string;
			}
			return $array_or_string;
		}

		$id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : false;
		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash($_POST['name'] ) ) : __( 'Untitled', 'simpleform' );
		$from_data = isset( $_POST['formdata'] ) ? sanitize_text_or_array_field( wp_unslash($_POST['formdata'] ) ) : []; //phpcs:ignore

		$table = [
			'id'  => $id,
			'form_name'     => $name,
			'form_fields'     => $from_data,
			'time'     => current_time('mysql'),
		];

		$table_id = SIMPLEFORM()->database->table->update( $id, $table );

		wp_send_json_success([
			'id'      => absint( $table_id ),
			'form_name'     => esc_attr( $name ),
			'form_fields' => json_encode( $from_data, true ),
			'message' => __( 'Table updated successfully.', 'simpleform' ),

		]);
	}


	/**
	 * Store Captcha keys
	 */
	public function storecaptchakeys() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$captchaEnable = isset($options['cloudflareCaptchaEnable']) ? filter_var($options['cloudflareCaptchaEnable'], FILTER_VALIDATE_BOOLEAN) : false;
		$siteKey     = isset( $_POST['siteKey'] ) ? sanitize_text_field( wp_unslash($_POST['siteKey'] ) ) : '';
		$secretKey = isset( $_POST['secretKey'] ) ? sanitize_text_field( wp_unslash($_POST['secretKey']) ) : '';

		if ( '' !== $siteKey && '' !== $secretKey ) {
			$cloudflareData = [
				'captchaEnable' => $captchaEnable,
				'siteKey' => $siteKey,
				'secretKey' => $secretKey,
				'validated' => true,
			];

			update_option( 'simple_form_turnstile_credentials', $cloudflareData );
			update_option('simple_form_turnstile_validated', true);

			wp_send_json_success([
				'message'   => __( 'Settings saved.', 'simpleform' ),
				'validated' => wp_validate_boolean( get_option( 'simple_form_turnstile_validated' ) ),
			]);

		} else {
			update_option('simple_form_turnstile_validated', false);
			wp_send_json_error([
				'message' => __( 'Invalid settings to save', 'simpleform' ),
			]);
		}
	}

	/**
	 * Verify and connect
	 */
	public function connectcaptcha() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$token     = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash($_POST['token'] ) ) : '';
		$secretKey = isset( $_POST['secretKey'] ) ? sanitize_text_field( wp_unslash($_POST['secretKey']) ) : '';
		$siteKey = isset( $_POST['siteKey'] ) ? sanitize_text_field( wp_unslash($_POST['siteKey']) ) : '';

		if ( '' !== $token && '' !== $secretKey ) {

			$verification_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

			$response = wp_remote_post(
				$verification_url,
				[
					'body' => [
						'secret'   => $secretKey,
						'response' => $token,
					],
				]
			);

			$varification = json_decode( wp_remote_retrieve_body( $response ), true );

			update_option( 'simple_form_turnstile_validated', wp_validate_boolean( $varification['success'] ) );

			if ( $varification['success'] ) {
				wp_send_json_success([
					'message'   => __( 'Connection verified & saved.', 'simpleform' ),
					'validated' => get_option( 'simple_form_turnstile_validated' ),
				]);
			} else {
				wp_send_json_error([
					'message'   => __( 'Validation error', 'simpleform' ),
					'validated' => get_option( 'simple_form_turnstile_validated' ),
				]);
			}
		} else {
			wp_send_json_error([
				'message'   => __( 'Validation error', 'simpleform' ),
				'validated' => get_option( 'simple_form_turnstile_validated' ),
			]);
		}
	}


	/**
	 * Get Form tables on ajax request.
	 *
	 * @since 2.0.0
	 */
	public function rendertable() {

		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'simpleform_sheet_nonce_action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$table_id = ! empty( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( ! $table_id ) {
			wp_send_json_error([
				'message' => __( 'Invalid table to edit.', 'simpleform' ),
			]);
		}

		$table = SIMPLEFORM()->database->table->get( $table_id );

		if ( ! $table ) {
			wp_send_json_error([
				'type'   => 'invalid_request',
				'output' => esc_html__( 'Request is invalid', 'simpleform' ),
			]);
		}

		$settings   = json_decode( $table['form_fields'], true );

		wp_send_json_success([
			'form_name'     => esc_attr( $table['form_name'] ),
			'table_settings' => $settings,
		]);
	}


	/**
	 * Get Form submitted data on ajax request.
	 *
	 * @since 2.0.0
	 */
	public function get_submitdata() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'simpleform_sheet_nonce_action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$id = isset($_POST['id']) ? sanitize_text_field( wp_unslash($_POST['id'] ) ) : 'simpleform'; //phpcs:ignore
		$form_data = isset( $_POST['form_data'] ) ? json_decode( stripslashes( wp_unslash( $_POST['form_data'] ) ), true ) : array(); //phpcs:ignore
		$form_data = is_array( $form_data ) ? array_map( 'sanitize_text_field', $form_data ) : array(); //phpcs:ignore
		$current_page = isset($_POST['current_page_url']) ? esc_url(wp_unslash($_POST['current_page_url'])) : ''; //phpcs:ignore

		if ( empty( $form_data ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Form data is empty, not storing in the database.', 'simpleform' ),
			) );
		}

		// Filter out any fields with an empty key.
		$clean_empty_key_form_data = array_filter($form_data, function($key) {
			return !empty($key);
		}, ARRAY_FILTER_USE_KEY);


		$options = get_option('form_settings');
		$storeToDB = isset($options['storeleds']) ? filter_var($options['storeleds'], FILTER_VALIDATE_BOOLEAN) : true;
		$storemetaToDB = isset($options['metadata']) ? filter_var($options['metadata'], FILTER_VALIDATE_BOOLEAN) : true;

		/**
		 * New meta fields.
		 */

		// IP and Meta data calculations.
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} else {
			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			}
		}

		$location_data = json_decode( wp_remote_retrieve_body( wp_remote_get( 'http://ip-api.com/json/' . $ip ) ) );

		if ( empty( $location_data ) || ! isset( $location_data->status ) || 'fail' === $location_data->status ) {
			$ipdata = wp_remote_get( 'http://ipecho.net/plain' );
			if ( ! is_wp_error( $ipdata ) ) {
				$ip = $ipdata['body'];
				$location_data = json_decode( wp_remote_retrieve_body( wp_remote_get( 'http://ip-api.com/json/' . $ip ) ) );
			} else {
				$location_data = new \stdClass();
				$location_data->country = null;
				$location_data->city = null;
			}
		}

		$country = $location_data->country;
		$city = $location_data->city;

		$bloginfoname = get_bloginfo('name');
		$bloginfodescription = get_bloginfo('description');
		$siteurl = get_bloginfo('url');
		$adminemail = get_bloginfo('admin_email');

		$current_page_url = $current_page;

		$current_date = current_time('Y-m-d');
		$current_time = current_time('H:i:s');
		$date = $current_date;
		$time = $current_time;

		$single_meta_data = array_merge( [ $bloginfoname ],[ $bloginfodescription ],[ $siteurl ], [ $current_page_url ], [ $adminemail ],[ $country ], [ $city ], [ $ip ], [ $date ], [ $time ]);

		$meta_data = [
			'name' => $single_meta_data[0],
			'description' => $single_meta_data[1],
			'siteurl' => $single_meta_data[2],
			'pageurl' => $single_meta_data[3],
			'adminemail' => $single_meta_data[4],
			'country' => $single_meta_data[5],
			'city' => $single_meta_data[6],
			'ip' => $single_meta_data[7],
			'date' => $single_meta_data[8],
			'time' => $single_meta_data[9],
		];

		// Initialize the $table array with common fields.
		$table = array(
			'form_id' => $id,
			'fields'  => $clean_empty_key_form_data,
			'time'    => current_time('mysql'),
		);

		if ( true === $storemetaToDB ) {
			$table['meta'] = $meta_data;
		}

		if ( true === $storeToDB ) {
			$table_id = SIMPLEFORM()->database->table->insertleads($table);
		}

		 /**
		  * To use the hook.
		  * add_action('formflow_form_submit', 'my_custom_form_submission_function', 10, 1);
		  * function my_custom_form_submission_function($table) {}, $table['form_id']; $table['fields']; $table['time'];
		  * $formflow = new \WPNTS\Inc\Formflow();
		  * use WPNTS\Inc\Formflow;
		  */
		 do_action('formflow_form_submit', $table);

		/**
		 * WhatsApp redirection.
		 */
		$selectedWhatsapp = isset($options['selectedWhatsapp']) ? (
			is_array($options['selectedWhatsapp']) ?
				array_map('sanitize_text_field', $options['selectedWhatsapp']) :
				( '' !== $options['selectedWhatsapp'] ? [ sanitize_text_field($options['selectedWhatsapp']) ] : [] )
		) : [];

		$mailNotification = isset($options['leadsinMail']) ? filter_var($options['leadsinMail'], FILTER_VALIDATE_BOOLEAN) : false;
		$recipiantmail = isset($options['recipiantmail']) ? sanitize_email($options['recipiantmail']) : null;

		$leadsinSlack = isset($options['leadsinSlack']) ? filter_var($options['leadsinSlack'], FILTER_VALIDATE_BOOLEAN) : false;
		$recipiantslack = isset($options['recipiantslack']) ? sanitize_text_field($options['recipiantslack']) : '';

		/**
		 * Pro feature.
		 */

		if ( SIMPLEFORM()->database->table->is_pro_active() ) {
			$pro_instance = new DB();
			$pro_instance->send_leads_to_mail($id, $storemetaToDB, $mailNotification, $recipiantmail, $clean_empty_key_form_data, $meta_data);
			$pro_instance->send_leads_to_slack($id, $storemetaToDB, $leadsinSlack, $recipiantslack, $clean_empty_key_form_data, $meta_data);
		}

		/**
		 * WhatsApp redirection Old code with esignature that not send in whatsapp.
		 * Now send as a link by uploading in WP.
		 */

		if ( in_array($id, $selectedWhatsapp) ) {
			// WhatsApp redirection.
			if ( isset($options['whatsappRedirection']) && 'true' === $options['whatsappRedirection'] ) {
				$whatsappNumber = $options['whatsappNumber'];
				$openInNewTab = $options['openInNewTab'];

				$whatsappNumber = preg_replace('/[^0-9\+]/', 'simpleform', $whatsappNumber);
				if ( substr($whatsappNumber, 0, 1) !== '+' ) {
					$whatsappNumber = '+' . $whatsappNumber;
				}

				// Filter out fields starting with 'esignature-'.
				$filtered_form_data = [];
				foreach ( $clean_empty_key_form_data as $key => $value ) {
					if ( strpos($key, 'esignature-') !== 0 ) {
						$filtered_form_data[ $key ] = $value;
					}
				}

				// Add uploaded image link to WhatsApp message.
				foreach ( $clean_empty_key_form_data as $field => $value ) {
					if ( strpos($field, 'esignature-') === 0 ) {
						$image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $value));
						$filename = wp_unique_filename(wp_upload_dir()['path'], 'esignature', null, wp_get_mime_types()['png']);
						$upload_path = wp_upload_dir()['path'] . '/' . $filename;
						file_put_contents($upload_path, $image_data);
						$attachment_id = wp_insert_attachment([
							'guid'           => wp_upload_dir()['url'] . '/' . $filename,
							'post_mime_type' => 'image/png',
							'post_title'     => 'E-Signature',
							'post_content'   => '',
							'post_status'    => 'inherit',
						], $upload_path);
						$image_url = wp_get_attachment_url($attachment_id);
						$filtered_form_data[ $field ] = $image_url;
					}
				}

				// Ensure $filtered_form_data is a string by joining array elements.
				$form_data_str = is_array($filtered_form_data) ? implode(' ', $filtered_form_data) : $filtered_form_data;

				if ( 'true' !== $openInNewTab ) {
					$wh_url = 'https://wa.me/' . $whatsappNumber . '?text=' . urlencode(html_entity_decode($form_data_str));
				} else {
					$wh_url = 'https://web.whatsapp.com/send?phone=' . $whatsappNumber . '&text=' . urlencode(html_entity_decode($form_data_str));
				}

				$simple_form_new_opt = [];
				// Send to WhatsApp now it has no used as URL set from JS with new update code.
				$simple_form_new_opt['simple_form_whatsapp_url'] = $wh_url;
				$simple_form_new_opt['simple_form_whatsapp_number'] = $whatsappNumber;
				$simple_form_new_opt['simple_form_whatsapp_data'] = $filtered_form_data;
				$simple_form_new_opt['simple_form_new_tab'] = $openInNewTab;

				// Add nonce.
				$nonce = wp_create_nonce('simple_form_submission');
				$simple_form_new_opt['nonce'] = $nonce;

				$cookie_name = 'simple_form_whatsapp_data';
				setcookie($cookie_name, json_encode($simple_form_new_opt), time() + ( 86400 * 30 ), '/');
			}
		}

		wp_send_json_success([
			'message' => __('Form data received and processed successfully.', 'simpleform'),
			'form_data' => $table,
			'status' => 'success',
		]);
	}


	/**
	 * CF7 Download, install and activate.
	 *
	 * @return void
	 */
	public function activate_cf7_plugin() {
		// Activate the Contact Form 7 plugin.
		activate_plugin('contact-form-7/wp-contact-form-7.php');
		wp_send_json('activated');
	}

	/**
	 * Install and activate the Contact Form 7 plugin.
	 *
	 * @since 3.0.0
	 */
	public function install_and_activate_cf7_plugin() {
		$plugin_slug = 'contact-form-7';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

		$api = plugins_api('plugin_information', [
			'slug' => $plugin_slug,
		]);

		$upgrader = new Plugin_Upgrader(new WP_Upgrader_Skin());
		$install = $upgrader->install($api->download_link);

		if ( is_wp_error($install) ) {
			wp_send_json('failedtoinstall');
		}

		activate_plugin('contact-form-7/wp-contact-form-7.php');

		wp_send_json('installedandactivated');
	}


	/**
	 * Check If CF7 installed and get list of form.
	 *
	 * @return void
	 */
	public function all_cf7_forms() {

		$cf7_is_installed = false;

		if ( is_plugin_active('contact-form-7/wp-contact-form-7.php') ) {
			// Get all the forms created in CF7.
			$forms = WPCF7_ContactForm::find([
				'orderby' => 'ID',
				'order' => 'ASC',
			]);

			// Format the forms data for the REST API response.
			$formatted_forms = [];
			foreach ( $forms as $form ) {
				$formatted_forms[] = [
					'id' => $form->id(),
					'title' => $form->title(),
				];
			}
			wp_send_json($formatted_forms);
		} elseif ( file_exists(WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php') ) {
			wp_send_json('inactive'); // 'Installed but not activated'.
		} else {
			wp_send_json($cf7_is_installed); // 'Not installed'.
		}
	}

	/**
	 * CF7 formwise config.
	 *
	 * @return void
	 */
	public function cf7_get_formwise_config() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$table_id = ! empty( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $table_id ) {
			wp_send_json_error([
				'message' => __( 'Invalid table to edit.', 'simpleform' ),
			]);
		}

		$table = SIMPLEFORM()->database->table->getcf7config( $table_id );

		if ( ! $table ) {
			wp_send_json_error([
				'type'   => 'invalid_request',
				'output' => esc_html__( 'Request is invalid', 'simpleform' ),
			]);
		}

		wp_send_json_success([
			'tables'       => $table,
		]);
	}

	/**
	 * CF7 get all presets.
	 *
	 * @return void
	 */
	public function cf7_get_preset() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'SIMPLEFORM-admin-app-nonce-action' ) ) {
			wp_send_json_error([
				'message' => __( 'Invalid nonce.', 'simpleform' ),
			]);
		}

		$table_id = ! empty( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $table_id ) {
			wp_send_json_error([
				'message' => __( 'Invalid table to edit.', 'simpleform' ),
			]);
		}

		$preset = SIMPLEFORM()->database->table->getPreset( $table_id );

		wp_send_json_success( $preset );
	}
}
