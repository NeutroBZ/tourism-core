<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Tour_Metaboxes {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'add_meta_boxes', $this, 'add_meta_boxes' );
		$loader->add_action( 'save_post_tour', $this, 'save', 10, 2 );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'tourism-core-tour-details',
			__( 'Tour Details', TOURISM_CORE_TD ),
			array( $this, 'render' ),
			'tour',
			'side',
			'default'
		);
	}

	public function render( $post ) {
		wp_nonce_field( 'tourism_core_save_tour', 'tourism_core_tour_nonce' );

		$price    = get_post_meta( $post->ID, 'tourism_price_from', true );
		$duration = get_post_meta( $post->ID, 'tourism_duration', true );
		$dest_ids = get_post_meta( $post->ID, 'tourism_destinations', true );
		$dest_ids = is_array( $dest_ids ) ? array_map( 'intval', $dest_ids ) : array();

		$destinations = get_posts(
			array(
				'post_type'      => 'destination',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
				'no_found_rows'  => true,
			)
		);
		?>
		<p>
			<label for="tourism_price_from"><strong><?php echo esc_html__( 'Price from', TOURISM_CORE_TD ); ?></strong></label><br />
			<input type="number" step="0.01" min="0" id="tourism_price_from" name="tourism_price_from" value="<?php echo esc_attr( $price ); ?>" style="width: 100%;" />
		</p>
		<p>
			<label for="tourism_duration"><strong><?php echo esc_html__( 'Duration', TOURISM_CORE_TD ); ?></strong></label><br />
			<input type="text" id="tourism_duration" name="tourism_duration" value="<?php echo esc_attr( $duration ); ?>" style="width: 100%;" placeholder="<?php echo esc_attr__( 'e.g. 3 days', TOURISM_CORE_TD ); ?>" />
		</p>
		<p>
			<label for="tourism_destinations"><strong><?php echo esc_html__( 'Destinations', TOURISM_CORE_TD ); ?></strong></label><br />
			<select id="tourism_destinations" name="tourism_destinations[]" multiple size="6" style="width: 100%;">
				<?php foreach ( $destinations as $dest ) : ?>
					<option value="<?php echo esc_attr( $dest->ID ); ?>" <?php selected( in_array( (int) $dest->ID, $dest_ids, true ) ); ?>>
						<?php echo esc_html( get_the_title( $dest->ID ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php echo esc_html__( 'Hold Ctrl/Cmd to select multiple destinations.', TOURISM_CORE_TD ); ?></span>
		</p>
		<?php
	}

	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['tourism_core_tour_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tourism_core_tour_nonce'] ) ), 'tourism_core_save_tour' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['tourism_price_from'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_POST['tourism_price_from'] ) );
			update_post_meta( $post_id, 'tourism_price_from', is_numeric( $raw ) ? (float) $raw : '' );
		}

		if ( isset( $_POST['tourism_duration'] ) ) {
			update_post_meta( $post_id, 'tourism_duration', sanitize_text_field( wp_unslash( $_POST['tourism_duration'] ) ) );
		}

		if ( isset( $_POST['tourism_destinations'] ) ) {
			$raw = wp_unslash( $_POST['tourism_destinations'] );
			$raw = is_array( $raw ) ? $raw : array();
			$out = array();
			foreach ( $raw as $v ) {
				$id = (int) $v;
				if ( $id > 0 && 'destination' === get_post_type( $id ) ) {
					$out[] = $id;
				}
			}
			update_post_meta( $post_id, 'tourism_destinations', array_values( array_unique( $out ) ) );
		} else {
			update_post_meta( $post_id, 'tourism_destinations', array() );
		}
	}
}

