<?php

if (! defined('ABSPATH')) {
	exit;
}

class Tourism_Core_Destination_Taxonomies
{
	public const DEPARTAMENTO_COVER_META_KEY = 'tourism_departamento_cover_image';

	public function register(Tourism_Core_Loader $loader)
	{
		$loader->add_action('init', $this, 'register_taxonomies');

		$loader->add_action('departamento_add_form_fields', $this, 'render_departamento_cover_add_field');
		$loader->add_action('departamento_edit_form_fields', $this, 'render_departamento_cover_edit_field');
		$loader->add_action('created_departamento', $this, 'save_departamento_cover');
		$loader->add_action('edited_departamento', $this, 'save_departamento_cover');

		$loader->add_filter('manage_edit-departamento_columns', $this, 'add_departamento_cover_column');
		$loader->add_filter('manage_departamento_custom_column', $this, 'render_departamento_cover_column', 10, 3);

		$loader->add_action('admin_enqueue_scripts', $this, 'enqueue_departamento_admin_assets');
	}

	public function register_taxonomies()
	{
		$this->register_taxonomy(
			'departamento',
			__('Departamentos', TOURISM_CORE_TD),
			__('Departamento', TOURISM_CORE_TD)
		);

		$this->register_taxonomy(
			'provincia',
			__('Provincias', TOURISM_CORE_TD),
			__('Provincia', TOURISM_CORE_TD)
		);

		$this->register_taxonomy(
			'distrito',
			__('Distritos', TOURISM_CORE_TD),
			__('Distrito', TOURISM_CORE_TD)
		);

		register_term_meta(
			'departamento',
			self::DEPARTAMENTO_COVER_META_KEY,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array($this, 'sanitize_departamento_cover_meta'),
			)
		);
	}

	/**
	 * @param mixed $value Raw term meta value.
	 * @return int Attachment ID or 0.
	 */
	public function sanitize_departamento_cover_meta($value)
	{
		$attachment_id = (int) $value;
		if ($attachment_id <= 0 || ! wp_attachment_is_image($attachment_id)) {
			return 0;
		}

		return $attachment_id;
	}

	public function render_departamento_cover_add_field()
	{
		$this->include_departamento_cover_template(
			array(
				'cover_id' => 0,
				'context'  => 'add',
			)
		);
	}

	/**
	 * @param WP_Term $term Term being edited.
	 */
	public function render_departamento_cover_edit_field($term)
	{
		if (! $term instanceof WP_Term) {
			return;
		}

		$this->include_departamento_cover_template(
			array(
				'cover_id' => (int) get_term_meta((int) $term->term_id, self::DEPARTAMENTO_COVER_META_KEY, true),
				'context'  => 'edit',
			)
		);
	}

	/**
	 * @param int $term_id Term ID.
	 */
	public function save_departamento_cover($term_id)
	{
		$term_id = (int) $term_id;
		if ($term_id <= 0) {
			return;
		}

		if (! isset($_POST['tourism_departamento_cover_nonce']) ||
			! wp_verify_nonce(
				sanitize_text_field(wp_unslash($_POST['tourism_departamento_cover_nonce'])),
				'tourism_core_save_departamento_cover'
			)
		) {
			return;
		}

		if (! current_user_can('edit_term', $term_id)) {
			return;
		}

		$cover_id = isset($_POST['tourism_departamento_cover_image'])
			? (int) wp_unslash($_POST['tourism_departamento_cover_image'])
			: 0;

		$cover_id = $this->sanitize_departamento_cover_meta($cover_id);

		if ($cover_id > 0) {
			update_term_meta($term_id, self::DEPARTAMENTO_COVER_META_KEY, $cover_id);
			return;
		}

		delete_term_meta($term_id, self::DEPARTAMENTO_COVER_META_KEY);
	}

	/**
	 * @param array<string> $columns List table columns.
	 * @return array<string>
	 */
	public function add_departamento_cover_column($columns)
	{
		if (! is_array($columns)) {
			return $columns;
		}

		$new_columns = array();
		foreach ($columns as $key => $label) {
			$new_columns[$key] = $label;
			if ($key === 'name') {
				$new_columns['tourism_departamento_cover'] = __('Cover', TOURISM_CORE_TD);
			}
		}

		if (! isset($new_columns['tourism_departamento_cover'])) {
			$new_columns['tourism_departamento_cover'] = __('Cover', TOURISM_CORE_TD);
		}

		return $new_columns;
	}

	/**
	 * @param string $content    Column output.
	 * @param string $column_name Column key.
	 * @param int    $term_id    Term ID.
	 * @return string
	 */
	public function render_departamento_cover_column($content, $column_name, $term_id)
	{
		if ($column_name !== 'tourism_departamento_cover') {
			return $content;
		}

		$cover_id = (int) get_term_meta((int) $term_id, self::DEPARTAMENTO_COVER_META_KEY, true);
		if ($cover_id <= 0) {
			return '&mdash;';
		}

		$image = wp_get_attachment_image($cover_id, array(48, 48));
		if ($image === '') {
			return '&mdash;';
		}

		return $image;
	}

	/**
	 * @param array{cover_id: int, context: string} $data Template data.
	 */
	private function include_departamento_cover_template(array $data)
	{
		$tourism_core_departamento_cover = $data;

		$template = TOURISM_CORE_PATH . 'modules/destinations/templates/departamento-cover-field.php';
		if (is_readable($template)) {
			include $template;
		}
	}

	public function enqueue_departamento_admin_assets($hook)
	{
		if ($hook !== 'edit-tags.php' && $hook !== 'term.php') {
			return;
		}

		$taxonomy = isset($_GET['taxonomy']) ? sanitize_key(wp_unslash($_GET['taxonomy'])) : '';
		if ($taxonomy !== 'departamento') {
			return;
		}

		wp_enqueue_media();

		wp_add_inline_style(
			'wp-admin',
			'
			.tourism-core-departamento-cover-preview-wrap{
				display:flex;
				justify-content:center;
				align-items:center;
				min-height:72px;
				max-height:220px;
				max-width:320px;
				border:1px solid #dcdcde;
				background:#f0f0f1;
				padding:8px;
				box-sizing:border-box;
				margin-bottom:10px;
			}
			.tourism-core-departamento-cover-preview-wrap img{
				max-width:100%;
				max-height:200px;
				width:auto;
				height:auto;
				object-fit:contain;
				display:block;
			}
			.tourism-core-departamento-cover-actions{
				margin:0 0 8px;
				display:flex;
				flex-wrap:wrap;
				gap:6px;
			}
			.column-tourism_departamento_cover{width:72px;text-align:center;}
			.column-tourism_departamento_cover img{
				max-width:48px;
				height:auto;
				border-radius:2px;
			}
			'
		);

		wp_add_inline_script(
			'jquery',
			"
			jQuery(document).ready(function($){
				let frame;

				$('.tourism-upload-departamento-cover').on('click', function(e){
					e.preventDefault();

					if(frame){
						frame.open();
						return;
					}

					frame = wp.media({
						title: '" . esc_js(__('Select cover image', TOURISM_CORE_TD)) . "',
						button: { text: '" . esc_js(__('Use image', TOURISM_CORE_TD)) . "' },
						multiple: false
					});

					frame.on('select', function(){
						const attachment = frame.state().get('selection').first().toJSON();
						$('#tourism_departamento_cover_image').val(attachment.id);
						const url = (attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url)
							? attachment.sizes.medium.url
							: attachment.url;
						$('#tourism-departamento-cover-preview').html('<img src=\"'+url+'\" alt=\"\" />');
					});

					frame.open();
				});

				$('.tourism-remove-departamento-cover').on('click', function(e){
					e.preventDefault();
					$('#tourism_departamento_cover_image').val('');
					$('#tourism-departamento-cover-preview').html('');
				});
			});
			"
		);
	}

	private function register_taxonomy($taxonomy, $plural, $singular)
	{
		register_taxonomy(
			(string) $taxonomy,
			array('destination'),
			array(
				'labels'            => array(
					'name'          => $plural,
					'singular_name' => $singular,
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array('slug' => $taxonomy),
			)
		);
	}
}
