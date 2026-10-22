<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Public agency reviews from Trustindex's stored data, never demo widget output. */
class Tourism_Core_Headless_Reviews {
	public static function export() {
		$result = array();
		foreach ( array( 'google' => 'Google', 'tripadvisor' => 'Tripadvisor' ) as $provider => $label ) {
			$manager = $GLOBALS[ 'trustindex_pm_' . $provider ] ?? null;
			$items = array();
			$details = $manager ? $manager->getPageDetails() : array();
			$details = is_array( $details ) ? $details : array();
			$connected = $manager && $manager->is_noreg_linked();
			$filter = get_option( 'trustindex-' . $provider . '-filter', array() );
			$filter = is_array( $filter ) ? $filter : array();
			if ( $connected ) {
				foreach ( $manager->getReviews() as $review ) {
					if ( ! empty( $review->hidden ) ) { continue; }
					$rating = is_numeric( $review->rating ) ? (float) $review->rating : null;
					if ( $rating && ! empty( $filter['stars'] ) && is_array( $filter['stars'] ) && ! in_array( $rating, array_map( 'floatval', $filter['stars'] ), true ) ) { continue; }
					if ( ! empty( $filter['only-ratings'] ) && ! trim( $review->text ?? '' ) ) { continue; }
					$date = $review->date ?? '';
					$valid_date = preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $parts ) && checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
					$items[] = array(
						'author' => sanitize_text_field( $review->user ?? '' ),
						'content' => sanitize_textarea_field( $review->text ?? '' ),
						'rating' => null !== $rating && $rating >= 1 && $rating <= 5 ? $rating : null,
						'date' => $valid_date ? $date : null,
						'avatar_url' => esc_url_raw( $review->user_photo ?? '', array( 'https' ) ),
					);
				}
			}
			$result[ $provider ] = array(
				'provider' => $provider, 'label' => $label, 'scope' => 'agency',
				'status' => ! $manager ? 'plugin_inactive' : ( $connected ? ( $items ? 'ready' : 'no_reviews' ) : 'connection_required' ),
				'business_name' => sanitize_text_field( $details['name'] ?? '' ),
				'rating' => isset( $details['rating_score'] ) && is_numeric( $details['rating_score'] ) && $details['rating_score'] >= 0 && $details['rating_score'] <= 5 ? (float) $details['rating_score'] : null,
				'total_reviews' => isset( $details['rating_number'] ) && is_numeric( $details['rating_number'] ) && $details['rating_number'] >= 0 ? (int) $details['rating_number'] : null,
				'rating_scale' => 5,
				'profile_url' => $connected ? esc_url_raw( $manager->getPageUrl(), array( 'https' ) ) : '',
				'icon_url' => 'https://cdn.trustindex.io/assets/platform/' . $label . '/icon.svg',
				'last_synced_at' => isset( $details['timestamp'] ) && is_numeric( $details['timestamp'] ) && $details['timestamp'] > 0 ? gmdate( 'c', (int) $details['timestamp'] ) : null,
				'displayed_reviews' => count( $items ),
				'items' => $items,
			);
		}
		return $result;
	}
}
