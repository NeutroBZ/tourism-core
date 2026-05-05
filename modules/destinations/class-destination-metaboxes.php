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
			.tourism-core-destination-hero-preview-wrap img{width:100%;display:block;}
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
						$('#destination-hero-preview').html('<img src=\"'+url+'\" style=\"width:100%;\" />');
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
			});
		");
	}
}

