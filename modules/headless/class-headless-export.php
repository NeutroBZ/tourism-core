<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Authenticated build-time contract. Never exports arbitrary options, users or post meta. */
class Tourism_Core_Headless_Export {
	private $origin;
	private $urls = array();
	private $paths = array();
	private $source_urls = array();
	private $posts = array();

	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'rest_api_init', $this, 'routes' );
	}

	public function routes() {
		register_rest_route( 'tourism/v1', '/headless', array(
			'methods' => WP_REST_Server::READABLE,
			'permission_callback' => static function () { return current_user_can( 'manage_options' ) || current_user_can( 'tourism_export_headless' ); },
			'callback' => function () {
				$origin = get_option( 'tourism_headless_frontend_url', '' );
				if ( ! $origin ) { return new WP_Error( 'frontend_not_configured', 'Configura el dominio público del frontend.', array( 'status' => 409 ) ); }
				$response = new WP_REST_Response( $this->snapshot( $origin ) );
				$response->header( 'Cache-Control', 'private, no-store' );
				return $response;
			},
		) );
	}

	private static function path( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		return $path ? '/' . ltrim( $path, '/' ) : '/';
	}

	/** Rewrite site links, never external hosts or customer-provided destinations. */
	public function rewrite( $value ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $key => $item ) { $out[ $key ] = $this->rewrite( $item ); }
			return $out;
		}
		if ( ! is_string( $value ) ) { return $value; }
		return preg_replace_callback( '~https?://[^\s<>"\x27]+~u', function ( $match ) {
			$url = html_entity_decode( $match[0], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$host = strtolower( wp_parse_url( $url, PHP_URL_HOST ) ?? '' );
			$hosts = array( strtolower( wp_parse_url( home_url(), PHP_URL_HOST ) ), strtolower( wp_parse_url( $this->origin, PHP_URL_HOST ) ), 'test.local' );
			$hosts[] = 'www.' . preg_replace( '/^www\./', '', strtolower( wp_parse_url( $this->origin, PHP_URL_HOST ) ) );
			if ( ! in_array( $host, $hosts, true ) ) { return $match[0]; }
			$path = self::path( $url ); $query = wp_parse_url( $url, PHP_URL_QUERY );
			parse_str( $query ?? '', $args );
			$id = absint( $args['p'] ?? $args['page_id'] ?? 0 );
			$ids = $host === strtolower( wp_parse_url( home_url(), PHP_URL_HOST ) ) ? $this->urls : $this->source_urls + $this->urls;
			if ( $id && isset( $ids[ $id ] ) ) {
				$target = $ids[ $id ];
				unset( $args['p'], $args['page_id'], $args['post_type'] );
				$query = http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
			} else {
				$target_path = $this->paths[ $path ] ?? $path;
				foreach ( get_option( 'tourism_headless_route_policy', array() ) as $taxonomy => $base ) {
					$target_path = preg_replace( '~^(/(?:fr/)?)(?:' . preg_quote( $taxonomy, '~' ) . ')/~', '$1' . $base . '/', $target_path );
				}
				$target = $this->origin . $target_path;
			}
			$result = $target . ( $query ? '?' . $query : '' ) . ( wp_parse_url( $url, PHP_URL_FRAGMENT ) ? '#' . wp_parse_url( $url, PHP_URL_FRAGMENT ) : '' );
			return str_contains( $match[0], '&amp;' ) ? str_replace( '&', '&amp;', $result ) : $result;
		}, $value );
	}

	private function prepare( $origin ) {
		$parts = wp_parse_url( $origin );
		if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || ! empty( $parts['query'] ) || ! empty( $parts['fragment'] ) || ! empty( $parts['port'] ) || ! in_array( $parts['path'] ?? '', array( '', '/' ), true ) ) {
			throw new InvalidArgumentException( 'El frontend requiere un origen HTTPS sin ruta, credenciales ni puerto.' );
		}
		$this->origin = rtrim( $origin, '/' ); $this->urls = array(); $this->paths = array(); $this->source_urls = array();
		$this->posts = get_posts( array( 'post_type' => array( 'tour', 'page', 'post', 'destination' ), 'post_status' => 'publish', 'has_password' => false, 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'lang' => '', 'suppress_filters' => true ) );
		$legacy = get_option( 'tourism_legacy_url_map', array() ); $used = array();
		foreach ( $this->posts as $post ) {
			$current = self::path( get_permalink( $post ) ); $path = $current;
			if ( 'tour' === $post->post_type ) {
				foreach ( $legacy as $old => $id ) {
					if ( (int) $id === $post->ID && preg_match( '~^/(?:fr/)?tour/[^/]+/$~', $old ) ) { $path = $old; break; }
				}
			}
			if ( isset( $used[ $path ] ) ) { throw new RuntimeException( 'Colisión de rutas: ' . $path ); }
			$used[ $path ] = $post->ID; $this->paths[ $current ] = $path; $this->urls[ $post->ID ] = $this->origin . $path;
			if ( preg_match( '/^legacy-test-(?:tour|page|post)-(\d+)$/', get_post_meta( $post->ID, '_tourism_import_key', true ), $match ) ) { $this->source_urls[ (int) $match[1] ] = $this->origin . $path; }
		}
		foreach ( $legacy as $old => $id ) { if ( isset( $this->urls[ $id ] ) ) { $this->paths[ $old ] = self::path( $this->urls[ $id ] ); } }
		foreach ( get_option( 'tourism_migration_front_pages', array() ) as $language => $id ) {
			if ( isset( $this->urls[ $id ] ) && function_exists( 'pll_default_language' ) && $language !== pll_default_language() ) { $this->paths[ '/' . $language . '/' ] = self::path( $this->urls[ $id ] ); }
		}
	}

	private static function image( $id ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
		return $src ? array( 'id' => (int) $id, 'url' => $src[0], 'width' => $src[1], 'height' => $src[2], 'alt' => get_post_meta( $id, '_wp_attachment_image_alt', true ) ) : null;
	}

	private function author( $post ) {
		$authors = get_option( 'tourism_headless_public_authors', array() );
		$key = get_post_meta( $post->ID, '_tourism_import_key', true );
		if ( preg_match( '/-(\d+)$/', $key, $match ) && isset( $authors[ $match[1] ] ) ) {
			$author = $authors[ $match[1] ];
			return array( 'name' => sanitize_text_field( $author['name'] ), 'url' => $this->origin . '/author/' . rawurlencode( $author['slug'] ) . '/' );
		}
		return array( 'name' => get_the_author_meta( 'display_name', $post->post_author ), 'url' => $this->rewrite( get_author_posts_url( $post->post_author ) ) );
	}

	/** Shortcodes become component data, never HTML generated by the WordPress theme. */
	public static function components( $html ) {
		$segments = array(); $offset = 0;
		preg_match_all( '/\[([A-Za-z_][A-Za-z0-9_-]*)\b/', $html, $names );
		if ( ! $names[1] ) { return '' !== $html ? array( array( 'type' => 'html', 'html' => $html ) ) : array(); }
		preg_match_all( '/' . get_shortcode_regex( array_unique( $names[1] ) ) . '/s', $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
		foreach ( $matches as $match ) {
			$at = $match[0][1];
			if ( $at > $offset ) { $segments[] = array( 'type' => 'html', 'html' => substr( $html, $offset, $at - $offset ) ); }
			$attributes = shortcode_parse_atts( $match[3][0] );
			$segments[] = array( 'type' => 'component', 'name' => $match[2][0], 'attributes' => is_array( $attributes ) ? $attributes : array(), 'content' => $match[5][0], 'source' => $match[0][0] );
			$offset = $at + strlen( $match[0][0] );
		}
		if ( $offset < strlen( $html ) ) { $segments[] = array( 'type' => 'html', 'html' => substr( $html, $offset ) ); }
		return $segments;
	}

	private function seo( $post ) {
		$keys = array( 'title', 'metadesc', 'canonical', 'meta-robots-noindex', 'meta-robots-nofollow', 'meta-robots-adv', 'opengraph-title', 'opengraph-description', 'opengraph-image', 'twitter-title', 'twitter-description', 'twitter-image', 'bctitle', 'schema_page_type', 'schema_article_type', 'primary_category', 'primary_tour_category' );
		$stored = array();
		foreach ( $keys as $key ) { $stored[ $key ] = get_post_meta( $post->ID, '_yoast_wpseo_' . $key, true ); }
		$meta = function_exists( 'YoastSEO' ) ? YoastSEO()->meta->for_post( $post->ID ) : null;
		$title = $meta ? $meta->title : get_the_title( $post );
		$defaults = get_option( 'tourism_headless_source_seo_defaults', array() );
		$template = $stored['title'] ?: ( $defaults[ 'title-' . $post->post_type ] ?? '' );
		if ( $template && function_exists( 'wpseo_replace_vars' ) ) { $title = wpseo_replace_vars( $template, $post ); }
		$description = $meta ? $meta->description : '';
		if ( ! $description ) {
			$text = $post->post_excerpt ?: ( 'tour' === $post->post_type ? Tourism_Core_Tour_Schema::description( $post->ID ) : $post->post_content );
			$description = wp_html_excerpt( Tourism_Core_Tour_Schema::text( $text ), 155, '…' );
		}
		$title = Tourism_Core_Tour_Schema::text( $title ); $description = Tourism_Core_Tour_Schema::text( $description );
		$canonical = $stored['canonical'] ?: $this->urls[ $post->ID ];
		$index = (string) $stored['meta-robots-noindex'];
		$robots = array( 'index' => '1' === $index ? 'noindex' : ( '2' === $index ? 'index' : 'inherit' ), 'follow' => '1' === (string) $stored['meta-robots-nofollow'] ? 'nofollow' : 'follow', 'advanced' => $stored['meta-robots-adv'] );
		$images = array();
		if ( $meta ) {
			foreach ( (array) $meta->open_graph_images as $image ) {
				$images[] = array_intersect_key( $image, array_flip( array( 'url', 'width', 'height', 'alt', 'type' ) ) );
			}
		}
		$graph = $meta ? $meta->schema : array();
		$primary_terms = $this->primary_terms( $post );
		$fronts = get_option( 'tourism_migration_front_pages', array() );
		$language = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $post->ID ) : '';
		$breadcrumbs = array( array( 'name' => ! empty( $defaults['breadcrumbs-home'] ) ? $defaults['breadcrumbs-home'] : get_bloginfo( 'name' ), 'url' => $this->urls[ $fronts[ $language ] ?? 0 ] ?? $this->origin . '/' ) );
		$breadcrumb_terms = $primary_terms;
		if ( ! $breadcrumb_terms && in_array( $post->post_type, array( 'post', 'tour' ), true ) ) {
			$taxonomy = 'tour' === $post->post_type ? 'tour_category' : 'category'; $assigned = get_the_terms( $post, $taxonomy );
			if ( ! is_wp_error( $assigned ) && $assigned ) { $breadcrumb_terms[] = array( 'id' => $assigned[0]->term_id, 'taxonomy' => $taxonomy ); }
		}
		foreach ( $breadcrumb_terms as $primary ) {
			$parents = array_reverse( get_ancestors( $primary['id'], $primary['taxonomy'], 'taxonomy' ) );
			foreach ( array_merge( $parents, array( $primary['id'] ) ) as $term_id ) {
				$term = get_term( $term_id, $primary['taxonomy'] ); $url = get_term_link( $term );
				if ( ! is_wp_error( $url ) ) { $breadcrumbs[] = array( 'name' => $term->name, 'url' => $url ); }
			}
			break;
		}
		if ( $breadcrumbs[0]['url'] !== $this->urls[ $post->ID ] ) { $breadcrumbs[] = array( 'name' => $stored['bctitle'] ?: $post->post_title, 'url' => $this->urls[ $post->ID ] ); }
		$person_ids = array();
		foreach ( $graph['@graph'] ?? array() as $index => $entity ) {
			if ( in_array( 'BreadcrumbList', (array) ( $entity['@type'] ?? array() ), true ) ) {
				$items = array(); foreach ( $breadcrumbs as $position => $crumb ) { $items[] = array( '@type' => 'ListItem', 'position' => $position + 1, 'name' => $crumb['name'], 'item' => $crumb['url'] ); }
				$graph['@graph'][ $index ]['itemListElement'] = $items;
			}
			if ( isset( $entity['url'] ) && in_array( 'WebPage', (array) ( $entity['@type'] ?? array() ), true ) ) {
				$graph['@graph'][ $index ]['name'] = $title; $graph['@graph'][ $index ]['description'] = $description;
				if ( function_exists( 'pll_get_post_language' ) ) { $graph['@graph'][ $index ]['inLanguage'] = str_replace( '_', '-', pll_get_post_language( $post->ID, 'locale' ) ); }
			}
			if ( in_array( 'Person', (array) ( $entity['@type'] ?? array() ), true ) ) {
				$author = $this->author( $post ); $person_id = $author['url'] . '#person';
				if ( isset( $entity['@id'] ) ) { $person_ids[ $entity['@id'] ] = $person_id; }
				$graph['@graph'][ $index ] = array( '@type' => 'Person', '@id' => $person_id, 'name' => $author['name'], 'url' => $author['url'] );
			}
		}
		array_walk_recursive( $graph, static function ( &$value ) use ( $person_ids ) { if ( is_string( $value ) && isset( $person_ids[ $value ] ) ) { $value = $person_ids[ $value ]; } } );
		return $this->rewrite( array( 'title' => $title, 'description' => $description, 'canonical' => $canonical, 'robots' => $robots, 'source_default_noindex' => $defaults[ 'noindex-' . $post->post_type ] ?? null, 'source_yoast' => $stored, 'primary_terms' => $primary_terms, 'breadcrumbs' => $breadcrumbs, 'open_graph' => array( 'title' => $meta ? $meta->open_graph_title : $title, 'description' => $meta ? $meta->open_graph_description : $description, 'url' => $this->urls[ $post->ID ], 'images' => $images ), 'twitter' => array( 'card' => $meta ? $meta->twitter_card : 'summary_large_image', 'title' => $meta ? $meta->twitter_title : $title, 'description' => $meta ? $meta->twitter_description : $description, 'image' => $meta ? $meta->twitter_image : '' ), 'yoast_schema' => $graph ) );
	}

	private function primary_terms( $post ) {
		$map = get_option( 'tourism_headless_term_id_map', array() ); $terms = array();
		$imported = (bool) get_post_meta( $post->ID, '_tourism_import_key', true );
		$original = get_option( 'tourism_headless_original_primary_values', array() );
		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			$source = absint( get_post_meta( $post->ID, '_yoast_wpseo_primary_' . $taxonomy, true ) );
			if ( ! $source ) { continue; }
			$id = has_term( $source, $taxonomy, $post ) ? $source : ( $imported ? ( $map[ $taxonomy ][ $source ] ?? 0 ) : 0 );
			if ( ! $id || ! has_term( $id, $taxonomy, $post ) ) { continue; }
			$term = get_term( $id, $taxonomy ); $url = get_term_link( $term );
			if ( is_wp_error( $url ) ) { continue; }
			$terms[ $taxonomy ] = array( 'id' => $id, 'source_id' => $original[ $post->ID ][ $taxonomy ] ?? $source, 'taxonomy' => $taxonomy, 'slug' => $term->slug, 'name' => $term->name, 'url' => $url );
		}
		return $terms;
	}

	private function post( $post ) {
		$id = $post->ID; $translations = array();
		if ( function_exists( 'pll_get_post_translations' ) ) {
			foreach ( pll_get_post_translations( $id ) as $language => $other ) { if ( isset( $this->urls[ $other ] ) ) { $translations[ $language ] = array( 'id' => (int) $other, 'url' => $this->urls[ $other ], 'hreflang' => str_replace( '_', '-', pll_get_post_language( $other, 'locale' ) ) ); } }
		}
		$content = $this->rewrite( $post->post_content ); $data = array();
		if ( 'tour' === $post->post_type ) {
			$keys = array( '_tour_hero_image', 'tourism_intro_title', 'tourism_intro_description', 'tourism_duration', 'tourism_difficulty', 'tourism_group_size', 'tourism_max_elevation', 'tourism_price_from', 'tourism_price_before', 'tourism_price_discount_percent', 'tourism_price_valid_until', 'tourism_offer_availability', 'tourism_tour_sku', 'tourism_trip_origin', 'tourism_destinations', 'tourism_gallery_images', 'tourism_gallery_ids', 'tourism_highlights', 'tourism_itinerary_items', 'tourism_includes_new', 'tourism_excludes_new', 'tourism_what_to_expect', 'tourism_what_to_bring_items', 'tourism_bring_items', 'tourism_booking_policy', 'tourism_pricing_details', 'tourism_faqs', 'tourism_sidebar_content' );
			foreach ( $keys as $key ) { $data[ $key ] = get_post_meta( $id, $key, true ); }
			$data['currency'] = tourism_core_get_tour_currency( $id );
			$data['videos'] = Tourism_Core_Tour_Extras::videos( $id ); $data['reviews'] = array();
			foreach ( Tourism_Core_Tour_Extras::reviews( $id ) as $review ) { $data['reviews'][] = array_intersect_key( $review, array_flip( array( 'author', 'content', 'rating', 'date' ) ) ); }
			$data['rating'] = Tourism_Core_Tour_Extras::rating( $id ); $data['maps'] = Tourism_Core_Tour_Extras::maps( $id );
			$data['paypal_url'] = Tourism_Core_Payment_Providers::paypal_url( $id );
			$data['wetravel_uuid'] = get_post_meta( $id, '_wetravel_button_code', true );
		}
		$terms = array();
		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			$object = get_taxonomy( $taxonomy ); if ( ! $object->public ) { continue; }
			$found = wp_get_object_terms( $id, $taxonomy, array( 'fields' => 'ids' ) ); if ( ! is_wp_error( $found ) ) { $terms[ $taxonomy ] = $found; }
		}
		// The frontend must display these same public reviews/videos before emitting this graph.
		$visible = static function () { return true; };
		add_filter( 'tourism_core_tour_extras_visible', $visible, PHP_INT_MAX );
		try { $schema = 'tour' === $post->post_type ? Tourism_Core_Tour_Schema::build( $id ) : array(); }
		finally { remove_filter( 'tourism_core_tour_extras_visible', $visible, PHP_INT_MAX ); }
		$result = $this->rewrite( array( 'id' => $id, 'source_key' => get_post_meta( $id, '_tourism_import_key', true ), 'type' => $post->post_type, 'slug' => $post->post_name, 'path' => self::path( $this->urls[ $id ] ), 'url' => $this->urls[ $id ], 'title' => $post->post_title, 'excerpt' => $post->post_excerpt, 'published_gmt' => $post->post_date_gmt, 'modified_gmt' => $post->post_modified_gmt, 'language' => function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $id ) : '', 'locale' => function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $id, 'locale' ) : '', 'translations' => $translations, 'parent_id' => (int) $post->post_parent, 'featured_image' => self::image( get_post_thumbnail_id( $id ) ), 'content' => array( 'html' => $content, 'source_sha256' => hash( 'sha256', $post->post_content ), 'public_sha256' => hash( 'sha256', $content ), 'segments' => self::components( $content ) ), 'fields' => $data, 'terms' => $terms, 'seo' => $this->seo( $post ), 'tour_schema' => $schema ) );
		$result['backend_url'] = get_permalink( $id );
		$result['author'] = $this->author( $post );
		$result['menu_order'] = (int) $post->menu_order;
		$result['title_text'] = Tourism_Core_Tour_Schema::text( $post->post_title );
		$result['published_local'] = $post->post_date;
		$result['modified_local'] = $post->post_modified;
		$result['article_schema'] = $this->article( $post, $result );
		return $result;
	}

	/** Backend noindex can suppress Yoast Article; keep editorial facts available independently. */
	private function article( $post, $data ) {
		if ( 'post' !== $post->post_type ) { return array(); }
		$type = get_post_meta( $post->ID, '_yoast_wpseo_schema_article_type', true );
		if ( 'None' === $type ) { return array(); }
		$types = array( 'Article', 'BlogPosting', 'NewsArticle', 'Report', 'ScholarlyArticle', 'TechArticle', 'SocialMediaPosting', 'DiscussionForumPosting', 'LiveBlogPosting', 'MedicalScholarlyArticle', 'SatiricalArticle', 'AdvertiserContentArticle' );
		$article = array(
			'@context' => 'https://schema.org',
			'@type' => in_array( $type, $types, true ) ? $type : 'BlogPosting',
			'@id' => $data['url'] . '#article',
			'url' => $data['url'],
			'mainEntityOfPage' => array( '@type' => 'WebPage', '@id' => $data['url'] ),
			'headline' => Tourism_Core_Tour_Schema::text( $data['title'] ),
			'description' => $data['seo']['description'],
			'author' => array( '@type' => 'Person', '@id' => $data['author']['url'] . '#person', 'name' => $data['author']['name'], 'url' => $data['author']['url'] ),
			'inLanguage' => str_replace( '_', '-', $data['locale'] ),
		);
		foreach ( array( 'post_date_gmt' => 'datePublished', 'post_modified_gmt' => 'dateModified' ) as $field => $key ) {
			$value = $post->$field;
			if ( preg_match( '/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/D', $value ) ) { $article[ $key ] = str_replace( ' ', 'T', $value ) . '+00:00'; }
		}
		if ( ! empty( $data['featured_image']['url'] ) ) { $article['image'] = array( $data['featured_image']['url'] ); }
		return $article;
	}

	private function terms() {
		$out = array(); $seo = get_option( 'tourism_headless_taxonomy_seo', array() );
		foreach ( get_taxonomies( array( 'public' => true ) ) as $taxonomy ) {
			$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => '' ) );
			if ( is_wp_error( $terms ) ) { continue; }
			foreach ( $terms as $term ) {
				$url = get_term_link( $term ); if ( is_wp_error( $url ) ) { continue; }
				$stored = $seo[ $term->term_id ] ?? array();
				$defaults = get_option( 'tourism_headless_source_seo_defaults', array() );
				$title = ! empty( $stored['wpseo_title'] ) ? $stored['wpseo_title'] : ( $defaults[ 'title-tax-' . $taxonomy ] ?? $term->name );
				$description = ! empty( $stored['wpseo_desc'] ) ? $stored['wpseo_desc'] : ( $defaults[ 'metadesc-tax-' . $taxonomy ] ?? $term->description );
				if ( function_exists( 'wpseo_replace_vars' ) ) { $title = wpseo_replace_vars( $title, $term ); $description = wpseo_replace_vars( $description, $term ); }
				$out[] = array( 'id' => $term->term_id, 'taxonomy' => $taxonomy, 'slug' => $term->slug, 'name' => $term->name, 'description' => $term->description, 'parent_id' => $term->parent, 'count' => $term->count, 'url' => $url, 'language' => function_exists( 'pll_get_term_language' ) ? pll_get_term_language( $term->term_id ) : '', 'translations' => function_exists( 'pll_get_term_translations' ) ? pll_get_term_translations( $term->term_id ) : array(), 'seo' => array( 'title' => $title, 'description' => $description, 'canonical' => ! empty( $stored['wpseo_canonical'] ) ? $stored['wpseo_canonical'] : $url, 'source_yoast' => $stored, 'source_default_noindex' => $defaults[ 'noindex-tax-' . $taxonomy ] ?? null ) );
			}
		}
		return $this->rewrite( $out );
	}

	private function menus() {
		$out = array();
		foreach ( wp_get_nav_menus() as $menu ) {
			$items = array();
			foreach ( wp_get_nav_menu_items( $menu->term_id ) ?: array() as $item ) {
				$items[] = array( 'id' => $item->ID, 'parent_id' => (int) $item->menu_item_parent, 'order' => $item->menu_order, 'title' => $item->title, 'url' => $item->url, 'target' => $item->target, 'rel' => $item->xfn, 'classes' => $item->classes, 'object' => $item->object, 'object_id' => (int) $item->object_id );
			}
			$out[] = array( 'id' => $menu->term_id, 'name' => $menu->name, 'slug' => $menu->slug, 'language' => function_exists( 'pll_get_term_language' ) ? pll_get_term_language( $menu->term_id ) : '', 'items' => $items );
		}
		return $this->rewrite( $out );
	}

	private function media() {
		$out = array();
		$ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => array( 'image', 'video', 'application/pdf' ), 'numberposts' => -1, 'fields' => 'ids', 'lang' => '', 'suppress_filters' => true ) );
		foreach ( $ids as $id ) {
			$post = get_post( $id ); if ( $post->post_password ) { continue; }
			$url = wp_get_attachment_url( $id ); $metadata = wp_get_attachment_metadata( $id ); $sizes = array();
			foreach ( (array) ( $metadata['sizes'] ?? array() ) as $name => $size ) {
				$sizes[ $name ] = array( 'url' => trailingslashit( dirname( $url ) ) . $size['file'], 'width' => $size['width'], 'height' => $size['height'], 'mime_type' => $size['mime-type'] ?? '' );
			}
			$out[] = array( 'id' => $id, 'source_url' => $url, 'url' => $this->rewrite( $url ), 'path' => self::path( $url ), 'mime_type' => get_post_mime_type( $id ), 'title' => get_the_title( $id ), 'alt' => get_post_meta( $id, '_wp_attachment_image_alt', true ), 'caption' => $post->post_excerpt, 'description' => $post->post_content, 'width' => $metadata['width'] ?? null, 'height' => $metadata['height'] ?? null, 'sizes' => $this->rewrite( $sizes ) );
		}
		return $out;
	}

	/** Preserve legacy assets, adding newly uploaded files without a manual migration step. */
	private function assets( $media ) {
		$assets = get_option( 'tourism_headless_public_asset_manifest', array() );
		$known = array_fill_keys( array_column( $assets, 'path' ), true );
		$uploads = wp_get_upload_dir(); $prefix = rtrim( self::path( $uploads['baseurl'] ), '/' ) . '/';
		$root = realpath( $uploads['basedir'] );
		if ( ! $root ) { throw new RuntimeException( 'No existe el directorio de medios públicos.' ); }
		foreach ( $media as $item ) {
			$urls = array( $item['url'] );
			foreach ( $item['sizes'] as $size ) { $urls[] = $size['url']; }
			foreach ( $urls as $url ) {
				$path = rawurldecode( self::path( $url ) );
				if ( isset( $known[ $path ] ) ) { continue; }
				if ( ! str_starts_with( $path, $prefix ) ) { continue; }
				$file = realpath( $root . '/' . substr( $path, strlen( $prefix ) ) );
				$base = wp_normalize_path( $root ) . '/'; $resolved = $file ? wp_normalize_path( $file ) : '';
				if ( '\\' === DIRECTORY_SEPARATOR ) { $base = strtolower( $base ); $resolved = strtolower( $resolved ); }
				if ( ! $file || ! is_file( $file ) || ! str_starts_with( $resolved, $base ) ) { throw new RuntimeException( 'Falta un archivo público referenciado: ' . $path ); }
				$assets[] = array( 'path' => $path, 'bytes' => filesize( $file ), 'sha256' => hash_file( 'sha256', $file ) );
				$known[ $path ] = true;
			}
		}
		return $assets;
	}

	/** Routes are build requirements, not a claim that Astro already renders them. */
	private function inventory( $posts, $terms ) {
		usort( $posts, static function ( $a, $b ) { return strcmp( $b['published_gmt'], $a['published_gmt'] ) ?: $b['id'] <=> $a['id']; } );
		$routes = array();
		foreach ( $posts as $post ) { $routes[ $post['path'] ] = array( 'path' => $post['path'], 'kind' => $post['type'], 'id' => $post['id'], 'language' => $post['language'] ); }
		$per_page = max( 1, (int) get_option( 'tourism_headless_source_posts_per_page', get_option( 'posts_per_page', 10 ) ) );
		foreach ( $terms as $term ) {
			$ids = array();
			$term_ids = array( $term['id'] );
			if ( is_taxonomy_hierarchical( $term['taxonomy'] ) ) { $children = get_term_children( $term['id'], $term['taxonomy'] ); if ( ! is_wp_error( $children ) ) { $term_ids = array_merge( $term_ids, array_map( 'intval', $children ) ); } }
			foreach ( $posts as $post ) { if ( array_intersect( $term_ids, $post['terms'][ $term['taxonomy'] ] ?? array() ) ) { $ids[] = $post['id']; } }
			$path = self::path( $term['url'] ); $pages = max( 1, (int) ceil( count( $ids ) / $per_page ) );
			for ( $page = 1; $page <= $pages; $page++ ) {
				$route = $path . ( $page > 1 ? 'page/' . $page . '/' : '' );
				if ( ! isset( $routes[ $route ] ) ) { $routes[ $route ] = array( 'path' => $route, 'kind' => 'taxonomy', 'taxonomy' => $term['taxonomy'], 'term_id' => $term['id'], 'post_ids' => $ids, 'page' => $page, 'page_size' => $per_page, 'language' => $term['language'], 'empty' => ! $ids ); }
			}
		}
		$blog_id = (int) get_option( 'tourism_migration_blog_page' );
		$blog_path = isset( $this->urls[ $blog_id ] ) ? self::path( $this->urls[ $blog_id ] ) : '/blog/';
		$ids = array();
		foreach ( $posts as $post ) { if ( 'post' === $post['type'] ) { $ids[] = $post['id']; } }
		for ( $page = 2; $page <= ceil( count( $ids ) / 21 ); $page++ ) { $path = $blog_path . 'page/' . $page . '/'; $routes[ $path ] = array( 'path' => $path, 'kind' => 'blog_pagination', 'post_ids' => $ids, 'page' => $page, 'page_size' => 21, 'query_alias' => $blog_path . '?blog_page=' . $page ); }
		$routes['/feed/'] = array( 'path' => '/feed/', 'kind' => 'rss', 'post_ids' => $ids );
		$routes['/robots.txt'] = array( 'path' => '/robots.txt', 'kind' => 'robots' );
		$routes['/sitemap_index.xml'] = array( 'path' => '/sitemap_index.xml', 'kind' => 'sitemap_index' );
		$sitemap_groups = array_unique( array_merge( array_column( $posts, 'type' ), array_column( $terms, 'taxonomy' ), array( 'author' ) ) );
		foreach ( $sitemap_groups as $group ) { $path = '/' . $group . '-sitemap.xml'; $routes[ $path ] = array( 'path' => $path, 'kind' => 'sitemap', 'group' => $group ); }
		$archives = array();
		foreach ( $posts as $post ) {
			if ( 'post' !== $post['type'] ) { continue; }
			$date = str_replace( '-', '/', substr( $post['published_local'], 0, 10 ) );
			$paths = array( self::path( $post['author']['url'] ), '/' . substr( $date, 0, 4 ) . '/', '/' . substr( $date, 0, 7 ) . '/', '/' . $date . '/' );
			foreach ( $paths as $path ) { $archives[ $path ][] = $post['id']; }
		}
		foreach ( $archives as $path => $ids ) {
			for ( $page = 1; $page <= ceil( count( $ids ) / $per_page ); $page++ ) {
				$route = $path . ( $page > 1 ? 'page/' . $page . '/' : '' );
				if ( ! isset( $routes[ $route ] ) ) { $routes[ $route ] = array( 'path' => $route, 'kind' => str_starts_with( $path, '/author/' ) ? 'author_archive' : 'date_archive', 'post_ids' => $ids, 'page' => $page, 'page_size' => $per_page, 'source_robots_policy' => 'site.source_seo_defaults' ); }
			}
		}
		return array_values( $routes );
	}

	public function snapshot( $origin ) {
		$this->prepare( $origin ); $posts = array();
		foreach ( $this->posts as $post ) { $posts[] = $this->post( $post ); }
		$redirects = array();
		foreach ( $this->paths as $from => $to ) { if ( $from !== $to ) { $redirects[ $from ] = array( 'from' => $from, 'to' => $to, 'status' => 301, 'reason' => 'content_alias' ); } }
		foreach ( get_option( 'tourism_legacy_redirect_rules', array() ) as $from => $rule ) {
			$to = $this->rewrite( $rule['url'] );
			if ( wp_parse_url( $to, PHP_URL_HOST ) !== wp_parse_url( $this->origin, PHP_URL_HOST ) ) { continue; }
			$to = self::path( $to ) . ( wp_parse_url( $to, PHP_URL_QUERY ) ? '?' . wp_parse_url( $to, PHP_URL_QUERY ) : '' );
			if ( $from !== $to && ! in_array( $from, array_map( array( __CLASS__, 'path' ), $this->urls ), true ) ) { $redirects[ $from ] = array( 'from' => $from, 'to' => $to, 'status' => (int) $rule['code'], 'reason' => 'legacy_rule' ); }
		}
		$agency = array_intersect_key( tourism_core_get_agency(), array_flip( array( 'company_name', 'commercial_name', 'name', 'phone', 'whatsapp', 'email', 'address', 'google_maps', 'facebook', 'instagram', 'tripadvisor', 'linkedin', 'hours', 'hours_schedule' ) ) );
		$fronts = get_option( 'tourism_migration_front_pages', array() );
		$terms = $this->terms();
		foreach ( $terms as $term ) {
			$current = get_term_link( $term['id'], $term['taxonomy'] ); if ( is_wp_error( $current ) ) { continue; }
			$from = self::path( $current ); $to = self::path( $term['url'] );
			if ( $from !== $to ) { $redirects[ $from ] = array( 'from' => $from, 'to' => $to, 'status' => 301, 'reason' => 'taxonomy_alias' ); }
		}
		$routes = array_values( array_filter( $this->inventory( $posts, $terms ), static function ( $route ) use ( $redirects ) { return ! isset( $redirects[ $route['path'] ] ); } ) );
		$payments = Tourism_Core_Payment_Providers::settings();
		$media = $this->media();
		return array( 'contract_version' => 1,
			'plugin_version' => TOURISM_CORE_VERSION,
			'generated_gmt' => gmdate( 'c' ),
			'frontend_origin' => $this->origin,
			'backend_origin' => home_url(),
			'source_environment' => wp_get_environment_type(),
			'source_noindex' => '0' === (string) get_option( 'blog_public' ),
			'production_indexing' => 'not_authorized_by_this_export',
			'site' => array(
				'name' => get_bloginfo( 'name' ),
				'description' => get_bloginfo( 'description' ),
				'agency' => $this->rewrite( $agency ),
				'logo' => $this->rewrite( self::image( get_theme_mod( 'custom_logo' ) ) ),
				'front_pages' => $fronts,
				'blog_page_id' => (int) get_option( 'tourism_migration_blog_page' ),
				'menu_locations' => get_theme_mod( 'nav_menu_locations', array() ),
				'languages' => function_exists( 'pll_languages_list' ) ? pll_languages_list( array( 'fields' => 'slug' ) ) : array(),
				'default_language' => function_exists( 'pll_default_language' ) ? pll_default_language() : '',
				'source_seo_defaults' => get_option( 'tourism_headless_source_seo_defaults', array() ),
				'public_integrations' => get_option( 'tourism_headless_public_integrations', array() ),
				'external_reviews' => Tourism_Core_Headless_Reviews::export(),
				'payments' => array( 'paypal_me_url' => $payments['paypal_me_url'], 'wetravel_uid' => $payments['wetravel_uid'], 'wetravel_configured' => '' !== $payments['wetravel_uid'] ),
				'contact' => array( 'endpoint' => rest_url( 'tourism/v1/headless/contact' ), 'method' => 'POST', 'submission' => 'authenticated_runtime_proxy', 'capability' => 'tourism_submit_contact', 'fingerprint' => 'server_hmac_sha256_hex', 'private_storage' => true ),
			),
			'posts' => $posts,
			'terms' => $terms,
			'menus' => $this->menus(),
			'media' => $media,
			'assets' => $this->assets( $media ),
			'legacy_media_ids' => get_option( 'tourism_headless_legacy_media_ids', array() ),
			'redirects' => array_values( $redirects ),
			'route_aliases' => $this->paths,
			'legacy_post_ids' => $this->source_urls,
			'backend_post_ids' => $this->urls,
			'query_routes' => array( array( 'parameter' => 's', 'kind' => 'search', 'index' => false ), array( 'parameter' => 'blog_page', 'kind' => 'blog_pagination', 'page_size' => 21 ), array( 'parameters' => array( 'p', 'page_id' ), 'kind' => 'legacy_post_ids' ), array( 'parameter' => 'attachment_id', 'kind' => 'legacy_media_ids' ) ),
			'routes' => $routes,
			'route_policy' => get_option( 'tourism_headless_route_policy', array() ) );
	}
}
