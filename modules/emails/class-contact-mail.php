<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** Notifications for newly submitted contact messages, independent of legacy bookings. */
class Tourism_Core_Contact_Mail {
    public static function register() {
        add_action( 'phpmailer_init', array( __CLASS__, 'local_transport' ) );
        add_action( 'add_meta_boxes_tourism_message', array( __CLASS__, 'metabox' ) );
    }

    public static function local_transport( $mailer ) {
        if ( 'local' !== wp_get_environment_type() ) return;
        $port = (int) get_option( 'tourism_core_local_smtp_port', 0 );
        // Local uses its own capture inbox, never an external SMTP recipient.
        if ( $port < 1024 || $port > 65535 ) throw new RuntimeException( 'Local SMTP capture is not configured.' );
        $mailer->isSMTP(); $mailer->Host = '127.0.0.1'; $mailer->Port = $port;
        $mailer->SMTPAuth = false; $mailer->SMTPSecure = ''; $mailer->SMTPAutoTLS = false;
        $mailer->Timeout = 10;
    }

    public static function notify( $id ) {
        if ( 'tourism_message' !== get_post_type( $id ) || 'private' !== get_post_status( $id ) ) return false;
        if ( 'accepted' === get_post_meta( $id, '_notification_status', true ) ) return true;
        $recipients = array_values( array_filter( array_map( 'sanitize_email', (array) get_option( 'tourism_core_contact_recipients', array() ) ), 'is_email' ) );
        $email = sanitize_email( get_post_meta( $id, '_contact_email', true ) );
        if ( ! $recipients || ! $email ) { update_post_meta( $id, '_notification_status', 'not_configured' ); return false; }
        $host = wp_parse_url( home_url(), PHP_URL_HOST );
        $body = 'Name: ' . get_post_meta( $id, '_contact_name', true ) . "\nEmail: " . $email . "\n\n" . get_post_field( 'post_content', $id );
        try { $sent = wp_mail( $recipients, '[' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . '] ' . get_the_title( $id ), $body, array( 'From: Palotoa Amazon Travel <no-reply@' . $host . '>', 'Reply-To: ' . $email ) ); }
        catch ( Throwable $error ) { $sent = false; }
        update_post_meta( $id, '_notification_status', $sent ? 'accepted' : 'failed' );
        if ( $sent ) update_post_meta( $id, '_notification_accepted_at', gmdate( 'c' ) );
        return $sent;
    }

    public static function metabox() {
        add_meta_box( 'tourism-contact-details', __( 'Contact details', TOURISM_CORE_TD ), static function( $post ) {
            foreach ( array( '_contact_name' => 'Name', '_contact_email' => 'Email', '_notification_status' => 'Email notification' ) as $key => $label ) {
                echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( get_post_meta( $post->ID, $key, true ) ) . '</p>';
            }
        }, 'tourism_message', 'side' );
    }
}
Tourism_Core_Contact_Mail::register();
