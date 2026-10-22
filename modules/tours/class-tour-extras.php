<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Public videos and verified, tour-specific reviews. Never accepts customer contact data. */
class Tourism_Core_Tour_Extras {
    public function register( Tourism_Core_Loader $loader ) {
        $loader->add_action( 'init', $this, 'register_meta' );
        $loader->add_action( 'add_meta_boxes_tour', $this, 'metabox' );
        $loader->add_action( 'save_post_tour', $this, 'save', 30, 2 );
        $loader->add_filter( 'tourism_core_tour_schema_graph', $this, 'schema', 10, 2 );
    }

    public static function youtube( $url ) {
        if ( ! is_string( $url ) ) { return ''; }
        $parts = wp_parse_url( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        if ( ! is_array( $parts ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) { return ''; }
        $host = strtolower( $parts['host'] ?? '' ); $path = $parts['path'] ?? ''; $id = '';
        if ( in_array( $host, array( 'youtu.be', 'www.youtu.be' ), true ) ) { $id = ltrim( $path, '/' ); }
        elseif ( in_array( $host, array( 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com' ), true ) ) {
            if ( preg_match( '~^/(?:embed|shorts)/([^/]+)$~', $path, $m ) ) { $id = $m[1]; }
            elseif ( '/watch' === $path ) { parse_str( $parts['query'] ?? '', $query ); $id = $query['v'] ?? ''; }
        }
        return is_string( $id ) && preg_match( '/^[A-Za-z0-9_-]{11}$/D', $id ) ? $id : '';
    }

    public static function valid_date( $date ) {
        return is_string( $date ) && preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
    }

    public static function embed_url( $url ) {
        $id = self::youtube( $url ); if ( ! $id ) { return ''; }
        $parts = wp_parse_url( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ); parse_str( $parts['query'] ?? '', $query );
        $start = $query['start'] ?? $query['t'] ?? ''; $seconds = 0;
        if ( is_string( $start ) && ctype_digit( $start ) ) { $seconds = (int) $start; }
        elseif ( is_string( $start ) && preg_match( '/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/D', $start, $m ) ) { $seconds = (int) ( $m[1] ?? 0 ) * 3600 + (int) ( $m[2] ?? 0 ) * 60 + (int) ( $m[3] ?? 0 ); }
        return 'https://www.youtube-nocookie.com/embed/' . $id . ( $seconds > 0 && $seconds <= 86400 ? '?start=' . $seconds : '' );
    }

    public static function videos( $post_id, $location = null ) {
        $raw = get_post_meta( $post_id, 'tourism_videos', true ); $out = array();
        foreach ( is_array( $raw ) ? $raw : array() as $row ) {
            if ( ! is_array( $row ) || ! self::youtube( $row['url'] ?? '' ) ) { continue; }
            $row['location'] = ( $row['location'] ?? 'sidebar' ) === 'main' ? 'main' : 'sidebar';
            if ( null === $location || $row['location'] === $location ) { $out[] = $row; }
        }
        return $out;
    }

    public static function reviews( $post_id ) {
        $raw = get_post_meta( $post_id, 'tourism_review_items', true ); $out = array(); $seen = array();
        foreach ( is_array( $raw ) ? $raw : array() as $row ) {
            if ( ! is_array( $row ) || empty( $row['approved'] ) || ! is_string( $row['author'] ?? null ) || ! is_string( $row['content'] ?? null ) || ! is_numeric( $row['rating'] ?? null ) || ! is_finite( (float) $row['rating'] ) || (float) $row['rating'] < 1 || (float) $row['rating'] > 5 || '' === trim( wp_strip_all_tags( $row['content'] ) ) || '' === trim( $row['author'] ) ) { continue; }
            $key = ! empty( $row['source_id'] ) ? 'id:' . $row['source_id'] : hash( 'sha256', $row['author'] . $row['content'] . ( $row['date'] ?? '' ) );
            if ( isset( $seen[ $key ] ) ) { continue; }
            $seen[ $key ] = true; $out[] = $row;
        }
        return $out;
    }

    public static function rating( $post_id ) {
        $rows = self::reviews( $post_id );
        return $rows ? array( 'value' => round( array_sum( array_column( $rows, 'rating' ) ) / count( $rows ), 2 ), 'count' => count( $rows ) ) : null;
    }

    public function register_meta() {
        foreach ( array( 'tourism_videos', 'tourism_review_items', 'tourism_sidebar_content' ) as $key ) {
            register_post_meta( 'tour', $key, array( 'type' => 'array', 'single' => true, 'show_in_rest' => false, 'auth_callback' => static function ( $allowed, $meta_key, $post_id ) { return current_user_can( 'edit_post', $post_id ); } ) );
        }
    }

    public static function render_videos( $post_id, $location = 'sidebar', $show_heading = true ) {
        $rows = self::videos( $post_id, $location ); if ( ! $rows ) { return ''; }
        $html = '<section class="tourism-tour-videos">';
        if ( $show_heading ) { $html .= '<h3>' . esc_html__( 'Videos del tour', TOURISM_CORE_TD ) . '</h3>'; }
        foreach ( $rows as $row ) {
            $id = self::youtube( $row['url'] ); $title = sanitize_text_field( $row['title'] ?? '' );
            if ( '' === $title ) { $title = get_the_title( $post_id ); }
            $html .= '<figure><iframe width="560" height="315" loading="lazy" src="' . esc_url( self::embed_url( $row['url'] ) ) . '" title="' . esc_attr( $title ) . '" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe><figcaption>' . esc_html( $title ) . ' · <a href="' . esc_url( $row['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Watch on YouTube', TOURISM_CORE_TD ) . '</a></figcaption></figure>';
        }
        return $html . '</section>';
    }

    public static function render_reviews( $post_id ) {
        $rows = self::reviews( $post_id ); $rating = self::rating( $post_id ); if ( ! $rating ) { return ''; }
        $html = '<section class="tourism-tour-reviews" id="tour-reviews"><h2>' . esc_html__( 'Reseñas del tour', TOURISM_CORE_TD ) . '</h2><p><span aria-hidden="true">' . esc_html( str_repeat( '★', (int) round( $rating['value'] ) ) ) . '</span> ' . esc_html( $rating['value'] . '/5 · ' . $rating['count'] . ' ' . __( 'reseñas', TOURISM_CORE_TD ) ) . '</p>';
        foreach ( $rows as $row ) {
            $html .= '<article><h3>' . esc_html( $row['author'] ) . '</h3><p>' . esc_html( $row['rating'] . '/5' ) . '</p>';
            if ( self::valid_date( $row['date'] ?? '' ) ) { $html .= '<time datetime="' . esc_attr( $row['date'] ) . '">' . esc_html( $row['date'] ) . '</time>'; }
            $html .= wpautop( wp_kses_post( $row['content'] ) ) . '</article>';
        }
        return $html . '</section>';
    }

    public static function render_sidebar_content( $post_id ) {
        $rows = get_post_meta( $post_id, 'tourism_sidebar_content', true ); $html = '';
        foreach ( is_array( $rows ) ? $rows : array() as $row ) {
            if ( ! is_array( $row ) ) { continue; }
            if ( apply_filters( 'tourism_core_tour_maps_visible', false, $post_id ) && self::map_images( $row ) && '' === trim( wp_strip_all_tags( $row['content'] ?? '' ) ) ) { continue; }
            $html .= '<section class="tourism-sidebar-content">';
            if ( ! empty( $row['title'] ) ) { $html .= '<h3>' . esc_html( $row['title'] ) . '</h3>'; }
            $html .= wp_kses_post( $row['content'] ?? '' ) . '</section>';
        }
        return $html;
    }

    /** Original map images only; never assign a map to another tour or language. */
    private static function map_images( $row ) {
        $out = array();
        preg_match_all( '~<img\b[^>]*\bsrc=["\x27]([^"\x27]+)["\x27][^>]*>~i', $row['content'] ?? '', $matches, PREG_SET_ORDER );
        foreach ( $matches as $match ) {
            $url = html_entity_decode( $match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $path = wp_parse_url( $url, PHP_URL_PATH );
            if ( ! is_string( $path ) || ! preg_match( '/(?:^|[^a-z])(?:map|mapa|carte)(?:[^a-z]|$)/i', basename( $path ) ) ) { continue; }
            $attachment_id = attachment_url_to_postid( $url );
            if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) { continue; }
            $out[] = array( 'id' => $attachment_id, 'url' => wp_get_attachment_url( $attachment_id ) );
        }
        return $out;
    }

    public static function maps( $post_id ) {
        $rows = get_post_meta( $post_id, 'tourism_sidebar_content', true ); $out = array();
        foreach ( is_array( $rows ) ? $rows : array() as $row ) {
            if ( ! is_array( $row ) ) { continue; }
            foreach ( self::map_images( $row ) as $image ) { $out[ $image['id'] ] = $image; }
        }
        return array_values( $out );
    }

    public static function render_maps( $post_id ) {
        $html = '';
        foreach ( self::maps( $post_id ) as $image ) {
            $label = sprintf( __( 'Tour map: %s', TOURISM_CORE_TD ), get_the_title( $post_id ) );
            $html .= '<figure class="tourism-tour-map"><a href="' . esc_url( $image['url'] ) . '" target="_blank" rel="noopener noreferrer">' . wp_get_attachment_image( $image['id'], 'large', false, array( 'alt' => $label, 'loading' => 'lazy' ) ) . '</a><figcaption><a href="' . esc_url( $image['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open full-size map', TOURISM_CORE_TD ) . '</a></figcaption></figure>';
        }
        return $html;
    }

    public function schema( $graph, $post_id ) {
        if ( ! is_array( $graph ) || empty( $graph['@graph'] ) ) { return $graph; }
        // Enabled only when the theme renders these exact public data through our helpers.
        if ( ! apply_filters( 'tourism_core_tour_extras_visible', false, $post_id ) ) { return $graph; }
        $rating = self::rating( $post_id ); $reviews = self::reviews( $post_id );
        foreach ( $graph['@graph'] as &$entity ) {
            if ( ( $entity['@id'] ?? '' ) !== get_permalink( $post_id ) . '#tour' ) { continue; }
            if ( $rating ) {
                $entity['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => $rating['value'], 'reviewCount' => $rating['count'], 'bestRating' => 5, 'worstRating' => 1 );
                $entity['review'] = array();
                foreach ( $reviews as $row ) {
                    $review = array( '@type' => 'Review', 'author' => array( '@type' => 'Person', 'name' => sanitize_text_field( $row['author'] ) ), 'reviewBody' => wp_strip_all_tags( $row['content'] ), 'reviewRating' => array( '@type' => 'Rating', 'ratingValue' => (float) $row['rating'], 'bestRating' => 5, 'worstRating' => 1 ) );
                    if ( self::valid_date( $row['date'] ?? '' ) ) { $review['datePublished'] = $row['date']; }
                    $entity['review'][] = $review;
                }
            }
            foreach ( self::videos( $post_id ) as $row ) {
                $thumb = esc_url_raw( $row['thumbnail'] ?? '', array( 'https', 'http' ) );
                if ( ! is_string( $row['title'] ?? null ) || '' === trim( $row['title'] ) || ! $thumb || ! self::valid_date( $row['upload_date'] ?? '' ) ) { continue; }
                $video = array( '@type' => 'VideoObject', 'name' => sanitize_text_field( $row['title'] ), 'thumbnailUrl' => $thumb, 'uploadDate' => $row['upload_date'], 'embedUrl' => self::embed_url( $row['url'] ) );
                if ( ! empty( $row['description'] ) ) { $video['description'] = wp_strip_all_tags( $row['description'] ); }
                $entity['subjectOf'][] = $video;
            }
        }
        unset( $entity ); return $graph;
    }

    public function metabox() { add_meta_box( 'tourism-tour-extras', __( 'Videos y reseñas', TOURISM_CORE_TD ), array( $this, 'editor' ), 'tour', 'normal' ); }

    public function editor( $post ) {
        wp_nonce_field( 'tourism_extras_save', 'tourism_extras_nonce' );
        echo '<p>' . esc_html__( 'Completa la fila vacía y guarda para añadir otro elemento. Las reseñas solo se publican al aprobarlas.', TOURISM_CORE_TD ) . '</p>';
        $groups = array( 'tourism_videos' => array( 'title' => 'Título', 'url' => 'URL de YouTube', 'location' => 'Ubicación: main o sidebar', 'thumbnail' => 'URL de miniatura', 'upload_date' => 'Fecha original del video (AAAA-MM-DD)', 'description' => 'Descripción' ), 'tourism_review_items' => array( 'author' => 'Nombre público del autor', 'rating' => 'Valoración de 1 a 5', 'date' => 'Fecha (AAAA-MM-DD)', 'content' => 'Reseña' ) );
        foreach ( $groups as $key => $fields ) {
            $rows = get_post_meta( $post->ID, $key, true ); $rows = is_array( $rows ) ? array_values( $rows ) : array(); $rows[] = array();
            echo '<h3>' . esc_html( 'tourism_videos' === $key ? __( 'Videos', TOURISM_CORE_TD ) : __( 'Reseñas', TOURISM_CORE_TD ) ) . '</h3>';
            foreach ( $rows as $i => $row ) {
                echo '<fieldset style="border:1px solid #ccc;padding:12px;margin-bottom:12px">';
                foreach ( $fields as $field => $label ) {
                    $name = $key . '[' . $i . '][' . $field . ']'; $value = $row[ $field ] ?? '';
                    echo '<p><label>' . esc_html( $label ) . '<textarea class="widefat" rows="' . ( in_array( $field, array( 'content', 'description' ), true ) ? '4' : '1' ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea></label></p>';
                }
                if ( 'tourism_review_items' === $key ) { echo '<p><label><input type="checkbox" name="' . esc_attr( $key . '[' . $i . '][approved]' ) . '" value="1" ' . checked( ! empty( $row['approved'] ), true, false ) . '> ' . esc_html__( 'Reseña verificada y aprobada para publicar', TOURISM_CORE_TD ) . '</label></p>'; }
                echo '<p><label><input type="checkbox" name="' . esc_attr( $key . '[' . $i . '][remove]' ) . '" value="1"> ' . esc_html__( 'Eliminar este elemento', TOURISM_CORE_TD ) . '</label></p></fieldset>';
            }
        }
    }

    public function save( $post_id, $post ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['tourism_extras_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tourism_extras_nonce'] ) ), 'tourism_extras_save' ) ) { return; }
        foreach ( array( 'tourism_videos', 'tourism_review_items' ) as $key ) {
            if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) { continue; }
            $old = get_post_meta( $post_id, $key, true ); $old = is_array( $old ) ? array_values( $old ) : array(); $rows = array();
            foreach ( wp_unslash( $_POST[ $key ] ) as $i => $row ) {
                if ( ! is_array( $row ) || ! empty( $row['remove'] ) ) { continue; }
                $clean = array();
                foreach ( array( 'title', 'author', 'date', 'upload_date', 'description' ) as $field ) { if ( isset( $row[ $field ] ) && is_scalar( $row[ $field ] ) ) { $clean[ $field ] = sanitize_text_field( $row[ $field ] ); } }
                if ( 'tourism_videos' === $key ) {
                    if ( ! self::youtube( $row['url'] ?? '' ) ) { if ( isset( $old[ $i ] ) ) { $rows[] = $old[ $i ]; } continue; }
                    $clean['url'] = esc_url_raw( $row['url'] ); $clean['thumbnail'] = esc_url_raw( $row['thumbnail'] ?? '', array( 'http', 'https' ) ); $clean['location'] = ( $row['location'] ?? '' ) === 'main' ? 'main' : 'sidebar';
                } else {
                    if ( ! is_numeric( $row['rating'] ?? null ) || (float) $row['rating'] < 1 || (float) $row['rating'] > 5 || ! is_string( $row['content'] ?? null ) || '' === trim( $row['content'] ) || '' === trim( $clean['author'] ?? '' ) ) { if ( isset( $old[ $i ] ) ) { $rows[] = $old[ $i ]; } continue; }
                    $clean['rating'] = (float) $row['rating']; $clean['content'] = wp_kses_post( $row['content'] ); $clean['approved'] = ! empty( $row['approved'] );
                    if ( isset( $old[ $i ]['source_id'] ) ) { $clean['source_id'] = $old[ $i ]['source_id']; }
                }
                $rows[] = $clean;
            }
            update_post_meta( $post_id, $key, $rows );
        }
    }
}
