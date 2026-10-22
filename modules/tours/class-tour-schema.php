<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** A tour is one entity with both TouristTrip and Product types. */
class Tourism_Core_Tour_Schema {
	private $emitted = false;

	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'wp_head', $this, 'output', 30 );
	}

	public static function text( $value ) {
		if ( ! is_scalar( $value ) ) { return ''; }
		$value = wp_check_invalid_utf8( (string) $value );
		$value = preg_replace( '/<(?:br|\/p|\/li|\/h[1-6])[^>]*>/i', ' ', $value );
		$value = html_entity_decode( wp_strip_all_tags( strip_shortcodes( $value ), true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( preg_replace( '/\s+/u', ' ', $value ) ?? '' );
	}

	private static function meta( $id, $key ) { return self::text( get_post_meta( $id, $key, true ) ); }

	/** Use the visible introduction, or summarize the visible itinerary when it is absent. */
	public static function description( $id ) {
		$description = self::meta( $id, 'tourism_intro_description' );
		if ( '' !== $description ) { return $description; }
		$rows = get_post_meta( $id, 'tourism_itinerary_items', true );
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$description = self::text( $row['description'] ?? '' );
			if ( '' !== $description ) { return wp_trim_words( $description, 55, '…' ); }
		}
		return '';
	}

	private static function url( $url ) {
		if ( ! is_string( $url ) || ! in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) || ! wp_parse_url( $url, PHP_URL_HOST ) ) { return ''; }
		return esc_url_raw( $url, array( 'http', 'https' ) );
	}

	public static function images( $id ) {
		$images = array();
		$hero = absint( get_post_meta( $id, '_tour_hero_image', true ) );
		$first = $hero ? wp_get_attachment_image_url( $hero, 'full' ) : get_the_post_thumbnail_url( $id, 'full' );
		if ( ! $first ) { $first = get_the_post_thumbnail_url( $id, 'full' ); }
		if ( $first ) { $images[] = $first; }
		$rows = get_post_meta( $id, 'tourism_gallery_images', true );
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$url = ! empty( $row['url'] ) ? $row['url'] : wp_get_attachment_image_url( absint( $row['id'] ?? 0 ), 'full' );
			if ( $url ) { $images[] = $url; }
		}
		return array_values( array_unique( array_filter( array_map( array( __CLASS__, 'url' ), $images ) ) ) );
	}

	private static function agency() {
		$data = apply_filters( 'tourism_core_tour_schema_agency', tourism_core_get_agency() );
		if ( ! is_array( $data ) ) { return array(); }
		$name = self::text( $data['commercial_name'] ?? '' ) ?: self::text( $data['name'] ?? '' ) ?: self::text( $data['company_name'] ?? '' );
		if ( '' === $name ) { return array(); }
		$agency = array( '@type' => 'TravelAgency', '@id' => home_url( '/#tourism-agency' ), 'name' => $name, 'url' => home_url( '/' ) );
		foreach ( array( 'company_name' => 'legalName', 'phone' => 'telephone', 'email' => 'email', 'address' => 'address' ) as $key => $property ) {
			$value = self::text( $data[ $key ] ?? '' );
			if ( '' !== $value && ( 'email' !== $property || is_email( $value ) ) ) { $agency[ $property ] = $value; }
		}
		$links = array();
		foreach ( array( 'facebook', 'instagram', 'tripadvisor', 'linkedin' ) as $key ) {
			$link = self::url( $data[ $key ] ?? '' );
			if ( $link ) { $links[] = $link; }
		}
		if ( $links ) { $agency['sameAs'] = array_values( array_unique( $links ) ); }
		return $agency;
	}

	public static function offer( $id ) {
		$price = get_post_meta( $id, 'tourism_price_from', true );
		$currency = tourism_core_get_tour_currency( $id );
		$until = get_post_meta( $id, 'tourism_price_valid_until', true );
		if ( ! is_numeric( $price ) || ! is_finite( (float) $price ) || (float) $price < 0 || '' === $currency || ! Tourism_Core_Tour_Schema_Metabox::valid_date( $until ) || ( '' !== $until && $until < current_time( 'Y-m-d' ) ) ) { return array(); }
		$url = get_permalink( $id );
		$offer = array( '@type' => 'Offer', '@id' => $url . '#offer', 'url' => $url, 'price' => number_format( (float) $price, 2, '.', '' ), 'priceCurrency' => $currency, 'itemOffered' => array( '@id' => $url . '#tour' ) );
		if ( '' !== $until ) { $offer['priceValidUntil'] = $until; }
		$availability = self::meta( $id, 'tourism_offer_availability' );
		if ( '' !== $availability && isset( Tourism_Core_Tour_Schema_Metabox::availability()[ $availability ] ) ) { $offer['availability'] = 'https://schema.org/' . $availability; }
		return $offer;
	}

	private static function properties( $id ) {
		$properties = array();
		foreach ( array( 'tourism_duration' => 'Duración', 'tourism_difficulty' => 'Dificultad', 'tourism_group_size' => 'Tamaño del grupo', 'tourism_max_elevation' => 'Altitud máxima' ) as $key => $label ) {
			$value = self::meta( $id, $key );
			if ( '' !== $value ) { $properties[] = array( '@type' => 'PropertyValue', 'name' => $label, 'value' => $value ); }
		}
		foreach ( array( 'tourism_highlights' => 'Destacados', 'tourism_includes_new' => 'Incluye', 'tourism_excludes_new' => 'No incluye', 'tourism_what_to_expect' => 'Qué esperar', 'tourism_what_to_bring_items' => 'Qué llevar', 'tourism_bring_items' => 'Equipaje', 'tourism_pricing_details' => 'Detalles del precio', 'tourism_faqs' => 'Pregunta frecuente' ) as $key => $label ) {
			$rows = get_post_meta( $id, $key, true );
			foreach ( is_array( $rows ) ? $rows : array() as $row ) {
				if ( ! is_array( $row ) ) { continue; }
				$title = self::text( $row['title'] ?? $row['question'] ?? '' );
				$content = self::text( $row['content'] ?? $row['description'] ?? $row['answer'] ?? '' );
				if ( '' === $title && '' === $content ) { continue; }
				$properties[] = array( '@type' => 'PropertyValue', 'name' => $label . ( '' !== $title && '' !== $content ? ': ' . $title : '' ), 'value' => '' !== $content ? $content : $title );
			}
		}
		return $properties;
	}

	private static function destinations( $id ) {
		$places = array();
		foreach ( tourism_core_get_tour_destinations( $id ) as $destination_id ) {
			if ( 'publish' !== get_post_status( $destination_id ) ) { continue; }
			$name = self::text( get_the_title( $destination_id ) );
			if ( '' === $name ) { continue; }
			$place = array( '@type' => 'Place', '@id' => get_permalink( $destination_id ) . '#place', 'name' => $name, 'url' => get_permalink( $destination_id ) );
			$places[] = $place;
		}
		// Destination checkboxes do not declare chronological route order.
		return $places ? array( '@type' => 'ItemList', 'itemListOrder' => 'https://schema.org/ItemListUnordered', 'numberOfItems' => count( $places ), 'itemListElement' => $places ) : array();
	}

	private static function stages( $id, $tour_ref ) {
		$stages = array();
		$rows = get_post_meta( $id, 'tourism_itinerary_items', true );
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$name = self::text( $row['day'] ?? '' );
			$description = self::text( $row['description'] ?? '' );
			if ( '' === $name ) { continue; }
			$stage = array( '@type' => 'TouristTrip', '@id' => get_permalink( $id ) . '#stage-' . ( count( $stages ) + 1 ), 'name' => $name, 'partOfTrip' => array( '@id' => $tour_ref ) );
			$features = array();
			foreach ( isset( $row['features'] ) && is_array( $row['features'] ) ? $row['features'] : array() as $feature ) {
				if ( ! is_array( $feature ) ) { continue; }
				$label = self::text( $feature['label'] ?? '' );
				$value = self::text( $feature['value'] ?? '' );
				if ( '' !== $value ) { $features[] = ( $label ? $label . ': ' : '' ) . $value; }
			}
			$description = trim( $description . ( $features ? ' ' . implode( '; ', $features ) : '' ) );
			if ( '' !== $description ) { $stage['description'] = $description; }
			$stages[] = $stage;
		}
		return $stages;
	}

	public static function build( $id ) {
		$post = get_post( $id );
		if ( ! $post || 'tour' !== $post->post_type || 'publish' !== $post->post_status || ! tourism_core_is_module_enabled( 'tours' ) || post_password_required( $post ) || get_post_meta( $id, 'tourism_schema_disabled', true ) || ! apply_filters( 'tourism_core_tour_schema_enabled', true, $id ) ) { return array(); }
		$name = self::text( get_the_title( $id ) );
		if ( '' === $name ) { return array(); }
		$url = get_permalink( $id );
		$tour = array( '@type' => array( 'TouristTrip', 'Product' ), '@id' => $url . '#tour', 'name' => $name, 'url' => $url, 'mainEntityOfPage' => $url );
		$description = self::description( $id );
		if ( $description ) { $tour['description'] = $description; }
		$images = self::images( $id );
		if ( $images ) { $tour['image'] = $images; }
		$sku = self::meta( $id, 'tourism_tour_sku' );
		if ( $sku ) { $tour['sku'] = $sku; }
		$origin = self::meta( $id, 'tourism_trip_origin' );
		if ( $origin ) { $tour['tripOrigin'] = array( '@type' => 'Place', 'name' => $origin ); }
		foreach ( array( 'tour_type' => 'touristType', 'tour_category' => 'category' ) as $taxonomy => $property ) {
			$terms = get_the_terms( $id, $taxonomy );
			if ( is_array( $terms ) ) {
				$names = array_values( array_filter( array_map( array( __CLASS__, 'text' ), wp_list_pluck( $terms, 'name' ) ) ) );
				if ( $names ) { $tour[ $property ] = $names; }
			}
		}
		$properties = self::properties( $id );
		if ( $properties ) { $tour['additionalProperty'] = $properties; }
		$destinations = self::destinations( $id );
		if ( $destinations ) { $tour['itinerary'] = $destinations; }
		$stages = self::stages( $id, $tour['@id'] );
		if ( $stages ) { $tour['subTrip'] = $stages; }
		$agency = self::agency();
		if ( $agency ) {
			$tour['provider'] = array( '@id' => $agency['@id'] );
			$tour['brand'] = array( '@type' => 'Brand', 'name' => $agency['name'] );
		}
		$offer = self::offer( $id );
		if ( $offer ) {
			if ( $agency ) { $offer['seller'] = array( '@id' => $agency['@id'] ); }
			$tour['offers'] = $offer;
		}
		$graph = array( $tour );
		if ( $agency ) { $graph[] = $agency; }
		$graph = apply_filters( 'tourism_core_tour_schema_graph', array( '@context' => 'https://schema.org', '@graph' => $graph ), $id );
		// Product snippets require an offer, review or aggregate rating. Keep the trip
		// description without advertising an incomplete Product to search engines.
		foreach ( $graph['@graph'] as &$entity ) {
			if ( ( $entity['@id'] ?? '' ) !== $tour['@id'] ) { continue; }
			if ( empty( $entity['offers'] ) && empty( $entity['review'] ) && empty( $entity['aggregateRating'] ) ) {
				$entity['@type'] = 'TouristTrip';
				unset( $entity['sku'], $entity['brand'], $entity['category'], $entity['additionalProperty'] );
			}
		}
		unset( $entity );
		return $graph;
	}

	public function output() {
		if ( $this->emitted || ! is_singular( 'tour' ) || is_preview() || is_feed() || is_admin() ) { return; }
		$graph = self::build( get_queried_object_id() );
		if ( ! $graph ) { return; }
		$json = wp_json_encode( $graph, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( ! $json ) { return; }
		$this->emitted = true;
		echo '<script type="application/ld+json" id="tourism-core-tour-schema">' . $json . '</script>' . "\n";
	}

	public static function diagnostics( $id ) {
		$warnings = array();
		if ( '' === self::text( get_the_title( $id ) ) ) { $warnings[] = 'Falta el nombre del tour.'; }
		if ( '' === self::description( $id ) ) { $warnings[] = 'Completa la descripción de la introducción o el itinerario visible.'; }
		if ( ! self::images( $id ) ) { $warnings[] = 'Añade una imagen de portada, destacada o galería.'; }
		if ( ! self::offer( $id ) ) { $warnings[] = 'No se publicará Offer: revisa precio, moneda y vigencia. Sin oferta ni reseñas publicables se emitirá solo TouristTrip.'; }
		if ( ! self::agency() ) { $warnings[] = 'Configura el nombre de la agencia para identificar al proveedor y vendedor.'; }
		if ( get_post_meta( $id, 'tourism_schema_disabled', true ) ) { $warnings[] = 'El marcado está desactivado para este tour.'; }
		return $warnings;
	}

	/** Public facts corresponding to the new fields, usable from any theme. */
	public static function public_details( $id ) {
		$values = array();
		foreach ( array( 'tourism_tour_sku' => 'Código del tour', 'tourism_trip_origin' => 'Lugar de salida', 'tourism_price_valid_until' => 'Precio válido hasta' ) as $key => $label ) {
			$value = self::meta( $id, $key );
			if ( $value ) { $values[ $label ] = $value; }
		}
		$availability = self::meta( $id, 'tourism_offer_availability' );
		if ( $availability && isset( Tourism_Core_Tour_Schema_Metabox::availability()[ $availability ] ) ) { $values['Disponibilidad'] = Tourism_Core_Tour_Schema_Metabox::availability()[ $availability ]; }
		if ( ! $values ) { return ''; }
		$html = '<dl class="tourism-core-tour-facts">';
		foreach ( $values as $label => $value ) { $html .= '<div><dt>' . esc_html( __( $label, TOURISM_CORE_TD ) ) . '</dt><dd>' . esc_html( __( $value, TOURISM_CORE_TD ) ) . '</dd></div>'; }
		return $html . '</dl>';
	}
}
