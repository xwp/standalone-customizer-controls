<?php
/**
 * Plugin name: Standalone Customizer Controls
 * Description: Use Customizer controls in contexts outside of the Customizer itself.
 * Author: Weston Ruter, XWP
 * Version: 0.1
 * Author: XWP
 * Author URI: https://xwp.co/
 * License: GPLv2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * Copyright (c) 2016 XWP (https://xwp.co/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @package StandaloneCustomizerControls
 */

namespace StandaloneCustomizerControls;

define( __NAMESPACE__ . '\PLUGIN_SLUG', 'standalone-customizer-controls' );

global $standalone_customizer_control_examples;
$standalone_customizer_control_examples = array();

/**
 * Register scripts.
 *
 * @param \WP_Scripts $scripts Scripts.
 */
function register_scripts( \WP_Scripts $scripts ) {
	$handle = PLUGIN_SLUG;
	$src = plugin_dir_url( __FILE__ ) . 'standalone-customizer-controls.js';
	$deps = array(
		'wp-util', // @todo Should be a dependency for customize-controls in Core.
		'customize-controls',
	);
	$scripts->add( $handle, $src, $deps );
}
add_action( 'wp_default_scripts', __NAMESPACE__ . '\register_scripts' );

/**
 * Register styles.
 *
 * @param \WP_Styles $styles Styles.
 */
function register_styles( \WP_Styles $styles ) {
	$handle = PLUGIN_SLUG;
	$src = plugin_dir_url( __FILE__ ) . 'standalone-customizer-controls.css';
	$deps = array(
		'customize-controls',
	);
	$styles->add( $handle, $src, $deps );
}
add_action( 'wp_default_styles', __NAMESPACE__ . '\register_styles' );

/**
 * Register a custom menu page.
 */
function register_admin_page() {
	$page_title = __( 'Standalone Customizer Controls Demo', 'standalone-customizer-controls' );
	$menu_title = __( 'Standalone Customizer Controls', 'standalone-customizer-controls' );
	$capability = 'edit_theme_options';
	$menu_slug = PLUGIN_SLUG;
	$admin_page_hook = add_theme_page( $page_title, $menu_title, $capability, $menu_slug, __NAMESPACE__ . '\render_admin_page_contents' );
	define( __NAMESPACE__ . '\ADMIN_PAGE_HOOK', $admin_page_hook );

	add_action( 'load-' . ADMIN_PAGE_HOOK, __NAMESPACE__ . '\load_admin_page' );
}
add_action( 'admin_menu', __NAMESPACE__ . '\register_admin_page' );

/**
 * Load admin page.
 */
function load_admin_page() {
	global $wp_customize, $standalone_customizer_control_examples;

	require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
	require_once ABSPATH . WPINC . '/class-wp-customize-setting.php';
	// @todo require_once __DIR__ . '/class-wp-customize-client-validated-setting.php';
	if ( empty( $wp_customize ) ) {
		$wp_customize = new \WP_Customize_Manager(); // WPCS: override ok.
		$wp_customize->register_controls();
	}

	// Re-used blogname control and setting.
	$setting = $wp_customize->get_setting( 'blogname' );
	$control = $wp_customize->get_control( 'blogname' );
	if ( $setting && $control ) {
		$setting->transport = 'none';
		$control->section = null;
		$standalone_customizer_control_examples['text-control'] = array(
			'heading' => __( 'Text Control', 'standalone-customizer-controls' ),
			'setting' => $setting,
			'control' => $control,
		);
	}

	// New sky color control.
	$id = 'sky_color';
	$setting = $wp_customize->add_setting( new \WP_Customize_Setting( $wp_customize, $id, array(
		'type' => 'js', // Prevent setting from being handled on the server.
		'transport' => 'none', // Prevent setting change from being synced anywhere.
		'default' => '#278df4',
	) ) );
	// @todo Allow a control's setting param to be WP_Customize_Setting instances in addition to just setting IDs.
	$control = $wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, $id, array(
		'label' => __( 'Sky Color', 'standalone-customizer-controls' ),
		'setting' => array( $setting->id ),
	) ) );
	$standalone_customizer_control_examples['color-control'] = array(
		'heading' => __( 'Color Control', 'standalone-customizer-controls' ),
		'setting' => $setting,
		'control' => $control,
	);

	// Textarea control.
	$id = 'about';
	$setting = $wp_customize->add_setting( $id, array(
		'type' => 'js',
		'transport' => 'none',
		'default' => 'WordPress is Free and open source software, built by a distributed community of mostly volunteer developers from around the world. WordPress comes with some awesome, worldview-changing rights courtesy of its license, the GPL.',
	) );
	$control = $wp_customize->add_control( $id, array(
		'type' => 'textarea',
		'label' => __( 'About', 'standalone-customizer-controls' ),
		'setting' => array( $setting->id ),
	) );
	$standalone_customizer_control_examples['textarea-control'] = array(
		'heading' => __( 'Textarea Control', 'standalone-customizer-controls' ),
		'setting' => $setting,
		'control' => $control,
	);

	// Media control.
	$id = 'avatar';
	$setting = $wp_customize->add_setting( $id, array(
		'type' => 'js',
		'transport' => 'none',
		'default' => 0,
	) );
	$control = new \WP_Customize_Media_Control( $wp_customize, $id, array(
		'mime_type' => 'image',
		'label' => __( 'Avatar', 'standalone-customizer-controls' ),
		'setting' => array( $setting->id ),
	) );
	$standalone_customizer_control_examples['media-control'] = array(
		'heading' => __( 'Media Control', 'standalone-customizer-controls' ),
		'setting' => $setting,
		'control' => $control,
	);

	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_admin_scripts' );
	add_action( 'admin_footer', array( $wp_customize, 'render_control_templates' ) );
	add_action( 'admin_footer', array( $wp_customize, 'render_section_templates' ) );
}

/**
 * Enqueue admin scripts.
 */
function enqueue_admin_scripts() {
	global $standalone_customizer_control_examples;

	wp_scripts()->add_data( PLUGIN_SLUG, 'data', sprintf(
		sprintf( '_wpCustomizeControlsL10n.required_value_invalidity = %s;', wp_json_encode(
			__( 'Missing required value.', 'standalone-customizer-controls' )
		) )
	) );

	wp_enqueue_script( PLUGIN_SLUG );
	wp_enqueue_style( PLUGIN_SLUG );

	foreach ( $standalone_customizer_control_examples as $example ) {
		$example['control']->enqueue();
	}
}

/**
 * Render admin page contents.
 */
function render_admin_page_contents() {
	global $standalone_customizer_control_examples;

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Standalone Customizer Controls Demo', 'standalone-customizer-controls' ) ?></h1>

		<p>
			<?php esc_html_e( 'This is a demonstration of how to use Customizer settings and controls outside the context of the Customizer app. The goal of this demo is to show how Customizer controls can be used in Shortcode UI (Shortcake) forms and also provide an example for how Customizer controls can be embedded on the frontend.', 'standalone-customizer-controls' ); ?>
		</p>

		<?php foreach ( $standalone_customizer_control_examples as $example_id => $example ) : ?>
			<?php
			$example_data = array_merge(
				$example,
				array(
					'setting' => array(
						'id' => $example['setting']->id,
						'params' => $example['setting']->json(),
					),
					'control' => array(
						'id' => $example['control']->id,
						'params' => $example['control']->json(),
					),
				)
			);

			?>
			<section id="<?php echo esc_attr( $example_id ) ?>" class="standalone-control-example" data-config="<?php echo esc_attr( wp_json_encode( $example_data ) ) ?>">
				<h2><?php echo esc_html( $example_data['heading'] ) ?></h2>
				<fieldset class="control"><ul></ul></fieldset>

				<label class="setting" for="<?php echo esc_attr( $example_id . 'setting' ) ?>"><?php esc_html_e( 'Setting value (JSON):', 'standalone-customizer-controls' ) ?></label>
				<textarea id="<?php echo esc_attr( $example_id . 'setting' ) ?>" class="setting widefat"></textarea>
			</section>
		<?php endforeach; ?>

		<script>
			jQuery( function( $ ) {
				StandaloneCustomizerControls.init( wp.customize, $( ".standalone-control-example" ) );
			} );
		</script>
	</div>
	<?php
}
