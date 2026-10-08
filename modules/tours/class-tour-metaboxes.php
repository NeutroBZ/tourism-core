<?php

if (! defined('ABSPATH')) {
	exit;
}

require_once TOURISM_CORE_PATH . 'includes/class-metabox-helper.php';
require_once TOURISM_CORE_PATH . 'includes/class-metabox-base.php';

use Tourism_Core_Metabox_Helper as Metabox_Helper;

class Tourism_Core_Tour_Metaboxes extends Tourism_Core_Metabox_Base
{
	use Tourism_Core_Order_Column;

	protected function order_column_config()
	{
		return array(
			'post_type'         => 'tour',
			'meta_key'          => 'tourism_tour_order',
			'quickedit_field'   => 'tourism_tour_order',
			'quickedit_nonce'   => 'tourism_core_tour_quickedit_nonce',
			'quickedit_action'  => 'tourism_core_quickedit_tour',
		);
	}

	public function register(Tourism_Core_Loader $loader)
	{
		$loader->add_action('add_meta_boxes', $this, 'add_meta_boxes');
		$loader->add_action('save_post_tour', $this, 'save', 10, 2);
		$loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
		$this->register_order_column($loader);
	}

	public function add_meta_boxes()
	{
		// MAIN BOX
		add_meta_box(
			'tourism-core-tour-details',
			__('Características del tour', TOURISM_CORE_TD),
			array($this, 'render'),
			'tour',
			'normal',
			'default'
		);

		// HERO IMAGE BOX
		add_meta_box(
			'tourism-core-tour-hero',
			__('Imagen de portada', TOURISM_CORE_TD),
			array($this, 'render_hero'),
			'tour',
			'normal',
			'high'
		);

		// Photo gallery (modular template)
		add_meta_box(
			'tourism-core-tour-gallery',
			__('Galería de fotos', TOURISM_CORE_TD),
			array($this, 'render_gallery'),
			'tour',
			'normal',
			'default'
		);

		// What to bring (template de texto)
		add_meta_box(
			'tourism-core-tour-what-to-bring',
			__('Qué llevar', TOURISM_CORE_TD),
			array($this, 'render_packing'),
			'tour',
			'normal',
			'default'
		);
		// What's Included / Excludes (templates/whats-included-metabox.php).
		add_meta_box(
			'tourism-core-whats-included',
			__("Incluidos y excluidos", TOURISM_CORE_TD),
			array($this, 'render_whats_included'),
			'tour',
			'normal',
			'default'
		);

		// Introduction (title + description)
		add_meta_box(
			'tourism-core-tour-introduction',
			__('Presentación del tour', TOURISM_CORE_TD),
			array($this, 'render_introduction'),
			'tour',
			'normal',
			'default'
		);

		// Pricing (current + previous + discount)
		add_meta_box(
			'tourism-core-tour-pricing',
			__('Precio del tour', TOURISM_CORE_TD),
			array($this, 'render_pricing'),
			'tour',
			'normal',
			'default'
		);

		// Pricing Details (repeater: title + WYSIWYG content) — separate from Tour Pricing above.
		add_meta_box(
			'tourism-core-tour-pricing-details',
			__('Detalles del precio', TOURISM_CORE_TD),
			array($this, 'render_pricing_details'),
			'tour',
			'normal',
			'default'
		);

		add_meta_box(
			'tourism-core-tour-booking-policy',
			__('Políticas de reserva', TOURISM_CORE_TD),
			array($this, 'render_booking_policy'),
			'tour',
			'normal',
			'default'
		);

		// Itinerary (days + description + dynamic features)
		add_meta_box(
			'tourism-core-tour-itinerary',
			__('Itinerario del tour', TOURISM_CORE_TD),
			array($this, 'render_itinerary'),
			'tour',
			'normal',
			'default'
		);


		// What to Expect (repeater: title + WYSIWYG content)
		add_meta_box(
			'tourism-core-tour-what-to-expect',
			__('Qué esperar', TOURISM_CORE_TD),
			array($this, 'render_what_to_expect'),
			'tour',
			'normal',
			'default'
		);

		// FAQs (modular template)
		add_meta_box(
			'tourism-core-tour-faqs',
			__('Preguntas frecuentes', TOURISM_CORE_TD),
			array($this, 'render_faqs'),
			'tour',
			'normal',
			'default'
		);

		// Highlights (modular template)
		add_meta_box(
			'tourism-core-tour-highlights',
			__('Destacados del tour', TOURISM_CORE_TD),
			array($this, 'render_highlights'),
			'tour',
			'normal',
			'default'
		);

		// Manual order.
		add_meta_box(
			'tourism-core-tour-order',
			__('Orden', TOURISM_CORE_TD),
			array($this, 'render_order'),
			'tour',
			'side',
			'default'
		);

		// WeTravel booking UUID.
		add_meta_box(
			'tourism-core-tour-wetravel',
			__('Reserva con WeTravel', TOURISM_CORE_TD),
			array($this, 'render_wetravel'),
			'tour',
			'normal',
			'low'
		);
	}

	/* =========================
	   MAIN FIELDS
	========================= */
	public function render($post)
	{
		wp_nonce_field('tourism_core_save_tour', 'tourism_core_tour_nonce');

		$duration = get_post_meta($post->ID, 'tourism_duration', true);
		$duration_options = $this->get_tour_duration_options_for_edit((int) $post->ID, $duration);
		$difficulty = get_post_meta($post->ID, 'tourism_difficulty', true);
		$difficulty_options = $this->get_tour_difficulty_options_for_edit((int) $post->ID, $difficulty);
		$group_size = get_post_meta($post->ID, 'tourism_group_size', true);
		$group_size_options = $this->get_tour_group_size_options_for_edit((int) $post->ID, $group_size);
		$max_elevation = get_post_meta($post->ID, 'tourism_max_elevation', true);
		$max_elevation_options = $this->get_tour_max_elevation_options_for_edit((int) $post->ID, $max_elevation);
		$dest_ids = (array) get_post_meta($post->ID, 'tourism_destinations', true);
		$dest_ids = array_map('intval', $dest_ids);

		$destinations = get_posts([
			'post_type'      => 'destination',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
			'no_found_rows'  => true,
		]);
?>

		<div class="tourism-core-details-grid">
			<div class="tourism-core-detail-column">
				<p>
					<label><strong><?php esc_html_e('Duración', TOURISM_CORE_TD); ?></strong></label>
					<select name="tourism_duration" id="tourism_duration" style="width:100%;">
						<option value=""><?php esc_html_e('Selecciona una duración', TOURISM_CORE_TD); ?></option>
						<?php foreach ($duration_options as $duration_option) : ?>
							<option value="<?php echo esc_attr($duration_option); ?>" <?php selected($duration, $duration_option); ?>>
								<?php echo esc_html($duration_option); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<div class="tourism-core-duration-builder">
					<p><strong><?php esc_html_e('Opciones de duración', TOURISM_CORE_TD); ?></strong></p>
					<div id="tourism-core-duration-options-list">
						<?php foreach ($duration_options as $duration_index => $duration_option) : ?>
							<div class="tourism-core-duration-option-row">
								<input
									type="text"
									class="widefat tourism-core-duration-option-input"
									name="tourism_duration_options[<?php echo esc_attr((string) $duration_index); ?>]"
									value="<?php echo esc_attr($duration_option); ?>"
									placeholder="<?php esc_attr_e('Ejemplo: día completo', TOURISM_CORE_TD); ?>"
								/>
								<button type="button" class="button tourism-core-delete-duration-option"><?php esc_html_e('Eliminar', TOURISM_CORE_TD); ?></button>
							</div>
						<?php endforeach; ?>
					</div>
					<p>
						<button type="button" id="tourism-core-add-duration-option" class="button button-secondary"><?php esc_html_e('Añadir opción', TOURISM_CORE_TD); ?></button>
					</p>
				</div>
			</div>

			<div class="tourism-core-detail-column">
				<p>
					<label><strong><?php esc_html_e('Dificultad', TOURISM_CORE_TD); ?></strong></label>
					<select name="tourism_difficulty" id="tourism_difficulty" style="width:100%;">
						<option value=""><?php esc_html_e('Selecciona una dificultad', TOURISM_CORE_TD); ?></option>
						<?php foreach ($difficulty_options as $difficulty_option) : ?>
							<option value="<?php echo esc_attr($difficulty_option); ?>" <?php selected($difficulty, $difficulty_option); ?>>
								<?php echo esc_html($difficulty_option); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<div class="tourism-core-difficulty-builder">
					<p><strong><?php esc_html_e('Opciones de dificultad', TOURISM_CORE_TD); ?></strong></p>
					<div id="tourism-core-difficulty-options-list">
						<?php foreach ($difficulty_options as $difficulty_index => $difficulty_option) : ?>
							<div class="tourism-core-difficulty-option-row">
								<input
									type="text"
									class="widefat tourism-core-difficulty-option-input"
									name="tourism_difficulty_options[<?php echo esc_attr((string) $difficulty_index); ?>]"
									value="<?php echo esc_attr($difficulty_option); ?>"
									placeholder="<?php esc_attr_e('Ejemplo: moderada', TOURISM_CORE_TD); ?>"
								/>
								<button type="button" class="button tourism-core-delete-difficulty-option"><?php esc_html_e('Eliminar', TOURISM_CORE_TD); ?></button>
							</div>
						<?php endforeach; ?>
					</div>
					<p>
						<button type="button" id="tourism-core-add-difficulty-option" class="button button-secondary"><?php esc_html_e('Añadir opción', TOURISM_CORE_TD); ?></button>
					</p>
				</div>
			</div>
		</div>
		<div class="tourism-core-details-grid">
			<div class="tourism-core-detail-column">
				<p>
					<label><strong><?php esc_html_e('Tamaño del grupo', TOURISM_CORE_TD); ?></strong></label>
					<select name="tourism_group_size" id="tourism_group_size" style="width:100%;">
						<option value=""><?php esc_html_e('Selecciona el tamaño del grupo', TOURISM_CORE_TD); ?></option>
						<?php foreach ($group_size_options as $group_size_option) : ?>
							<option value="<?php echo esc_attr($group_size_option); ?>" <?php selected($group_size, $group_size_option); ?>>
								<?php echo esc_html($group_size_option); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<div class="tourism-core-group-size-builder">
					<p><strong><?php esc_html_e('Opciones de grupo', TOURISM_CORE_TD); ?></strong></p>
					<div id="tourism-core-group-size-options-list">
						<?php foreach ($group_size_options as $group_size_index => $group_size_option) : ?>
							<div class="tourism-core-group-size-option-row">
								<input
									type="text"
									class="widefat tourism-core-group-size-option-input"
									name="tourism_group_size_options[<?php echo esc_attr((string) $group_size_index); ?>]"
									value="<?php echo esc_attr($group_size_option); ?>"
									placeholder="<?php esc_attr_e('Ejemplo: 2–12 personas', TOURISM_CORE_TD); ?>"
								/>
								<button type="button" class="button tourism-core-delete-group-size-option"><?php esc_html_e('Eliminar', TOURISM_CORE_TD); ?></button>
							</div>
						<?php endforeach; ?>
					</div>
					<p>
						<button type="button" id="tourism-core-add-group-size-option" class="button button-secondary"><?php esc_html_e('Añadir opción', TOURISM_CORE_TD); ?></button>
					</p>
				</div>
			</div>

			<div class="tourism-core-detail-column">
				<p>
					<label><strong><?php esc_html_e('Altitud máxima', TOURISM_CORE_TD); ?></strong></label>
					<select name="tourism_max_elevation" id="tourism_max_elevation" style="width:100%;">
						<option value=""><?php esc_html_e('Selecciona la altitud máxima', TOURISM_CORE_TD); ?></option>
						<?php foreach ($max_elevation_options as $max_elevation_option) : ?>
							<option value="<?php echo esc_attr($max_elevation_option); ?>" <?php selected($max_elevation, $max_elevation_option); ?>>
								<?php echo esc_html($max_elevation_option); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<div class="tourism-core-max-elevation-builder">
					<p><strong><?php esc_html_e('Opciones de altitud', TOURISM_CORE_TD); ?></strong></p>
					<div id="tourism-core-max-elevation-options-list">
						<?php foreach ($max_elevation_options as $max_elevation_index => $max_elevation_option) : ?>
							<div class="tourism-core-max-elevation-option-row">
								<input
									type="text"
									class="widefat tourism-core-max-elevation-option-input"
									name="tourism_max_elevation_options[<?php echo esc_attr((string) $max_elevation_index); ?>]"
									value="<?php echo esc_attr($max_elevation_option); ?>"
									placeholder="<?php esc_attr_e('Ejemplo: 5.200 m', TOURISM_CORE_TD); ?>"
								/>
								<button type="button" class="button tourism-core-delete-max-elevation-option"><?php esc_html_e('Eliminar', TOURISM_CORE_TD); ?></button>
							</div>
						<?php endforeach; ?>
					</div>
					<p>
						<button type="button" id="tourism-core-add-max-elevation-option" class="button button-secondary"><?php esc_html_e('Añadir opción', TOURISM_CORE_TD); ?></button>
					</p>
				</div>
			</div>
		</div>

		<p><strong><?php esc_html_e('Destinos', TOURISM_CORE_TD); ?></strong></p>

		<div style="max-height:150px; overflow:auto; border:1px solid #ddd; padding:8px;">
			<?php if ( empty( $destinations ) ) : ?>
				<p class="description">No hay destinos publicados. Puedes añadirlos en la sección Destinos y después asociarlos a este tour.</p>
			<?php endif; ?>
			<?php foreach ($destinations as $dest) : ?>
				<label style="display:block;">
					<input type="checkbox"
						name="tourism_destinations[]"
						value="<?php echo esc_attr($dest->ID); ?>"
						<?php checked(in_array((int) $dest->ID, $dest_ids, true)); ?> />
					<?php echo esc_html(get_the_title($dest->ID)); ?>
				</label>
			<?php endforeach; ?>
		</div>

<?php
	}

	/**
	 * Duration options for the editor.
	 *
	 * @param int    $post_id           Post ID.
	 * @param string $selected_duration Current selected duration.
	 * @return array<int, string>
	 */
	private function get_tour_duration_options_for_edit($post_id, $selected_duration = '')
	{
		return Metabox_Helper::normalize_select_options(
			(int) $post_id,
			'tourism_duration_options',
			array(
				'Full day',
				'Half day',
				'2 Days',
			),
			$selected_duration
		);
	}

	/**
	 * Difficulty options for the editor.
	 *
	 * @param int    $post_id             Post ID.
	 * @param string $selected_difficulty Current selected difficulty.
	 * @return array<int, string>
	 */
	private function get_tour_difficulty_options_for_edit($post_id, $selected_difficulty = '')
	{
		return Metabox_Helper::normalize_select_options(
			(int) $post_id,
			'tourism_difficulty_options',
			array(
				'Easy',
				'Moderate',
				'Challenging',
			),
			$selected_difficulty
		);
	}
	/**
	 * Group size options for the editor.
	 *
	 * @param int    $post_id           Post ID.
	 * @param string $selected_group_size Current selected group size.
	 * @return array<int, string>
	 */
	private function get_tour_group_size_options_for_edit($post_id, $selected_group_size = '')
	{
		return Metabox_Helper::normalize_select_options(
			(int) $post_id,
			'tourism_group_size_options',
			array(
				'2-6 people',
				'2-12 people',
				'4-16 people',
			),
			$selected_group_size
		);
	}

	/**
	 * Max elevation options for the editor.
	 *
	 * @param int    $post_id               Post ID.
	 * @param string $selected_max_elevation Current selected max elevation.
	 * @return array<int, string>
	 */
	private function get_tour_max_elevation_options_for_edit($post_id, $selected_max_elevation = '')
	{
		return Metabox_Helper::normalize_select_options(
			(int) $post_id,
			'tourism_max_elevation_options',
			array(
				'3,000 m',
				'4,000 m',
				'5,000 m',
			),
			$selected_max_elevation
		);
	}

	/**
	 * Normalize FAQs meta for the editor (array of question/answer rows).
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{question: string, answer: string}>
	 */
	private function get_tour_faqs_for_edit($post_id)
	{
		$raw = get_post_meta($post_id, 'tourism_faqs', true);

		if (is_array($raw)) {
			$out = array();
			foreach ($raw as $row) {
				if (! is_array($row)) {
					continue;
				}
				$out[] = array(
					'question' => isset($row['question']) ? (string) $row['question'] : '',
					'answer'   => isset($row['answer']) ? (string) $row['answer'] : '',
				);
			}
			return $out;
		}

		if (is_string($raw) && $raw !== '') {
			return array(
				array(
					'question' => '',
					'answer'   => $raw,
				),
			);
		}

		return array();
	}

	/**
	 * Normalize a "title + WYSIWYG content" repeater meta for the editor.
	 *
	 * Shared by: Includes, Excludes, Highlights, Pricing Details, What to Expect.
	 * Also understands the legacy shapes that predate this format:
	 *   - plain string rows                -> becomes { title: <string>, content: '' }
	 *   - { label: <string> } rows         -> becomes { title: <label>, content: '' }
	 *   - { description: <string> } rows   -> becomes { title: '', content: <description> }
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key to read.
	 * @return array<int, array{title: string, content: string}>
	 */
	private function get_tour_title_content_items_for_edit($post_id, $meta_key)
	{
		$raw = get_post_meta($post_id, $meta_key, true);
		$out = array();

		if (! is_array($raw)) {
			return $out;
		}

		foreach ($raw as $row) {
			$title = '';
			$content = '';

			if (is_string($row)) {
				// Legacy: plain label string (old includes/excludes format).
				$title = trim($row);
			} elseif (is_array($row)) {
				if (isset($row['title'])) {
					$title = (string) $row['title'];
				}
				if (isset($row['content'])) {
					$content = (string) $row['content'];
				}
				// Legacy: includes/excludes rows stored as { label: ... }.
				if ($title === '' && isset($row['label'])) {
					$title = (string) $row['label'];
				}
				// Legacy: highlight rows stored as { description: ... }.
				if ($content === '' && isset($row['description'])) {
					$content = (string) $row['description'];
				}
			} else {
				continue;
			}

			$title = trim($title);
			if ($title === '' && trim(wp_strip_all_tags($content)) === '') {
				continue;
			}

			$out[] = array(
				'title'   => $title,
				'content' => $content,
			);
		}

		return $out;
	}

	/**
	 * Sanitize a "title + WYSIWYG content" repeater array coming from $_POST.
	 * Shared by: Includes, Excludes, Highlights, Pricing Details, What to Expect.
	 *
	 * @param mixed $items Unslashed $_POST array (or anything else, defensively).
	 * @return array<int, array{title: string, content: string}>
	 */
	private function sanitize_title_content_items($items)
	{
		return Metabox_Helper::sanitize_title_content_items($items);
	}

	/**
	 * Normalize Highlights meta for the editor (array of title/content rows).
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{title: string, content: string}>
	 */
	private function get_tour_highlights_for_edit($post_id)
	{
		return $this->get_tour_title_content_items_for_edit($post_id, 'tourism_highlights');
	}

	/**
	 * Normalize Pricing Details meta for the editor (array of title/content rows).
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{title: string, content: string}>
	 */
	private function get_tour_pricing_details_for_edit($post_id)
	{
		return $this->get_tour_title_content_items_for_edit($post_id, 'tourism_pricing_details');
	}

	/**
	 * Normalize What to Expect meta for the editor (array of title/content rows).
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{title: string, content: string}>
	 */
	private function get_tour_what_to_expect_for_edit($post_id)
	{
		return $this->get_tour_title_content_items_for_edit($post_id, 'tourism_what_to_expect');
	}

	/**
	 * What's Included / Excludes metabox (templates/whats-included-metabox.php).
	 * Saves tourism_includes_new / tourism_excludes_new (read by the theme).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_whats_included($post)
	{
		$includes = get_post_meta($post->ID, 'tourism_includes_new', true);
		$excludes = get_post_meta($post->ID, 'tourism_excludes_new', true);
		if (!is_array($includes)) $includes = array();
		if (!is_array($excludes)) $excludes = array();

		Metabox_Helper::include_template('modules/tours/templates/whats-included-metabox.php', array(
			'includes' => $includes,
			'excludes' => $excludes,
		));
	}


	/**
	 * Pricing Details metabox (templates/pricing-info-metabox.php).
	 * Separate from the "Tour Pricing" metabox (price now/before/discount).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_booking_policy($post)
	{
		wp_enqueue_editor();
		Metabox_Helper::include_template('modules/tours/templates/booking-policy-metabox.php', array(
			'tourism_core_booking_policy' => $this->get_tour_title_content_items_for_edit((int) $post->ID, 'tourism_booking_policy'),
		));
	}

	public function render_pricing_details($post)
	{
		wp_enqueue_editor();

		$tourism_core_pricing_details = $this->get_tour_pricing_details_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/tours/templates/pricing-info-metabox.php', array(
			'tourism_core_pricing_details' => $tourism_core_pricing_details,
		));
	}

	/**
	 * What to Expect metabox (templates/what-to-expect-metabox.php).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_what_to_expect($post)
	{
		wp_enqueue_editor();

		$tourism_core_what_to_expect = $this->get_tour_what_to_expect_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/tours/templates/what-to-expect-metabox.php', array(
			'tourism_core_what_to_expect' => $tourism_core_what_to_expect,
		));
	}

	/**
	 * Normalize what-to-bring items for the editor.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{title: string, content: string}>
	 */
	private function get_tour_what_to_bring_for_edit($post_id)
	{
		// Intentar obtener el nuevo formato (repeater)
		$raw = get_post_meta($post_id, 'tourism_what_to_bring_items', true);

		if (is_array($raw) && !empty($raw)) {
			$out = array();
			foreach ($raw as $row) {
				if (!is_array($row)) {
					continue;
				}
				$out[] = array(
					'title'   => isset($row['title']) ? (string) $row['title'] : '',
					'content' => isset($row['content']) ? (string) $row['content'] : '',
				);
			}
			return $out;
		}

		// Legacy fallback: texto plano en tourism_what_to_bring
		$legacy = get_post_meta($post_id, 'tourism_what_to_bring', true);
		if (is_string($legacy) && trim($legacy) !== '') {
			return array(
				array(
					'title'   => '',
					'content' => $legacy,
				),
			);
		}

		// Vacío: iniciar con un item
		return array(
			array(
				'title'   => '',
				'content' => '',
			),
		);
	}

	/**
	 * Introduction rows for the editor.
	 *
	 * @param int $post_id Post ID.
	 * @return array{title: string, description: string}
	 */
	private function get_tour_introduction_for_edit($post_id)
	{
		return array(
			'title'       => (string) get_post_meta($post_id, 'tourism_intro_title', true),
			'description' => (string) get_post_meta($post_id, 'tourism_intro_description', true),
		);
	}

	/**
	 * Introduction metabox.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_introduction($post)
	{
		wp_enqueue_editor();

		$tourism_core_introduction = $this->get_tour_introduction_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/tours/templates/introduction-metabox.php', array(
			'tourism_core_introduction' => $tourism_core_introduction,
		));
	}

	/**
	 * Pricing data for the editor.
	 *
	 * @param int $post_id Post ID.
	 * @return array{price_now: string, price_before: string, discount_percent: string}
	 */
	private function get_tour_pricing_for_edit($post_id)
	{
		$price_now = get_post_meta($post_id, 'tourism_price_from', true);
		$price_before = get_post_meta($post_id, 'tourism_price_before', true);
		$discount_percent = get_post_meta($post_id, 'tourism_price_discount_percent', true);

		return array(
			'price_now'         => $price_now === '' ? '' : (string) $price_now,
			'price_before'      => $price_before === '' ? '' : (string) $price_before,
			'discount_percent'  => $discount_percent === '' ? '' : (string) $discount_percent,
		);
	}

	/**
	 * Pricing metabox.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_pricing($post)
	{
		$tourism_core_pricing = $this->get_tour_pricing_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/tours/templates/pricing-metabox.php', array(
			'tourism_core_pricing' => $tourism_core_pricing,
		));
	}

	/**
	 * Normalize itinerary rows for the editor.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{day: string, description: string, features: array<int, array{label: string, value: string}>}>
	 */
	private function get_tour_itinerary_for_edit($post_id)
	{
		$raw = get_post_meta($post_id, 'tourism_itinerary_items', true);

		if (is_array($raw)) {
			$out = array();
			foreach ($raw as $row) {
				if (! is_array($row)) {
					continue;
				}

				$features = array();
				if (isset($row['features']) && is_array($row['features'])) {
					foreach ($row['features'] as $feature) {
						if (! is_array($feature)) {
							continue;
						}
						$label = isset($feature['label']) ? trim((string) $feature['label']) : '';
						$value = isset($feature['value']) ? trim((string) $feature['value']) : '';
						if ($label === '' && $value === '') {
							continue;
						}
						$features[] = array(
							'label' => $label,
							'value' => $value,
						);
					}
				}

				$out[] = array(
					'day'         => isset($row['day']) ? (string) $row['day'] : '',
					'description' => isset($row['description']) ? (string) $row['description'] : '',
					'features'    => $features,
				);
			}

			return $out;
		}

		$legacy = get_post_meta($post_id, 'tourism_itinerary', true);
		if (is_string($legacy) && trim($legacy) !== '') {
			return array(
				array(
					'day'         => __('Día 1', TOURISM_CORE_TD),
					'description' => $legacy,
					'features'    => array(),
				),
			);
		}

		return array();
	}

	/**
	 * Itinerary metabox (markup in templates/itinerary-metabox.php).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_itinerary($post)
	{
		wp_enqueue_editor();

		$tourism_core_itinerary_items = $this->get_tour_itinerary_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/tours/templates/itinerary-metabox.php', array(
			'tourism_core_itinerary_items' => $tourism_core_itinerary_items,
		));
	}

	/**
	 * Normalize bring items for editor rows.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{id: int, url: string, title: string, description: string}>
	 */
	private function get_tour_bring_items_for_edit($post_id)
	{
		$raw = get_post_meta($post_id, 'tourism_bring_items', true);

		if (is_array($raw)) {
			$out = array();
			foreach ($raw as $row) {
				if (! is_array($row)) {
					continue;
				}
				$id = isset($row['id']) ? (int) $row['id'] : 0;
				$url = isset($row['url']) ? (string) $row['url'] : '';
				$title = isset($row['title']) ? (string) $row['title'] : '';
				$description = isset($row['description']) ? (string) $row['description'] : '';
				if ($id > 0 && $url === '') {
					$url = (string) wp_get_attachment_url($id);
				}
				if ($id <= 0 && $url === '') {
					continue;
				}
				$out[] = array(
					'id'          => $id,
					'url'         => $url,
					'title'       => $title,
					'description' => $description,
				);
			}
			return $out;
		}

		// Legacy fallback: one text blob -> title-only rows.
		$legacy = get_post_meta($post_id, 'tourism_things_to_carry', true);
		if (is_string($legacy) && $legacy !== '') {
			$out = array();
			$lines = preg_split('/\r\n|\r|\n/', $legacy);
			foreach ((array) $lines as $line) {
				$line = trim((string) $line);
				if ($line !== '') {
					$out[] = array(
						'id'          => 0,
						'url'         => '',
						'title'       => $line,
						'description' => '',
					);
				}
			}
			return $out;
		}

		return array();
	}

	/**
	 * Bring items metabox (templates/bring-metabox.php).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_bring($post)
	{
		$tourism_core_bring_items = $this->get_tour_bring_items_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/tours/templates/bring-metabox.php', array(
			'tourism_core_bring_items' => $tourism_core_bring_items,
		));
	}

	/**
	 * Gallery rows for the editor: id, url, title, caption.
	 * Uses `tourism_gallery_images`; migrates legacy `tourism_gallery_ids` when the new meta is unset.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{id: int, url: string, title: string, caption: string}>
	 */
	private function get_tour_gallery_images_for_edit($post_id)
	{
		$stored = get_post_meta($post_id, 'tourism_gallery_images', true);

		if (is_array($stored)) {
			if ($stored === array()) {
				return array();
			}
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

		$ids_raw = get_post_meta($post_id, 'tourism_gallery_ids', true);
		$ids = is_array($ids_raw) ? $ids_raw : array_filter(array_map('trim', explode(',', (string) $ids_raw)));
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		$out = array();
		foreach ($ids as $aid) {
			if ($aid <= 0) {
				continue;
			}
			$url = (string) wp_get_attachment_url($aid);
			$out[] = array(
				'id'      => $aid,
				'url'     => $url,
				'title'   => (string) get_the_title($aid),
				'caption' => (string) wp_get_attachment_caption($aid),
			);
		}

		return $out;
	}

	/**
	 * Gallery metabox (shared markup in includes/templates/gallery-metabox.php).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_gallery($post)
	{
		$tourism_core_gallery_images = $this->get_tour_gallery_images_for_edit((int) $post->ID);

		Metabox_Helper::include_template('includes/templates/gallery-metabox.php', array(
			'gc_images' => $tourism_core_gallery_images,
			'gc_config' => array(
				'present_key'     => 'tourism_gallery_metabox_present',
				'post_key'        => 'tourism_gallery_images',
				'wrapper_class'   => 'tourism-core-gallery-metabox-container',
				'container_class' => 'tourism-core-gallery-images-container',
				'item_class'      => 'tourism-core-gallery-image-item',
				'field_id_class'  => 'tourism-core-gallery-field-id',
				'field_url_class' => 'tourism-core-gallery-field-url',
				'preview_class'   => 'tourism-core-image-preview',
				'no_image_class'  => 'tourism-core-no-image',
				'actions_class'   => 'tourism-core-image-actions',
				'details_class'   => 'tourism-core-image-details',
				'replace_class'   => 'tourism-core-replace-image',
				'remove_class'    => 'tourism-core-remove-gallery-image',
				'add_id'          => 'tourism-core-add-gallery-image',
				'template_id'     => 'tourism-core-gallery-image-template',
			),
		));
	}

	/**
	 * What to bring metabox (template what-to-bring.php).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_packing($post)
	{
		echo '<h3>' . esc_html__('Lista de equipaje con iconos', TOURISM_CORE_TD) . '</h3>';
		$this->render_bring($post);
		echo '<h3>' . esc_html__('Recomendaciones detalladas', TOURISM_CORE_TD) . '</h3>';
		$this->render_what_to_bring($post);
	}

	public function render_what_to_bring($post)
	{
		wp_enqueue_editor();

		$tourism_core_what_to_bring_items = $this->get_tour_what_to_bring_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/tours/templates/what-to-bring.php', array(
			'tourism_core_what_to_bring_items' => $tourism_core_what_to_bring_items,
		));
	}

	/**
	 * FAQs metabox (markup lives in templates/faqs-metaboxes.php).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_faqs($post)
	{
		wp_enqueue_editor();

		$tourism_core_faqs = $this->get_tour_faqs_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/tours/templates/faqs-metaboxes.php', array(
			'tourism_core_faqs' => $tourism_core_faqs,
		));
	}

	/**
	 * Highlights metabox (markup lives in templates/highlights-metabox.php).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_highlights($post)
	{
		wp_enqueue_editor();

		$tourism_core_highlights = $this->get_tour_highlights_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/tours/templates/highlights-metabox.php', array(
			'tourism_core_highlights' => $tourism_core_highlights,
		));
	}

	/* =========================
	   HERO IMAGE
	========================= */
	public function render_hero($post)
	{
		$tourism_core_tour_hero = array(
			'hero_id' => (int) get_post_meta($post->ID, '_tour_hero_image', true),
		);

		Metabox_Helper::include_template('modules/tours/templates/hero-metabox.php', array(
			'tourism_core_tour_hero' => $tourism_core_tour_hero,
		));
	}

	/**
	 * Manual order value for editor.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	private function get_tour_order_for_edit($post_id)
	{
		$order = (int) get_post_meta($post_id, 'tourism_tour_order', true);
		return $order < 0 ? 0 : $order;
	}

	/**
	 * Manual order metabox.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_order($post)
	{
		$tourism_core_tour_order = $this->get_tour_order_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/tours/templates/order-tour-metabox.php', array(
			'tourism_core_tour_order' => $tourism_core_tour_order,
		));
	}

	/**
	 * WeTravel UUID value for editor.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_tour_wetravel_uuid_for_edit($post_id)
	{
		$uuid = absint(get_post_meta($post_id, '_wetravel_button_code', true));
		return $uuid > 0 ? (string) $uuid : '';
	}

	/**
	 * WeTravel UUID metabox.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_wetravel($post)
	{
		$tourism_core_wetravel_uuid = $this->get_tour_wetravel_uuid_for_edit((int) $post->ID);

		Metabox_Helper::include_template('modules/tours/templates/wetravel-metabox.php', array(
			'tourism_core_wetravel_uuid' => $tourism_core_wetravel_uuid,
		));
	}

	/* =========================
	   SAVE
	========================= */
	public function save($post_id, $post)
	{
		// 1) Quick Edit: allow saving only the manual order field with its own nonce.
		if ($this->maybe_save_quick_edit_order($post_id)) {
			return;
		}

		if (! Metabox_Helper::verify_nonce('tourism_core_tour_nonce', 'tourism_core_save_tour')) {
			return;
		}

		if (! Metabox_Helper::can_save($post_id)) return;

		// SELECT FIELDS + THEIR EDITABLE OPTION LISTS
		$select_fields = array(
			'duration',
			'difficulty',
			'group_size',
			'max_elevation',
		);
		foreach ($select_fields as $key) {
			$field = 'tourism_' . $key;
			if (isset($_POST[$field])) {
				update_post_meta($post_id, $field, sanitize_text_field(wp_unslash($_POST[$field])));
			}
			$options_field = $field . '_options';
			if (isset($_POST[$options_field]) && is_array($_POST[$options_field])) {
				update_post_meta(
					$post_id,
					$options_field,
					Metabox_Helper::sanitize_options_array(wp_unslash($_POST[$options_field]))
				);
			}
		}

		// Introduction (title + description).
		if (Metabox_Helper::metabox_present('tourism_introduction_metabox_present')) {
			$intro_title = isset($_POST['tourism_intro_title']) ? sanitize_text_field(wp_unslash($_POST['tourism_intro_title'])) : '';
			$intro_description = isset($_POST['tourism_intro_description']) ? wp_kses_post(wp_unslash($_POST['tourism_intro_description'])) : '';

			update_post_meta($post_id, 'tourism_intro_title', $intro_title);
			update_post_meta($post_id, 'tourism_intro_description', $intro_description);
		}

		// Pricing (current price + previous price + discount percent).
		if (Metabox_Helper::metabox_present('tourism_pricing_metabox_present')) {
			$price_now_raw = isset($_POST['tourism_price_from']) ? sanitize_text_field(wp_unslash($_POST['tourism_price_from'])) : '';
			$price_before_raw = isset($_POST['tourism_price_before']) ? sanitize_text_field(wp_unslash($_POST['tourism_price_before'])) : '';
			$discount_raw = isset($_POST['tourism_price_discount_percent']) ? sanitize_text_field(wp_unslash($_POST['tourism_price_discount_percent'])) : '';

			$price_now = is_numeric($price_now_raw) ? (float) $price_now_raw : '';
			$price_before = is_numeric($price_before_raw) ? (float) $price_before_raw : '';
			$discount = is_numeric($discount_raw) ? (float) $discount_raw : '';

			if ($discount !== '' && $discount < 0) {
				$discount = 0.0;
			}
			if ($discount !== '' && $discount > 100) {
				$discount = 100.0;
			}

			update_post_meta($post_id, 'tourism_price_from', $price_now);
			update_post_meta($post_id, 'tourism_price_before', $price_before);
			update_post_meta($post_id, 'tourism_price_discount_percent', $discount);
		}

		// Pricing Details (repeater: title + WYSIWYG content).
		if (Metabox_Helper::metabox_present('tourism_booking_policy_metabox_present')) {
			$booking_policy_items = isset($_POST['tourism_booking_policy_items']) ? wp_unslash($_POST['tourism_booking_policy_items']) : array();
			update_post_meta($post_id, 'tourism_booking_policy', $this->sanitize_title_content_items($booking_policy_items));
		}

		if (Metabox_Helper::metabox_present('tourism_pricing_details_metabox_present')) {
			$pricing_details_items = isset($_POST['tourism_pricing_info_items']) ? wp_unslash($_POST['tourism_pricing_info_items']) : array();
			update_post_meta($post_id, 'tourism_pricing_details', $this->sanitize_title_content_items($pricing_details_items));
		}

		// What to Expect (repeater: title + WYSIWYG content).
		if (Metabox_Helper::metabox_present('tourism_what_to_expect_metabox_present')) {
			$what_to_expect_items = isset($_POST['tourism_what_to_expect_items']) ? wp_unslash($_POST['tourism_what_to_expect_items']) : array();
			update_post_meta($post_id, 'tourism_what_to_expect', $this->sanitize_title_content_items($what_to_expect_items));
		}

		// DESTINATIONS
		if (isset($_POST['tourism_destinations'])) {
			$ids = array_map('intval', (array) wp_unslash($_POST['tourism_destinations']));
			update_post_meta($post_id, 'tourism_destinations', array_unique($ids));
		} else {
			update_post_meta($post_id, 'tourism_destinations', []);
		}

		// Gallery (repeater + legacy ID list for themes still using tourism_gallery_ids).
		if (Metabox_Helper::metabox_present('tourism_gallery_metabox_present')) {
			$gallery = Metabox_Helper::sanitize_gallery_post_rows('tourism_gallery_images');
			update_post_meta($post_id, 'tourism_gallery_images', $gallery['rows']);
			update_post_meta($post_id, 'tourism_gallery_ids', $gallery['ids']);
		}

		if (Metabox_Helper::metabox_present('tourism_itinerary_metabox_present')) {
			$itinerary_items = array();
			$legacy_sections = array();

			if (isset($_POST['tourism_itinerary_items']) && is_array($_POST['tourism_itinerary_items'])) {
				foreach (wp_unslash($_POST['tourism_itinerary_items']) as $row) {
					if (! is_array($row)) {
						continue;
					}

					$day = isset($row['day']) ? sanitize_text_field($row['day']) : '';
					$description = isset($row['description']) ? wp_kses_post($row['description']) : '';
					$features = array();

					if (isset($row['features']) && is_array($row['features'])) {
						foreach ($row['features'] as $feature) {
							if (! is_array($feature)) {
								continue;
							}
							$label = isset($feature['label']) ? sanitize_text_field($feature['label']) : '';
							$value = isset($feature['value']) ? sanitize_text_field($feature['value']) : '';
							if ($label === '' && $value === '') {
								continue;
							}
							$features[] = array(
								'label' => $label,
								'value' => $value,
							);
						}
					}

					if ($day === '' && $description === '' && $features === array()) {
						continue;
					}

					$itinerary_items[] = array(
						'day'         => $day,
						'description' => $description,
						'features'    => $features,
					);

					$legacy_chunk = array();
					if ($day !== '') {
						$legacy_chunk[] = $day;
					}
					if ($description !== '') {
						$legacy_chunk[] = $description;
					}
					foreach ($features as $feature) {
						$feature_line = trim($feature['label'] . ': ' . $feature['value'], ': ');
						if ($feature_line !== '') {
							$legacy_chunk[] = $feature_line;
						}
					}
					if ($legacy_chunk !== array()) {
						$legacy_sections[] = implode("\n", $legacy_chunk);
					}
				}
			}

			update_post_meta($post_id, 'tourism_itinerary_items', $itinerary_items);
			update_post_meta($post_id, 'tourism_itinerary', implode("\n\n", $legacy_sections));
		}

		// What to bring (repeater: title + content).
		if (Metabox_Helper::metabox_present('tourism_what_to_bring_metabox_present')) {
			$what_to_bring_items = Metabox_Helper::sanitize_title_content_items(
				isset($_POST['tourism_what_to_bring_items']) ? wp_unslash($_POST['tourism_what_to_bring_items']) : array()
			);

			$legacy_text = '';
			foreach ($what_to_bring_items as $row) {
				if (! empty($row['title'])) {
					$legacy_text .= '<h3>' . $row['title'] . '</h3>' . "\n";
				}
				if (! empty($row['content'])) {
					$legacy_text .= $row['content'] . "\n";
				}
			}

			update_post_meta($post_id, 'tourism_what_to_bring_items', $what_to_bring_items);
			update_post_meta($post_id, 'tourism_what_to_bring', trim($legacy_text));
		}
		// Includes / excludes
		if (Metabox_Helper::metabox_present('tourism_whats_included_metabox_present')) {
			$includes = isset($_POST['tourism_includes']) ? wp_unslash($_POST['tourism_includes']) : array();
			$excludes = isset($_POST['tourism_excludes']) ? wp_unslash($_POST['tourism_excludes']) : array();

			update_post_meta($post_id, 'tourism_includes_new', $this->sanitize_title_content_items($includes));
			update_post_meta($post_id, 'tourism_excludes_new', $this->sanitize_title_content_items($excludes));
		}

		// Bring items (icon image + title + short description).
		if (Metabox_Helper::metabox_present('tourism_bring_metabox_present')) {
			$bring_rows = Metabox_Helper::sanitize_rows(
				isset($_POST['tourism_bring_items']) ? wp_unslash($_POST['tourism_bring_items']) : array(),
				array(
					'id'          => 'absint',
					'url'         => 'esc_url_raw',
					'title'       => 'sanitize_text_field',
					'description' => 'sanitize_textarea_field',
				)
			);
			$legacy_lines = array();
			foreach ($bring_rows as $i => $row) {
				if (! empty($row['id'])) {
					$att_url = wp_get_attachment_url((int) $row['id']);
					if ($att_url) {
						$bring_rows[$i]['url'] = $att_url;
					}
				}
				if (! empty($row['title'])) {
					$legacy_lines[] = $row['title'];
				}
			}
			update_post_meta($post_id, 'tourism_bring_items', $bring_rows);
			update_post_meta($post_id, 'tourism_things_to_carry', implode("\n", $legacy_lines));
		}
		// FAQs (repeater: only when metabox is on the screen — avoids wiping if hidden in Screen Options).
		if (Metabox_Helper::metabox_present('tourism_faqs_metabox_present')) {
			$faq_items = Metabox_Helper::sanitize_rows(
				isset($_POST['tourism_faqs']) ? wp_unslash($_POST['tourism_faqs']) : array(),
				array(
					'question' => 'sanitize_text_field',
					'answer'   => 'wp_kses_post',
				)
			);
			update_post_meta($post_id, 'tourism_faqs', $faq_items);
		}

		// Highlights (repeater: title + WYSIWYG content; only when metabox is on the screen).
		if (Metabox_Helper::metabox_present('tourism_highlights_metabox_present')) {
			$highlights_items = isset($_POST['tourism_highlights_items']) ? wp_unslash($_POST['tourism_highlights_items']) : array();
			update_post_meta($post_id, 'tourism_highlights', $this->sanitize_title_content_items($highlights_items));
		}
		// HERO IMAGE (only when metabox is on the screen — avoids wiping if hidden in Screen Options).
		if (Metabox_Helper::metabox_present('tourism_tour_hero_metabox_present')) {
			$hero_id = isset($_POST['tour_hero_image']) ? (int) wp_unslash($_POST['tour_hero_image']) : 0;
			if ($hero_id < 0) {
				$hero_id = 0;
			}
			update_post_meta($post_id, '_tour_hero_image', $hero_id);
		}

		// Manual order (only when metabox is on the screen — avoids wiping if hidden in Screen Options).
		if (Metabox_Helper::metabox_present('tourism_tour_order_metabox_present')) {
			$order = isset($_POST['tourism_tour_order']) ? (int) wp_unslash($_POST['tourism_tour_order']) : 0;
			if ($order < 0) {
				$order = 0;
			}
			update_post_meta($post_id, 'tourism_tour_order', $order);
		}

		// WeTravel UUID (only when metabox is on the screen — avoids wiping if hidden in Screen Options).
		if (Metabox_Helper::metabox_present('tourism_wetravel_metabox_present')) {
			$uuid = isset($_POST['tourism_wetravel_button_code']) ? absint(wp_unslash($_POST['tourism_wetravel_button_code'])) : 0;
			if ($uuid > 0) {
				update_post_meta($post_id, '_wetravel_button_code', $uuid);
			} else {
				delete_post_meta($post_id, '_wetravel_button_code');
			}
		}
	}

	/* =========================
	   ADMIN JS
	========================= */
	public function enqueue_admin_assets($hook)
	{
		global $post_type;
		if ($post_type !== 'tour') return;

		wp_enqueue_media();
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_editor();

		// CSS
		$css_path = TOURISM_CORE_PATH . 'assets/css/tour-admin-metabox.css';
		$ver_css  = file_exists($css_path) ? (string) filemtime($css_path) : TOURISM_CORE_VERSION;

		wp_enqueue_style(
			'tourism-core-tour-admin',
			TOURISM_CORE_URL . 'assets/css/tour-admin-metabox.css',
			array(),
			$ver_css
		);

		// JS principal
		$js_path = TOURISM_CORE_PATH . 'assets/js/tour-admin-metabox.js';
		$ver_js  = file_exists($js_path) ? (string) filemtime($js_path) : TOURISM_CORE_VERSION;

		wp_enqueue_script(
			'tourism-core-tour-admin',
			TOURISM_CORE_URL . 'assets/js/tour-admin-metabox.js',
			array( 'jquery', 'jquery-ui-sortable', 'media-editor' ),
			$ver_js,
			true
		);

		// Shared gallery behaviour
		wp_enqueue_style( 'tourism-core-tour-editor', TOURISM_CORE_URL . 'assets/css/tour-editor.css', array( 'tourism-core-tour-admin' ), (string) filemtime( TOURISM_CORE_PATH . 'assets/css/tour-editor.css' ) );
		wp_enqueue_script( 'tourism-core-tour-editor', TOURISM_CORE_URL . 'assets/js/tour-editor.js', array( 'jquery', 'tourism-core-tour-admin' ), (string) filemtime( TOURISM_CORE_PATH . 'assets/js/tour-editor.js' ), true );
		wp_localize_script( 'tourism-core-tour-editor', 'TourismCoreEditor', Tourism_Core_Tour_Editor::settings() );
		wp_enqueue_script( 'tourism-core-tour-rich-editor', TOURISM_CORE_URL . 'assets/js/tour-rich-editor.js', array( 'editor' ), (string) filemtime( TOURISM_CORE_PATH . 'assets/js/tour-rich-editor.js' ), true );
		wp_enqueue_script( 'tourism-core-tour-experience-editors', TOURISM_CORE_URL . 'assets/js/tour-experience-editors.js', array( 'jquery', 'jquery-ui-sortable', 'editor', 'tourism-core-tour-rich-editor', 'tourism-core-tour-editor', 'media-editor' ), (string) filemtime( TOURISM_CORE_PATH . 'assets/js/tour-experience-editors.js' ), true );

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
					'containerId'   => 'tourism-core-gallery-images-container',
					'itemClass'     => 'tourism-core-gallery-image-item',
					'fieldIdClass'  => 'tourism-core-gallery-field-id',
					'fieldUrlClass' => 'tourism-core-gallery-field-url',
					'detailsClass'  => 'tourism-core-image-details',
					'previewClass'  => 'tourism-core-image-preview',
					'noImageClass'  => 'tourism-core-no-image',
					'postKey'       => 'tourism_gallery_images',
					'addId'         => 'tourism-core-add-gallery-image',
					'templateId'    => 'tourism-core-gallery-image-template',
					'removeClass'   => 'tourism-core-remove-gallery-image',
					'replaceClass'  => 'tourism-core-replace-image',
				),
			)
		);

		// Cargar automáticamente todos los JS de la carpeta modules/tours/
		// IMPORTANTE: se excluyen los *.min.js del listado base para no
		// enqueue-arlos dos veces (una vez el .js y otra vez el .min.js).
		// Si existe un .min.js y NO estamos en modo debug, se usa ese en
		// su lugar bajo el MISMO handle que el archivo fuente.
		$modules_dir = TOURISM_CORE_PATH . 'assets/js/modules/tours/';
		if (is_dir($modules_dir)) {
			$all_js_files = glob($modules_dir . '*.js');
			$source_files = array_filter((array) $all_js_files, static function ($file) {
				return substr($file, -7) !== '.min.js';
			});

			$use_minified = !(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);

			foreach ($source_files as $file) {
				$filename = basename($file);
				$dependencies = array( 'jquery', 'jquery-ui-sortable', 'editor', 'tourism-core-tour-rich-editor' );
				if ( in_array( $filename, array( 'booking-policy-metabox.js', 'highlights-metabox.js', 'what-to-expect-metabox.js', 'whats-included-metabox.js' ), true ) ) {
					$dependencies[] = 'tourism-core-tour-experience-editors';
				}
				$handle = 'tourism-core-tour-' . sanitize_title(str_replace('.js', '', $filename));

				$min_file = substr($file, 0, -3) . '.min.js';
				$file_to_enqueue = ($use_minified && file_exists($min_file)) ? $min_file : $file;
				$filename_to_enqueue = basename($file_to_enqueue);

				$ver = file_exists($file_to_enqueue) ? (string) filemtime($file_to_enqueue) : TOURISM_CORE_VERSION;

				wp_enqueue_script(
					$handle,
					TOURISM_CORE_URL . 'assets/js/modules/tours/' . $filename_to_enqueue,
					$dependencies,
					$ver,
					true
				);
			}
		}

		// Localize solo los que necesitan traducciones
		wp_enqueue_script( 'tourism-core-tour-repeaters', TOURISM_CORE_URL . 'assets/js/tour-repeaters.js', array( 'jquery', 'tourism-core-tour-editor', 'tourism-core-tour-faqs-metabox', 'tourism-core-tour-highlights-metabox', 'tourism-core-tour-what-to-expect-metabox' ), (string) filemtime( TOURISM_CORE_PATH . 'assets/js/tour-repeaters.js' ), true );

		wp_localize_script(
			'tourism-core-tour-faqs-metabox',
			'TourismCoreTourFaqs',
			array(
				'deleteConfirm' => __( '¿Quieres eliminar esta pregunta?', TOURISM_CORE_TD ),
			)
		);

		wp_localize_script(
			'tourism-core-tour-itinerary-metabox',
			'TourismCoreTourItinerary',
			array(
				'deleteConfirm' => __( '¿Quieres eliminar este día?', TOURISM_CORE_TD ),
			)
		);

		wp_localize_script(
			'tourism-core-tour-admin',
			'TourismCoreTourAdmin',
			array(
				'selectDuration'     => __( 'Selecciona una duración', TOURISM_CORE_TD ),
				'selectDifficulty'   => __( 'Selecciona una dificultad', TOURISM_CORE_TD ),
				'selectGroupSize'    => __( 'Selecciona el tamaño del grupo', TOURISM_CORE_TD ),
				'selectMaxElevation' => __( 'Selecciona la altitud máxima', TOURISM_CORE_TD ),
				'exampleFullDay'     => __( 'Ejemplo: día completo', TOURISM_CORE_TD ),
				'exampleModerate'    => __( 'Ejemplo: moderada', TOURISM_CORE_TD ),
				'exampleGroupSize'   => __( 'Ejemplo: 2–12 personas', TOURISM_CORE_TD ),
				'exampleMaxElevation'=> __( 'Ejemplo: 5.200 m', TOURISM_CORE_TD ),
				'deleteLabel'        => __( 'Eliminar', TOURISM_CORE_TD ),
				'heroMediaTitle'     => __( 'Selecciona la portada', TOURISM_CORE_TD ),
				'heroMediaButton'    => __( 'Usar imagen', TOURISM_CORE_TD ),
				'galleryMediaTitle'  => __( 'Selecciona una imagen', TOURISM_CORE_TD ),
				'galleryMediaButton' => __( 'Usar imagen', TOURISM_CORE_TD ),
				'bringMediaTitle'    => __( 'Selecciona un icono', TOURISM_CORE_TD ),
				'bringMediaButton'   => __( 'Usar imagen', TOURISM_CORE_TD ),
			)
		);
	}

}
