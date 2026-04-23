<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'admin/columns/class-booking-columns.php';
require_once TOURISM_CORE_PATH . 'admin/columns/class-tour-columns.php';

class Tourism_Core_Admin {
	public function register( Tourism_Core_Loader $loader ) {
		$booking_cols = new Tourism_Core_Admin_Booking_Columns();
		$booking_cols->register( $loader );

		$tour_cols = new Tourism_Core_Admin_Tour_Columns();
		$tour_cols->register( $loader );

		$loader->add_action( 'add_meta_boxes', $this, 'add_booking_metabox' );
		$loader->add_action( 'save_post_booking', $this, 'save_booking_admin', 10, 2 );
	}

	public function add_booking_metabox() {
		add_meta_box(
			'tourism-core-booking-details',
			__( 'Booking Details', TOURISM_CORE_TD ),
			array( $this, 'render_booking_metabox' ),
			'booking',
			'normal',
			'default'
		);
	}

	public function render_booking_metabox( $post ) {
		wp_nonce_field( 'tourism_core_save_booking_admin', 'tourism_core_booking_admin_nonce' );

		$tour_id = (int) get_post_meta( $post->ID, 'tourism_tour_id', true );
		$name    = (string) get_post_meta( $post->ID, 'tourism_customer_name', true );
		$email   = (string) get_post_meta( $post->ID, 'tourism_customer_email', true );
		$phone   = (string) get_post_meta( $post->ID, 'tourism_customer_phone', true );
		$people  = (string) get_post_meta( $post->ID, 'tourism_people', true );
		$date    = (string) get_post_meta( $post->ID, 'tourism_date', true );
		$msg     = (string) get_post_meta( $post->ID, 'tourism_message', true );
		$status  = (string) get_post_status( $post->ID );

		?>
		<table class="widefat striped" style="max-width: 900px;">
			<tbody>
				<tr>
					<th style="width:220px;"><?php echo esc_html__( 'Tour', TOURISM_CORE_TD ); ?></th>
					<td>
						<?php if ( $tour_id ) : ?>
							<a href="<?php echo esc_url( get_edit_post_link( $tour_id ) ); ?>">
								<?php echo esc_html( get_the_title( $tour_id ) ); ?>
							</a>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Customer', TOURISM_CORE_TD ); ?></th>
					<td><?php echo esc_html( $name ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Email', TOURISM_CORE_TD ); ?></th>
					<td><?php echo $email ? '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' : ''; ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Phone', TOURISM_CORE_TD ); ?></th>
					<td><?php echo esc_html( $phone ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'People', TOURISM_CORE_TD ); ?></th>
					<td><?php echo esc_html( $people ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Preferred date', TOURISM_CORE_TD ); ?></th>
					<td><?php echo esc_html( $date ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Message', TOURISM_CORE_TD ); ?></th>
					<td><pre style="white-space: pre-wrap; margin:0;"><?php echo esc_html( $msg ); ?></pre></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Status', TOURISM_CORE_TD ); ?></th>
					<td>
						<select name="tourism_booking_status">
							<?php foreach ( $this->status_options() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html__( 'Change status and update the booking.', TOURISM_CORE_TD ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public function save_booking_admin( $post_id, $post ) {
		if ( ! isset( $_POST['tourism_core_booking_admin_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['tourism_core_booking_admin_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'tourism_core_save_booking_admin' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( 'booking' !== $post->post_type ) {
			return;
		}

		if ( isset( $_POST['tourism_booking_status'] ) ) {
			$new_status = sanitize_text_field( wp_unslash( $_POST['tourism_booking_status'] ) );
			$allowed    = array_keys( $this->status_options() );
			if ( in_array( $new_status, $allowed, true ) ) {
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_status' => $new_status,
					)
				);
			}
		}
	}

	private function status_options() {
		return array(
			'booking_pending'   => __( 'Pending', TOURISM_CORE_TD ),
			'booking_confirmed' => __( 'Confirmed', TOURISM_CORE_TD ),
			'booking_cancelled' => __( 'Cancelled', TOURISM_CORE_TD ),
		);
	}
}

