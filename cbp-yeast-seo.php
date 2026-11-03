<?php
/**
 * Plugin Name: Yeast SEO
 * Description: Per-post SEO controls for title, meta description, and JSON-LD schema.
 * Version: 0.6.0
 * Requires PHP: 7.4
 * Author: Chillibyte - DS
 * Text Domain: cbp-yeast-seo
 *
 * @package cbp-yeast-seo
 */

defined( 'ABSPATH' ) || exit;

define( 'CBP_YEAST_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'CBP_YEAST_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'CBP_YEAST_SEO_VERSION', '0.6.0' );

require_once CBP_YEAST_SEO_PATH . 'inc/settings.php';
require_once CBP_YEAST_SEO_PATH . 'inc/post-meta.php';
require_once CBP_YEAST_SEO_PATH . 'inc/admin-columns.php';
require_once CBP_YEAST_SEO_PATH . 'inc/bulk-actions.php';
require_once CBP_YEAST_SEO_PATH . 'inc/sitemaps.php';
require_once CBP_YEAST_SEO_PATH . 'inc/schema.php';

register_activation_hook( __FILE__, 'cbp_yeast_seo_activate' );
register_deactivation_hook( __FILE__, 'cbp_yeast_seo_deactivate' );
