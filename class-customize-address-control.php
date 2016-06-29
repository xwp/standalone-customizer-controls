<?php
/**
 * Address control.
 *
 * @package StandaloneCustomizerControls
 */

namespace StandaloneCustomizerControls;

/**
 * Class Plugin
 */
class Customize_Address_Control extends \WP_Customize_Dynamic_Control {

	/**
	 * Address.
	 *
	 * @var string
	 */
	public $type = 'address';

	/**
	 * Enqueue control related scripts/styles.
	 */
	public function enqueue() {
		wp_enqueue_script( 'customize-address-control' );
	}

	/**
	 * Render the Underscore template for this control.
	 *
	 * @access protected
	 */
	protected function content_template() {
		?>
		<span class="customize-control-title">{{ data.label }}</span>

		<p class="street">
			<label>
				Street Address:
				<textarea data-customize-setting-property-link="street" required></textarea>
			</label>
		</p>

		<p class="city">
			<label>
				City:
				<input type="text" data-customize-setting-property-link="city" required>
			</label>
		</p>

		<p class="state">
			<label>
				State:
				<select data-customize-setting-property-link="state">
					<option value="ID">Idaho</option>
					<option value="OR">Oregon</option>
					<option value="WA">Washington</option>
				</select>
			</label>
		</p>

		<p class="zip">
			<label>
				ZIP:
				<input data-customize-setting-property-link="zip" size="5" pattern="\d\d\d\d\d(-\d\d\d\d)?" required>
			</label>
		</p>

		<p class="is-business">
			<label>
				<input type="checkbox" data-customize-setting-property-link="is_business">
				Is business address?
			</label>
		</p>
		<?php
	}
}
