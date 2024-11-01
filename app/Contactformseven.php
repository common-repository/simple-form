<?php
/**
 * Registering WordPress Contactformseven for the plugin.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */

namespace SIMPLEFORM; //phpcs:ignore

use SIMPLEFORMPRO\Database\DB; //phpcs:ignore
use WPCF7_ContactForm; //phpcs:ignore
use WPCF7_Submission; //phpcs:ignore

// If direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Responsible for registering Contactformseven.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */
class Contactformseven {

	/**
	 * Class constructor.
	 *
	 * @since 2.12.15
	 */
	public function __construct() {
		add_filter( 'wpcf7_editor_panels', [ $this, 'sf_custom_tab' ], 20, 1 );
		add_action( 'wpcf7_before_send_mail', [ $this, 'formflow_cf7_send_message' ] );
		add_action( 'wpcf7_skip_mail', [ $this, 'formflow_disable_default_cf7_mail' ] );
	}

	/**
	 * Create tab inside the CF7.
	 *
	 * @param array $panels Plugin Tabs and callback.
	 *
	 * @return mixed
	 */
	public function sf_custom_tab( $panels ) {
		$panels['new-tab'] = [
			'title' => __('FormFlow', 'simpleform'),
			'callback' => [ $this, 'wpcf7_formdlow_new_tab' ],
		];
		return $panels;
	}

	/**
	 * Create tab and all its functionalities in CF7.
	 *
	 * @param array $post Plugin Id and post  meta.
	 *
	 * @return mixed
	 */
	public function wpcf7_formdlow_new_tab( $post ) {
		?>
		<div class='scf-card'>
		<h2><?php esc_html_e('FormFlow- Intuitive Drag & Drop builder with Advanced Lead Magic', 'simpleform'); ?></h2>

			<div class='row'>
				<div class='col-40'>
				<img alt='' src='https://wpxperties.com/wp-content/themes/wpxperties/assets/img/simple-form/Drag-drop.png'>
				</div>
				<div class='col-60'>
				<h1 class='<?php echo esc_attr('title'); ?>'>
					<?php esc_html_e('Configure', 'simpleform'); ?>
					<span class='<?php echo esc_attr('success'); ?>'>
						<?php esc_html_e('FormFlow', 'simpleform'); ?>
					</span>
					<span>
						<?php esc_html_e('to use', 'simpleform'); ?>
						<span class='<?php echo esc_attr('success'); ?>'>
							<?php esc_html_e('Contact Form 7', 'simpleform'); ?>
						</span>
						<?php esc_html_e('as whatsapp redirection, floating widgets, leads and more.', 'simpleform'); ?>
					</span>
				</h1>
			
				<div class='row'>
					<a class='button' href='<?php echo esc_url( admin_url( 'admin.php?page=simpleform-dashboard#/settings' ) ); ?>'>
					<?php esc_html_e('Configure FormFlow', 'simpleform'); ?>
					</a>
				</div>
				</div>
			</div>
		<?php
	}

	 /**
	  * Get tab configuration data.
	  *
	  * @param array $contact_form CF7 Id and get configuration.
	  *
	  * @return mixed
	  */
	public function formflow_cf7_send_message( $contact_form ) {
		global $wpdb;
		$table = $wpdb->prefix . 'formflow_cf7_config_data';

		$options = get_option('form_settings');

		$widgetsposition = $options['widgetsposition'];
		$floating_widgets = $options['floatingwidgets'];
		$whatsappNumber = $options['whatsappNumber'];
		$openInNewTab = $options['openInNewTab'];
		$submitbtntext = $options['submitbtntext'];
		$cf7_config_settings = $options['cf7_config_settings'];
		$selectedCF7toconfigureID = $options['cf7_config_settings']['selectedCF7toconfigureID'];
		$cf7formData = $options['cf7_config_settings']['cf7formData'];
		$disableCF7Mail = $options['cf7_config_settings']['disableCF7Mail'];
		$selectedCF7Whatsredi = $options['selectedCF7Whatsredi'];

		$storecf7leds = isset($options['storecf7leds']) ? filter_var($options['storecf7leds'], FILTER_VALIDATE_BOOLEAN) : true;
		$cf7metadata = isset($options['cf7metadata']) ? filter_var($options['cf7metadata'], FILTER_VALIDATE_BOOLEAN) : true;
		$cf7leadsinslack = isset($options['cf7leadsinslack']) ? filter_var($options['cf7leadsinslack'], FILTER_VALIDATE_BOOLEAN) : true;
		$recipiantslack = isset($options['recipiantslack']) ? sanitize_text_field($options['recipiantslack']) : '';

		$mailNotification = isset($options['leadsinMail']) ? filter_var($options['leadsinMail'], FILTER_VALIDATE_BOOLEAN) : false;
		$recipiantmail = isset($options['recipiantmail']) ? sanitize_email($options['recipiantmail']) : null;

		/**
		 * CF7 Form submission part.
		 */
		$submission = WPCF7_Submission::get_instance();
		if ( $submission ) {
			$posted_data = $submission->get_posted_data();
			$contact_forms = WPCF7_ContactForm::get_instance($contact_form->id());
			$form_fields = $contact_forms->scan_form_tags();
			$contact_forms_id = $contact_form->id();

			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE selectedCF7toconfigureID=%d", absint( $contact_forms_id ) ), ARRAY_A ); // phpcs:ignore

			// Extracting options individually.
			$id = $result['id'];
			$selectedCF7toconfigureID = $result['selectedCF7toconfigureID'];
			$disableCF7Mail = $result['disableCF7Mail'];
			$cf7formData = $result['cf7formData'];

			$current_url = '';
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$current_url = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			}

			$posted_data['site_title'] = get_bloginfo('name');
			$posted_data['site_description'] = get_bloginfo('description');
			$posted_data['site_url'] = get_bloginfo('url');
			$posted_data['admin_email'] = get_bloginfo('admin_email');
			$posted_data['page_url'] = $current_url;

			/**
			 * Meta information.
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

			$current_page_url = $current_url;

			$current_date = current_time('Y-m-d');
			$current_time = current_time('H:i:s');
			$date = $current_date;
			$time = $current_time;

			$scf_user_agent = $submission->get_meta( 'user_agent' ) ? $submission->get_meta( 'user_agent' ) : '';

			$single_meta_data = array_merge( [ $bloginfoname ],[ $bloginfodescription ],[ $siteurl ], [ $current_page_url ], [ $adminemail ],[ $country ], [ $city ], [ $ip ], [ $date ], [ $time ], [ $scf_user_agent ]);

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
				'user_agent' => $single_meta_data[10],
			];

			if ( ! empty($posted_data) ) {
				foreach ( $posted_data as $key => $value ) {
					if ( is_array($value) ) {
						$value = implode(', ', $value);
					}

					if ( strpos($cf7formData, '_format_' . $key) ) {
						if ( strpos($cf7formData, $key) ) {
							$new_fields[ '[' . $key . ']' ] = $value;
						}
						$format = substr($cf7formData, strpos($cf7formData, '[_format_' . $key));
						$format = substr($format, 0, strpos($format, ']') + 1);
						$format = str_replace([ '[_format_' . $key . ' "', '"' ], '', $format);
						$timestamp = strtotime($value);
						$date = gmdate($format, $timestamp);
						$new_fields[ '[_format_' . $key . ' "' . $format . '"]' ] = ( false !== $date ) ? $date : '';
					} elseif ( strpos($cf7formData, '_raw_' . $key) ) {
						if ( strpos($cf7formData, $key) ) {
							$new_fields[ '[' . $key . ']' ] = $value;
						}
						if ( $form_fields ) {
							foreach ( $form_fields as $form_field ) {
								if ( isset($form_field->basetype) && 'select' === $form_field->basetype ) {
									$raw_index = array_search($value, $form_field->raw_values, true);
									if ( false !== $raw_index ) {
										$new_fields[ '[_raw_' . $key . ']' ] = $form_field->values[ $raw_index ];
									}
								}
							}
						}
					} else {
						$new_fields[ '[' . $key . ']' ] = $value;
					}
				}
			}


			// Initialize the CF7  $table array with common fields.
			
			$table = array(
				'form_id' => $contact_forms_id,
				'fields'  => $posted_data,
				'time'    => current_time('mysql'),
			);

			if ( true === $storecf7leds ) {
				SIMPLEFORM()->database->table->insert_cf7_leads($table);
			}

			/**
			 * Pro feature.
			 */
			if ( SIMPLEFORM()->database->table->is_pro_active() ) {
				$pro_instance = new DB();
				if ( true === $cf7metadata ) {
					$table['meta'] = $meta_data;
				}

				if ( true === $cf7leadsinslack ) {
					$pro_instance->send_leads_to_slack($contact_forms_id, $cf7metadata, $cf7leadsinslack, $recipiantslack, $posted_data, $meta_data);
				}

				$pro_instance->send_leads_to_mail($contact_forms_id, $cf7metadata, $mailNotification, $recipiantmail, $posted_data, $meta_data);
			}

			// Send to WhatsApp from selected IDs.
			if ( in_array($contact_forms_id, $selectedCF7Whatsredi) ) {
				$message = [];
				$message['data'] = strtr($cf7formData, $new_fields);
				$message['data'] = str_replace('{NEW_LINE}', "\n", $message['data']);

				// error_log( 'Data Received: message data' . print_r( $message['data'], true ) );

				// Send to WhatsApp .
				$formflow_new_opt = [];
				$formflow_new_opt['formflow_whatsapp_number'] = $whatsappNumber;
				$formflow_new_opt['formflow_whatsapp_data'] = $message['data'];
				$formflow_new_opt['formflow_new_tab'] = $openInNewTab;

				// Add nonce.
				$nonce = wp_create_nonce( 'formy_chat_submission' );
				$formflow_new_opt['nonce'] = $nonce;

				$cookie_name = 'formflow_cf7_redirection';
				setcookie($cookie_name, json_encode($formflow_new_opt), time() + ( 86400 * 30 ), '/');
			}
		}
	}

	/**
	 * Get tab form id and disable mail.
	 *
	 * @param array $skip_mail CF7 Id and disable mail.
	 *
	 * @return mixed
	 */
	public function formflow_disable_default_cf7_mail( $skip_mail ) {
		global $wpdb;
		$table = $wpdb->prefix . 'formflow_cf7_config_data';

		$contact_form = WPCF7_ContactForm::get_current();
		$form_id = $contact_form->id();

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE selectedCF7toconfigureID=%d", absint( $form_id ) ), ARRAY_A ); // phpcs:ignore

		// Extracting options individually.
		$selectedCF7toconfigureID = isset( $result['selectedCF7toconfigureID'] ) ? $result['selectedCF7toconfigureID'] : '';
		$disableCF7Mail = isset( $result['disableCF7Mail'] ) ? $result['disableCF7Mail'] : '';

		if ( ( $disableCF7Mail === 'true' || $disableCF7Mail === 'on' ) && $form_id == $selectedCF7toconfigureID ) {// phpcs:ignore
			
			return true;
		}

		return $skip_mail;
	}
}
