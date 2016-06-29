<?php
/**
 * Plugin class.
 *
 * @package StandaloneCustomizerControls
 */

namespace StandaloneCustomizerControls;

/**
 * Class Plugin
 */
class Mailing_Address_Control extends \WP_Customize_Dynamic_Control {

	/**
	 * Render the Underscore template for this control.
	 *
	 * @access protected
	 */
	protected function content_template() {
		?>
		<p>
			<label>
				Street Address:
				<input data-customize-setting-property-link="street_address">
			</label>
		</p>
		<p>
			<label>
				City:
				<input data-customize-setting-property-link="city">
			</label>
		</p>
		<p>
			<label>
				State:
				<select data-customize-setting-property-link="state">
					<option value="ID">Idaho</option>
					<option value="OR">Oregon</option>
					<option value="WA">Washington</option>
				</select>
			</label>
		</p>
		<p>
			<label>
				ZIP:
				<input data-customize-setting-property-link="zip" size="5" pattern="\d\d\d\d\d(-\d\d\d\d)?">
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" data-customize-setting-property-link="is_business">
				Is business address?
			</label>
		</p>
		<?php
	}
}
