<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Server-to-server contact submission. Credentials and client fingerprint stay in the proxy. */
class Tourism_Core_Headless_Contact {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'post_type' );
		$loader->add_action( 'rest_api_init', $this, 'routes' );
	}

	public function post_type() {
		if ( post_type_exists( 'tourism_message' ) ) { return; }
		register_post_type( 'tourism_message', array( 'label' => 'Contact messages', 'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'supports' => array( 'title', 'editor' ), 'capability_type' => 'post', 'map_meta_cap' => true, 'capabilities' => array( 'create_posts' => 'do_not_allow' ) ) );
	}

	public function routes() {
		register_rest_route( 'tourism/v1', '/headless/contact', array(
			'methods' => WP_REST_Server::CREATABLE,
			'permission_callback' => static function () { return current_user_can( 'manage_options' ) || current_user_can( 'tourism_submit_contact' ); },
			'callback' => array( $this, 'submit' ),
		) );
	}

	public function submit( WP_REST_Request $request ) {
		$limits = array( 'name' => 200, 'email' => 254, 'subject' => 200, 'message' => 10000, 'fingerprint' => 64 ); $input = array();
		foreach ( $limits as $key => $limit ) {
			$value = $request->get_param( $key );
			if ( ! is_string( $value ) || '' === trim( $value ) || strlen( $value ) > $limit ) {
				return new WP_Error( 'invalid_contact', 'Completa los campos del mensaje con valores válidos.', array( 'status' => 400 ) );
			}
			$input[ $key ] = trim( $value );
		}
		if ( ! is_email( $input['email'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $input['fingerprint'] ) ) {
			return new WP_Error( 'invalid_contact', 'Email o identificador de solicitud inválido.', array( 'status' => 400 ) );
		}
		$name = sanitize_text_field( $input['name'] ); $subject = sanitize_text_field( $input['subject'] ); $message = sanitize_textarea_field( $input['message'] );
		if ( '' === $name || '' === $subject || '' === $message ) { return new WP_Error( 'invalid_contact', 'El mensaje no puede estar vacío.', array( 'status' => 400 ) ); }
		$key = 'tourism_headless_contact_' . hash_hmac( 'sha256', $input['fingerprint'], wp_salt() );
		if ( get_transient( $key ) ) { return new WP_Error( 'contact_rate_limit', 'Espera antes de enviar otro mensaje.', array( 'status' => 429 ) ); }
		$this->post_type();
		$id = wp_insert_post( wp_slash( array( 'post_type' => 'tourism_message', 'post_status' => 'private', 'post_title' => $subject, 'post_content' => $message, 'meta_input' => array( '_contact_name' => $name, '_contact_email' => sanitize_email( $input['email'] ) ) ) ), true );
		if ( is_wp_error( $id ) || ! $id ) { return new WP_Error( 'contact_not_saved', 'No se pudo guardar el mensaje.', array( 'status' => 500 ) ); }
		set_transient( $key, 1, MINUTE_IN_SECONDS );
		// Keep the private message even when notification delivery fails.
		Tourism_Core_Contact_Mail::notify( $id );
		$response = new WP_REST_Response( array( 'accepted' => true ), 202 );
		$response->header( 'Cache-Control', 'private, no-store' );
		return $response;
	}
}
