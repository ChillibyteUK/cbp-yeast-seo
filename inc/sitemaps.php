<?php
/**
 * XML sitemap generation.
 *
 * @package cbp-yeast-seo
 */

defined( 'ABSPATH' ) || exit;

define( 'CBP_YEAST_SEO_SITEMAP_PAGE_SIZE', 1000 );

/**
 * Adds rewrite rules for sitemap endpoints.
 *
 * @return void
 */
function cbp_yeast_seo_register_sitemap_rewrites() {
	add_rewrite_rule( '^sitemap\.xml$', 'index.php?cbp_yeast_seo_sitemap=index', 'top' );
	add_rewrite_rule( '^sitemap_index\.xml$', 'index.php?cbp_yeast_seo_sitemap=index', 'top' );
	add_rewrite_rule( '^([^/]+)-sitemap([0-9]+)?\.xml$', 'index.php?cbp_yeast_seo_sitemap=post-type&cbp_yeast_seo_post_type=$matches[1]&cbp_yeast_seo_sitemap_page=$matches[2]', 'top' );
}
add_action( 'init', 'cbp_yeast_seo_register_sitemap_rewrites' );

/**
 * Disable WordPress core sitemaps so this plugin owns sitemap URLs.
 *
 * @return bool
 */
function cbp_yeast_seo_disable_core_sitemaps() {
	return false;
}
add_filter( 'wp_sitemaps_enabled', 'cbp_yeast_seo_disable_core_sitemaps' );

/**
 * Registers custom query vars.
 *
 * @param string[] $vars Existing query vars.
 * @return string[]
 */
function cbp_yeast_seo_register_sitemap_query_vars( $vars ) {
	$vars[] = 'cbp_yeast_seo_sitemap';
	$vars[] = 'cbp_yeast_seo_post_type';
	$vars[] = 'cbp_yeast_seo_sitemap_page';

	return $vars;
}
add_filter( 'query_vars', 'cbp_yeast_seo_register_sitemap_query_vars' );

/**
 * Flushes rewrites on activation.
 *
 * @return void
 */
function cbp_yeast_seo_activate() {
	cbp_yeast_seo_register_sitemap_rewrites();
	flush_rewrite_rules();
}

/**
 * Flushes rewrites on deactivation.
 *
 * @return void
 */
function cbp_yeast_seo_deactivate() {
	flush_rewrite_rules();
}

/**
 * Returns the enabled post types for sitemap output.
 *
 * @return string[]
 */
function cbp_yeast_seo_sitemap_post_types() {
	$post_types = array();

	foreach ( cbp_yeast_seo_post_types() as $post_type ) {
		if ( cbp_yeast_seo_sitemap_post_type_enabled( $post_type ) ) {
			$post_types[] = $post_type;
		}
	}

	return $post_types;
}

/**
 * Returns the sitemap URL for a post type and page.
 *
 * @param string $post_type Post type slug.
 * @param int    $page      Page number.
 * @return string
 */
function cbp_yeast_seo_sitemap_url( $post_type, $page = 1 ) {
	$suffix = $page > 1 ? (string) $page : '';
	return home_url( '/' . $post_type . '-sitemap' . $suffix . '.xml' );
}

/**
 * Whether a given post should be excluded from sitemap output.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function cbp_yeast_seo_post_is_noindex( $post_id ) {
	return 'noindex' === get_post_meta( $post_id, CBP_YEAST_SEO_ROBOTS_META_KEY, true );
}

/**
 * Returns all published IDs for a post type that should appear in sitemaps.
 *
 * @param string $post_type Post type slug.
 * @return int[]
 */
function cbp_yeast_seo_sitemap_post_ids( $post_type ) {
	$query = new WP_Query(
		array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	if ( empty( $query->posts ) ) {
		return array();
	}

	return array_values(
		array_filter(
			array_map( 'intval', $query->posts ),
			function ( $post_id ) {
				return ! cbp_yeast_seo_post_is_noindex( $post_id );
			}
		)
	);
}

/**
 * Emits an XML response body and exits.
 *
 * @param string $xml XML string.
 * @return void
 */
function cbp_yeast_seo_output_xml( $xml ) {
	status_header( 200 );
	header( 'Content-Type: application/xml; charset=' . get_bloginfo( 'charset' ) );
	echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}

/**
 * Returns the stylesheet URL for sitemap rendering.
 *
 * @return string
 */
function cbp_yeast_seo_sitemap_stylesheet_url() {
	return CBP_YEAST_SEO_URL . 'assets/sitemap.xsl';
}

/**
 * Emits a sitemap 404 and exits.
 *
 * @return void
 */
function cbp_yeast_seo_output_sitemap_404() {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	exit;
}

/**
 * Renders the sitemap index.
 *
 * @return void
 */
function cbp_yeast_seo_render_sitemap_index() {
	$items = array();

	foreach ( cbp_yeast_seo_sitemap_post_types() as $post_type ) {
		$post_ids = cbp_yeast_seo_sitemap_post_ids( $post_type );
		$total    = count( $post_ids );

		if ( 0 === $total ) {
			continue;
		}

		$chunks = (int) ceil( $total / CBP_YEAST_SEO_SITEMAP_PAGE_SIZE );

		for ( $page = 1; $page <= $chunks; $page++ ) {
			$chunk    = array_slice( $post_ids, ( $page - 1 ) * CBP_YEAST_SEO_SITEMAP_PAGE_SIZE, CBP_YEAST_SEO_SITEMAP_PAGE_SIZE );
			$lastmod  = get_post_modified_time( DATE_W3C, true, $chunk[0] );
			$items[]  = array(
				'loc'     => cbp_yeast_seo_sitemap_url( $post_type, $page ),
				'lastmod' => $lastmod,
				'count'   => count( $chunk ),
			);
		}
	}

	$xml  = '<?xml version="1.0" encoding="' . esc_attr( get_bloginfo( 'charset' ) ) . '"?>';
	$xml .= '<?xml-stylesheet type="text/xsl" href="' . esc_url( cbp_yeast_seo_sitemap_stylesheet_url() ) . '"?>';
	$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:cbp="https://chillibyte.co.uk/ns/yeast-seo">';

	foreach ( $items as $item ) {
		$xml .= '<sitemap>';
		$xml .= '<loc>' . esc_url( $item['loc'] ) . '</loc>';
		$xml .= '<lastmod>' . esc_html( $item['lastmod'] ) . '</lastmod>';
		$xml .= '<cbp:count>' . absint( $item['count'] ) . '</cbp:count>';
		$xml .= '</sitemap>';
	}

	$xml .= '</sitemapindex>';

	cbp_yeast_seo_output_xml( $xml );
}

/**
 * Renders one post-type sitemap page.
 *
 * @param string $post_type Post type slug.
 * @param int    $page      Page number.
 * @return void
 */
function cbp_yeast_seo_render_post_type_sitemap( $post_type, $page ) {
	if ( ! in_array( $post_type, cbp_yeast_seo_sitemap_post_types(), true ) ) {
		cbp_yeast_seo_output_sitemap_404();
	}

	$page     = max( 1, absint( $page ) );
	$post_ids = cbp_yeast_seo_sitemap_post_ids( $post_type );
	$chunks   = array_chunk( $post_ids, CBP_YEAST_SEO_SITEMAP_PAGE_SIZE );

	if ( empty( $chunks ) || empty( $chunks[ $page - 1 ] ) ) {
		cbp_yeast_seo_output_sitemap_404();
	}

	$xml  = '<?xml version="1.0" encoding="' . esc_attr( get_bloginfo( 'charset' ) ) . '"?>';
	$xml .= '<?xml-stylesheet type="text/xsl" href="' . esc_url( cbp_yeast_seo_sitemap_stylesheet_url() ) . '"?>';
	$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

	foreach ( $chunks[ $page - 1 ] as $post_id ) {
		$xml .= '<url>';
		$xml .= '<loc>' . esc_url( get_permalink( $post_id ) ) . '</loc>';
		$xml .= '<lastmod>' . esc_html( get_post_modified_time( DATE_W3C, true, $post_id ) ) . '</lastmod>';
		$xml .= '</url>';
	}

	$xml .= '</urlset>';

	cbp_yeast_seo_output_xml( $xml );
}

/**
 * Handles sitemap requests.
 *
 * @return void
 */
function cbp_yeast_seo_maybe_render_sitemap() {
	$type = get_query_var( 'cbp_yeast_seo_sitemap' );

	if ( ! $type ) {
		return;
	}

	if ( ! cbp_yeast_seo_sitemaps_enabled() ) {
		cbp_yeast_seo_output_sitemap_404();
	}

	if ( 'index' === $type ) {
		cbp_yeast_seo_render_sitemap_index();
	}

	if ( 'post-type' === $type ) {
		cbp_yeast_seo_render_post_type_sitemap( sanitize_key( get_query_var( 'cbp_yeast_seo_post_type' ) ), get_query_var( 'cbp_yeast_seo_sitemap_page' ) );
	}

	cbp_yeast_seo_output_sitemap_404();
}
add_action( 'template_redirect', 'cbp_yeast_seo_maybe_render_sitemap' );
