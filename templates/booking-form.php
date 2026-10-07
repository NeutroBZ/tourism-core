<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tour_id = isset( $tour_id ) ? (int) $tour_id : 0;
if ( $tour_id <= 0 || 'publish' !== get_post_status( $tour_id ) ) {
	return;
}

$action_url = admin_url( 'admin-post.php' );
$error      = isset( $_GET['tc_error'] ) && is_string( $_GET['tc_error'] ) ? sanitize_text_field( wp_unslash( $_GET['tc_error'] ) ) : '';
$success    = isset( $_GET['tc_success'] ) && is_scalar( $_GET['tc_success'] ) ? (int) $_GET['tc_success'] : 0;
$errors = array(
	'date' => __( 'Choose a valid date from today onward.', TOURISM_CORE_TD ),
	'people' => sprintf( __( 'Enter between 1 and %d people.', TOURISM_CORE_TD ), Tourism_Core_Booking_Validator::max_people() ),
	'tour' => __( 'This tour is not available for booking requests.', TOURISM_CORE_TD ),
	'rate' => __( 'Too many requests. Please wait ten minutes before trying again.', TOURISM_CORE_TD ),
	'busy' => __( 'A request is being processed. Please wait a few seconds and try again.', TOURISM_CORE_TD ),
	'nonce' => __( 'Reload the page before sending your request again.', TOURISM_CORE_TD ),
	'length' => __( 'One of the fields is too long. Please shorten it and try again.', TOURISM_CORE_TD ),
);
?>

<div class="tourism-core-booking">
	<?php if ( $success && ! $error ) : ?>
		<div class="tourism-core-notice tourism-core-notice--success">
			<?php echo esc_html__( 'Thanks! Your booking request was saved.', TOURISM_CORE_TD ); ?>
		</div>
	<?php elseif ( $error ) : ?>
		<div class="tourism-core-notice tourism-core-notice--error">
			<?php echo esc_html( $errors[ $error ] ?? __( 'Please review the form and try again.', TOURISM_CORE_TD ) ); ?>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( $action_url ); ?>" class="tourism-core-booking__form">
		<input type="hidden" name="action" value="tourism_core_create_booking" />
		<input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>" />
		<?php wp_nonce_field( 'tourism_core_create_booking', 'tourism_core_booking_nonce' ); ?>
		<div class="tourism-core-booking__trap" aria-hidden="true" inert>
			<label>Website <input type="text" name="booking_website" value="" tabindex="-1" autocomplete="off" /></label>
		</div>

		<p>
			<label>
				<strong><?php echo esc_html__( 'Name', TOURISM_CORE_TD ); ?></strong><br />
				<input type="text" name="name" required maxlength="120" autocomplete="name" style="width:100%;" />
			</label>
		</p>
		<p>
			<label>
				<strong><?php echo esc_html__( 'Email', TOURISM_CORE_TD ); ?></strong><br />
				<input type="email" name="email" required maxlength="254" autocomplete="email" style="width:100%;" />
			</label>
		</p>
		<p>
			<label>
				<strong><?php echo esc_html__( 'Phone', TOURISM_CORE_TD ); ?></strong><br />
				<input type="tel" name="phone" maxlength="50" autocomplete="tel" style="width:100%;" />
			</label>
		</p>
		<p>
			<label>
				<strong><?php echo esc_html__( 'People', TOURISM_CORE_TD ); ?></strong><br />
				<input type="number" name="people" min="1" max="<?php echo esc_attr( Tourism_Core_Booking_Validator::max_people() ); ?>" step="1" value="1" required style="width:100%;" />
			</label>
		</p>
		<p>
			<label>
				<strong><?php echo esc_html__( 'Preferred date', TOURISM_CORE_TD ); ?></strong><br />
				<input type="date" name="date" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" style="width:100%;" />
			</label>
		</p>
		<p>
			<label>
				<strong><?php echo esc_html__( 'Message', TOURISM_CORE_TD ); ?></strong><br />
				<textarea name="message" rows="4" maxlength="5000" style="width:100%;"></textarea>
			</label>
		</p>

		<p>
			<button type="submit"><?php echo esc_html__( 'Request booking', TOURISM_CORE_TD ); ?></button>
		</p>
	</form>
</div>

