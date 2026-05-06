<?php

if (! defined('ABSPATH')) {
	exit;
}

class Tourism_Core_Destination_Metaboxes
{
	public function register(Tourism_Core_Loader $loader)
	{
		$loader->add_action('add_meta_boxes', $this, 'add_meta_boxes');
		$loader->add_action('save_post_destination', $this, 'save', 10, 2);
		$loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
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

		$template = TOURISM_CORE_PATH . 'modules/destinations/templates/introduccion-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
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

		$template = TOURISM_CORE_PATH . 'modules/destinations/templates/hero-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
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

		$template = TOURISM_CORE_PATH . 'modules/destinations/templates/gallery-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
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

		$template = TOURISM_CORE_PATH . 'modules/destinations/templates/highlights-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
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

		$template = TOURISM_CORE_PATH . 'modules/destinations/templates/season-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
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
	 *   altitud:string
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
		);
	}

	public function render_ubication($post)
	{
		wp_nonce_field('tourism_core_save_destination', 'tourism_core_destination_nonce');

		$tourism_core_destination_ubication = $this->get_destination_ubication_for_edit((int) $post->ID);

		$template = TOURISM_CORE_PATH . 'modules/destinations/templates/ubication-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
	}

	public function save($post_id, $post)
	{
		if (! isset($_POST['tourism_core_destination_nonce']) ||
			! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tourism_core_destination_nonce'])), 'tourism_core_save_destination')) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (! current_user_can('edit_post', $post_id)) return;

		// Hero (imagen + textos opcionales; sólo si el metabox está visible).
		if (isset($_POST['tourism_destination_hero_metabox_present'])) {
			$hero_id = isset($_POST['destination_hero_image']) ? (int) wp_unslash($_POST['destination_hero_image']) : 0;
			update_post_meta($post_id, '_destination_hero_image', $hero_id);

			$hero_title = isset($_POST['tourism_destination_hero_title']) ? sanitize_text_field(wp_unslash($_POST['tourism_destination_hero_title'])) : '';
			$hero_short_description = isset($_POST['tourism_destination_hero_short_description']) ? sanitize_textarea_field(wp_unslash($_POST['tourism_destination_hero_short_description'])) : '';
			update_post_meta($post_id, 'tourism_destination_hero_title', $hero_title);
			update_post_meta($post_id, 'tourism_destination_hero_short_description', $hero_short_description);
		}

		// INTRO
		if (isset($_POST['tourism_destination_intro_metabox_present'])) {
			$title = isset($_POST['tourism_destination_intro_title']) ? sanitize_text_field(wp_unslash($_POST['tourism_destination_intro_title'])) : '';
			$description = isset($_POST['tourism_destination_intro_description']) ? sanitize_textarea_field(wp_unslash($_POST['tourism_destination_intro_description'])) : '';

			update_post_meta($post_id, 'tourism_destination_intro_title', $title);
			update_post_meta($post_id, 'tourism_destination_intro_description', $description);
		}

		// GALLERY
		if (isset($_POST['tourism_destination_gallery_metabox_present'])) {
			$gallery_rows = array();
			$sync_ids = array();

			if (isset($_POST['tourism_destination_gallery_images']) && is_array($_POST['tourism_destination_gallery_images'])) {
				foreach (wp_unslash($_POST['tourism_destination_gallery_images']) as $row) {
					if (! is_array($row)) {
						continue;
					}
					$gid = isset($row['id']) ? (int) $row['id'] : 0;
					$gurl = isset($row['url']) ? esc_url_raw($row['url']) : '';
					$gtitle = isset($row['title']) ? sanitize_text_field($row['title']) : '';
					$gcaption = isset($row['caption']) ? sanitize_textarea_field($row['caption']) : '';

					if ($gid <= 0 && $gurl === '') {
						continue;
					}
					if ($gid > 0) {
						$att_url = wp_get_attachment_url($gid);
						if ($att_url) {
							$gurl = $att_url;
						}
						$sync_ids[] = $gid;
					}

					$gallery_rows[] = array(
						'id'      => $gid,
						'url'     => $gurl,
						'title'   => $gtitle,
						'caption' => $gcaption,
					);
				}
			}

			update_post_meta($post_id, 'tourism_destination_gallery_images', $gallery_rows);
			update_post_meta($post_id, 'tourism_destination_gallery_ids', array_values(array_unique(array_filter($sync_ids))));
		}

		// Highlights (repeater: sólo cuando el metabox está visible).
		if (isset($_POST['tourism_destination_highlights_metabox_present'])) {
			$highlight_rows = array();
			if (isset($_POST['tourism_destination_highlights']) && is_array($_POST['tourism_destination_highlights'])) {
				foreach (wp_unslash($_POST['tourism_destination_highlights']) as $row) {
					if (! is_array($row)) {
						continue;
					}
					$htitle = isset($row['title']) ? sanitize_text_field($row['title']) : '';
					$hdesc = isset($row['description']) ? sanitize_textarea_field($row['description']) : '';
					if ($htitle === '' && $hdesc === '') {
						continue;
					}
					$highlight_rows[] = array(
						'title'       => $htitle,
						'description' => $hdesc,
					);
				}
			}
			update_post_meta($post_id, 'tourism_destination_highlights', $highlight_rows);
		}

		// Season (mejores meses + tip; sólo si el metabox está visible).
		if (isset($_POST['tourism_destination_season_metabox_present'])) {
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
		if (isset($_POST['tourism_destination_ubication_metabox_present'])) {
			$departamento_term_id = isset($_POST['tourism_destination_departamento_term']) ? (int) wp_unslash($_POST['tourism_destination_departamento_term']) : 0;
			$provincia_term_id = isset($_POST['tourism_destination_provincia_term']) ? (int) wp_unslash($_POST['tourism_destination_provincia_term']) : 0;
			$distrito_term_id = isset($_POST['tourism_destination_distrito_term']) ? (int) wp_unslash($_POST['tourism_destination_distrito_term']) : 0;

			wp_set_object_terms($post_id, $departamento_term_id > 0 ? array($departamento_term_id) : array(), 'departamento', false);
			wp_set_object_terms($post_id, $provincia_term_id > 0 ? array($provincia_term_id) : array(), 'provincia', false);
			wp_set_object_terms($post_id, $distrito_term_id > 0 ? array($distrito_term_id) : array(), 'distrito', false);

			$latitud = isset($_POST['tourism_destination_latitud']) ? sanitize_text_field(wp_unslash($_POST['tourism_destination_latitud'])) : '';
			$longitud = isset($_POST['tourism_destination_longitud']) ? sanitize_text_field(wp_unslash($_POST['tourism_destination_longitud'])) : '';
			$altitud = isset($_POST['tourism_destination_altitud']) ? sanitize_text_field(wp_unslash($_POST['tourism_destination_altitud'])) : '';

			update_post_meta($post_id, 'tourism_destination_latitud', $latitud);
			update_post_meta($post_id, 'tourism_destination_longitud', $longitud);
			update_post_meta($post_id, 'tourism_destination_altitud', $altitud);
		}
	}

	public function enqueue_admin_assets($hook)
	{
		global $post_type;
		if ($post_type !== 'destination') return;

		wp_enqueue_media();
		wp_enqueue_script('jquery-ui-sortable');

		wp_add_inline_style(
			'wp-admin',
			'
			.tourism-core-destination-gallery-metabox-container{margin-top:4px;}
			#tourism-core-destination-gallery-images-container{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:12px;}
			.tourism-core-destination-gallery-image-item{border:1px solid #c3c4c7;background:#fff;padding:10px;}
			.tourism-core-destination-image-preview{position:relative;aspect-ratio:4/3;background:#f0f0f1;border:1px solid #dcdcde;margin-bottom:10px;overflow:hidden;}
			.tourism-core-destination-image-preview img{display:block;width:100%;height:100%;object-fit:cover;}
			.tourism-core-destination-no-image{display:flex;align-items:center;justify-content:center;height:100%;min-height:120px;color:#646970;font-size:12px;text-align:center;padding:8px;}
			.tourism-core-destination-image-actions{position:absolute;right:6px;top:6px;z-index:2;display:flex;gap:4px;}
			.tourism-core-destination-image-actions .button{min-width:32px;padding:0 6px;}
			.tourism-core-destination-image-details label{display:block;margin:6px 0 2px;font-weight:600;}
			.tourism-core-destination-image-details input,.tourism-core-destination-image-details textarea{width:100%;}
			.tourism-core-destination-hero-text-fields label{display:block;margin:0 0 4px;}
			.tourism-core-destination-hero-preview-wrap{
				display:flex;
				justify-content:center;
				align-items:center;
				min-height:72px;
				max-height:220px;
				border:1px solid #dcdcde;
				background:#f0f0f1;
				padding:8px;
				box-sizing:border-box;
			}
			.tourism-core-destination-hero-preview-wrap img{
				max-width:100%;
				max-height:200px;
				width:auto;
				height:auto;
				object-fit:contain;
				display:block;
			}
			#tourism-core-destination-highlights-container .tourism-core-destination-highlight-item{border:1px solid #c3c4c7;background:#fff;margin-bottom:10px;}
			#tourism-core-destination-highlights-container .tourism-core-destination-highlight-header{display:flex;align-items:center;gap:8px;padding:8px 10px;border-bottom:1px solid #c3c4c7;background:#f6f7f7;}
			#tourism-core-destination-highlights-container .tourism-core-destination-highlight-header h4{margin:0;flex:1;font-size:13px;}
			#tourism-core-destination-highlights-container .tourism-core-handle{cursor:move;color:#787c82;}
			#tourism-core-destination-highlights-container .tourism-core-destination-highlight-content{padding:10px;}
			.tourism-core-season-best-months-heading{margin-bottom:6px;}
			.tourism-core-season-months-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px 12px;margin-top:10px;max-width:720px;}
			.tourism-core-season-month-label{display:flex;align-items:center;gap:6px;font-size:13px;}
			.tourism-core-season-month-label input{margin:0;}
			@media (max-width:782px){.tourism-core-season-months-grid{grid-template-columns:1fr 1fr;}}
			'
		);

		wp_add_inline_script('jquery', "
			jQuery(document).ready(function($){
				let frame;

				$('.tourism-upload-destination-hero').on('click', function(e){
					e.preventDefault();

					if(frame){
						frame.open();
						return;
					}

					frame = wp.media({
						title: 'Select Hero Image',
						button: { text: 'Use image' },
						multiple: false
					});

					frame.on('select', function(){
						const attachment = frame.state().get('selection').first().toJSON();
						$('#destination_hero_image').val(attachment.id);
						const url = (attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url) ? attachment.sizes.medium.url : attachment.url;
						$('#destination-hero-preview').html('<img src=\"'+url+'\" alt=\"\" />');
					});

					frame.open();
				});

				$('.tourism-remove-destination-hero').on('click', function(){
					$('#destination_hero_image').val('');
					$('#destination-hero-preview').html('');
				});

				function tourismCoreRenumberDestinationGallery(){
					const \$g = $('#tourism-core-destination-gallery-images-container');
					if(!\$g.length) return;
					\$g.children('.tourism-core-destination-gallery-image-item').each(function(i){
						const \$item = $(this);
						\$item.find('.tourism-core-destination-gallery-field-id').attr('name', 'tourism_destination_gallery_images['+i+'][id]');
						\$item.find('.tourism-core-destination-gallery-field-url').attr('name', 'tourism_destination_gallery_images['+i+'][url]');
						\$item.find('.tourism-core-destination-image-details input[type=\"text\"]').attr('name', 'tourism_destination_gallery_images['+i+'][title]');
						\$item.find('.tourism-core-destination-image-details textarea').attr('name', 'tourism_destination_gallery_images['+i+'][caption]');
					});
				}

				function tourismCoreRenumberDestinationHighlights(){
					const \$c = $('#tourism-core-destination-highlights-container');
					if(!\$c.length) return;
					\$c.children('.tourism-core-destination-highlight-item').each(function(i){
						const \$item = $(this);
						\$item.attr('data-tourism-core-index', String(i));
						\$item.find('.tourism-core-item-number').text(String(i + 1));
						\$item.find('input[name^=\"tourism_destination_highlights[\"]').attr('name', 'tourism_destination_highlights['+i+'][title]');
						\$item.find('textarea[name^=\"tourism_destination_highlights[\"]').attr('name', 'tourism_destination_highlights['+i+'][description]');
						\$item.find('label[for^=\"tourism_core_destination_highlight_title_\"]').attr('for', 'tourism_core_destination_highlight_title_'+i);
						\$item.find('input[id^=\"tourism_core_destination_highlight_title_\"]').attr('id', 'tourism_core_destination_highlight_title_'+i);
						\$item.find('label[for^=\"tourism_core_destination_highlight_description_\"]').attr('for', 'tourism_core_destination_highlight_description_'+i);
						\$item.find('textarea[id^=\"tourism_core_destination_highlight_description_\"]').attr('id', 'tourism_core_destination_highlight_description_'+i);
					});
				}

				const \$galleryContainer = $('#tourism-core-destination-gallery-images-container');
				if(\$galleryContainer.length){
					\$galleryContainer.sortable({
						items: '> .tourism-core-destination-gallery-image-item',
						cancel: 'input,textarea,button,select,option',
						update: function(){
							tourismCoreRenumberDestinationGallery();
						}
					});

					$(document).on('click', '#tourism-core-add-destination-gallery-image', function(e){
						e.preventDefault();
						const \$tpl = $('#tourism-core-destination-gallery-image-template').children('.tourism-core-destination-gallery-image-item').first();
						if(!\$tpl.length) return;
						const \$clone = \$tpl.clone(true, true);
						\$galleryContainer.append(\$clone);
						tourismCoreRenumberDestinationGallery();
					});

					$(document).on('click', '.tourism-core-remove-destination-gallery-image', function(e){
						e.preventDefault();
						$(this).closest('.tourism-core-destination-gallery-image-item').remove();
						tourismCoreRenumberDestinationGallery();
					});

					$(document).on('click', '.tourism-core-replace-destination-image', function(e){
						e.preventDefault();
						const \$item = $(this).closest('.tourism-core-destination-gallery-image-item');
						const media = wp.media({
							title: 'Select image',
							button: { text: 'Use image' },
							multiple: false
						});
						media.on('select', function(){
							const att = media.state().get('selection').first().toJSON();
							const url = (att.sizes && att.sizes.medium && att.sizes.medium.url) ? att.sizes.medium.url : att.url;
							\$item.find('.tourism-core-destination-gallery-field-id').val(String(att.id));
							\$item.find('.tourism-core-destination-gallery-field-url').val(att.url);
							\$item.find('.tourism-core-destination-image-preview .tourism-core-destination-no-image').remove();
							\$item.find('.tourism-core-destination-image-preview img').remove();
							\$item.find('.tourism-core-destination-image-preview').prepend('<img src=\"'+url+'\" alt=\"\" />');
						});
						media.open();
					});
				}

				const \$highlightsContainer = $('#tourism-core-destination-highlights-container');
				if(\$highlightsContainer.length){
					\$highlightsContainer.sortable({
						handle: '.tourism-core-handle',
						items: '> .tourism-core-destination-highlight-item',
						cancel: 'input,textarea,button,select,option',
						update: function(){
							tourismCoreRenumberDestinationHighlights();
						}
					});

					$(document).on('click', '#tourism-core-add-destination-highlight', function(e){
						e.preventDefault();
						const tmpl = $('#tourism-core-tmpl-destination-highlight-item').html();
						if(!tmpl) return;
						const idx = \$highlightsContainer.children('.tourism-core-destination-highlight-item').length;
						const html = tmpl
							.split('{{tourism_index}}').join(String(idx))
							.split('{{tourism_index_plus_one}}').join(String(idx + 1));
						\$highlightsContainer.append(html);
						tourismCoreRenumberDestinationHighlights();
					});

					$(document).on('click', '.tourism-core-delete-destination-highlight', function(e){
						e.preventDefault();
						$(this).closest('.tourism-core-destination-highlight-item').remove();
						tourismCoreRenumberDestinationHighlights();
					});

					tourismCoreRenumberDestinationHighlights();
				}
			});
		");
	}
}

