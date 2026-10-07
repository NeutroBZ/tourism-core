<?php

if (! defined('ABSPATH')) {
	exit;
}

require_once TOURISM_CORE_PATH . 'includes/class-metabox-helper.php';
require_once TOURISM_CORE_PATH . 'includes/class-metabox-base.php';

use Tourism_Core_Metabox_Helper as Metabox_Helper;

class Tourism_Core_Destination_Metaboxes extends Tourism_Core_Metabox_Base
{
	use Tourism_Core_Order_Column;

	protected function order_column_config()
	{
		return array(
			'post_type'         => 'destination',
			'meta_key'          => 'tourism_destination_order',
			'quickedit_field'   => 'tourism_destination_order',
			'quickedit_nonce'   => 'tourism_core_destination_quickedit_nonce',
			'quickedit_action'  => 'tourism_core_quickedit_destination',
		);
	}

	public function register(Tourism_Core_Loader $loader)
	{
		$loader->add_action('add_meta_boxes', $this, 'add_meta_boxes');
		$loader->add_action('save_post_destination', $this, 'save', 10, 2);
		$loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
		$this->register_order_column($loader);
	}

	public function add_meta_boxes()
	{
		// Intro
		add_meta_box(
			'tourism-core-destination-intro',
			__('Destination Introduction', TOURISM_CORE_TD),
			array($this, 'render_intro'),
			'destination',
			'normal',
			'default'
		);

		// Hero image
		add_meta_box(
			'tourism-core-destination-hero',
			__('Hero Image', TOURISM_CORE_TD),
			array($this, 'render_hero'),
			'destination',
			'side',
			'high'
		);

		// Gallery
		add_meta_box(
			'tourism-core-destination-gallery',
			__('Photo Gallery', TOURISM_CORE_TD),
			array($this, 'render_gallery'),
			'destination',
			'normal',
			'default'
		);

		// Highlights (title + description)
		add_meta_box(
			'tourism-core-destination-highlights',
			__('Destination Highlights', TOURISM_CORE_TD),
			array($this, 'render_highlights'),
			'destination',
			'normal',
			'default'
		);

		// Season (best months + travel tip)
		add_meta_box(
			'tourism-core-destination-season',
			__('Season & tips', TOURISM_CORE_TD),
			array($this, 'render_season'),
			'destination',
			'normal',
			'default'
		);

		// Ubicación (Departamento/Provincia/Distrito + coordenadas + altitud)
		add_meta_box(
			'tourism-core-destination-ubication',
			__('Ubicación', TOURISM_CORE_TD),
			array($this, 'render_ubication'),
			'destination',
			'normal',
			'default'
		);
	}

	/**
	 * @param int $post_id
	 * @return array{title: string, description: string}
	 */
	private function get_destination_intro_for_edit($post_id)
	{
		return array(
			'title'       => (string) get_post_meta($post_id, 'tourism_destination_intro_title', true),
			'description' => (string) get_post_meta($post_id, 'tourism_destination_intro_description', true),
		);
	}

	public function render_intro($post)
	{
		wp_nonce_field('tourism_core_save_destination', 'tourism_core_destination_nonce');

		$tourism_core_destination_intro = $this->get_destination_intro_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/destinations/templates/introduccion-metabox.php', array(
			'tourism_core_destination_intro' => $tourism_core_destination_intro,
		));
	}

	/**
	 * Hero: imagen + título corto opcional sobre el h1 del hero y línea breve opcional.
	 *
	 * @param int $post_id Post ID.
	 * @return array{hero_id: int, title: string, short_description: string}
	 */
	private function get_destination_hero_for_edit($post_id)
	{
		return array(
			'hero_id'           => (int) get_post_meta($post_id, '_destination_hero_image', true),
			'title'             => (string) get_post_meta($post_id, 'tourism_destination_hero_title', true),
			'short_description' => (string) get_post_meta($post_id, 'tourism_destination_hero_short_description', true),
		);
	}

	public function render_hero($post)
	{
		wp_nonce_field('tourism_core_save_destination', 'tourism_core_destination_nonce');

		$tourism_core_destination_hero = $this->get_destination_hero_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/destinations/templates/hero-metabox.php', array(
			'tourism_core_destination_hero' => $tourism_core_destination_hero,
		));
	}

	/**
	 * @param int $post_id
	 * @return array<int, array{id: int, url: string, title: string, caption: string}>
	 */
	private function get_destination_gallery_images_for_edit($post_id)
	{
		$stored = get_post_meta($post_id, 'tourism_destination_gallery_images', true);
		if (is_array($stored)) {
			$out = array();
			foreach ($stored as $row) {
				if (! is_array($row)) {
					continue;
				}
				$id = isset($row['id']) ? (int) $row['id'] : 0;
				$url = isset($row['url']) ? (string) $row['url'] : '';
				$title = isset($row['title']) ? (string) $row['title'] : '';
				$caption = isset($row['caption']) ? (string) $row['caption'] : '';
				if ($id > 0 && $url === '') {
					$url = (string) wp_get_attachment_url($id);
				}
				if ($id <= 0 && $url === '') {
					continue;
				}
				if ($id > 0 && $title === '') {
					$title = (string) get_the_title($id);
				}
				$out[] = array(
					'id'      => $id,
					'url'     => $url,
					'title'   => $title,
					'caption' => $caption,
				);
			}
			return $out;
		}

		return array();
	}

	public function render_gallery($post)
	{
		wp_nonce_field('tourism_core_save_destination', 'tourism_core_destination_nonce');

		$tourism_core_destination_gallery_images = $this->get_destination_gallery_images_for_edit((int) $post->ID);

		Metabox_Helper::include_template('includes/templates/gallery-metabox.php', array(
			'gc_images' => $tourism_core_destination_gallery_images,
			'gc_config' => array(
				'present_key'     => 'tourism_destination_gallery_metabox_present',
				'post_key'        => 'tourism_destination_gallery_images',
				'wrapper_class'   => 'tourism-core-destination-gallery-metabox-container',
				'container_class' => 'tourism-core-destination-gallery-images-container',
				'item_class'      => 'tourism-core-destination-gallery-image-item',
				'field_id_class'  => 'tourism-core-destination-gallery-field-id',
				'field_url_class' => 'tourism-core-destination-gallery-field-url',
				'preview_class'   => 'tourism-core-destination-image-preview',
				'no_image_class'  => 'tourism-core-destination-no-image',
				'actions_class'   => 'tourism-core-destination-image-actions',
				'details_class'   => 'tourism-core-destination-image-details',
				'replace_class'   => 'tourism-core-replace-destination-image',
				'remove_class'    => 'tourism-core-remove-destination-gallery-image',
				'add_id'          => 'tourism-core-add-destination-gallery-image',
				'template_id'     => 'tourism-core-destination-gallery-image-template',
			),
		));
	}

	/**
	 * Highlights filas para el editor.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{title: string, description: string}>
	 */
	private function get_destination_highlights_for_edit($post_id)
	{
		$raw = get_post_meta($post_id, 'tourism_destination_highlights', true);

		if (! is_array($raw)) {
			return array();
		}

		$out = array();
		foreach ($raw as $row) {
			if (! is_array($row)) {
				continue;
			}
			$out[] = array(
				'title'       => isset($row['title']) ? (string) $row['title'] : '',
				'description' => isset($row['description']) ? (string) $row['description'] : '',
			);
		}

		return $out;
	}

	public function render_highlights($post)
	{
		wp_nonce_field('tourism_core_save_destination', 'tourism_core_destination_nonce');

		$tourism_core_destination_highlights = $this->get_destination_highlights_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/destinations/templates/highlights-metabox.php', array(
			'tourism_core_destination_highlights' => $tourism_core_destination_highlights,
		));
	}

	/**
	 * Etiquetas de mes localizadas (1 = enero … 12 = diciembre según configuración).
	 *
	 * @return array<int, string>
	 */
	private function get_destination_month_labels()
	{
		$labels = array();
		for ($m = 1; $m <= 12; $m++) {
			// Fecha estable para evitar días inválidos al variar meses.
			$ts = strtotime(sprintf('2000-%02d-15 12:00:00', $m));
			$labels[$m] = wp_date('F', $ts);
		}
		return $labels;
	}

	/**
	 * Temporada / mejor época para el editor.
	 *
	 * @param int $post_id Post ID.
	 * @return array{best_months: array<int, int>, travel_tip: string}
	 */
	private function get_destination_season_for_edit($post_id)
	{
		$raw_months = get_post_meta($post_id, 'tourism_destination_best_months', true);
		$best_months = array();

		if (is_array($raw_months)) {
			foreach ($raw_months as $v) {
				$i = (int) $v;
				if ($i >= 1 && $i <= 12) {
					$best_months[] = $i;
				}
			}
		}

		$best_months = array_values(array_unique($best_months));
		sort($best_months);

		return array(
			'best_months' => $best_months,
			'travel_tip'  => (string) get_post_meta($post_id, 'tourism_destination_travel_tip', true),
		);
	}

	public function render_season($post)
	{
		wp_nonce_field('tourism_core_save_destination', 'tourism_core_destination_nonce');

		$tourism_core_destination_season = $this->get_destination_season_for_edit((int) $post->ID);
		$tourism_core_destination_month_labels = $this->get_destination_month_labels();

		Metabox_Helper::include_template('modules/destinations/templates/season-metabox.php', array(
			'tourism_core_destination_season'      => $tourism_core_destination_season,
			'tourism_core_destination_month_labels' => $tourism_core_destination_month_labels,
		));
	}

	/**
	 * @param int $post_id
	 * @return array{
	 *   departamento_term_id:int,
	 *   provincia_term_id:int,
	 *   distrito_term_id:int,
	 *   departamentos:array<int,WP_Term>,
	 *   provincias:array<int,WP_Term>,
	 *   distritos:array<int,WP_Term>,
	 *   latitud:string,
	 *   longitud:string,
	 *   altitud:string,
	 *   order:int
	 * }
	 */
	private function get_destination_ubication_for_edit($post_id)
	{
		$departamento_terms = get_the_terms($post_id, 'departamento');
		$provincia_terms = get_the_terms($post_id, 'provincia');
		$distrito_terms = get_the_terms($post_id, 'distrito');

		$departamento_term_id = (is_array($departamento_terms) && ! empty($departamento_terms) && $departamento_terms[0] instanceof WP_Term)
			? (int) $departamento_terms[0]->term_id
			: 0;
		$provincia_term_id = (is_array($provincia_terms) && ! empty($provincia_terms) && $provincia_terms[0] instanceof WP_Term)
			? (int) $provincia_terms[0]->term_id
			: 0;
		$distrito_term_id = (is_array($distrito_terms) && ! empty($distrito_terms) && $distrito_terms[0] instanceof WP_Term)
			? (int) $distrito_terms[0]->term_id
			: 0;

		$departamentos = get_terms(
			array(
				'taxonomy'   => 'departamento',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		$provincias = get_terms(
			array(
				'taxonomy'   => 'provincia',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		$distritos = get_terms(
			array(
				'taxonomy'   => 'distrito',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		return array(
			'departamento_term_id' => $departamento_term_id,
			'provincia_term_id'    => $provincia_term_id,
			'distrito_term_id'     => $distrito_term_id,
			'departamentos'        => is_array($departamentos) ? $departamentos : array(),
			'provincias'           => is_array($provincias) ? $provincias : array(),
			'distritos'            => is_array($distritos) ? $distritos : array(),
			'latitud'              => (string) get_post_meta($post_id, 'tourism_destination_latitud', true),
			'longitud'             => (string) get_post_meta($post_id, 'tourism_destination_longitud', true),
			'altitud'              => (string) get_post_meta($post_id, 'tourism_destination_altitud', true),
			'order'                => (int) get_post_meta($post_id, 'tourism_destination_order', true),
		);
	}

	public function render_ubication($post)
	{
		wp_nonce_field('tourism_core_save_destination', 'tourism_core_destination_nonce');

		$tourism_core_destination_ubication = $this->get_destination_ubication_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/destinations/templates/ubication-metabox.php', array(
			'tourism_core_destination_ubication' => $tourism_core_destination_ubication,
		));
	}

	public function save($post_id, $post)
	{
		// 1) Quick Edit: allow saving only the manual order field with its own nonce.
		if ($this->maybe_save_quick_edit_order($post_id)) {
			return;
		}

		// 2) Full edit screen save: require the main metabox nonce.
		if (! Metabox_Helper::verify_nonce('tourism_core_destination_nonce', 'tourism_core_save_destination')) {
			return;
		}

		if (! Metabox_Helper::can_save($post_id)) return;

		// Hero (imagen + textos opcionales; sólo si el metabox está visible).
		if (Metabox_Helper::metabox_present('tourism_destination_hero_metabox_present')) {
			$hero_id = isset($_POST['destination_hero_image']) ? (int) wp_unslash($_POST['destination_hero_image']) : 0;
			update_post_meta($post_id, '_destination_hero_image', $hero_id);

			$hero_title = isset($_POST['tourism_destination_hero_title']) ? sanitize_text_field(wp_unslash($_POST['tourism_destination_hero_title'])) : '';
			$hero_short_description = isset($_POST['tourism_destination_hero_short_description']) ? sanitize_textarea_field(wp_unslash($_POST['tourism_destination_hero_short_description'])) : '';
			update_post_meta($post_id, 'tourism_destination_hero_title', $hero_title);
			update_post_meta($post_id, 'tourism_destination_hero_short_description', $hero_short_description);
		}

		// INTRO
		if (Metabox_Helper::metabox_present('tourism_destination_intro_metabox_present')) {
			$title = isset($_POST['tourism_destination_intro_title']) ? sanitize_text_field(wp_unslash($_POST['tourism_destination_intro_title'])) : '';
			$description = isset($_POST['tourism_destination_intro_description']) ? sanitize_textarea_field(wp_unslash($_POST['tourism_destination_intro_description'])) : '';

			update_post_meta($post_id, 'tourism_destination_intro_title', $title);
			update_post_meta($post_id, 'tourism_destination_intro_description', $description);
		}

		// GALLERY
		if (Metabox_Helper::metabox_present('tourism_destination_gallery_metabox_present')) {
			$gallery = Metabox_Helper::sanitize_gallery_post_rows('tourism_destination_gallery_images');
			update_post_meta($post_id, 'tourism_destination_gallery_images', $gallery['rows']);
			update_post_meta($post_id, 'tourism_destination_gallery_ids', $gallery['ids']);
		}

		// Highlights (repeater: sólo cuando el metabox está visible).
		if (Metabox_Helper::metabox_present('tourism_destination_highlights_metabox_present')) {
			$highlight_rows = Metabox_Helper::sanitize_rows(
				isset($_POST['tourism_destination_highlights']) ? wp_unslash($_POST['tourism_destination_highlights']) : array(),
				array(
					'title'       => 'sanitize_text_field',
					'description' => 'sanitize_textarea_field',
				)
			);
			update_post_meta($post_id, 'tourism_destination_highlights', $highlight_rows);
		}

		// Season (mejores meses + tip; sólo si el metabox está visible).
		if (Metabox_Helper::metabox_present('tourism_destination_season_metabox_present')) {
			$best_months = array();
			if (isset($_POST['tourism_destination_best_months']) && is_array($_POST['tourism_destination_best_months'])) {
				foreach (wp_unslash($_POST['tourism_destination_best_months']) as $v) {
					$i = (int) $v;
					if ($i >= 1 && $i <= 12) {
						$best_months[] = $i;
					}
				}
			}
			$best_months = array_values(array_unique($best_months));
			sort($best_months);
			update_post_meta($post_id, 'tourism_destination_best_months', $best_months);

			$travel_tip = isset($_POST['tourism_destination_travel_tip']) ? sanitize_text_field(wp_unslash($_POST['tourism_destination_travel_tip'])) : '';
			update_post_meta($post_id, 'tourism_destination_travel_tip', $travel_tip);
		}

		// Ubicación (taxonomías + coordenadas + altitud; sólo si el metabox está visible).
		if (Metabox_Helper::metabox_present('tourism_destination_ubication_metabox_present')) {
			$departamento_term_id = isset($_POST['tourism_destination_departamento_term']) ? (int) wp_unslash($_POST['tourism_destination_departamento_term']) : 0;
			$provincia_term_id = isset($_POST['tourism_destination_provincia_term']) ? (int) wp_unslash($_POST['tourism_destination_provincia_term']) : 0;
			$distrito_term_id = isset($_POST['tourism_destination_distrito_term']) ? (int) wp_unslash($_POST['tourism_destination_distrito_term']) : 0;

			wp_set_object_terms($post_id, $departamento_term_id > 0 ? array($departamento_term_id) : array(), 'departamento', false);
			wp_set_object_terms($post_id, $provincia_term_id > 0 ? array($provincia_term_id) : array(), 'provincia', false);
			wp_set_object_terms($post_id, $distrito_term_id > 0 ? array($distrito_term_id) : array(), 'distrito', false);

			$latitud = isset($_POST['tourism_destination_latitud']) ? sanitize_text_field(wp_unslash($_POST['tourism_destination_latitud'])) : '';
			$longitud = isset($_POST['tourism_destination_longitud']) ? sanitize_text_field(wp_unslash($_POST['tourism_destination_longitud'])) : '';
			$altitud = isset($_POST['tourism_destination_altitud']) ? sanitize_text_field(wp_unslash($_POST['tourism_destination_altitud'])) : '';
			$order = isset($_POST['tourism_destination_order']) ? (int) wp_unslash($_POST['tourism_destination_order']) : 0;
			if ($order < 0) {
				$order = 0;
			}

			update_post_meta($post_id, 'tourism_destination_latitud', $latitud);
			update_post_meta($post_id, 'tourism_destination_longitud', $longitud);
			update_post_meta($post_id, 'tourism_destination_altitud', $altitud);
			update_post_meta($post_id, 'tourism_destination_order', $order);
		}
	}

	public function enqueue_admin_assets($hook)
	{
		global $post_type;
		if ($post_type !== 'destination') return;

		wp_enqueue_media();
		wp_enqueue_script('jquery-ui-sortable');

		$css_path = TOURISM_CORE_PATH . 'assets/css/destination-admin-metabox.css';
		$ver_css  = file_exists($css_path) ? (string) filemtime($css_path) : TOURISM_CORE_VERSION;
		wp_enqueue_style(
			'tourism-core-destination-admin',
			TOURISM_CORE_URL . 'assets/css/destination-admin-metabox.css',
			array(),
			$ver_css
		);

		$gallery_js_path = TOURISM_CORE_PATH . 'assets/js/gallery-metabox.js';
		$gallery_js_ver  = file_exists($gallery_js_path) ? (string) filemtime($gallery_js_path) : TOURISM_CORE_VERSION;
		wp_enqueue_script(
			'tourism-core-gallery-metabox',
			TOURISM_CORE_URL . 'assets/js/gallery-metabox.js',
			array('jquery', 'jquery-ui-sortable', 'media-editor'),
			$gallery_js_ver,
			true
		);
		wp_localize_script(
			'tourism-core-gallery-metabox',
			'TourismCoreGalleries',
			array(
				array(
					'containerId'   => 'tourism-core-destination-gallery-images-container',
					'itemClass'     => 'tourism-core-destination-gallery-image-item',
					'fieldIdClass'  => 'tourism-core-destination-gallery-field-id',
					'fieldUrlClass' => 'tourism-core-destination-gallery-field-url',
					'detailsClass'  => 'tourism-core-destination-image-details',
					'previewClass'  => 'tourism-core-destination-image-preview',
					'noImageClass'  => 'tourism-core-destination-no-image',
					'postKey'       => 'tourism_destination_gallery_images',
					'addId'         => 'tourism-core-add-destination-gallery-image',
					'templateId'    => 'tourism-core-destination-gallery-image-template',
					'removeClass'   => 'tourism-core-remove-destination-gallery-image',
					'replaceClass'  => 'tourism-core-replace-destination-image',
				),
			)
		);

		$use_min = !(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);
		$js_rel  = ($use_min && file_exists(TOURISM_CORE_PATH . 'assets/js/destination-admin-metabox.min.js'))
			? 'destination-admin-metabox.min.js'
			: 'destination-admin-metabox.js';
		$js_path = TOURISM_CORE_PATH . 'assets/js/' . $js_rel;
		$ver_js  = file_exists($js_path) ? (string) filemtime($js_path) : TOURISM_CORE_VERSION;

		wp_enqueue_script(
			'tourism-core-destination-admin',
			TOURISM_CORE_URL . 'assets/js/' . $js_rel,
			array('jquery', 'jquery-ui-sortable', 'media-editor'),
			$ver_js,
			true
		);
	}

}

