<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tour_id = isset( $tour_id ) ? (int) $tour_id : 0;
if ( $tour_id <= 0 ) {
	return;
}

$action_url = admin_url( 'admin-post.php' );
$error      = isset( $_GET['tc_error'] ) ? sanitize_text_field( wp_unslash( $_GET['tc_error'] ) ) : '';
$success    = isset( $_GET['tc_success'] ) ? (int) $_GET['tc_success'] : 0;
?>

<div class="tourism-core-booking">
	<?php if ( $success ) : ?>
		<div class="tourism-core-notice tourism-core-notice--success">
			<?php echo esc_html__( 'Thanks! Your booking request was sent.', TOURISM_CORE_TD ); ?>
		</div>
	<?php elseif ( $error ) : ?>
		<div class="tourism-core-notice tourism-core-notice--error">
			<?php echo esc_html__( 'Please review the form and try again.', TOURISM_CORE_TD ); ?>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( $action_url ); ?>" class="tourism-core-booking__form">
		<input type="hidden" name="action" value="tourism_core_create_booking" />
		<input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>" />
		<?php wp_nonce_field( 'tourism_core_create_booking', 'tourism_core_booking_nonce' ); ?>

		<p>
			<label>
				<strong><?php echo esc_html__( 'Name', TOURISM_CORE_TD ); ?></strong><br />
				<input type="text" name="name" required style="width:100%;" />
			</label>
		</p>
		<p>
			<label>
				<strong><?php echo esc_html__( 'Email', TOURISM_CORE_TD ); ?></strong><br />
				<input type="email" name="email" required style="width:100%;" />
			</label>
		</p>
		<p>
			<label>
				<strong><?php echo esc_html__( 'Phone', TOURISM_CORE_TD ); ?></strong><br />
				<input type="text" name="phone" style="width:100%;" />
			</label>
		</p>
		<p>
			<label>
				<strong><?php echo esc_html__( 'People', TOURISM_CORE_TD ); ?></strong><br />
				<input type="number" name="people" min="1" value="1" style="width:100%;" />
			</label>
		</p>
		<p>
			<label>
				<strong><?php echo esc_html__( 'Preferred date', TOURISM_CORE_TD ); ?></strong><br />
				<input type="date" name="date" style="width:100%;" />
			</label>
		</p>
		<p>
			<label>
				<strong><?php echo esc_html__( 'Message', TOURISM_CORE_TD ); ?></strong><br />
				<textarea name="message" rows="4" style="width:100%;"></textarea>
			</label>
		</p>

		<p>
			<button type="submit"><?php echo esc_html__( 'Request booking', TOURISM_CORE_TD ); ?></button>
		</p>
	</form>
</div>

