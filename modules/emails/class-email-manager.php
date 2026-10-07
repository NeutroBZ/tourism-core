<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'modules/emails/class-email-templates.php';
require_once TOURISM_CORE_PATH . 'includes/class-lock.php';

class Tourism_Core_Email_Manager {
	const RETRY_HOOK = 'tourism_core_retry_booking_email';
	const CLEANUP_HOOK = 'tourism_core_cleanup_locks';
	/** @var Tourism_Core_Email_Templates */
	private $templates;

	public function __construct() {
		$this->templates = new Tourism_Core_Email_Templates();
	}

	public function send_booking_admin_notice( $booking_id ) {
		return $this->send_notification( $booking_id, 'admin' );
	}

	public function send_booking_customer_confirmation( $booking_id ) {
		return $this->send_notification( $booking_id, 'customer' );
	}

	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( self::RETRY_HOOK, $this, 'retry', 10, 2 );
		$loader->add_action( self::CLEANUP_HOOK, $this, 'cleanup' );
		$loader->add_action( 'init', $this, 'schedule_cleanup' );
		$loader->add_action( 'admin_post_tourism_core_retry_email', $this, 'manual_retry' );
	}

	public function schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	public function cleanup() {
		Tourism_Core_Lock::cleanup();
	}

	public function retry( $booking_id, $kind ) {
		$this->send_notification( $booking_id, $kind );
	}

	public function manual_retry() {
		$id = isset( $_POST['booking_id'] ) && is_scalar( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		$kind = isset( $_POST['email_kind'] ) && is_string( $_POST['email_kind'] ) ? sanitize_key( $_POST['email_kind'] ) : '';
		if ( 'booking' !== get_post_type( $id ) || ! current_user_can( 'manage_tourism_bookings' ) || ! current_user_can( 'edit_post', $id ) || ! in_array( $kind, array( 'admin', 'customer' ), true ) ) {
			wp_die( esc_html__( 'You do not have permission to retry this notification.', TOURISM_CORE_TD ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'tourism_core_retry_email_' . $id . '_' . $kind );
		$this->send_notification( $id, $kind, true );
		wp_safe_redirect( get_edit_post_link( $id, 'raw' ) );
		exit;
	}

	/** Track each recipient independently; wp_mail success means accepted, not delivered. */
	public function send_notification( $booking_id, $kind, $restart = false ) {
		if ( ! in_array( $kind, array( 'admin', 'customer' ), true ) || 'trash' === get_post_status( $booking_id ) ) {
			return false;
		}
		$booking = $this->get_booking_payload( $booking_id );
		if ( ! $booking ) {
			return false;
		}
		$lock = new Tourism_Core_Lock( 'email_' . $booking_id . '_' . $kind );
		if ( ! $lock->acquire( 300 ) ) {
			return false;
		}
		try {
			$meta_key = '_tourism_email_' . $kind;
			$state = get_post_meta( $booking_id, $meta_key, true );
			$state = is_array( $state ) ? $state : array();
			if ( 'sent' === ( $state['status'] ?? '' ) ) {
				return true;
			}
			$attempts = $restart ? 0 : (int) ( $state['attempts'] ?? 0 );
			if ( $attempts >= 4 ) {
				return false;
			}
			$state = array( 'status' => 'processing', 'attempts' => $attempts + 1, 'updated' => current_time( 'mysql' ), 'error' => '', 'retry_scheduled' => false );
			update_post_meta( $booking_id, $meta_key, $state );
			$to = 'admin' === $kind ? tourism_core_get_agency( 'email' ) : $booking['email'];
			if ( 'admin' === $kind && ! is_email( $to ) ) {
				$to = (string) get_option( 'admin_email' );
			}
			$error = '';
			$failure = static function ( $mail_error ) use ( &$error ) {
				$error = substr( sanitize_text_field( $mail_error->get_error_message() ), 0, 500 );
			};
			$sent = false;
			if ( is_email( $to ) ) {
				add_action( 'wp_mail_failed', $failure );
				try {
					$subject = 'admin' === $kind ? $this->templates->admin_subject() : $this->templates->customer_subject();
					$body = 'admin' === $kind ? $this->templates->admin_body( $booking ) : $this->templates->customer_body( $booking );
					$sent = wp_mail( $to, $subject, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
				} catch ( Throwable $exception ) {
					$error = __( 'The mail service raised an error. Check your mail configuration.', TOURISM_CORE_TD );
				} finally {
					remove_action( 'wp_mail_failed', $failure );
				}
			} else {
				$error = __( 'The recipient email address is invalid.', TOURISM_CORE_TD );
			}
			$state['status'] = $sent ? 'sent' : 'failed';
			$state['error'] = $sent ? '' : ( $error ?: __( 'The mail service did not accept this notification.', TOURISM_CORE_TD ) );
			$args = array( (int) $booking_id, $kind );
			if ( $sent || $restart ) {
				wp_clear_scheduled_hook( self::RETRY_HOOK, $args );
			}
			if ( ! $sent && $state['attempts'] < 4 ) {
				$delays = array( 1 => 60, 2 => 300, 3 => 900 );
				$state['retry_scheduled'] = (bool) wp_next_scheduled( self::RETRY_HOOK, $args ) || (bool) wp_schedule_single_event( time() + $delays[ $state['attempts'] ], self::RETRY_HOOK, $args );
			}
			update_post_meta( $booking_id, $meta_key, $state );
			return (bool) $sent;
		} finally {
			$lock->release();
		}
	}

	private function get_booking_payload( $booking_id ) {
		$booking_id = (int) $booking_id;
		if ( $booking_id <= 0 || 'booking' !== get_post_type( $booking_id ) ) {
			return null;
		}

		return array(
			'id'      => $booking_id,
			'tour_id' => (int) get_post_meta( $booking_id, 'tourism_tour_id', true ),
			'name'    => (string) get_post_meta( $booking_id, 'tourism_customer_name', true ),
			'email'   => (string) get_post_meta( $booking_id, 'tourism_customer_email', true ),
			'phone'   => (string) get_post_meta( $booking_id, 'tourism_customer_phone', true ),
			'people'  => (int) get_post_meta( $booking_id, 'tourism_people', true ),
			'date'    => (string) get_post_meta( $booking_id, 'tourism_date', true ),
			'message' => (string) get_post_meta( $booking_id, 'tourism_message', true ),
		);
	}
}

