<?php
/** Local integration checks for repeatable videos/reviews. Temporary draft is always removed. */
if ( 'cli' !== PHP_SAPI ) { exit; }
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
$_SERVER['HTTP_HOST'] = 'palota-2026.local';
$_SERVER['REQUEST_METHOD'] = 'POST';
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { throw new RuntimeException( 'Local only' ); }
$checks = 0; $id = 0; $old_post = $_POST;
function extras_check( $ok, $message ) { global $checks; $checks++; if ( ! $ok ) { throw new RuntimeException( $message ); } }
$admins = get_users( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 1 ) );
wp_set_current_user( $admins[0] );
$extras = new Tourism_Core_Tour_Extras();
$videos = array(
	array( 'title' => 'Original A', 'url' => 'https://youtu.be/dQw4w9WgXcQ', 'location' => 'main' ),
	array( 'title' => 'Original B', 'url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'location' => 'sidebar' ),
);
$reviews = array(
	array( 'author' => 'First', 'rating' => 4, 'content' => 'First review', 'approved' => true, 'source_id' => 'source-first' ),
	array( 'author' => 'Second', 'rating' => 5, 'content' => 'Second review', 'approved' => true, 'source_id' => 'source-second' ),
);
try {
	$_POST = array();
	$id = wp_insert_post( array( 'post_type' => 'tour', 'post_status' => 'draft', 'post_title' => 'Tourism Core extras test fixture' ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$post = get_post( $id );
	update_post_meta( $id, 'tourism_videos', $videos ); update_post_meta( $id, 'tourism_review_items', $reviews );
	$_POST = array( 'tourism_videos' => array( array( 'remove' => 1 ) ) );
	$extras->save( $id, $post );
	extras_check( $videos === get_post_meta( $id, 'tourism_videos', true ), 'Missing nonce preserves data' );
	$nonce = wp_create_nonce( 'tourism_extras_save' );
	$_POST = array( 'tourism_extras_nonce' => $nonce,
		'tourism_videos' => array( 0 => array( 'remove' => 1 ), 1 => array( 'url' => 'invalid' ), 3 => array( 'title' => 'Added', 'url' => 'https://youtu.be/dQw4w9WgXcQ?t=30', 'location' => 'main' ) ),
		'tourism_review_items' => array( 0 => array( 'remove' => 1 ), 1 => array( 'author' => 'Second edited', 'rating' => '4.5', 'content' => '<p>Updated</p>', 'approved' => 1 ), 3 => array( 'author' => 'New', 'rating' => 5, 'content' => 'Pending approval' ) ),
	);
	$extras->save( $id, $post );
	$saved_videos = get_post_meta( $id, 'tourism_videos', true ); $saved_reviews = get_post_meta( $id, 'tourism_review_items', true );
	extras_check( 2 === count( $saved_videos ) && $videos[1] === $saved_videos[0], 'Removing first video and invalid second preserves the correct original' );
	extras_check( 'Added' === $saved_videos[1]['title'] && 'main' === $saved_videos[1]['location'], 'New video saved with sparse form index' );
	extras_check( str_ends_with( Tourism_Core_Tour_Extras::embed_url( $saved_videos[1]['url'] ), '?start=30' ), 'Video start time preserved' );
	extras_check( 2 === count( $saved_reviews ) && 'source-second' === $saved_reviews[0]['source_id'], 'Deleting a review does not transfer imported source IDs' );
	extras_check( 'Second edited' === $saved_reviews[0]['author'] && 4.5 === $saved_reviews[0]['rating'], 'Existing review can be edited after a deletion' );
	extras_check( ! isset( $saved_reviews[1]['source_id'] ) && false === $saved_reviews[1]['approved'], 'New reviews do not inherit IDs or approval' );
	extras_check( 1 === count( Tourism_Core_Tour_Extras::reviews( $id ) ), 'Unapproved reviews remain unpublished' );
	$_POST = array( 'tourism_extras_nonce' => $nonce ); $extras->save( $id, $post );
	extras_check( $saved_reviews === get_post_meta( $id, 'tourism_review_items', true ), 'Omitted group preserves existing reviews' );
	$_POST = array( 'tourism_extras_nonce' => $nonce, 'tourism_videos' => array( array( 'remove' => 1 ), array( 'remove' => 1 ) ), 'tourism_review_items' => array( array( 'remove' => 1 ), array( 'remove' => 1 ) ) );
	$extras->save( $id, $post );
	extras_check( array() === get_post_meta( $id, 'tourism_videos', true ) && array() === get_post_meta( $id, 'tourism_review_items', true ), 'All elements can be deleted' );
	update_post_meta( $id, 'tourism_videos', $videos ); update_post_meta( $id, 'tourism_review_items', $reviews );
	$_POST = array( 'tourism_extras_nonce' => $nonce, 'tourism_extras_present' => array( 'tourism_videos' => 1, 'tourism_review_items' => 1 ) ); $extras->save( $id, $post );
	extras_check( array() === get_post_meta( $id, 'tourism_videos', true ) && array() === get_post_meta( $id, 'tourism_review_items', true ), 'Explicit empty groups are saved as empty lists' );
	ob_start(); $extras->editor( $post ); $html = ob_get_clean();
	extras_check( str_contains( $html, 'tourism-extras-template' ) && str_contains( $html, 'Añadir video' ) && str_contains( $html, 'Añadir reseña' ), 'Editor renders both repeaters' );
	extras_check( in_array( 'tourism-tour-extras', Tourism_Core_Tour_Editor::sections()['extras']['boxes'], true ), 'Metabox belongs to the section navigation' );
} finally {
	$_POST = $old_post;
	if ( is_int( $id ) && $id > 0 ) { wp_delete_post( $id, true ); }
	wp_set_current_user( 0 );
}
echo wp_json_encode( array( 'result' => 'PASS', 'checks' => $checks, 'fixture_removed' => true ) ) . "\n";
