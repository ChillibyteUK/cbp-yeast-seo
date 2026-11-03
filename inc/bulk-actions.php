<?php
/**
 * Bulk Index / Noindex actions on the post list tables.
 *
 * @package cbp-yeast-seo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds the bulk actions to the list table dropdown.
 *
 * @param array $actions Existing bulk actions.
 * @return array
 */
function cbp_yeast_seo_bulk_actions( $actions ) {
	$actions['cbp_yeast_seo_index']   = __( 'Set to Index', 'cbp-yeast-seo' );
	$actions['cbp_yeast_seo_noindex'] = __( 'Set to Noindex', 'cbp-yeast-seo' );

	return $actions;
}

/**
 * Applies a bulk index/noindex action.
 *
 * @param string $redirect_url Redirect URL after the action.
 * @param string $action       Chosen bulk action.
 * @param int[]  $post_ids     Selected post IDs.
 * @return string
 */
function cbp_yeast_seo_handle_bulk_action( $redirect_url, $action, $post_ids ) {
	if ( 'cbp_yeast_seo_index' !== $action && 'cbp_yeast_seo_noindex' !== $action ) {
		return $redirect_url;
	}

	$value   = 'cbp_yeast_seo_noindex' === $action ? 'noindex' : 'index';
	$updated = 0;

	foreach ( $post_ids as $post_id ) {
		$post_id = (int) $post_id;

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			continue;
		}

		update_post_meta( $post_id, CBP_YEAST_SEO_ROBOTS_META_KEY, $value );
		++$updated;
	}

	return add_query_arg(
		array(
			'cbp_yeast_seo_bulk'       => $value,
			'cbp_yeast_seo_bulk_count' => $updated,
		),
		remove_query_arg( array( 'cbp_yeast_seo_bulk', 'cbp_yeast_seo_bulk_count' ), $redirect_url )
	);
}

/**
 * Shows the result notice after a bulk action.
 *
 * @return void
 */
function cbp_yeast_seo_bulk_notice() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['cbp_yeast_seo_bulk'], $_GET['cbp_yeast_seo_bulk_count'] ) ) {
		return;
	}

	$value = 'noindex' === $_GET['cbp_yeast_seo_bulk'] ? 'noindex' : 'index';
	$count = absint( $_GET['cbp_yeast_seo_bulk_count'] );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$message = sprintf(
		/* translators: 1: number of items, 2: index or noindex. */
		_n( '%1$d item set to %2$s.', '%1$d items set to %2$s.', $count, 'cbp-yeast-seo' ),
		$count,
		$value
	);

	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
}
add_action( 'admin_notices', 'cbp_yeast_seo_bulk_notice' );

/**
 * Registers the bulk actions for every post type Yeast covers.
 *
 * @return void
 */
function cbp_yeast_seo_register_bulk_actions() {
	foreach ( cbp_yeast_seo_post_types() as $post_type ) {
		add_filter( "bulk_actions-edit-{$post_type}", 'cbp_yeast_seo_bulk_actions' );
		add_filter( "handle_bulk_actions-edit-{$post_type}", 'cbp_yeast_seo_handle_bulk_action', 10, 3 );
	}
}
add_action( 'admin_init', 'cbp_yeast_seo_register_bulk_actions' );
