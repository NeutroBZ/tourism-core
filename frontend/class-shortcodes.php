<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Frontend_Shortcodes {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_shortcodes' );
	}

	public function register_shortcodes() {
		add_shortcode( 'tour_list', array( $this, 'tour_list' ) );
	}

	public function tour_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'posts_per_page' => 6,
				'order'          => 'DESC',
				'orderby'        => 'date',
			),
			(array) $atts,
			'tour_list'
		);

		$q = new WP_Query(
			array(
				'post_type'      => 'tour',
				'posts_per_page' => (int) $atts['posts_per_page'],
				'order'          => sanitize_text_field( $atts['order'] ),
				'orderby'        => sanitize_text_field( $atts['orderby'] ),
			)
		);

		if ( ! $q->have_posts() ) {
			return '';
		}

		ob_start();
		echo '<div class="tourism-core-list">';
		while ( $q->have_posts() ) {
			$q->the_post();
			echo Tourism_Core_Template::render( 'tour-card.php', array( 'post_id' => get_the_ID() ) );
		}
		echo '</div>';
		wp_reset_postdata();

		return (string) ob_get_clean();
	}
}

