<?php
/** Offline adapter checks: no WordPress writes or external services. */
define( 'ABSPATH', __DIR__ );
$options = array();
function get_option( $key, $default = false ) { global $options; return $options[$key] ?? $default; }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( $value ) ); }
function esc_url_raw( $value, $protocols = array() ) { return str_starts_with($value, 'https://') ? $value : ''; }
require dirname(__DIR__) . '/modules/headless/class-headless-reviews.php';
function verify( $condition, $message ) { if (!$condition) { throw new RuntimeException($message); } }
class ReviewManagerFixture {
 public $connected = true;
 public $rows = array();
 public function is_noreg_linked() { return $this->connected; }
 public function getReviews() { return $this->rows; }
 public function getPageDetails() { return array('name'=>'Palotoa','rating_score'=>4.8,'rating_number'=>237,'timestamp'=>1792540800); }
 public function getPageUrl() { return 'https://www.google.com/maps/'; }
}
verify(Tourism_Core_Headless_Reviews::export()['google']['status'] === 'plugin_inactive', 'Inactive provider');
$manager = new ReviewManagerFixture();
$GLOBALS['trustindex_pm_google'] = $manager;
$manager->connected = false;
verify(Tourism_Core_Headless_Reviews::export()['google']['status'] === 'connection_required', 'Disconnected provider');
$manager->connected = true;
verify(Tourism_Core_Headless_Reviews::export()['google']['status'] === 'no_reviews', 'No invented demo reviews');
$manager->rows = array(
 (object) array('user'=>'Public author','text'=>'Real review','rating'=>4.5,'date'=>'2026-10-20','hidden'=>0,'email'=>'private@example.com','reviewId'=>'private-id'),
 (object) array('user'=>'Hidden','text'=>'Hidden review','rating'=>5,'date'=>'2026-10-20','hidden'=>1),
 (object) array('user'=>'Invalid','text'=>'Other review','rating'=>9,'date'=>'2026-02-30','hidden'=>0),
);
$result = Tourism_Core_Headless_Reviews::export()['google'];
verify($result['status']==='ready' && count($result['items'])===2,'Hidden reviews excluded');
verify(array_keys($result['items'][0])===array('author','content','rating','date','avatar_url'),'Only public allowlisted fields');
verify($result['total_reviews']===237 && $result['displayed_reviews']===2 && $result['rating']===4.8,'Official totals independent of downloaded reviews');
verify($result['items'][0]['rating']===4.5,'Preserve real rating');
verify($result['items'][1]['rating']===null && $result['items'][1]['date']===null,'Invalid ratings/dates excluded');
$options['trustindex-google-filter']=array('stars'=>array(5));
verify(count(Tourism_Core_Headless_Reviews::export()['google']['items'])===0,'Respect configured star filter');
echo "PASS: inactive, disconnected, empty, hidden, public fields, rating, date, filter\n";
