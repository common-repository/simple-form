<?php
/**
 * Registering WordPress shortcode for the plugin.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */

namespace SIMPLEFORM;

// If direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Responsible for registering shortcode.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */
class Shortcode {

	/**
	 * Class constructor.
	 *
	 * @since 2.12.15
	 */
	public function __construct() {
		add_shortcode( 'simple_form', [ $this, 'shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Generate table html straight.
	 *
	 * @param  array $atts The shortcode attributes.
	 * @return HTML
	 */
	public function shortcode( $atts ) {
		if ( defined( 'ELEMENTOR_VERSION' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return $this->table_shortcode( $atts );
		} else {
			return $this->table_shortcode( $atts );
		}
	}

	/**
	 * Generate table edit link.
	 *
	 * @param  array $atts The shortcode attributes.
	 * @return null|HTML
	 */
	public function table_shortcode( $atts ) {

		$output = '';
		$shortcodeID = isset($atts['id']) ? absint($atts['id']) : null;

		if ( null === $shortcodeID || ! SIMPLEFORM()->database->table->get($shortcodeID) ) {
			// Shortcode ID not found or table cannot be loaded.
			return '<h5><b>' . __( 'Form may be deleted or can\'t be loaded.', 'simpleform' ) . '</b></h5><br>';
		}

		$form_id = 'simple_form_' . uniqid();

		if ( null !== $shortcodeID ) {
			$form_data = SIMPLEFORM()->database->table->get($shortcodeID);

			$markup_id = 'markup_' . esc_attr($form_id);

			if ( null !== $form_data && isset($form_data['id']) ) {
				$this->enqueue_scripts( $markup_id );
				$site_key = SIMPLEFORM()->database->table->turnstileget( 'siteKey' );

				$output .= '<div class="simple_form_container ' . esc_attr($form_id) . '" data-form-id="' . esc_attr($shortcodeID) . '" data-nonce="' . esc_attr(wp_create_nonce('simpleform_sheet_nonce_action')) . '" data-cf7="false">';
				$output .= '<form class="simple_form" data-form-id="' . esc_attr($shortcodeID) . '" data-nonce="' . esc_attr(wp_create_nonce('simpleform_sheet_nonce_action')) . '">';
				$output .= '<div class="simple_form_content">';

				// Check if form has captcha.
				$has_captcha = false;
				$captcha_positioned = 'top';
				$btnaccess = 'false';
				$theme = 'auto';
				if ( ! empty($form_data['form_fields']) ) {
					$form_fields = json_decode($form_data['form_fields'], true);
					foreach ( $form_fields as $field ) {
						if ( isset($field['type']) && 'cloudflare' === $field['type'] && isset($field['sitekey']) && $field['sitekey'] === $site_key ) {
							$has_captcha = true;
							$captcha_positioned = isset($field['positioned']) ? $field['positioned'] : 'above-btn';
							$btnaccess = isset($field['btnaccess']) ? $field['btnaccess'] : 'false';
							$theme = isset($field['theme']) ? $field['theme'] : 'auto';
							break;
						}
					}
				}

				if ( $has_captcha && 'top' === $captcha_positioned ) {
					// Include Turnstile container.
					$output .= '<div class="sf-turnstile-container" data-sitekey="' . esc_attr($site_key) . '" data-theme="' . esc_attr($theme) . '"></div>';
				}

				$output .= '<div class="ui segment simple_form_loader" id="' . esc_attr($markup_id) . '"></div>';
				$output .= '<br>';

				if ( $has_captcha && 'above-btn' === $captcha_positioned ) {
					// Include Turnstile container.
					$output .= '<div class="sf-turnstile-container" data-sitekey="' . esc_attr($site_key) . '" data-theme="' . esc_attr($theme) . '"></div>';
				}

				$output .= '<button type="button" class="submit-button sf-form-submit">Submit</button>';
				if ( $has_captcha && 'false' === $btnaccess ) {
					$output .= '<style>
						button.sf-form-submit {
							pointer-events: none;
							opacity: .5;
						}
					</style>';
				}

				if ( $has_captcha && 'below-btn' === $captcha_positioned ) {
					// Include Turnstile container.
					$output .= '<div class="sf-turnstile-container" data-sitekey="' . esc_attr($site_key) . '" data-theme="' . esc_attr($theme) . '"></div>';
				}

				$output .= '</div></form></div>';
				$output .= '<br><br>';

				// Pass the unique markup identifier as an attribute to the JavaScript code.
				$output .= '<script type="text/javascript">var markupId = "' . esc_js($markup_id) . '";</script>';
			}
		}

		return $output;
	}

	/**
	 * Enqueue turnstile files.
	 *
	 * @param  mixed $markup_id The markup_id id.
	 * @since 2.12.15
	 */
	public function enqueue_scripts( $markup_id ) {
		wp_register_script(
			'simpleform-turnstile-challenges',
			'//challenges.cloudflare.com/turnstile/v0/api.js?onload=simpleFormIntegration',
			[],
			'1.0',
			true
		);

		$site_key = SIMPLEFORM()->database->table->turnstileget( 'siteKey' );
		if ( $markup_id ) {
			$script = 'window.simpleFormIntegration = function () { ';

			$script .= "document.querySelectorAll('.sf-turnstile-container').forEach(function(container) {

					turnstile.render(container, {
						sitekey: '" . esc_attr( $site_key ) . "',
						callback: function(token) {
							console.log(token);
							var form = container.closest('.simple_form_container').querySelector('button.sf-form-submit');
							form.style.pointerEvents = 'auto';
							form.style.opacity = '1';
						}
					});
				});";

			$script .= '}';

			wp_add_inline_script( 'simpleform-turnstile-challenges', $script, 'before' );
			wp_enqueue_script( 'simpleform-turnstile-challenges' );
		}
	}
}
