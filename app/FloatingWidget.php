<?php
/**
 * Registering WordPress shortcode for the plugin.
 *
 * @since 2.0.0
 * @package SIMPLEFORM
 */

namespace SIMPLEFORM;

// If direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Responsible for registering shortcode.
 *
 * @since 2.0.0
 * @package SIMPLEFORM
 */
class FloatingWidget {

	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action('wp_footer', [ $this, 'display_floating_widget' ], 99 );
	}


	/**
	 * Sanitize data before use.
	 *
	 * @param int $array_or_string  The form data to sanitize.
	 *
	 * @return int|false
	 *
	 * @since 2.0.0
	 */
	public function sanitize_inputs( $array_or_string ) {
		if ( is_string( $array_or_string ) ) {
			$allowed_protocols = wp_allowed_protocols();
			$allowed_protocols[] = 'data';
			$allowed_tags = [
				'form' => [
					'action' => [],
					'method' => [],
					'class' => [],
					'aria-label' => [],
					'novalidate' => [],
					'data-status' => [],
					'style' => [],
				],
				'div' => [
					'class' => [],
					'id' => [],
					'lang' => [],
					'dir' => [],
					'style' => [],
				],
				'span' => [
					'class' => [],
					'data-name' => [],
					'style' => [],
				],
				'input' => [
					'size' => [],
					'class' => [],
					'autocomplete' => [],
					'aria-required' => [],
					'aria-invalid' => [],
					'placeholder' => [],
					'value' => [],
					'type' => [],
					'name' => [],
					'style' => [],
					'min' => [],
					'max' => [],
				],
				'textarea' => [
					'cols' => [],
					'rows' => [],
					'class' => [],
					'aria-invalid' => [],
					'placeholder' => [],
					'name' => [],
					'style' => [],
				],
				'select' => [
					'name' => [],
					'class' => [],
					'id' => [],
					'style' => [],
				],
				'option' => [
					'value' => [],
					'selected' => [],
					'disabled' => [],
					'label' => [],
					'class' => [],
					'style' => [],
				],
				'label' => [
					'for' => [],
					'style' => [],
				],
				'p' => [
					'role' => [],
					'aria-live' => [],
					'aria-atomic' => [],
					'style' => [],
				],
				'ul' => [],
				'li' => [],
				'br' => [],
			];
			$array_or_string = wp_kses( $array_or_string, $allowed_tags, $allowed_protocols );
		} elseif ( is_array( $array_or_string ) ) {
			foreach ( $array_or_string as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value = $this->sanitize_inputs( $value );
				} else {
					$allowed_protocols = wp_allowed_protocols();
					$allowed_protocols[] = 'data';
					$allowed_tags = [
						'form' => [
							'action' => [],
							'method' => [],
							'class' => [],
							'aria-label' => [],
							'novalidate' => [],
							'data-status' => [],
							'style' => [],
						],
						'div' => [
							'class' => [],
							'id' => [],
							'lang' => [],
							'dir' => [],
							'style' => [],
						],
						'span' => [
							'class' => [],
							'data-name' => [],
							'style' => [],
						],
						'input' => [
							'size' => [],
							'class' => [],
							'autocomplete' => [],
							'aria-required' => [],
							'aria-invalid' => [],
							'placeholder' => [],
							'value' => [],
							'type' => [],
							'name' => [],
							'style' => [],
							'min' => [],
							'max' => [],
						],
						'textarea' => [
							'cols' => [],
							'rows' => [],
							'class' => [],
							'aria-invalid' => [],
							'placeholder' => [],
							'name' => [],
							'style' => [],
						],
						'select' => [
							'name' => [],
							'class' => [],
							'id' => [],
							'style' => [],
						],
						'option' => [
							'value' => [],
							'selected' => [],
							'disabled' => [],
							'label' => [],
							'class' => [],
							'style' => [],
						],
						'label' => [
							'for' => [],
							'style' => [],
						],
						'p' => [
							'role' => [],
							'aria-live' => [],
							'aria-atomic' => [],
							'style' => [],
						],
						'ul' => [],
						'li' => [],
						'br' => [],
					];
					$value = wp_kses( $value, $allowed_tags, $allowed_protocols  );
				}
			}
		}
		return $array_or_string;
	}

	/**
	 * Generate table Floating straight.
	 */
	public function display_floating_widget() {
		// Get the options from the options table.
		$submittext = 'Send Message';
		$formheader = 'Have question? - Submit the Form';

		$headertextcolor = '#ffffff';
		$headerbgcolor = '#293239';
		$formheadertextalignment = 'center';
		$formheaderfontsize = '15';

		$submitbtntextcolor = '#ffffff';
		$submitbtnbgcolor = 'orange';

		$bodytextcolor = '#293239';
		$bodybgcolor = '#f7f7f7';

		$ctatextcolor = '#293239';
		$ctabgcolor = '#f7f7f7';

		$flotingwidgetsbgcolor = '#0065a0';
		$submitbtntexthovercolor = '#2196f3';
		$selectedFont = 'Arial';
		$formcta = '';

		$formCustomization = false;
		$widgetsposition  = 'right';
		$left_widgetsposition  = '';
		$right_widgetsposition = '20';

		$left_position_wid = 'unset';
		$right_position_wid = '20px';

		$left_position_cta = 'unset';
		$right_position_cta = '72px';

		$widgetForm_left = 'unset';
		$widgetForm_right = '10px';

		$formopenbydefault = 'false';
		$formcloseonsubmit = 'false';

		$floating_widgets = 'true';
		$cf7floatingwidgets = 'false';
		$selected_cf7_floatingwidgets = null;
		$selectedTable = null;

		$options = get_option('form_settings');
		if ( isset($options['formCustomization']) && $options['formCustomization'] === 'true' ) {
			$submittext = isset($options['submitbtntext']) ? esc_html($options['submitbtntext']) : 'Send Message';
			$formheader = isset($options['formheader']) ? esc_html($options['formheader']) : 'Have question? - Submit the Form';

			$formheaderfontsize = isset($options['formheaderfontsize']) ? esc_html($options['formheaderfontsize']) : '15';
			$formheadertextalignment = isset($options['formheadertextalignment']) ? esc_html($options['formheadertextalignment']) : 'center';

			$submitbtntextcolor = isset($options['submitbtntextcolor']) ? sanitize_hex_color($options['submitbtntextcolor']) : '#ffffff';
			$submitbtnbgcolor = isset($options['submitbtnbgcolor']) ? sanitize_hex_color($options['submitbtnbgcolor']) : 'orange';

			$headerbgcolor = isset($options['headerbackgroundcolor']) ? sanitize_hex_color($options['headerbackgroundcolor']) : 'orange';
			$headertextcolor = isset($options['headertextcolor']) ? sanitize_hex_color($options['headertextcolor']) : '#ffffff';

			$bodytextcolor = isset($options['formfieldtextcolor']) ? sanitize_hex_color($options['formfieldtextcolor']) : '#ffffff';
			$bodybgcolor = isset($options['formbackgroundcolor']) ? sanitize_hex_color($options['formbackgroundcolor']) : 'orange';

			$ctatextcolor = isset($options['ctatextcolor']) ? sanitize_hex_color($options['ctatextcolor']) : '#293239';
			$ctabgcolor = isset($options['ctabgcolor']) ? sanitize_hex_color($options['ctabgcolor']) : '#FFFFFF';

			$flotingwidgetsbgcolor = isset($options['flotingwidgetsbgcolor']) ? sanitize_hex_color($options['flotingwidgetsbgcolor']) : 'orange';
			$submitbtntexthovercolor = isset($options['submitbtntexthovercolor']) ? sanitize_hex_color($options['submitbtntexthovercolor']) : '#2196f3';

			$selectedFont = isset($options['selectedFont']) ? esc_html($options['selectedFont']) : 'Arial';
			$formcta = isset($options['formcta']) ? esc_html($options['formcta']) : 'Click to Chat';

			$formCustomization = isset($options['formCustomization']) ? esc_html($options['formCustomization']) : false;

			// New Pro.
			$iconUrl = isset($options['iconUrl']) ? esc_html($options['iconUrl']) : '';
			$widgetsposition = isset($options['widgetsposition']) ? esc_html($options['widgetsposition']) : 'right';
			$left_widgetsposition = isset($options['left_widgetsposition']) ? esc_html($options['left_widgetsposition']) : 'unset';
			$right_widgetsposition = isset($options['right_widgetsposition']) ? esc_html($options['right_widgetsposition']) : '20';

			$formopenbydefault = isset($options['formopenbydefault']) ? esc_html($options['formopenbydefault']) : 'false';
			$formcloseonsubmit = isset($options['formcloseonsubmit']) ? esc_html($options['formcloseonsubmit']) : 'false';

			$floating_widgets = isset($options['floatingwidgets']) ? esc_html($options['floatingwidgets']) : 'true';

			$cf7floatingwidgets = isset($options['cf7floatingwidgets']) ? esc_html($options['cf7floatingwidgets']) : 'false';

			$selected_cf7_floatingwidgets = isset($options['selectedCF7Floatingwidgets']) ? esc_html($options['selectedCF7Floatingwidgets']) : null;
			$selectedTable = isset($options['selectedTable']) ? esc_html($options['selectedTable']) : null;

		}

		if ( 'true' === $formCustomization ) {
			// Float Icon position.
			$left_position_wid = ( 'Left' === $widgetsposition ) ? '20px' : 'unset';
			$right_position_wid = ( 'Right' === $widgetsposition ) ? '20px' : 'unset';

			// CTA position.
			$left_position_cta = ( 'Left' === $widgetsposition ) ? '72px' : 'unset';
			$right_position_cta = ( 'Right' === $widgetsposition ) ? '72px' : 'unset';

			// Form position.
			$widgetForm_left = ( 'Left' === $widgetsposition ) ? '10px' : 'unset';
			$widgetForm_right = ( 'Right' === $widgetsposition ) ? '10px' : 'unset';
		}

		if ( 'Custom' === $widgetsposition ) {
			// Float Icon position.
			$left_position_wid = $left_widgetsposition . 'px';
			$right_position_wid = $right_widgetsposition . 'px';

			// CTA position.
			$left_position_cta = ( 'Custom' === $widgetsposition ) ? '72px' : 'unset';

			// Form position.
			$widgetForm_left = ( 'Custom' === $widgetsposition ) ? '0px' : 'unset';
			$widgetForm_right = ( 'Custom' === $widgetsposition ) ? '0px' : 'unset';
		}

		// Check if floatingwidgets is true and selectedTable is set.

		if ( isset($options['floatingwidgets']) && 'true' === $options['floatingwidgets'] && isset($options['selectedTable']) ) {
			$selectedTable = $options['selectedTable'];
		}
		if ( isset($options['cf7floatingwidgets']) && 'true' === $options['cf7floatingwidgets'] && isset($options['selectedCF7Floatingwidgets']) ) {
			$selected_cf7_floatingwidgets = $options['selectedCF7Floatingwidgets'];
		}

		$output = '
		<div class="floating-whatsapp">
			<button type="button" class="whatsapp-icon" id="jumping-whatsapp">';
		if ( ! empty($iconUrl) ) {

			$output .= '<div class="scf-uploaded-icon">
					<img src="' . esc_url($iconUrl) . '" alt="Custom Icon" />
				</div>';
		} else {
			$output .= '<svg width="60" height="60" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
				<g fill="none" fill-rule="evenodd">
					<circle cx="16" cy="16" r="16" fill="#1C98F7"/>
					<path fill="#FFF" d="M16.28 23.325a11.45 11.45 0 0 0 2.084-.34a5.696 5.696 0 0 0 2.602.17a.627.627 0 0 1 .104-.008c.31 0 .717.18 1.31.56v-.625a.61.61 0 0 1 .311-.531c.258-.146.498-.314.717-.499c.864-.732 1.352-1.708 1.352-2.742c0-.347-.055-.684-.159-1.006c.261-.487.472-.999.627-1.53A4.59 4.59 0 0 1 26 19.31c0 1.405-.654 2.715-1.785 3.673a5.843 5.843 0 0 1-.595.442v1.461c0 .503-.58.792-.989.493a15.032 15.032 0 0 0-1.2-.81a2.986 2.986 0 0 0-.368-.187c-.34.051-.688.077-1.039.077c-1.412 0-2.716-.423-3.743-1.134zm-7.466-2.922C7.03 18.89 6 16.829 6 14.62c0-4.513 4.258-8.12 9.457-8.12c5.2 0 9.458 3.607 9.458 8.12c0 4.514-4.259 8.121-9.458 8.121c-.584 0-1.162-.045-1.728-.135c-.245.058-1.224.64-2.635 1.67c-.511.374-1.236.013-1.236-.616v-2.492a9.27 9.27 0 0 1-1.044-.765zm4.949.666c.043 0 .087.003.13.01c.51.086 1.034.13 1.564.13c4.392 0 7.907-2.978 7.907-6.589c0-3.61-3.515-6.588-7.907-6.588c-4.39 0-7.907 2.978-7.907 6.588c0 1.746.821 3.39 2.273 4.62c.365.308.766.588 1.196.832c.241.136.39.39.39.664v1.437c1.116-.749 1.85-1.104 2.354-1.104zm-2.337-4.916c-.685 0-1.24-.55-1.24-1.226c0-.677.555-1.226 1.24-1.226c.685 0 1.24.549 1.24 1.226c0 .677-.555 1.226-1.24 1.226zm4.031 0c-.685 0-1.24-.55-1.24-1.226c0-.677.555-1.226 1.24-1.226c.685 0 1.24.549 1.24 1.226c0 .677-.555 1.226-1.24 1.226zm4.031 0c-.685 0-1.24-.55-1.24-1.226c0-.677.555-1.226 1.24-1.226c.685 0 1.24.549 1.24 1.226c0 .677-.555 1.226-1.24 1.226z"/>
				</g>
			</svg>';
		}
		if ( ! empty( $formcta ) ) {
			$output .= '<span class="cta-text">' . esc_attr($formcta) . '</span>';
		}
			$output .= '</button>
			<div class="form-content">    
				<header class="clearfix">
					<h4 class="offline">' . esc_attr($formheader) . '</h4>
					<span class="sf-close"><i class="fa fa-close fa-2x" aria-hidden="true"></i></span>
				</header>

				'
				;

			// Form Flow.
		if ( isset($options['floatingwidgets']) && 'true' === $options['floatingwidgets'] && isset($options['selectedTable']) ) {

			$output .= '<div class="simple_form_container simple_form_container_floating simple_form_formFlow_container ' . esc_attr($selectedTable) . '" 			data-open="' . esc_attr($formopenbydefault) . '" data-close="' . esc_attr($formcloseonsubmit) . '" data-cf7="false"  data-form-id="' . esc_attr($selectedTable) . '" data-nonce="' . esc_attr(wp_create_nonce('simpleform_sheet_nonce_action')) . '">
					<form class="simple_form" data-form-id="' . esc_attr($selectedTable) . '" data-nonce="' . esc_attr(wp_create_nonce('simpleform_sheet_nonce_action')) . '">
						<div class="simple_form_content">
							<div class="ui segment simple_form_loader" id="' . esc_attr($selectedTable) . '"></div>
							<br>
							<div>
							<button type="button" class="submit-button main-search-btn">' . esc_attr($submittext) . '</button>
							</div>
						</div>
					</form>
				</div>';

		}

		/**
		 * Contact form 7 widgets
		 */
		
		if ( isset($options['cf7floatingwidgets']) && 'true' === $options['cf7floatingwidgets'] && isset($options['selectedCF7Floatingwidgets']) ) {
			$output .= '<div class="simple_form_container simple_form_container_floating simple_form_cf7_container ' . esc_attr($selected_cf7_floatingwidgets) . '" data-open="' . esc_attr($formopenbydefault) . '" data-close="' . esc_attr($formcloseonsubmit) . '" data-cf7="' . esc_attr($cf7floatingwidgets) . '" data-form-id="' . esc_attr($selected_cf7_floatingwidgets) . '" data-nonce="' . esc_attr(wp_create_nonce('simpleform_sheet_nonce_action')) . '">
					
					<div class="ui segment formflow_cf7_loader" id="' . esc_attr($selected_cf7_floatingwidgets) . '">' . do_shortcode("[contact-form-7 id='$selected_cf7_floatingwidgets']") . '</div>

				</div>';
		}

			$output .= '</div>';
			$output .= '<style>	
					.form-content,
					h4.offline,
					button.submit-button.main-search-btn,
					input.wpcf7-form-control.wpcf7-submit,
					span.cta-text{
						font-family: ' . esc_attr($selectedFont) . ';
					}

					.form-content{
						left: ' . esc_attr($widgetForm_left) . ';
						right: ' . esc_attr($widgetForm_right) . ';
					}

					span.cta-text{
						background-color: ' . esc_attr($ctabgcolor) . '; 
						color: ' . esc_attr($ctatextcolor) . ';

						left: ' . esc_attr($left_position_cta) . '; 
						right: ' . esc_attr($right_position_cta) . '; 							
					}

				
					.simple_form_container_floating{
						background-color: ' . esc_attr($bodybgcolor) . '; 
						color: ' . esc_attr($bodytextcolor) . ';
						scrollbar-width: thin;
						scrollbar-color: ' . esc_attr($headerbgcolor) . ' #fff;
					}

					header.clearfix{
						background-color: ' . esc_attr($headerbgcolor) . '; 
						justify-content: ' . esc_attr($formheadertextalignment) . ';
					}

					span.sf-close{
						color: ' . esc_attr($headertextcolor) . ';
					}

					.floating-whatsapp .form-content h4{
						color: ' . esc_attr($headertextcolor) . ';
						font-size: ' . esc_attr($formheaderfontsize) . 'px;
					}

					circle {
						fill: ' . esc_attr($flotingwidgetsbgcolor) . ';
					}
					.floating-whatsapp {
						font-family: ' . esc_attr($selectedFont) . '; 
						left: ' . esc_attr($left_position_wid) . '; 
						right: ' . esc_attr($right_position_wid) . '; 
					}	
					.floating-whatsapp .main-search-btn, input.wpcf7-form-control.wpcf7-submit{
						width: 100%;
						background-color: ' . esc_attr($submitbtnbgcolor) . '; 
						color: ' . esc_attr($submitbtntextcolor) . ';
					}
					.floating-whatsapp .main-search-btn:hover, input.wpcf7-form-control.wpcf7-submit:hover {
						background-color: ' . esc_attr($submitbtntexthovercolor) . ';
					}

					input.wpcf7-form-control.wpcf7-submit.has-spinner {
						align-items: center;
						justify-content: center;
						transition: all .35s;
						border: rgba(0, 0, 0, 0);
						color: #fff;
						padding: 10px 16px;
						cursor: pointer;
						border-radius: 5px;
					}
					span.wpcf7-spinner {
						top: -30px;
						right: 5px;
					}
				</style>
		</div>';

		if ( isset($options['floatingwidgets']) && 'true' === $options['floatingwidgets'] && isset($options['selectedTable']) ) {
			$output .= '<script type="text/javascript"> var markupId = "' . esc_js($selectedTable) . '"; </script>';
		}

		if ( isset($options['cf7floatingwidgets']) && 'true' === $options['cf7floatingwidgets'] && isset($options['selectedTable']) ) {
			$output .= '<script type="text/javascript"> var markupId = "' . esc_js($selected_cf7_floatingwidgets) . '"; </script>';
		}

		// Render the html floating form.
		if ( isset($options['cf7floatingwidgets']) && 'true' === $options['cf7floatingwidgets'] ||
			isset($options['floatingwidgets']) && 'true' === $options['floatingwidgets'] ) {
			echo $output; //phpcs:ignore 
		}
	}
}
