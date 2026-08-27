<?php
/**
 * Plugin Name: Yeast SEO
 * Description: Per-post JSON-LD schema markup with Yoast-awareness (suppresses Yoast's Organization node where this plugin supplies one, warns on type overlap). Intended to grow into a full Yoast replacement over time — starts with schema only.
 * Version: 0.1.0
 * Requires PHP: 7.4
 * Author: Chillibyte - DS
 * Text Domain: cbp-yeast-seo
 *
 * @package cbp-yeast-seo
 */

defined( 'ABSPATH' ) || exit;

define( 'CBP_YEAST_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'CBP_YEAST_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'CBP_YEAST_SEO_VERSION', '0.1.0' );

require_once CBP_YEAST_SEO_PATH . 'inc/settings.php';
require_once CBP_YEAST_SEO_PATH . 'inc/schema.php';
