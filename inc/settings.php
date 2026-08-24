<?php
/**
 * Admin settings page — currently just an enable/disable toggle for the
 * schema module, growing to cover more as this plugin absorbs more of
 * Yoast's job over time.
 *
 * @package cbp-yeast-seo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option name for the single serialized settings array.
 *
 * @var string
 */
define( 'CBP_YEAST_SEO_SETTINGS_OPTION', 'cbp_yeast_seo_settings' );

/**
 * Read one CBP Yeast SEO setting.
 *
 * @param string $key     Setting key.
 * @param string $default Fallback if the key isn't set.
 * @return mixed
 */
function cbp_yeast_seo_get_setting( $key, $default = '' ) {
	$settings = get_option( CBP_YEAST_SEO_SETTINGS_OPTION, array() );
	return isset( $settings[ $key ] ) && '' !== $settings[ $key ] ? $settings[ $key ] : $default;
}

/**
 * Whether the schema module is enabled. Defaults to enabled on a fresh
 * install (no option saved yet) so behaviour matches the source theme's
 * always-on system out of the box; the hidden-field trick in the checkbox
 * markup below is what makes an explicit uncheck actually persist as off
 * rather than silently reverting to "on" (unchecked checkboxes submit
 * nothing at all, not a false value).
 *
 * @return bool
 */
function cbp_yeast_seo_schema_enabled() {
	$settings = get_option( CBP_YEAST_SEO_SETTINGS_OPTION, array() );
	return ! isset( $settings['enable_schema'] ) || ! empty( $settings['enable_schema'] );
}

/**
 * Register the settings page, section, and field.
 *
 * @return void
 */
function cbp_yeast_seo_register_settings_page() {
	add_menu_page(
		'CBP Yeast SEO',
		'CBP Yeast SEO',
		'manage_options',
		'cbp-yeast-seo',
		'cbp_yeast_seo_render_settings_page',
		'dashicons-search',
		80
	);

	register_setting( 'cbp_yeast_seo_settings', CBP_YEAST_SEO_SETTINGS_OPTION );

	add_settings_section( 'cbp_yeast_seo_schema', 'Schema', '__return_false', 'cbp-yeast-seo' );

	add_settings_field(
		'enable_schema',
		'Enable Schema Markup',
		'cbp_yeast_seo_render_enable_schema_field',
		'cbp-yeast-seo',
		'cbp_yeast_seo_schema'
	);
}
add_action( 'admin_menu', 'cbp_yeast_seo_register_settings_page' );

/**
 * Render the enable/disable checkbox.
 *
 * @return void
 */
function cbp_yeast_seo_render_enable_schema_field() {
	$checked = cbp_yeast_seo_schema_enabled();
	?>
	<label>
		<input type="hidden" name="<?php echo esc_attr( CBP_YEAST_SEO_SETTINGS_OPTION ); ?>[enable_schema]" value="0">
		<input
			type="checkbox"
			name="<?php echo esc_attr( CBP_YEAST_SEO_SETTINGS_OPTION ); ?>[enable_schema]"
			value="1"
			<?php checked( $checked ); ?>
		>
		Output the per-post JSON-LD schema field and the Yoast Organization-node suppression filter.
	</label>
	<p class="description">Turning this off stops schema output entirely and leaves Yoast's own output untouched — the per-post field content is preserved either way.</p>
	<?php
}

/**
 * Settings page HTML.
 *
 * @return void
 */
function cbp_yeast_seo_render_settings_page() {
	?>
	<div class="wrap">
		<h1>CBP Yeast SEO</h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'cbp_yeast_seo_settings' );
			do_settings_sections( 'cbp-yeast-seo' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
