<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Progressive enhancement of the existing WordPress tour form. */
class Tourism_Core_Tour_Editor {

	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'edit_form_after_title', $this, 'navigation' );
	}

	public static function sections() {
		return array(
			'general' => array( 'label' => 'Información general', 'help' => 'Presenta el tour y sus características principales.', 'boxes' => array( 'tourism-core-tour-introduction', 'tourism-core-tour-details', 'tour_categorydiv', 'tour_typediv' ) ),
			'photos' => array( 'label' => 'Fotos', 'help' => 'Elige la portada del tour y las imágenes de su galería.', 'boxes' => array( 'tourism-core-tour-hero', 'tourism-core-tour-gallery', 'postimagediv' ) ),
			'experience' => array( 'label' => 'Experiencia', 'help' => 'Explica qué disfrutará el viajero y qué necesita preparar.', 'boxes' => array( 'tourism-core-tour-highlights', 'tourism-core-tour-what-to-expect', 'tourism-core-whats-included', 'tourism-core-tour-what-to-bring' ) ),
			'itinerary' => array( 'label' => 'Itinerario', 'help' => 'Organiza las etapas. Puedes desplegar, duplicar y mover cada día.', 'boxes' => array( 'tourism-core-tour-itinerary' ) ),
			'pricing' => array( 'label' => 'Precio y reservas', 'help' => 'Define el precio y revisa las opciones de reserva de este tour.', 'boxes' => array( 'tourism-core-tour-pricing', 'tourism-core-tour-wetravel', 'tourism-core-tour-pricing-details' ) ),
			'faqs' => array( 'label' => 'Preguntas frecuentes', 'help' => 'Responde las dudas habituales antes de la reserva.', 'boxes' => array( 'tourism-core-tour-faqs' ) ),
			'advanced' => array( 'label' => 'Avanzado', 'help' => 'Código comercial, orden en los listados y controles del marcado automático.', 'boxes' => array( 'tourism-core-tour-schema', 'tourism-core-tour-order' ) ),
		);
	}

	public function navigation( $post ) {
		if ( 'tour' !== $post->post_type ) { return; }
		echo '<div id="tourism-tour-editor" class="tourism-tour-editor" hidden>';
		echo '<div class="tourism-editor-heading"><div><strong>Contenido del tour</strong><p>Completa las secciones y guarda con los botones de WordPress.</p></div><span id="tourism-editor-save-state" role="status" aria-live="polite">Sin cambios pendientes</span></div>';
		echo '<div class="tourism-editor-tabs" role="tablist" aria-label="Secciones del tour">';
		foreach ( self::sections() as $key => $section ) {
			echo '<button type="button" role="tab" id="tourism-tab-' . esc_attr( $key ) . '" data-section="' . esc_attr( $key ) . '" aria-controls="tourism-panel-' . esc_attr( $key ) . '" aria-selected="false" tabindex="-1">' . esc_html( $section['label'] ) . '</button>';
		}
		echo '</div></div>';
	}

	public static function settings() {
		$payments = Tourism_Core_Payment_Providers::settings();
		return array(
			'sections' => self::sections(),
			'postId' => get_the_ID(),
			'payments' => array(
				'wetravel' => is_string( $payments['wetravel_uid'] ) && (bool) preg_match( '/^[1-9][0-9]*$/D', $payments['wetravel_uid'] ),
				'paypal' => '' !== Tourism_Core_Payment_Providers::valid_email( $payments['paypal_email'] ),
				'currency' => $payments['paypal_currency'],
				'whatsapp' => '' !== Tourism_Core_Payment_Providers::whatsapp_number(),
			),
			'paymentsUrl' => current_user_can( 'manage_options' ) ? admin_url( 'admin.php?page=tourism-core-payments' ) : '',
		);
	}
}
