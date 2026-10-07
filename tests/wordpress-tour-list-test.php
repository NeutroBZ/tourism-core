<?php
/** Run only against the local development WordPress database. */
if ( 'cli' !== PHP_SAPI ) { exit; }
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = 'travel-theme.local';
$_SERVER['REQUEST_METHOD'] = 'GET';
$wp_root = dirname( __DIR__, 4 );
require_once $wp_root . '/wp-includes/plugin.php';
add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
require_once $wp_root . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { throw new RuntimeException( 'Run only on a local development site.' ); }
function list_check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	echo "PASS: $message\n";
}
$ids = array(); $terms = array();
$old_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
$original_modules = get_option( Tourism_Core_Config::OPTION_MODULES );
try {
	$config = Tourism_Core_Config::instance(); $modules = $config->get_modules(); $modules['tours'] = true; $config->set_modules( $modules );
	$slug = 'tc-list-test-' . strtolower( wp_generate_password( 8, false ) );
	foreach ( array( 'tour_category', 'tour_type' ) as $taxonomy ) {
		$term = wp_insert_term( $slug, $taxonomy, array( 'slug' => $slug ) );
		if ( is_wp_error( $term ) ) { throw new RuntimeException( $term->get_error_message() ); }
		$terms[ $taxonomy ] = $term['term_id'];
	}
	foreach ( array( 'Alpha public', 'Beta public', 'Draft hidden', 'Private hidden', 'Password hidden', 'Other category' ) as $index => $title ) {
		$id = wp_insert_post( array( 'post_type' => 'tour', 'post_title' => $title, 'post_status' => 2 === $index ? 'draft' : ( 3 === $index ? 'private' : 'publish' ), 'post_password' => 4 === $index ? 'secret' : '' ), true );
		if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
		$ids[] = $id;
		if ( 5 !== $index ) { wp_set_object_terms( $id, array( $terms['tour_category'] ), 'tour_category' ); }
		if ( 0 === $index ) { wp_set_object_terms( $id, array( $terms['tour_type'] ), 'tour_type' ); }
	}
	$id = $ids[0];
	update_post_meta( $id, 'tourism_duration', '2 days' );
	update_post_meta( $id, 'tourism_difficulty', '<script>bad()</script>Moderate' );
	update_post_meta( $id, 'tourism_price_from', '0' );
	update_post_meta( $id, 'tourism_price_currency', 'PEN' );
	update_post_meta( $id, 'tourism_intro_description', '<p>Introduction <strong>text</strong>.</p>' );
	update_post_meta( $id, 'tourism_gallery_images', array( array( 'url' => 'javascript:alert(1)' ), array( 'url' => 'https://example.invalid/tour.jpg' ) ) );
	$GLOBALS['post'] = get_post( $ids[5] );
	$args = array( 'category' => $slug, 'orderby' => 'title', 'order' => 'ASC' );
	$html = tourism_core_render_tours( $args );
	list_check( 2 === substr_count( $html, '<article' ), 'Category selects only public tours without passwords' );
	list_check( strpos( $html, 'Alpha public' ) < strpos( $html, 'Beta public' ), 'Title ordering applied' );
	list_check( $GLOBALS['post']->ID === $ids[5], 'Rendering preserves outer WordPress loop' );
	list_check( false !== strpos( $html, 'PEN 0.00' ), 'Zero price and tour currency preserved' );
	list_check( false !== strpos( $html, 'https://example.invalid/tour.jpg' ) && false === strpos( $html, 'javascript:' ), 'Gallery image fallback rejects unsafe URLs' );
	list_check( $html === do_shortcode( '[tour_list category="' . $slug . '" orderby="title" order="ASC"]' ), 'Shortcode and PHP function return identical cards' );
	list_check( 1 === substr_count( do_shortcode( '[tour_list category="' . $slug . '" posts_per_page="1"]' ), '<article' ), 'Existing posts_per_page attribute supported' );
	list_check( 2 === substr_count( do_shortcode( '[tour_list category="' . $slug . '" posts_per_page="1" limit="2"]' ), '<article' ), 'Explicit limit takes precedence' );
	list_check( 1 === substr_count( tourism_core_render_tours( $args + array( 'tour_type' => $slug ) ), '<article' ), 'Category and type filters intersect' );
	$selected = tourism_core_render_tours( $args + array( 'fields' => 'title,difficulty,unknown', 'columns' => 99 ) );
	list_check( false === strpos( $selected, '<img' ) && false === strpos( $selected, 'card__price' ) && false !== strpos( $selected, 'Moderate' ) && false === strpos( $selected, '<script' ), 'Field selection and escaped text honored' );
	list_check( false !== strpos( $selected, 'columns-4' ), 'Columns clamped to supported range' );
	list_check( '' === tourism_core_render_tours( array( 'category' => $slug . '-missing' ) ), 'Unknown category returns no section' );
	list_check( array() === tourism_core_get_tour_card_data( $ids[2] ), 'Data helper excludes unpublished tours' );
	update_post_meta( $id, 'tourism_price_from', '-10' );
	list_check( '' === tourism_core_get_tour_card_data( $id )['price'], 'Negative price omitted' );
	if ( getenv( 'TOURISM_LIST_PREVIEW' ) ) {
		$css = file_get_contents( dirname( __DIR__ ) . '/assets/css/frontend.css' );
		file_put_contents( getenv( 'TOURISM_LIST_PREVIEW' ), '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:24px;font-family:Arial,sans-serif}*{box-sizing:border-box}' . $css . '</style><main>' . $html . '</main></html>' );
	}
	$modules['tours'] = false; $config->set_modules( $modules );
	list_check( '' === tourism_core_render_tours( $args ), 'Disabled module produces no tours' );
} finally {
	foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
	foreach ( $terms as $taxonomy => $term_id ) { wp_delete_term( $term_id, $taxonomy ); }
	update_option( Tourism_Core_Config::OPTION_MODULES, $original_modules );
	Tourism_Core_Config::instance()->set_modules( is_array( $original_modules ) ? $original_modules : array() );
	$GLOBALS['post'] = $old_post;
}
