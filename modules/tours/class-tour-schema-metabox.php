<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Tourism_Core_Tour_Schema_Metabox {
	private $invalid = false;

	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_meta' );
		$loader->add_action( 'add_meta_boxes_tour', $this, 'add_metabox' );
		$loader->add_action( 'save_post_tour', $this, 'save', 20, 2 );
		$loader->add_action( 'admin_notices', $this, 'notice' );
		$loader->add_filter( 'redirect_post_location', $this, 'redirect' );
	}

	public static function availability() {
		return array(
			'' => 'Sin especificar',
			'InStock' => 'Disponible para reservar',
			'LimitedAvailability' => 'Disponibilidad limitada',
			'SoldOut' => 'Agotado',
			'PreOrder' => 'Preventa',
			'Discontinued' => 'Ya no se ofrece',
		);
	}

	public static function valid_date( $date ) {
		return is_string( $date ) && ( '' === $date || ( preg_match( '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $date, $parts ) && checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) );
	}

	public static function fields() {
		return array(
			'tourism_price_currency' => array( 'label' => 'Moneda del precio', 'type' => 'select', 'options' => array( '' => 'Usar moneda general de Payments' ) + tourism_core_tour_currencies() ),
			'tourism_offer_availability' => array( 'label' => 'Disponibilidad del tour', 'type' => 'select', 'options' => self::availability() ),
			'tourism_price_valid_until' => array( 'label' => 'Precio válido hasta (opcional)', 'type' => 'date' ),
			'tourism_tour_sku' => array( 'label' => 'Código comercial del tour / SKU (opcional)', 'type' => 'text', 'max' => 120 ),
			'tourism_trip_origin' => array( 'label' => 'Lugar de salida (opcional)', 'type' => 'text', 'max' => 200 ),
		);
	}

	public static function sanitize( $key, $value ) {
		$fields = self::fields();
		if ( ! isset( $fields[ $key ] ) || ! is_string( $value ) ) { return null; }
		$value = trim( $value );
		$field = $fields[ $key ];
		if ( 'select' === $field['type'] ) { return isset( $field['options'][ $value ] ) ? $value : null; }
		if ( 'date' === $field['type'] ) { return self::valid_date( $value ) ? $value : null; }
		$value = sanitize_text_field( $value );
		return ( function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value ) ) <= $field['max'] ? $value : null;
	}

	public function register_meta() {
		foreach ( self::fields() as $key => $field ) {
			$schema = array( 'type' => 'string' );
			if ( isset( $field['options'] ) ) { $schema['enum'] = array_keys( $field['options'] ); }
			if ( isset( $field['max'] ) ) { $schema['maxLength'] = $field['max']; }
			register_post_meta( 'tour', $key, array(
				'type' => 'string', 'single' => true,
				'show_in_rest' => array( 'schema' => $schema ),
				'sanitize_callback' => static function ( $value ) use ( $key ) { return self::sanitize( $key, $value ) ?? ''; },
				'auth_callback' => static function ( $allowed, $meta_key, $post_id ) { return current_user_can( 'edit_post', $post_id ); },
			) );
		}
		register_post_meta( 'tour', 'tourism_schema_disabled', array(
			'type' => 'boolean', 'single' => true, 'show_in_rest' => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'auth_callback' => static function ( $allowed, $meta_key, $post_id ) { return current_user_can( 'edit_post', $post_id ); },
		) );
	}

	public function add_metabox() {
		add_meta_box( 'tourism-core-tour-schema', 'Datos estructurados del tour', array( $this, 'render' ), 'tour', 'normal' );
	}

	public function render( $post ) {
		wp_nonce_field( 'tourism_core_save_schema', 'tourism_core_schema_nonce' );
		echo '<input type="hidden" name="tourism_schema_metabox_present" value="1" />';
		echo '<p>TouristTrip y Product usan automáticamente el nombre, descripción, imágenes, precio, destinos e itinerario de las otras cajas. Completa aquí los datos adicionales reales.</p>';
		foreach ( self::fields() as $key => $field ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label><br />';
			if ( 'select' === $field['type'] ) {
				echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '">';
				foreach ( $field['options'] as $option => $label ) {
					echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
			} else {
				echo '<input class="regular-text" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( is_scalar( $value ) ? $value : '' ) . '"' . ( isset( $field['max'] ) ? ' maxlength="' . esc_attr( $field['max'] ) . '"' : '' ) . ' />';
			}
			echo '</p>';
		}
		echo '<p class="description">La moneda debe coincidir con el precio anunciado. PayPal admite USD y EUR en esta integración; un precio en PEN no se convierte automáticamente. La disponibilidad se declara aquí; no se sincroniza con cupos ni pagos.</p>';
		echo '<p><label><input type="checkbox" name="tourism_schema_disabled" value="1" ' . checked( (bool) get_post_meta( $post->ID, 'tourism_schema_disabled', true ), true, false ) . ' /> Desactivar este marcado para este tour (por ejemplo, si otro plugin ya lo genera).</label></p>';
		$warnings = Tourism_Core_Tour_Schema::diagnostics( $post->ID );
		if ( $warnings ) {
			echo '<p><strong>Datos por revisar:</strong></p><ul>';
			foreach ( $warnings as $warning ) { echo '<li>' . esc_html( $warning ) . '</li>'; }
			echo '</ul>';
		} else { echo '<p>Los datos básicos de nombre, descripción, imagen y oferta están completos.</p>'; }
	}

	public function save( $post_id, $post ) {
		if ( 'tour' !== $post->post_type || ! isset( $_POST['tourism_schema_metabox_present'] ) || ! Tourism_Core_Metabox_Helper::verify_nonce( 'tourism_core_schema_nonce', 'tourism_core_save_schema' ) || ! Tourism_Core_Metabox_Helper::can_save( $post_id ) ) { return; }
		foreach ( self::fields() as $key => $field ) {
			if ( ! array_key_exists( $key, $_POST ) ) { continue; }
			$value = self::sanitize( $key, wp_unslash( $_POST[ $key ] ) );
			if ( null === $value ) { $this->invalid = true; continue; }
			update_post_meta( $post_id, $key, $value );
		}
		update_post_meta( $post_id, 'tourism_schema_disabled', isset( $_POST['tourism_schema_disabled'] ) && '1' === $_POST['tourism_schema_disabled'] );
	}

	public function redirect( $url ) { return $this->invalid ? add_query_arg( 'tc_schema_error', '1', $url ) : remove_query_arg( 'tc_schema_error', $url ); }

	public function notice() {
		$screen = get_current_screen();
		if ( $screen && 'tour' === $screen->post_type && isset( $_GET['tc_schema_error'] ) ) {
			echo '<div class="notice notice-error"><p>Algunos datos estructurados no se guardaron porque no eran válidos. Se conservaron sus valores anteriores.</p></div>';
		}
	}
}
