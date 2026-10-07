<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'admin/columns/class-booking-columns.php';
require_once TOURISM_CORE_PATH . 'admin/columns/class-tour-columns.php';
require_once TOURISM_CORE_PATH . 'includes/class-metabox-helper.php';

use Tourism_Core_Metabox_Helper as Metabox_Helper;

class Tourism_Core_Admin {
	public function register( Tourism_Core_Loader $loader ) {
		$booking_cols = new Tourism_Core_Admin_Booking_Columns();
		$booking_cols->register( $loader );

		$tour_cols = new Tourism_Core_Admin_Tour_Columns();
		$tour_cols->register( $loader );

		$loader->add_action( 'add_meta_boxes', $this, 'add_booking_metabox' );
		$loader->add_action( 'save_post_booking', $this, 'save_booking_admin', 10, 2 );
		$loader->add_action( 'admin_footer', $this, 'render_email_retry_forms' );
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
		if ( ! current_user_can( 'manage_tourism_bookings' ) || ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}
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
				<?php foreach ( array( 'admin' => __( 'Agency notification', TOURISM_CORE_TD ), 'customer' => __( 'Customer notification', TOURISM_CORE_TD ) ) as $kind => $label ) :
					$email_state = get_post_meta( $post->ID, '_tourism_email_' . $kind, true );
					$email_state = is_array( $email_state ) ? $email_state : array();
					$email_status = $email_state['status'] ?? 'none';
					$email_labels = array( 'sent' => __( 'Accepted by the mail service', TOURISM_CORE_TD ), 'failed' => __( 'Failed', TOURISM_CORE_TD ), 'processing' => __( 'Processing', TOURISM_CORE_TD ), 'none' => __( 'No sending record', TOURISM_CORE_TD ) ); ?>
					<tr><th><?php echo esc_html( $label ); ?></th><td>
						<?php echo esc_html( $email_labels[ $email_status ] ?? $email_status ); ?>
						<?php if ( isset( $email_state['attempts'] ) ) : ?>
							<p><?php echo esc_html( sprintf( __( 'Attempts: %d', TOURISM_CORE_TD ), $email_state['attempts'] ) ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $email_state['error'] ) ) : ?><p><?php echo esc_html( $email_state['error'] ); ?></p><?php endif; ?>
						<?php if ( ! empty( $email_state['retry_scheduled'] ) ) : ?><p><?php esc_html_e( 'An automatic retry is scheduled.', TOURISM_CORE_TD ); ?></p><?php endif; ?>
						<?php if ( 'sent' !== $email_status ) : ?>
							<button type="submit" class="button" form="tc-email-retry-<?php echo esc_attr( $kind ); ?>"><?php esc_html_e( 'Retry notification', TOURISM_CORE_TD ); ?></button>
						<?php endif; ?>
					</td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	public function save_booking_admin( $post_id, $post ) {
		if ( ! Metabox_Helper::verify_nonce( 'tourism_core_booking_admin_nonce', 'tourism_core_save_booking_admin' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_tourism_bookings' ) || ! Metabox_Helper::can_save( $post_id ) ) {
			return;
		}
		if ( 'booking' !== $post->post_type ) {
			return;
		}

		if ( isset( $_POST['tourism_booking_status'] ) ) {
			$new_status = sanitize_text_field( wp_unslash( $_POST['tourism_booking_status'] ) );
			$allowed    = array_keys( $this->status_options() );
			if ( in_array( $new_status, $allowed, true ) && $new_status !== $post->post_status ) {
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

	/** Separate forms avoid nesting notification submissions inside the post editor. */
	public function render_email_retry_forms() {
		global $post;
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || ! $post || 'booking' !== $post->post_type || ! current_user_can( 'manage_tourism_bookings' ) || ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}
		foreach ( array( 'admin', 'customer' ) as $kind ) {
			echo '<form id="tc-email-retry-' . esc_attr( $kind ) . '" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			echo '<input type="hidden" name="action" value="tourism_core_retry_email" />';
			echo '<input type="hidden" name="booking_id" value="' . esc_attr( $post->ID ) . '" />';
			echo '<input type="hidden" name="email_kind" value="' . esc_attr( $kind ) . '" />';
			wp_nonce_field( 'tourism_core_retry_email_' . $post->ID . '_' . $kind );
			echo '</form>';
		}
	}
}

