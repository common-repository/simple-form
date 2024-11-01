<?php
/**
 * Managing database operations for tables.
 *
 * @since 3.0.0
 * @package SIMPLEFORM
 */

namespace SIMPLEFORM\Database; //phpcs:ignore

use SIMPLEFORM\sf_fs; //phpcs:ignore
use WPCF7_ContactForm; //phpcs:ignore
use WPCF7_Submission; //phpcs:ignore
use WP_Upgrader; // phpcs:ignore
use Plugin_Upgrader; // phpcs:ignore
use WP_Upgrader_Skin; // phpcs:ignore

// If direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Manages plugin database operations.
 *
 * @since 3.0.0
 */
class Table {
	/**
	 * Pro version installed checking.
	 *
	 * @since 2.12.15
	 */
	public function check_pro_plugin_exists(): bool {
		return file_exists( WP_PLUGIN_DIR . '/simple-form-pro/simple-form-pro.php' );
	}

	/**
	 * Is CF7 installed and all forms.
	 *
	 * @since 2.12.15
	 */
	public function cf7_all_forms() {
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
			return $formatted_forms;
		} elseif ( file_exists(WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php') ) {
				// 'Installed but not activated'.
			return 'inactive';
		} else {
			// 'Not installed'.
			return 'notinstalled';
		}
	}

	/**
	 * Pro version activated checking.
	 *
	 * @since 2.12.15
	 */
	public function is_pro_active() {
		$is_pro_installed = class_exists('SimpleFormPro') && $this->check_pro_plugin_exists();
		return sf_fs()->can_use_premium_code__premium_only() && $is_pro_installed;
	}

	/**
	 * Insert table into the db.
	 *
	 * @param array $data The data to save.
	 * @return int|false
	 */
	public function insert( array $data ) {
		global $wpdb;

		// Initialize an array to store the formatted data.
		$formatted_data = [];

		// Extract values from the $data array and format them.
		foreach ( $data as $key => $value ) {
			if ( 'form_fields' === $key ) {
				// Serialize the form_fields array as a JSON string.
				$formatted_data[ $key ] = json_encode($value);
			} else {
				// Use the %s format for non-array values.
				$formatted_data[ $key ] = is_array($value) ? '' : $value;
			}
		}

		$table  = $wpdb->prefix . 'simple_form_tables';
		$format = [ '%s', '%s', '%s', '%s' ];

		$wpdb->insert( $table, $formatted_data, $format );
		return $wpdb->insert_id;
	}


	/**
	 * Insert Leads into the db.
	 *
	 * @param array $data The data to save.
	 * @return int|false
	 */
	public function insertleads( array $data ) {

		global $wpdb;

		// Initialize an array to store the formatted data.
		$formatted_data = [];

		// Extract values from the $data array and format them.
		foreach ( $data as $key => $value ) {
			if ( 'fields' === $key ) {
				// Serialize the form_fields array as a JSON string.
				$formatted_data[ $key ] = json_encode($value);
			} elseif ( 'meta' === $key ) {
				// Encode the meta data array as a JSON string.
				$formatted_data[ $key ] = json_encode($value);
			} else {
				// Use the %s format for non-array values.
				$formatted_data[ $key ] = is_array($value) ? '' : $value;
			}
		}

		$table  = $wpdb->prefix . 'simple_form_leads';
		$format = [ '%s', '%s', '%s', '%s', '%s' ];

		$wpdb->insert( $table, $formatted_data, $format );
		return $wpdb->insert_id;
	}


	/**
	 * Insert Leads into the CF7 table.
	 *
	 * @param array $data The data to save.
	 * @return int|false
	 */
	public function insert_cf7_leads( $data ){
		global $wpdb;

		// Initialize an array to store the formatted data.
		$formatted_data = [];

		// Extract values from the $data array and format them.
		foreach ( $data as $key => $value ) {
			if ( $key === 'fields' ) {
				// Serialize the form_fields array as a JSON string.
				$formatted_data[ $key ] = json_encode($value);
			} elseif ( $key === 'meta' ) {
				// Encode the meta data array as a JSON string.
				$formatted_data[ $key ] = json_encode($value);
			} else {
				// Use the %s format for non-array values.
				$formatted_data[ $key ] = is_array($value) ? '' : $value;
			}
		}

		$table  = $wpdb->prefix . 'simple_form_cf7_leads';
		$format = [ '%s', '%s', '%s', '%s', '%s' ];

		$wpdb->insert( $table, $formatted_data, $format );
		return $wpdb->insert_id;
	}



	/**
	 * Fetch table with specific ID.
	 *
	 * @param  int $id The table id.
	 * @return mixed
	 */
	public function get( int $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'simple_form_tables';

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d", absint( $id ) ), ARRAY_A ); // phpcs:ignore

		return ! is_null( $result ) ? $result : null;
	}

	/**
	 * Fetch contact form 7 table with specific ID.
	 *
	 * @param  int $id The table id.
	 * @return mixed
	 */
	public function getcf7config( int $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'formflow_cf7_config_data';

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE selectedCF7toconfigureID=%d", absint( $id ) ), ARRAY_A ); // phpcs:ignore

		return ! is_null( $result ) ? $result : null;
	}

	/**
	 * Fetch presets.
	 *
	 * @param  int $id The table id.
	 * @return mixed
	 */
	public function getPreset( int $id ) {
		// Check if the provided form ID is valid.
		if ( $id <= 0 ) {
			return null;
		}

		// Attempt to retrieve the Contact Form 7 form instance.
		$form = WPCF7_ContactForm::get_instance($id);

		// Check if the form instance is obtained successfully.
		if ( ! $form ) {
			return null;
		}

		$mail_tags = $form->suggest_mail_tags();

		return ! is_null( $mail_tags ) ? $mail_tags : null;
	}

	/**
	 * Fetch forms leads with specific ID.
	 *
	 * @param  int $id The table id.
	 * @return mixed
	 */
	public function getleads( int $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'simple_form_leads';
		$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE form_id = %d ORDER BY time DESC", absint($id)), ARRAY_A); // phpcs:ignore

		return ! empty($results) ? $results : null;
	}

	/**
	 * Fetch cf7 leads with specific ID.
	 *
	 * @param  int $id The table id.
	 * @return mixed
	 */
	public function getCF7leads( int $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'simple_form_cf7_leads';
		$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE form_id = %d ORDER BY time DESC", absint($id)), ARRAY_A); // phpcs:ignore

		return ! empty($results) ? $results : null;
	}


	/**
	 * Update table with specific ID.
	 *
	 * @param int   $id The table id.
	 * @param array $data The data to update.
	 */
	public function update( int $id, array $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'simple_form_tables';

		// Initialize an array to store the formatted data.
		$formatted_data = [];

		// Extract values from the $data array and format them.
		foreach ( $data as $key => $value ) {
			if ( 'form_fields' === $key ) {
				// Serialize the form_fields array as a JSON string.
				$formatted_data[ $key ] = json_encode($value);
			} else {
				// Use the %s format for non-array values.
				$formatted_data[ $key ] = is_array($value) ? '' : $value;
			}
		}

		$where  = [ 'id' => $id ];
		$format = [ '%s', '%s', '%s', '%s' ];

		$where_format = [ '%d' ];

		return $wpdb->update( $table, $formatted_data, $where, $format, $where_format );
	}

	/**
	 * Delete table data from the DB.
	 *
	 * @param int $id  The table id to delete.
	 * @return int|false
	 */
	public function delete( int $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'simple_form_tables';

		return $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Delete table data from the DB.
	 *
	 * @param int $id  The table id to delete.
	 * @return int|false
	 */
	public function deleteleads( int $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'simple_form_leads';

		return $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Delete table data from the DB.
	 *
	 * @param int $id  The table id to delete.
	 * @return int|false
	 */
	public function deletecf7leads( int $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'simple_form_cf7_leads';

		return $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Fetch all the saved tables
	 *
	 * @return mixed
	 */
	public function get_all() {
		global $wpdb;

		$table  = $wpdb->prefix . 'simple_form_tables';
		$query  = "SELECT * FROM $table";
		$result = $wpdb->get_results( $query ); // phpcs:ignore

		return $result;
	}


	/**
	 * Get default form settings data.
	 *
	 * @return int|false
	 */
	public function get_settings() {
		$options = get_option('form_settings');

		if ( $options ) {
			return $options;
		} else {
			$admin_user = get_userdata(1);
			$admin_email = isset($admin_user->user_email) ? $admin_user->user_email : '';

			return array(
				'selectedTable' => null,
				'selectedWhatsapp' => null,
				'whatsappRedirection' => false,
				'formCustomization' => false,
				'storeleds' => true,
				'metadata' => true,
				'leadsinMail' => false,
				'recipiantmail' => $admin_email ? $admin_email : '',
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
			);
		}
	}


	/**
	 * Get all turnstile data.
	 *
	 * @return int|false
	 */
	public function turnstile_get_all() {
		$defaults = [
			'captchaEnable'      => false,
			'verifyKeys'    => false,
			'siteKey'    => '',
			'secretKey'    => '',
			'customMessageforcaptha'    => '',
		];

		$settings = get_option( 'simple_form_turnstile_credentials', [] );
		$settings = is_array( $settings ) ? $settings : json_decode( $settings, true );
		$settings = wp_parse_args( $settings, $defaults );

		return $settings;
	}

	/**
	 * Retrieve setting value by the key.
	 *
	 * @param string $key The settings key.
	 * @param mixed  $default The default value.
	 *
	 * @since  1.0.0
	 * @return mixed
	 */
	public function turnstileget( $key, $default = null ) {
		$settings = $this->turnstile_get_all();

		if ( $default ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
		}

		return isset( $settings[ $key ] ) ? $settings[ $key ] : false;
	}

	/**
	 * Get captcha keys from the options.
	 *
	 * @return int|false
	 */
	public function getKeys() {
		$settings = get_option( 'simple_form_turnstile_credentials', [] );
		$keylist = is_array( $settings ) ? $settings : json_decode( $settings, true );

		return $keylist;
	}
}
