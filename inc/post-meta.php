<?php
/**
 * Per-post SEO fields, editor UI, and front-end output.
 *
 * @package cbp-yeast-seo
 */

defined( 'ABSPATH' ) || exit;

define( 'CBP_YEAST_SEO_SCHEMA_META_KEY', 'cbp_yeast_seo_schema_jsonld' );
define( 'CBP_YEAST_SEO_TITLE_META_KEY', 'cbp_yeast_seo_title' );
define( 'CBP_YEAST_SEO_DESCRIPTION_META_KEY', 'cbp_yeast_seo_meta_description' );
define( 'CBP_YEAST_SEO_OG_TITLE_META_KEY', 'cbp_yeast_seo_og_title' );
define( 'CBP_YEAST_SEO_OG_DESCRIPTION_META_KEY', 'cbp_yeast_seo_og_description' );
define( 'CBP_YEAST_SEO_OG_IMAGE_META_KEY', 'cbp_yeast_seo_og_image' );
define( 'CBP_YEAST_SEO_TWITTER_IMAGE_META_KEY', 'cbp_yeast_seo_twitter_image' );
define( 'CBP_YEAST_SEO_ROBOTS_META_KEY', 'cbp_yeast_seo_robots_index' );
define( 'CBP_YEAST_SEO_CANONICAL_META_KEY', 'cbp_yeast_seo_canonical_url' );

/**
 * Returns the post types this plugin attaches to.
 *
 * @return string[]
 */
function cbp_yeast_seo_post_types() {
	$post_types = get_post_types(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'names'
	);

	unset( $post_types['attachment'] );

	return $post_types;
}

/**
 * Registers all per-post SEO meta fields.
 *
 * @return void
 */
function cbp_yeast_seo_register_meta() {
	$fields = array(
		CBP_YEAST_SEO_TITLE_META_KEY         => array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
		),
		CBP_YEAST_SEO_DESCRIPTION_META_KEY   => array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
		),
		CBP_YEAST_SEO_OG_TITLE_META_KEY      => array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
		),
		CBP_YEAST_SEO_OG_DESCRIPTION_META_KEY => array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
		),
		CBP_YEAST_SEO_OG_IMAGE_META_KEY      => array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
		),
		CBP_YEAST_SEO_TWITTER_IMAGE_META_KEY => array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
		),
		CBP_YEAST_SEO_ROBOTS_META_KEY        => array(
			'type'              => 'string',
			'single'            => true,
			'default'           => 'index',
			'show_in_rest'      => true,
			'sanitize_callback' => 'cbp_yeast_seo_sanitize_robots_meta',
		),
		CBP_YEAST_SEO_CANONICAL_META_KEY     => array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
		),
		CBP_YEAST_SEO_SCHEMA_META_KEY        => array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'cbp_yeast_seo_sanitize_schema_meta',
		),
	);

	foreach ( cbp_yeast_seo_post_types() as $post_type ) {
		foreach ( $fields as $meta_key => $args ) {
			register_post_meta(
				$post_type,
				$meta_key,
				$args + array(
					'auth_callback' => function ( $allowed, $registered_meta_key, $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}
}
add_action( 'init', 'cbp_yeast_seo_register_meta' );

/**
 * Sanitizes the robots choice.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function cbp_yeast_seo_sanitize_robots_meta( $value ) {
	return 'noindex' === $value ? 'noindex' : 'index';
}

/**
 * Schema sanitizer shared between classic save and REST saves.
 *
 * @param mixed $value Raw meta value.
 * @return string
 */
function cbp_yeast_seo_sanitize_schema_meta( $value ) {
	return is_string( $value ) ? trim( $value ) : '';
}

/**
 * Whether a post type uses the block editor, where this plugin renders a
 * dedicated modal instead of classic meta boxes.
 *
 * @param string $post_type Post type slug.
 * @return bool
 */
function cbp_yeast_seo_uses_block_editor( $post_type ) {
	return function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( $post_type );
}

/**
 * Registers a classic fallback meta box for non-block-editor screens.
 *
 * @param string $post_type Current post type.
 * @return void
 */
function cbp_yeast_seo_add_meta_box( $post_type ) {
	if ( cbp_yeast_seo_uses_block_editor( $post_type ) ) {
		return;
	}

	add_meta_box(
		'cbp-yeast-seo',
		__( 'Yeast SEO', 'cbp-yeast-seo' ),
		'cbp_yeast_seo_render_meta_box',
		$post_type,
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'cbp_yeast_seo_add_meta_box' );

/**
 * Renders the classic-editor fallback meta box.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function cbp_yeast_seo_render_meta_box( $post ) {
	$title            = get_post_meta( $post->ID, CBP_YEAST_SEO_TITLE_META_KEY, true );
	$description      = get_post_meta( $post->ID, CBP_YEAST_SEO_DESCRIPTION_META_KEY, true );
	$og_title         = get_post_meta( $post->ID, CBP_YEAST_SEO_OG_TITLE_META_KEY, true );
	$og_description   = get_post_meta( $post->ID, CBP_YEAST_SEO_OG_DESCRIPTION_META_KEY, true );
	$og_image         = get_post_meta( $post->ID, CBP_YEAST_SEO_OG_IMAGE_META_KEY, true );
	$twitter_image    = get_post_meta( $post->ID, CBP_YEAST_SEO_TWITTER_IMAGE_META_KEY, true );
	$robots_index     = get_post_meta( $post->ID, CBP_YEAST_SEO_ROBOTS_META_KEY, true );
	$canonical_url    = get_post_meta( $post->ID, CBP_YEAST_SEO_CANONICAL_META_KEY, true );
	$schema           = get_post_meta( $post->ID, CBP_YEAST_SEO_SCHEMA_META_KEY, true );
	$global_noindex   = ! get_option( 'blog_public' );
	$robots_index     = $robots_index ? $robots_index : 'index';

	wp_nonce_field( 'cbp_yeast_seo_save_meta_box', 'cbp_yeast_seo_meta_box_nonce' );
	?>
	<p>
		<label for="cbp-yeast-seo-title-field"><strong><?php esc_html_e( 'Page title', 'cbp-yeast-seo' ); ?></strong></label>
		<input type="text" id="cbp-yeast-seo-title-field" name="<?php echo esc_attr( CBP_YEAST_SEO_TITLE_META_KEY ); ?>" value="<?php echo esc_attr( $title ); ?>" class="widefat">
	</p>
	<p>
		<label for="cbp-yeast-seo-description-field"><strong><?php esc_html_e( 'Meta description', 'cbp-yeast-seo' ); ?></strong></label>
		<textarea id="cbp-yeast-seo-description-field" name="<?php echo esc_attr( CBP_YEAST_SEO_DESCRIPTION_META_KEY ); ?>" rows="3" class="widefat"><?php echo esc_textarea( $description ); ?></textarea>
	</p>
	<hr>
	<p>
		<label for="cbp-yeast-seo-robots-field"><strong><?php esc_html_e( 'Indexing', 'cbp-yeast-seo' ); ?></strong></label>
		<select id="cbp-yeast-seo-robots-field" name="<?php echo esc_attr( CBP_YEAST_SEO_ROBOTS_META_KEY ); ?>">
			<option value="index" <?php selected( $robots_index, 'index' ); ?>><?php esc_html_e( 'Index', 'cbp-yeast-seo' ); ?></option>
			<option value="noindex" <?php selected( $robots_index, 'noindex' ); ?>><?php esc_html_e( 'Noindex', 'cbp-yeast-seo' ); ?></option>
		</select>
	</p>
	<?php if ( $global_noindex ) : ?>
		<p class="description"><?php esc_html_e( 'WordPress is currently set to discourage search engines, so this page will output noindex regardless of the per-post setting.', 'cbp-yeast-seo' ); ?></p>
	<?php endif; ?>
	<p>
		<label for="cbp-yeast-seo-canonical-field"><strong><?php esc_html_e( 'Canonical URL', 'cbp-yeast-seo' ); ?></strong></label>
		<input type="url" id="cbp-yeast-seo-canonical-field" name="<?php echo esc_attr( CBP_YEAST_SEO_CANONICAL_META_KEY ); ?>" value="<?php echo esc_attr( $canonical_url ); ?>" class="widefat code">
	</p>
	<p class="description"><?php esc_html_e( 'Optional. Overrides the canonical link tag for this page only. Leave blank to keep the default (this page\'s own URL).', 'cbp-yeast-seo' ); ?></p>
	<hr>
	<p>
		<label for="cbp-yeast-seo-og-title-field"><strong><?php esc_html_e( 'Open Graph title', 'cbp-yeast-seo' ); ?></strong></label>
		<input type="text" id="cbp-yeast-seo-og-title-field" name="<?php echo esc_attr( CBP_YEAST_SEO_OG_TITLE_META_KEY ); ?>" value="<?php echo esc_attr( $og_title ); ?>" class="widefat">
	</p>
	<p>
		<label for="cbp-yeast-seo-og-description-field"><strong><?php esc_html_e( 'Open Graph description', 'cbp-yeast-seo' ); ?></strong></label>
		<textarea id="cbp-yeast-seo-og-description-field" name="<?php echo esc_attr( CBP_YEAST_SEO_OG_DESCRIPTION_META_KEY ); ?>" rows="3" class="widefat"><?php echo esc_textarea( $og_description ); ?></textarea>
	</p>
	<p>
		<label for="cbp-yeast-seo-og-image-field"><strong><?php esc_html_e( 'Open Graph image URL', 'cbp-yeast-seo' ); ?></strong></label>
		<input type="url" id="cbp-yeast-seo-og-image-field" name="<?php echo esc_attr( CBP_YEAST_SEO_OG_IMAGE_META_KEY ); ?>" value="<?php echo esc_attr( $og_image ); ?>" class="widefat code">
	</p>
	<p>
		<label for="cbp-yeast-seo-twitter-image-field"><strong><?php esc_html_e( 'Twitter/X image URL', 'cbp-yeast-seo' ); ?></strong></label>
		<input type="url" id="cbp-yeast-seo-twitter-image-field" name="<?php echo esc_attr( CBP_YEAST_SEO_TWITTER_IMAGE_META_KEY ); ?>" value="<?php echo esc_attr( $twitter_image ); ?>" class="widefat code">
	</p>
	<?php if ( cbp_yeast_seo_schema_enabled() ) : ?>
		<hr>
		<p>
			<label for="cbp-yeast-seo-schema-field"><strong><?php esc_html_e( 'Schema (JSON-LD)', 'cbp-yeast-seo' ); ?></strong></label>
		</p>
		<p>
			<?php echo esc_html( cbp_yeast_seo_yoast_active() ? __( 'One JSON object, or an array/@graph of objects. Leave blank to use Yoast defaults. Saving with a problem will not be blocked; check for an admin notice after save.', 'cbp-yeast-seo' ) : __( 'One JSON object, or an array/@graph of objects. Saving with a problem will not be blocked; check for an admin notice after save.', 'cbp-yeast-seo' ) ); ?>
		</p>
		<textarea id="cbp-yeast-seo-schema-field" name="<?php echo esc_attr( CBP_YEAST_SEO_SCHEMA_META_KEY ); ?>" rows="10" class="widefat" style="font-family:monospace;"><?php echo esc_textarea( $schema ); ?></textarea>
	<?php endif; ?>
	<?php
}

/**
 * Saves the classic-editor fallback meta box.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function cbp_yeast_seo_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['cbp_yeast_seo_meta_box_nonce'] ) ||
		! wp_verify_nonce( wp_unslash( $_POST['cbp_yeast_seo_meta_box_nonce'] ), 'cbp_yeast_seo_save_meta_box' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array(
		CBP_YEAST_SEO_TITLE_META_KEY          => 'sanitize_text_field',
		CBP_YEAST_SEO_DESCRIPTION_META_KEY    => 'sanitize_textarea_field',
		CBP_YEAST_SEO_OG_TITLE_META_KEY       => 'sanitize_text_field',
		CBP_YEAST_SEO_OG_DESCRIPTION_META_KEY => 'sanitize_textarea_field',
		CBP_YEAST_SEO_OG_IMAGE_META_KEY       => 'esc_url_raw',
		CBP_YEAST_SEO_TWITTER_IMAGE_META_KEY  => 'esc_url_raw',
		CBP_YEAST_SEO_ROBOTS_META_KEY         => 'cbp_yeast_seo_sanitize_robots_meta',
		CBP_YEAST_SEO_CANONICAL_META_KEY      => 'esc_url_raw',
		CBP_YEAST_SEO_SCHEMA_META_KEY         => 'cbp_yeast_seo_sanitize_schema_meta',
	);

	foreach ( $fields as $meta_key => $sanitize_callback ) {
		if ( ! isset( $_POST[ $meta_key ] ) ) {
			continue;
		}

		$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $meta_key ] ) );

		if ( CBP_YEAST_SEO_ROBOTS_META_KEY === $meta_key ) {
			update_post_meta( $post_id, $meta_key, $value );
			continue;
		}

		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}
}
add_action( 'save_post', 'cbp_yeast_seo_save_meta_box' );

/**
 * Loads the block-editor modal assets.
 *
 * @return void
 */
function cbp_yeast_seo_enqueue_block_editor_assets() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || empty( $screen->post_type ) || ! cbp_yeast_seo_uses_block_editor( $screen->post_type ) ) {
		return;
	}

	$script_path = CBP_YEAST_SEO_PATH . 'assets/editor.js';
	$style_path  = CBP_YEAST_SEO_PATH . 'assets/editor.css';

	wp_enqueue_media();

	wp_enqueue_script(
		'cbp-yeast-seo-editor',
		CBP_YEAST_SEO_URL . 'assets/editor.js',
		array( 'wp-components', 'wp-data', 'wp-edit-post', 'wp-element', 'wp-i18n', 'wp-plugins' ),
		file_exists( $script_path ) ? filemtime( $script_path ) : CBP_YEAST_SEO_VERSION,
		true
	);

	wp_enqueue_style(
		'cbp-yeast-seo-editor',
		CBP_YEAST_SEO_URL . 'assets/editor.css',
		array( 'wp-components' ),
		file_exists( $style_path ) ? filemtime( $style_path ) : CBP_YEAST_SEO_VERSION
	);

	wp_add_inline_script(
		'cbp-yeast-seo-editor',
		'window.cbpYeastSeoEditor = ' . wp_json_encode(
			array(
				'schemaEnabled' => cbp_yeast_seo_schema_enabled(),
				'yoastActive'   => cbp_yeast_seo_yoast_active(),
				'blogPublic'    => (bool) get_option( 'blog_public' ),
				'homeUrl'       => home_url( '/' ),
				'defaultSocialImage' => cbp_yeast_seo_get_setting( 'default_social_image', '' ),
				'twitterSite'   => cbp_yeast_seo_get_setting( 'twitter_site', '' ),
				'metaKeys'      => array(
					'title'            => CBP_YEAST_SEO_TITLE_META_KEY,
					'description'      => CBP_YEAST_SEO_DESCRIPTION_META_KEY,
					'ogTitle'          => CBP_YEAST_SEO_OG_TITLE_META_KEY,
					'ogDescription'    => CBP_YEAST_SEO_OG_DESCRIPTION_META_KEY,
					'ogImage'          => CBP_YEAST_SEO_OG_IMAGE_META_KEY,
					'twitterImage'     => CBP_YEAST_SEO_TWITTER_IMAGE_META_KEY,
					'robotsIndex'      => CBP_YEAST_SEO_ROBOTS_META_KEY,
					'canonicalUrl'     => CBP_YEAST_SEO_CANONICAL_META_KEY,
					'schema'           => CBP_YEAST_SEO_SCHEMA_META_KEY,
				),
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'cbp_yeast_seo_enqueue_block_editor_assets' );

/**
 * Whether the current request maps to a specific post this plugin can pull
 * per-post SEO fields from — either a singular view, or the static page set
 * as the site's "Posts page" (Settings → Reading) being shown as the blog
 * index. is_singular() alone is false for the latter even though it's a
 * real page with its own Yeast SEO fields, which otherwise left that
 * page's title/description/OG/robots settings entirely unused whenever
 * it's viewed in its posts-page role rather than opened directly.
 *
 * @return bool
 */
function cbp_yeast_seo_is_managed_view() {
	return is_singular() || ( is_home() && (int) get_option( 'page_for_posts' ) );
}

/**
 * Returns the post ID this plugin's per-post fields apply to for the
 * current request, per cbp_yeast_seo_is_managed_view().
 *
 * @return int
 */
function cbp_yeast_seo_current_post_id() {
	if ( is_singular() ) {
		return (int) get_queried_object_id();
	}

	if ( is_home() ) {
		return (int) get_option( 'page_for_posts' );
	}

	return 0;
}

/**
 * Returns one post-meta value for the current singular post.
 *
 * @param string $meta_key Meta key.
 * @return string
 */
function cbp_yeast_seo_current_meta( $meta_key ) {
	$post_id = cbp_yeast_seo_current_post_id();
	return $post_id ? (string) get_post_meta( $post_id, $meta_key, true ) : '';
}

/**
 * Returns the current singular page's custom title.
 *
 * @return string
 */
function cbp_yeast_seo_current_title() {
	return cbp_yeast_seo_current_meta( CBP_YEAST_SEO_TITLE_META_KEY );
}

/**
 * Returns the effective meta description.
 *
 * @return string
 */
function cbp_yeast_seo_current_description() {
	return trim( cbp_yeast_seo_current_meta( CBP_YEAST_SEO_DESCRIPTION_META_KEY ) );
}

/**
 * Returns the per-post canonical URL override, if set.
 *
 * @return string
 */
function cbp_yeast_seo_current_canonical() {
	return trim( cbp_yeast_seo_current_meta( CBP_YEAST_SEO_CANONICAL_META_KEY ) );
}

/**
 * Returns the effective Open Graph title.
 *
 * @return string
 */
function cbp_yeast_seo_current_og_title() {
	$title = trim( cbp_yeast_seo_current_meta( CBP_YEAST_SEO_OG_TITLE_META_KEY ) );

	if ( '' !== $title ) {
		return $title;
	}

	$title = trim( cbp_yeast_seo_current_title() );

	if ( '' !== $title ) {
		return $title;
	}

	$post_id = cbp_yeast_seo_current_post_id();

	return $post_id ? get_the_title( $post_id ) : '';
}

/**
 * Returns the effective Open Graph description.
 *
 * @return string
 */
function cbp_yeast_seo_current_og_description() {
	$description = trim( cbp_yeast_seo_current_meta( CBP_YEAST_SEO_OG_DESCRIPTION_META_KEY ) );

	if ( '' !== $description ) {
		return $description;
	}

	return cbp_yeast_seo_current_description();
}

/**
 * Returns the effective Open Graph image.
 *
 * @return string
 */
function cbp_yeast_seo_current_og_image() {
	$image = trim( cbp_yeast_seo_current_meta( CBP_YEAST_SEO_OG_IMAGE_META_KEY ) );

	if ( '' !== $image ) {
		return $image;
	}

	$post_id = cbp_yeast_seo_current_post_id();

	if ( $post_id ) {
		$image = cbp_yeast_seo_get_post_image( $post_id );

		if ( '' !== $image ) {
			return $image;
		}
	}

	return (string) cbp_yeast_seo_get_setting( 'default_social_image', '' );
}

/**
 * Finds a social image for a post when none was set explicitly: the
 * featured image first, then the first image found in the post's blocks
 * (typically the hero), in document order.
 *
 * Blocks are parsed, not rendered — rendering a post's content from the
 * head would fire every dynamic block's side effects (e.g. queued FAQ
 * schema) a second time. An attachment ID is preferred over a stored URL
 * so the result is always a current, absolute upload URL.
 *
 * @param int $post_id Post ID.
 * @return string Image URL, or '' when none found.
 */
function cbp_yeast_seo_get_post_image( $post_id ) {
	static $cache = array();

	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$image = '';

	if ( has_post_thumbnail( $post_id ) ) {
		$image = (string) get_the_post_thumbnail_url( $post_id, 'full' );
	}

	if ( '' === $image ) {
		$post = get_post( $post_id );

		if ( $post && has_blocks( $post->post_content ) ) {
			$image = cbp_yeast_seo_find_block_image( parse_blocks( $post->post_content ) );
		}
	}

	/**
	 * Filters the automatically detected social image for a post.
	 *
	 * @param string $image   Image URL, or '' when none was found.
	 * @param int    $post_id Post ID.
	 */
	$cache[ $post_id ] = (string) apply_filters( 'cbp_yeast_seo_post_image', $image, $post_id );

	return $cache[ $post_id ];
}

/**
 * Walks parsed blocks depth-first and returns the first image URL found
 * in any block attribute: a numeric attachment ID under a key containing
 * "image"/"img", a URL under such a key, or a core/image-style "id"+"url".
 *
 * @param array $blocks Parsed blocks.
 * @return string Image URL, or ''.
 */
function cbp_yeast_seo_find_block_image( $blocks ) {
	foreach ( $blocks as $block ) {
		$image = cbp_yeast_seo_find_attr_image( isset( $block['attrs'] ) ? $block['attrs'] : array(), 'core/image' === $block['blockName'] );

		if ( '' === $image && ! empty( $block['innerBlocks'] ) ) {
			$image = cbp_yeast_seo_find_block_image( $block['innerBlocks'] );
		}

		if ( '' !== $image ) {
			return $image;
		}
	}

	return '';
}

/**
 * Searches one attribute array (recursing into repeater-style nested
 * arrays) for an image, in key order.
 *
 * @param array $attrs      Block attributes.
 * @param bool  $core_image Whether these are core/image attributes.
 * @return string Image URL, or ''.
 */
function cbp_yeast_seo_find_attr_image( $attrs, $core_image = false ) {
	foreach ( $attrs as $key => $value ) {
		$is_image_key = $core_image ? in_array( $key, array( 'id', 'url' ), true ) : (bool) preg_match( '/image|img/i', (string) $key );

		if ( is_array( $value ) ) {
			$image = cbp_yeast_seo_find_attr_image( $value );

			if ( '' !== $image ) {
				return $image;
			}

			continue;
		}

		if ( ! $is_image_key ) {
			continue;
		}

		if ( is_numeric( $value ) && (int) $value > 0 && wp_attachment_is_image( (int) $value ) ) {
			$url = wp_get_attachment_image_url( (int) $value, 'full' );

			if ( $url ) {
				return $url;
			}
		} elseif ( is_string( $value ) && preg_match( '#^(https?:)?//[^\s]+\.(jpe?g|png|webp|gif|avif)(\?[^\s]*)?$#i', $value ) ) {
			return $value;
		}
	}

	return '';
}

/**
 * Returns the effective Twitter image.
 *
 * @return string
 */
function cbp_yeast_seo_current_twitter_image() {
	$image = trim( cbp_yeast_seo_current_meta( CBP_YEAST_SEO_TWITTER_IMAGE_META_KEY ) );

	if ( '' !== $image ) {
		return $image;
	}

	return cbp_yeast_seo_current_og_image();
}

/**
 * Returns whether the current page should be indexed.
 *
 * @return bool
 */
function cbp_yeast_seo_should_index_current_page() {
	if ( ! get_option( 'blog_public' ) ) {
		return false;
	}

	if ( ! cbp_yeast_seo_is_managed_view() ) {
		return true;
	}

	return 'noindex' !== cbp_yeast_seo_current_meta( CBP_YEAST_SEO_ROBOTS_META_KEY );
}

/**
 * Whether this request's robots directives should be overridden.
 *
 * @return bool
 */
function cbp_yeast_seo_should_override_robots() {
	return ! get_option( 'blog_public' ) || cbp_yeast_seo_is_managed_view();
}

/**
 * Overrides the document title when a custom one is set.
 *
 * @param string $title Existing title.
 * @return string
 */
function cbp_yeast_seo_filter_document_title( $title ) {
	$custom_title = trim( cbp_yeast_seo_current_title() );
	return '' !== $custom_title ? $custom_title : $title;
}
add_filter( 'pre_get_document_title', 'cbp_yeast_seo_filter_document_title' );
add_filter( 'wpseo_title', 'cbp_yeast_seo_filter_document_title' );

/**
 * Overrides the meta description Yoast outputs when present.
 *
 * @param string $description Existing description.
 * @return string
 */
function cbp_yeast_seo_filter_meta_description( $description ) {
	$custom_description = trim( cbp_yeast_seo_current_description() );
	return '' !== $custom_description ? $custom_description : $description;
}
add_filter( 'wpseo_metadesc', 'cbp_yeast_seo_filter_meta_description' );

/**
 * Overrides WordPress core's own canonical URL (wp_get_canonical_url(),
 * used by rel_canonical() on wp_head for singular content) when a custom
 * one is saved.
 *
 * @param string $canonical_url Existing canonical URL.
 * @return string
 */
function cbp_yeast_seo_filter_canonical_url( $canonical_url ) {
	$custom_canonical = cbp_yeast_seo_current_canonical();
	return '' !== $custom_canonical ? $custom_canonical : $canonical_url;
}
add_filter( 'get_canonical_url', 'cbp_yeast_seo_filter_canonical_url' );

/**
 * Overrides Yoast's own canonical URL when present.
 *
 * @param string $canonical_url Existing canonical URL.
 * @return string
 */
function cbp_yeast_seo_filter_wpseo_canonical( $canonical_url ) {
	$custom_canonical = cbp_yeast_seo_current_canonical();
	return '' !== $custom_canonical ? $custom_canonical : $canonical_url;
}
add_filter( 'wpseo_canonical', 'cbp_yeast_seo_filter_wpseo_canonical' );

/**
 * Filters WordPress core robots directives.
 *
 * @param array $robots Existing directives.
 * @return array
 */
function cbp_yeast_seo_filter_wp_robots( $robots ) {
	if ( ! cbp_yeast_seo_should_override_robots() ) {
		return $robots;
	}

	if ( cbp_yeast_seo_should_index_current_page() ) {
		unset( $robots['noindex'] );
		$robots['index'] = true;
	} else {
		unset( $robots['index'] );
		$robots['noindex'] = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'cbp_yeast_seo_filter_wp_robots' );

/**
 * Filters Yoast robots directives when Yoast is active.
 *
 * @param array $robots Existing directives.
 * @return array
 */
function cbp_yeast_seo_filter_wpseo_robots_array( $robots ) {
	if ( ! is_array( $robots ) ) {
		$robots = array();
	}

	if ( ! cbp_yeast_seo_should_override_robots() ) {
		return $robots;
	}

	if ( cbp_yeast_seo_should_index_current_page() ) {
		unset( $robots['noindex'] );
		$robots['index'] = 'index';
	} else {
		unset( $robots['index'] );
		$robots['noindex'] = 'noindex';
	}

	if ( ! isset( $robots['follow'] ) && ! isset( $robots['nofollow'] ) ) {
		$robots['follow'] = 'follow';
	}

	return $robots;
}
add_filter( 'wpseo_robots_array', 'cbp_yeast_seo_filter_wpseo_robots_array' );

/**
 * Filters Yoast Open Graph and Twitter values when present.
 *
 * @param string $value Existing value.
 * @param string $type  Value type.
 * @return string
 */
function cbp_yeast_seo_filter_social_value( $value, $type ) {
	$map = array(
		'og_title'       => cbp_yeast_seo_current_og_title(),
		'og_description' => cbp_yeast_seo_current_og_description(),
		'og_image'       => cbp_yeast_seo_current_og_image(),
		'twitter_title'  => cbp_yeast_seo_current_og_title(),
		'twitter_desc'   => cbp_yeast_seo_current_og_description(),
		'twitter_image'  => cbp_yeast_seo_current_twitter_image(),
	);

	return ! empty( $map[ $type ] ) ? $map[ $type ] : $value;
}

add_filter(
	'wpseo_opengraph_title',
	function ( $value ) {
		return cbp_yeast_seo_filter_social_value( $value, 'og_title' );
	}
);

add_filter(
	'wpseo_opengraph_desc',
	function ( $value ) {
		return cbp_yeast_seo_filter_social_value( $value, 'og_description' );
	}
);

add_filter(
	'wpseo_opengraph_image',
	function ( $value ) {
		return cbp_yeast_seo_filter_social_value( $value, 'og_image' );
	}
);

add_filter(
	'wpseo_twitter_title',
	function ( $value ) {
		return cbp_yeast_seo_filter_social_value( $value, 'twitter_title' );
	}
);

add_filter(
	'wpseo_twitter_description',
	function ( $value ) {
		return cbp_yeast_seo_filter_social_value( $value, 'twitter_desc' );
	}
);

add_filter(
	'wpseo_twitter_image',
	function ( $value ) {
		return cbp_yeast_seo_filter_social_value( $value, 'twitter_image' );
	}
);

/**
 * Outputs the plugin's meta tags when Yoast is not handling them.
 *
 * @return void
 */
function cbp_yeast_seo_render_frontend_meta() {
	if ( ! cbp_yeast_seo_is_managed_view() || cbp_yeast_seo_yoast_active() ) {
		return;
	}

	$post_id      = cbp_yeast_seo_current_post_id();
	$title        = cbp_yeast_seo_current_og_title();
	$description  = cbp_yeast_seo_current_og_description();
	$og_image     = cbp_yeast_seo_current_og_image();
	$twitter_image = cbp_yeast_seo_current_twitter_image();
	$url          = get_permalink( $post_id );
	$site_name    = get_bloginfo( 'name' );
	$twitter_site = cbp_yeast_seo_get_setting( 'twitter_site', '' );
	$card_type    = $twitter_image ? 'summary_large_image' : 'summary';
	// The posts page shown as the blog index is a listing, and the front
	// page is the site itself, not a single piece of content — "article"
	// would misdescribe either to OG consumers.
	$og_type      = is_singular() && ! is_front_page() ? 'article' : 'website';

	if ( '' !== $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}

	echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";

	if ( '' !== $title ) {
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	}

	if ( '' !== $description ) {
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
	}

	if ( '' !== $site_name ) {
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
	}

	if ( '' !== $og_image ) {
		echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
	}

	// Dates for article-type pages (posts and pages alike, matching og:type
	// above). Skipped for the front page / blog index, which report "website".
	if ( 'article' === $og_type && $post_id ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_post_time( 'c', true, $post_id ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_post_modified_time( 'c', true, $post_id ) ) . '">' . "\n";
	}

	if ( '' !== $twitter_image ) {
		echo '<meta name="twitter:image" content="' . esc_url( $twitter_image ) . '">' . "\n";
	}

	echo '<meta name="twitter:card" content="' . esc_attr( $card_type ) . '">' . "\n";

	if ( '' !== $twitter_site ) {
		echo '<meta name="twitter:site" content="' . esc_attr( $twitter_site ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'cbp_yeast_seo_render_frontend_meta', 1 );

/**
 * Outputs a canonical link tag for non-singular views.
 *
 * WordPress core's own rel_canonical() (wp-includes/link-template.php) only
 * fires for is_singular() — the posts page, taxonomy/post-type archives,
 * and author archives get no canonical at all out of core, which is what
 * left a page like the posts page (e.g. a "Guides" index) without one.
 * Skipped entirely when Yoast is active/handling it (same detection this
 * file already uses for render_frontend_meta) or on singular content, since
 * core's own rel_canonical() already covers both cases there.
 *
 * @return void
 */
function cbp_yeast_seo_render_canonical() {
	if ( is_singular() || cbp_yeast_seo_yoast_active() ) {
		return;
	}

	if ( is_404() || is_search() ) {
		return; // Nothing stable to canonicalise to.
	}

	$custom_canonical = cbp_yeast_seo_current_canonical();

	if ( '' !== $custom_canonical ) {
		$url = $custom_canonical;
	} else {
		$paged = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ), 1 );
		$url   = get_pagenum_link( $paged, false );
	}

	if ( ! $url ) {
		return;
	}

	echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
}
add_action( 'wp_head', 'cbp_yeast_seo_render_canonical', 1 );
