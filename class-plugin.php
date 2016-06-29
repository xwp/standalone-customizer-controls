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
class Plugin {

	const SLUG = 'standalone-customizer-controls';

	/**
	 * Examples.
	 *
	 * @var array
	 */
	public $examples = array();

	/**
	 * Admin page hook.
	 *
	 * @var string
	 */
	public $admin_page_hook;

	/**
	 * Ad hooks.
	 */
	function add_hooks() {
		add_action( 'wp_default_scripts', array( $this, 'register_scripts' ) );
		add_action( 'wp_default_styles', array( $this, 'register_styles' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
	}

	/**
	 * Register scripts.
	 *
	 * @param \WP_Scripts $scripts Scripts.
	 */
	function register_scripts( \WP_Scripts $scripts ) {
		$handle = static::SLUG;
		$src = plugin_dir_url( __FILE__ ) . 'standalone-customizer-controls.js';
		$deps = array(
			'wp-util', // @todo Should be a dependency for customize-controls in Core.
			'customize-controls',
		);
		$scripts->add( $handle, $src, $deps );
	}


	/**
	 * Register styles.
	 *
	 * @param \WP_Styles $styles Styles.
	 */
	function register_styles( \WP_Styles $styles ) {
		$handle = static::SLUG;
		$src = plugin_dir_url( __FILE__ ) . 'standalone-customizer-controls.css';
		$deps = array(
			'customize-controls',
		);
		$styles->add( $handle, $src, $deps );
	}

	/**
	 * Register a custom menu page.
	 */
	function register_admin_page() {
		$page_title = __( 'Standalone Customizer Controls Demo', 'standalone-customizer-controls' );
		$menu_title = __( 'Standalone Customizer Controls', 'standalone-customizer-controls' );
		$capability = 'edit_theme_options';
		$menu_slug = static::SLUG;
		$this->admin_page_hook = add_theme_page( $page_title, $menu_title, $capability, $menu_slug, array( $this, 'render_admin_page_contents' ) );

		add_action( 'load-' . $this->admin_page_hook, array( $this, 'load_admin_page' ) );
	}

	/**
	 * Load admin page.
	 */
	function load_admin_page() {
		global $wp_customize;

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
			$this->examples['text-control'] = array(
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
		$this->examples['color-control'] = array(
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
		$this->examples['textarea-control'] = array(
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
		$this->examples['media-control'] = array(
			'heading' => __( 'Media Control', 'standalone-customizer-controls' ),
			'setting' => $setting,
			'control' => $control,
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_footer', array( $wp_customize, 'render_control_templates' ) );
		add_action( 'admin_footer', array( $wp_customize, 'render_section_templates' ) );
	}

	/**
	 * Enqueue admin scripts.
	 */
	function enqueue_admin_scripts() {
		wp_scripts()->add_data( static::SLUG, 'data', sprintf(
			sprintf( '_wpCustomizeControlsL10n.required_value_invalidity = %s;', wp_json_encode(
				__( 'Missing required value.', 'standalone-customizer-controls' )
			) )
		) );

		wp_enqueue_script( static::SLUG );
		wp_enqueue_style( static::SLUG );

		foreach ( $this->examples as $example ) {
			/**
			 * Control.
			 *
			 * @var \WP_Customize_Control $control
			 */
			$control = $example['control'];
			$control->enqueue();
		}
	}

	/**
	 * Render admin page contents.
	 */
	function render_admin_page_contents() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Standalone Customizer Controls Demo', 'standalone-customizer-controls' ) ?></h1>

			<p>
				<?php esc_html_e( 'This is a demonstration of how to use Customizer settings and controls outside the context of the Customizer app. The goal of this demo is to show how Customizer controls can be used in Shortcode UI (Shortcake) forms and also provide an example for how Customizer controls can be embedded on the frontend.', 'standalone-customizer-controls' ); ?>
			</p>

			<?php foreach ( $this->examples as $example_id => $example ) : ?>
				<?php
				/**
				 * Control.
				 *
				 * @var \WP_Customize_Control $control
				 */
				$control = $example['control'];

				/**
				 * Setting.
				 *
				 * @var \WP_Customize_Setting $setting
				 */
				$setting = $example['setting'];

				$example_data = array_merge(
					$example,
					array(
						'setting' => array(
							'id' => $setting->id,
							'params' => $setting->json(),
						),
						'control' => array(
							'id' => $control->id,
							'params' => $control->json(),
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
}
