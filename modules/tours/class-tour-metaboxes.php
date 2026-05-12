<?php

if (! defined('ABSPATH')) {
	exit;
}

class Tourism_Core_Tour_Metaboxes
{
	public function register(Tourism_Core_Loader $loader)
	{
		$loader->add_action('add_meta_boxes', $this, 'add_meta_boxes');
		$loader->add_action('save_post_tour', $this, 'save', 10, 2);
		$loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');

		// Admin list table column for manual ordering.
		$loader->add_filter('manage_tour_posts_columns', $this, 'add_order_column');
		$loader->add_action('manage_tour_posts_custom_column', $this, 'render_order_column', 10, 2);
		$loader->add_filter('manage_edit-tour_sortable_columns', $this, 'make_order_column_sortable');
		$loader->add_action('pre_get_posts', $this, 'apply_admin_order_sorting');

		// Quick edit support for manual ordering.
		$loader->add_action('quick_edit_custom_box', $this, 'render_quick_edit_order_field', 10, 2);
	}

	public function add_order_column($columns)
	{
		if (! is_array($columns)) {
			return $columns;
		}

		$new_columns = array();
		foreach ($columns as $key => $label) {
			$new_columns[$key] = $label;
			if ($key === 'title') {
				$new_columns['tourism_tour_order'] = __('Order', TOURISM_CORE_TD);
			}
		}

		if (! isset($new_columns['tourism_tour_order'])) {
			$new_columns['tourism_tour_order'] = __('Order', TOURISM_CORE_TD);
		}

		return $new_columns;
	}

	public function render_order_column($column, $post_id)
	{
		if ($column !== 'tourism_tour_order') {
			return;
		}

		$value = (int) get_post_meta((int) $post_id, 'tourism_tour_order', true);
		echo esc_html((string) $value);
	}

	public function make_order_column_sortable($columns)
	{
		if (! is_array($columns)) {
			return $columns;
		}

		$columns['tourism_tour_order'] = 'tourism_tour_order';
		return $columns;
	}

	public function apply_admin_order_sorting($query)
	{
		if (! $query instanceof WP_Query) {
			return;
		}

		if (! is_admin() || ! $query->is_main_query()) {
			return;
		}

		$screen_post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
		if ($screen_post_type !== 'tour') {
			return;
		}

		$orderby = $query->get('orderby');
		if ($orderby !== 'tourism_tour_order') {
			return;
		}

		$query->set('meta_key', 'tourism_tour_order');
		$query->set('orderby', 'meta_value_num');
	}

	public function add_meta_boxes()
	{
		// MAIN BOX
		add_meta_box(
			'tourism-core-tour-details',
			__('Tour Details', TOURISM_CORE_TD),
			array($this, 'render'),
			'tour',
			'normal',
			'default'
		);

		// HERO IMAGE BOX
		add_meta_box(
			'tourism-core-tour-hero',
			__('Hero Image', TOURISM_CORE_TD),
			array($this, 'render_hero'),
			'tour',
			'normal',
			'high'
		);

		// Photo gallery (modular template)
		add_meta_box(
			'tourism-core-tour-gallery',
			__('Photo Gallery', TOURISM_CORE_TD),
			array($this, 'render_gallery'),
			'tour',
			'normal',
			'default'
		);

		// Includes / not included (modular template)
		add_meta_box(
			'tourism-core-tour-includes',
			__('Included & not included', TOURISM_CORE_TD),
			array($this, 'render_includes'),
			'tour',
			'normal',
			'default'
		);

		// Introduction (title + description)
		add_meta_box(
			'tourism-core-tour-introduction',
			__('Tour Introduction', TOURISM_CORE_TD),
			array($this, 'render_introduction'),
			'tour',
			'normal',
			'default'
		);

		// Pricing (current + previous + discount)
		add_meta_box(
			'tourism-core-tour-pricing',
			__('Tour Pricing', TOURISM_CORE_TD),
			array($this, 'render_pricing'),
			'tour',
			'normal',
			'default'
		);

		// Itinerary (days + description + dynamic features)
		add_meta_box(
			'tourism-core-tour-itinerary',
			__('Tour Itinerary', TOURISM_CORE_TD),
			array($this, 'render_itinerary'),
			'tour',
			'normal',
			'default'
		);

		// What to bring (icon + title + short description)
		add_meta_box(
			'tourism-core-tour-bring',
			__('What to bring', TOURISM_CORE_TD),
			array($this, 'render_bring'),
			'tour',
			'normal',
			'default'
		);

		// FAQs (modular template)
		add_meta_box(
			'tourism-core-tour-faqs',
			__('Tour FAQs', TOURISM_CORE_TD),
			array($this, 'render_faqs'),
			'tour',
			'normal',
			'default'
		);

		// Highlights (modular template)
		add_meta_box(
			'tourism-core-tour-highlights',
			__('Tour Highlights', TOURISM_CORE_TD),
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
					<label><strong><?php esc_html_e('Duration', TOURISM_CORE_TD); ?></strong></label>
					<select name="tourism_duration" id="tourism_duration" style="width:100%;">
						<option value=""><?php esc_html_e('Select a duration', TOURISM_CORE_TD); ?></option>
						<?php foreach ($duration_options as $duration_option) : ?>
							<option value="<?php echo esc_attr($duration_option); ?>" <?php selected($duration, $duration_option); ?>>
								<?php echo esc_html($duration_option); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<div class="tourism-core-duration-builder">
					<p><strong><?php esc_html_e('Duration options', TOURISM_CORE_TD); ?></strong></p>
					<div id="tourism-core-duration-options-list">
						<?php foreach ($duration_options as $duration_index => $duration_option) : ?>
							<div class="tourism-core-duration-option-row">
								<input
									type="text"
									class="widefat tourism-core-duration-option-input"
									name="tourism_duration_options[<?php echo esc_attr((string) $duration_index); ?>]"
									value="<?php echo esc_attr($duration_option); ?>"
									placeholder="<?php esc_attr_e('Example: Full day', TOURISM_CORE_TD); ?>"
								/>
								<button type="button" class="button tourism-core-delete-duration-option"><?php esc_html_e('Delete', TOURISM_CORE_TD); ?></button>
							</div>
						<?php endforeach; ?>
					</div>
					<p>
						<button type="button" id="tourism-core-add-duration-option" class="button button-secondary"><?php esc_html_e('Add option', TOURISM_CORE_TD); ?></button>
					</p>
				</div>
			</div>

			<div class="tourism-core-detail-column">
				<p>
					<label><strong><?php esc_html_e('Difficulty', TOURISM_CORE_TD); ?></strong></label>
					<select name="tourism_difficulty" id="tourism_difficulty" style="width:100%;">
						<option value=""><?php esc_html_e('Select a difficulty', TOURISM_CORE_TD); ?></option>
						<?php foreach ($difficulty_options as $difficulty_option) : ?>
							<option value="<?php echo esc_attr($difficulty_option); ?>" <?php selected($difficulty, $difficulty_option); ?>>
								<?php echo esc_html($difficulty_option); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<div class="tourism-core-difficulty-builder">
					<p><strong><?php esc_html_e('Difficulty options', TOURISM_CORE_TD); ?></strong></p>
					<div id="tourism-core-difficulty-options-list">
						<?php foreach ($difficulty_options as $difficulty_index => $difficulty_option) : ?>
							<div class="tourism-core-difficulty-option-row">
								<input
									type="text"
									class="widefat tourism-core-difficulty-option-input"
									name="tourism_difficulty_options[<?php echo esc_attr((string) $difficulty_index); ?>]"
									value="<?php echo esc_attr($difficulty_option); ?>"
									placeholder="<?php esc_attr_e('Example: Moderate', TOURISM_CORE_TD); ?>"
								/>
								<button type="button" class="button tourism-core-delete-difficulty-option"><?php esc_html_e('Delete', TOURISM_CORE_TD); ?></button>
							</div>
						<?php endforeach; ?>
					</div>
					<p>
						<button type="button" id="tourism-core-add-difficulty-option" class="button button-secondary"><?php esc_html_e('Add option', TOURISM_CORE_TD); ?></button>
					</p>
				</div>
			</div>
		</div>

		<p><strong><?php esc_html_e('Destinations', TOURISM_CORE_TD); ?></strong></p>

		<div style="max-height:150px; overflow:auto; border:1px solid #ddd; padding:8px;">
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
		$defaults = array(
			'Full day',
			'Half day',
			'2 Days',
		);

		$stored = get_post_meta($post_id, 'tourism_duration_options', true);
		$options = array();

		if (is_array($stored)) {
			foreach ($stored as $option) {
				if (! is_string($option)) {
					continue;
				}
				$option = trim($option);
				if ($option !== '') {
					$options[] = $option;
				}
			}
		}

		if ($options === array()) {
			$options = $defaults;
		}

		if (is_string($selected_duration) && trim($selected_duration) !== '' && ! in_array(trim($selected_duration), $options, true)) {
			$options[] = trim($selected_duration);
		}

		return array_values(array_unique($options));
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
		$defaults = array(
			'Easy',
			'Moderate',
			'Challenging',
		);

		$stored = get_post_meta($post_id, 'tourism_difficulty_options', true);
		$options = array();

		if (is_array($stored)) {
			foreach ($stored as $option) {
				if (! is_string($option)) {
					continue;
				}
				$option = trim($option);
				if ($option !== '') {
					$options[] = $option;
				}
			}
		}

		if ($options === array()) {
			$options = $defaults;
		}

		if (is_string($selected_difficulty) && trim($selected_difficulty) !== '' && ! in_array(trim($selected_difficulty), $options, true)) {
			$options[] = trim($selected_difficulty);
		}

		return array_values(array_unique($options));
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
	 * Normalize Highlights meta for the editor (array of description rows).
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array{description: string}>
	 */
	private function get_tour_highlights_for_edit($post_id)
	{
		$raw = get_post_meta($post_id, 'tourism_highlights', true);

		if (is_array($raw)) {
			$out = array();
			foreach ($raw as $row) {
				if (! is_array($row)) {
					continue;
				}
				$out[] = array(
					'description' => isset($row['description']) ? (string) $row['description'] : '',
				);
			}
			return $out;
		}

		if (is_string($raw) && trim($raw) !== '') {
			return array(
				array(
					'description' => $raw,
				),
			);
		}

		return array();
	}

	/**
	 * Normalize a list meta for the editor: array of label strings, or legacy textarea (by lines).
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key tourism_includes | tourism_excludes.
	 * @return array<int, string>
	 */
	private function get_tour_label_list_for_edit($post_id, $meta_key)
	{
		$raw = get_post_meta($post_id, $meta_key, true);

		if (is_array($raw)) {
			$out = array();
			foreach ($raw as $x) {
				if (is_string($x)) {
					$t = trim($x);
					if ($t !== '') {
						$out[] = $t;
					}
				} elseif (is_array($x) && isset($x['label'])) {
					$t = trim((string) $x['label']);
					if ($t !== '') {
						$out[] = $t;
					}
				}
			}
			return $out;
		}

		if (is_string($raw) && $raw !== '') {
			$lines = preg_split('/\r\n|\r|\n/', $raw);
			$out = array();
			foreach ($lines as $line) {
				$line = trim($line);
				if ($line !== '') {
					$out[] = $line;
				}
			}
			return $out;
		}

		return array();
	}

	/**
	 * Includes / excludes metabox (templates/includes-metabox.php).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_includes($post)
	{
		$post_id = (int) $post->ID;
		$tourism_core_includes_list = $this->get_tour_label_list_for_edit($post_id, 'tourism_includes');
		$tourism_core_excludes_list = $this->get_tour_label_list_for_edit($post_id, 'tourism_excludes');

		$template = TOURISM_CORE_PATH . 'modules/tours/templates/includes-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
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
		$tourism_core_introduction = $this->get_tour_introduction_for_edit((int) $post->ID);

		$template = TOURISM_CORE_PATH . 'modules/tours/templates/introduction-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
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

		$template = TOURISM_CORE_PATH . 'modules/tours/templates/pricing-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
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
					'day'         => __('Day 1', TOURISM_CORE_TD),
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
		$tourism_core_itinerary_items = $this->get_tour_itinerary_for_edit((int) $post->ID);

		$template = TOURISM_CORE_PATH . 'modules/tours/templates/itinerary-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
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

		$template = TOURISM_CORE_PATH . 'modules/tours/templates/bring-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
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
	 * Gallery metabox (markup in templates/gallery-metabox.php).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_gallery($post)
	{
		$tourism_core_gallery_images = $this->get_tour_gallery_images_for_edit((int) $post->ID);

		$template = TOURISM_CORE_PATH . 'modules/tours/templates/gallery-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
	}

	/**
	 * FAQs metabox (markup lives in templates/faqs-metaboxes.php).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_faqs($post)
	{
		$tourism_core_faqs = $this->get_tour_faqs_for_edit((int) $post->ID);

		$template = TOURISM_CORE_PATH . 'modules/tours/templates/faqs-metaboxes.php';
		if (is_readable($template)) {
			include $template;
		}
	}

	/**
	 * Highlights metabox (markup lives in templates/highlights-metabox.php).
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_highlights($post)
	{
		$tourism_core_highlights = $this->get_tour_highlights_for_edit((int) $post->ID);

		$template = TOURISM_CORE_PATH . 'modules/tours/templates/highlights-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
	}

	/* =========================
	   HERO IMAGE
	========================= */
	public function render_hero($post)
	{
		$hero_id = get_post_meta($post->ID, '_tour_hero_image', true);
		$image   = $hero_id ? wp_get_attachment_image($hero_id, 'medium') : '';
?>

		<div>
			<div id="tour-hero-preview" style="margin-bottom:10px;">
				<?php echo $image; ?>
			</div>

			<input type="hidden" id="tour_hero_image" name="tour_hero_image" value="<?php echo esc_attr($hero_id); ?>" />

			<button type="button" class="button tourism-upload-hero">
				<?php esc_html_e('Select Image', TOURISM_CORE_TD); ?>
			</button>

			<button type="button" class="button tourism-remove-hero">
				<?php esc_html_e('Remove', TOURISM_CORE_TD); ?>
			</button>
		</div>

<?php
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

		$template = TOURISM_CORE_PATH . 'modules/tours/templates/order-tour-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
	}

	/* =========================
	   SAVE
	========================= */
	public function save($post_id, $post)
	{
		// 1) Quick Edit: allow saving only the manual order field with its own nonce.
		if (isset($_POST['tourism_core_tour_quickedit_nonce']) &&
			wp_verify_nonce(
				sanitize_text_field(wp_unslash($_POST['tourism_core_tour_quickedit_nonce'])),
				'tourism_core_quickedit_tour'
			)
		) {
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
			if (! current_user_can('edit_post', $post_id)) return;

			if (isset($_POST['tourism_tour_order'])) {
				$order = (int) wp_unslash($_POST['tourism_tour_order']);
				if ($order < 0) {
					$order = 0;
				}
				update_post_meta($post_id, 'tourism_tour_order', $order);
			}
			return;
		}

		if (! isset($_POST['tourism_core_tour_nonce']) ||
			! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tourism_core_tour_nonce'])), 'tourism_core_save_tour')) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (! current_user_can('edit_post', $post_id)) return;

		// DURATION
		if (isset($_POST['tourism_duration'])) {
			update_post_meta($post_id, 'tourism_duration', sanitize_text_field(wp_unslash($_POST['tourism_duration'])));
		}
		if (isset($_POST['tourism_duration_options']) && is_array($_POST['tourism_duration_options'])) {
			$duration_options = array();
			foreach (wp_unslash($_POST['tourism_duration_options']) as $option) {
				$option = sanitize_text_field((string) $option);
				if ($option !== '') {
					$duration_options[] = $option;
				}
			}
			update_post_meta($post_id, 'tourism_duration_options', array_values(array_unique($duration_options)));
		}

		// DIFFICULTY
		if (isset($_POST['tourism_difficulty'])) {
			update_post_meta($post_id, 'tourism_difficulty', sanitize_text_field(wp_unslash($_POST['tourism_difficulty'])));
		}
		if (isset($_POST['tourism_difficulty_options']) && is_array($_POST['tourism_difficulty_options'])) {
			$difficulty_options = array();
			foreach (wp_unslash($_POST['tourism_difficulty_options']) as $option) {
				$option = sanitize_text_field((string) $option);
				if ($option !== '') {
					$difficulty_options[] = $option;
				}
			}
			update_post_meta($post_id, 'tourism_difficulty_options', array_values(array_unique($difficulty_options)));
		}

		// Introduction (title + description).
		if (isset($_POST['tourism_introduction_metabox_present'])) {
			$intro_title = isset($_POST['tourism_intro_title']) ? sanitize_text_field(wp_unslash($_POST['tourism_intro_title'])) : '';
			$intro_description = isset($_POST['tourism_intro_description']) ? sanitize_textarea_field(wp_unslash($_POST['tourism_intro_description'])) : '';

			update_post_meta($post_id, 'tourism_intro_title', $intro_title);
			update_post_meta($post_id, 'tourism_intro_description', $intro_description);
		}

		// Pricing (current price + previous price + discount percent).
		if (isset($_POST['tourism_pricing_metabox_present'])) {
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

		// DESTINATIONS
		if (isset($_POST['tourism_destinations'])) {
			$ids = array_map('intval', (array) wp_unslash($_POST['tourism_destinations']));
			update_post_meta($post_id, 'tourism_destinations', array_unique($ids));
		} else {
			update_post_meta($post_id, 'tourism_destinations', []);
		}

		// Gallery (repeater + legacy ID list for themes still using tourism_gallery_ids).
		if (isset($_POST['tourism_gallery_metabox_present'])) {
			$gallery_rows = array();
			$sync_ids = array();
			if (isset($_POST['tourism_gallery_images']) && is_array($_POST['tourism_gallery_images'])) {
				foreach (wp_unslash($_POST['tourism_gallery_images']) as $row) {
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
			update_post_meta($post_id, 'tourism_gallery_images', $gallery_rows);
			update_post_meta($post_id, 'tourism_gallery_ids', array_values(array_unique(array_filter($sync_ids))));
		}

		if (isset($_POST['tourism_itinerary_metabox_present'])) {
			$itinerary_items = array();
			$legacy_sections = array();

			if (isset($_POST['tourism_itinerary_items']) && is_array($_POST['tourism_itinerary_items'])) {
				foreach (wp_unslash($_POST['tourism_itinerary_items']) as $row) {
					if (! is_array($row)) {
						continue;
					}

					$day = isset($row['day']) ? sanitize_text_field($row['day']) : '';
					$description = isset($row['description']) ? sanitize_textarea_field($row['description']) : '';
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

		// Includes / excludes (repeaters; only when metabox is visible).
		if (isset($_POST['tourism_includes_excludes_metabox_present'])) {
			$includes_out = array();
			if (isset($_POST['tourism_includes_list']) && is_array($_POST['tourism_includes_list'])) {
				foreach (wp_unslash($_POST['tourism_includes_list']) as $row) {
					if (! is_array($row)) {
						continue;
					}
					$label = isset($row['label']) ? sanitize_text_field($row['label']) : '';
					if ($label !== '') {
						$includes_out[] = $label;
					}
				}
			}
			$excludes_out = array();
			if (isset($_POST['tourism_excludes_list']) && is_array($_POST['tourism_excludes_list'])) {
				foreach (wp_unslash($_POST['tourism_excludes_list']) as $row) {
					if (! is_array($row)) {
						continue;
					}
					$label = isset($row['label']) ? sanitize_text_field($row['label']) : '';
					if ($label !== '') {
						$excludes_out[] = $label;
					}
				}
			}
			update_post_meta($post_id, 'tourism_includes', $includes_out);
			update_post_meta($post_id, 'tourism_excludes', $excludes_out);
		}

		// Bring items (icon image + title + short description).
		if (isset($_POST['tourism_bring_metabox_present'])) {
			$bring_rows = array();
			$legacy_lines = array();
			if (isset($_POST['tourism_bring_items']) && is_array($_POST['tourism_bring_items'])) {
				foreach (wp_unslash($_POST['tourism_bring_items']) as $row) {
					if (! is_array($row)) {
						continue;
					}
					$bid = isset($row['id']) ? (int) $row['id'] : 0;
					$burl = isset($row['url']) ? esc_url_raw($row['url']) : '';
					$btitle = isset($row['title']) ? sanitize_text_field($row['title']) : '';
					$bdesc = isset($row['description']) ? sanitize_textarea_field($row['description']) : '';
					if ($bid <= 0 && $burl === '' && $btitle === '' && $bdesc === '') {
						continue;
					}
					if ($bid > 0) {
						$att_url = wp_get_attachment_url($bid);
						if ($att_url) {
							$burl = $att_url;
						}
					}
					$bring_rows[] = array(
						'id'          => $bid,
						'url'         => $burl,
						'title'       => $btitle,
						'description' => $bdesc,
					);
					if ($btitle !== '') {
						$legacy_lines[] = $btitle;
					}
				}
			}
			update_post_meta($post_id, 'tourism_bring_items', $bring_rows);
			update_post_meta($post_id, 'tourism_things_to_carry', implode("\n", $legacy_lines));
		}
		// FAQs (repeater: only when metabox is on the screen — avoids wiping if hidden in Screen Options).
		if (isset($_POST['tourism_faqs_metabox_present'])) {
			$faq_items = array();
			if (isset($_POST['tourism_faqs']) && is_array($_POST['tourism_faqs'])) {
				foreach (wp_unslash($_POST['tourism_faqs']) as $row) {
					if (! is_array($row)) {
						continue;
					}
					$question = isset($row['question']) ? sanitize_text_field($row['question']) : '';
					$answer   = isset($row['answer']) ? sanitize_textarea_field($row['answer']) : '';
					if ($question === '' && $answer === '') {
						continue;
					}
					$faq_items[] = array(
						'question' => $question,
						'answer'   => $answer,
					);
				}
			}
			update_post_meta($post_id, 'tourism_faqs', $faq_items);
		}

		// Highlights (repeater: only when metabox is on the screen — avoids wiping if hidden in Screen Options).
		if (isset($_POST['tourism_highlights_metabox_present'])) {
			$highlight_items = array();
			if (isset($_POST['tourism_highlights']) && is_array($_POST['tourism_highlights'])) {
				foreach (wp_unslash($_POST['tourism_highlights']) as $row) {
					if (! is_array($row)) {
						continue;
					}
					$description = isset($row['description']) ? sanitize_textarea_field($row['description']) : '';
					if ($description === '') {
						continue;
					}
					$highlight_items[] = array(
						'description' => $description,
					);
				}
			}
			update_post_meta($post_id, 'tourism_highlights', $highlight_items);
		}
		// HERO IMAGE
		if (isset($_POST['tour_hero_image'])) {
			update_post_meta($post_id, '_tour_hero_image', (int) $_POST['tour_hero_image']);
		}

		// Manual order (only when metabox is on the screen — avoids wiping if hidden in Screen Options).
		if (isset($_POST['tourism_tour_order_metabox_present'])) {
			$order = isset($_POST['tourism_tour_order']) ? (int) wp_unslash($_POST['tourism_tour_order']) : 0;
			if ($order < 0) {
				$order = 0;
			}
			update_post_meta($post_id, 'tourism_tour_order', $order);
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

		wp_add_inline_style(
			'wp-admin',
			'
			#tourism-core-faqs-container .tourism-core-faq-item{border:1px solid #c3c4c7;background:#fff;margin-bottom:10px;}
			#tourism-core-faqs-container .tourism-core-faq-header{display:flex;align-items:center;gap:8px;padding:8px 10px;border-bottom:1px solid #c3c4c7;background:#f6f7f7;}
			#tourism-core-faqs-container .tourism-core-faq-header h4{margin:0;flex:1;font-size:13px;}
			#tourism-core-faqs-container .tourism-core-handle{cursor:move;color:#787c82;}
			#tourism-core-faqs-container .tourism-core-faq-content{padding:10px;}
			#tourism-core-highlights-container .tourism-core-highlight-item{border:1px solid #c3c4c7;background:#fff;margin-bottom:10px;}
			#tourism-core-highlights-container .tourism-core-highlight-header{display:flex;align-items:center;gap:8px;padding:8px 10px;border-bottom:1px solid #c3c4c7;background:#f6f7f7;}
			#tourism-core-highlights-container .tourism-core-highlight-header h4{margin:0;flex:1;font-size:13px;}
			#tourism-core-highlights-container .tourism-core-handle{cursor:move;color:#787c82;}
			#tourism-core-highlights-container .tourism-core-highlight-content{padding:10px;}
			.tourism-core-gallery-metabox-container{margin-top:4px;}
			#tourism-core-gallery-images-container{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:12px;}
			.tourism-core-gallery-image-item{border:1px solid #c3c4c7;background:#fff;padding:10px;}
			.tourism-core-image-preview{position:relative;aspect-ratio:4/3;background:#f0f0f1;border:1px solid #dcdcde;margin-bottom:10px;overflow:hidden;}
			.tourism-core-image-preview img{display:block;width:100%;height:100%;object-fit:cover;}
			.tourism-core-no-image{display:flex;align-items:center;justify-content:center;height:100%;min-height:120px;color:#646970;font-size:12px;text-align:center;padding:8px;}
			.tourism-core-image-actions{position:absolute;right:6px;top:6px;z-index:2;display:flex;gap:4px;}
			.tourism-core-image-actions .button{min-width:32px;padding:0 6px;}
			.tourism-core-image-details label{display:block;margin:6px 0 2px;font-weight:600;}
			.tourism-core-image-details input,.tourism-core-image-details textarea{width:100%;}
			.tourism-core-includes-metabox-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;}
			@media (max-width:782px){.tourism-core-includes-metabox-grid{grid-template-columns:1fr;}}
			.tourism-core-includes-heading{margin:0 0 10px;font-size:13px;}
			.tourism-core-label-sortable{min-height:8px;}
			.tourism-core-label-item{border:1px solid #c3c4c7;background:#fff;margin-bottom:8px;}
			.tourism-core-label-row-header{display:flex;align-items:center;gap:8px;padding:6px 8px;border-bottom:1px solid #c3c4c7;background:#f6f7f7;}
			.tourism-core-label-row-header .tourism-core-handle{cursor:move;color:#787c82;}
			.tourism-core-label-item-title{flex:1;font-size:12px;font-weight:600;}
			.tourism-core-label-row-body{padding:8px;}
			.tourism-core-introduction-fields label,
			.tourism-core-pricing-fields label{display:block;margin:0 0 4px;font-weight:600;}
			.tourism-core-pricing-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:10px;}
			@media (max-width:782px){.tourism-core-pricing-grid{grid-template-columns:1fr;}}
			.tourism-core-pricing-help{margin:8px 0 0;color:#646970;font-size:12px;}
			.tourism-core-details-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;}
			@media (max-width:782px){.tourism-core-details-grid{grid-template-columns:1fr;}}
			.tourism-core-detail-column p:first-child{margin-top:0;}
			.tourism-core-duration-builder{margin:10px 0 14px;}
			#tourism-core-duration-options-list{display:grid;gap:8px;margin-top:8px;}
			.tourism-core-duration-option-row{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center;}
			@media (max-width:782px){.tourism-core-duration-option-row{grid-template-columns:1fr;}}
			.tourism-core-difficulty-builder{margin:10px 0 14px;}
			#tourism-core-difficulty-options-list{display:grid;gap:8px;margin-top:8px;}
			.tourism-core-difficulty-option-row{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center;}
			@media (max-width:782px){.tourism-core-difficulty-option-row{grid-template-columns:1fr;}}
			#tourism-core-itinerary-container{display:grid;gap:12px;margin-bottom:12px;}
			.tourism-core-itinerary-item{border:1px solid #c3c4c7;background:#fff;}
			.tourism-core-itinerary-header{display:flex;align-items:center;gap:8px;padding:8px 10px;border-bottom:1px solid #c3c4c7;background:#f6f7f7;}
			.tourism-core-itinerary-header .tourism-core-handle{cursor:move;color:#787c82;}
			.tourism-core-itinerary-header h4{margin:0;flex:1;font-size:13px;}
			.tourism-core-itinerary-body{padding:10px;}
			.tourism-core-itinerary-body > p{margin-top:0;}
			.tourism-core-itinerary-feature-list{display:grid;gap:8px;margin:10px 0;}
			.tourism-core-itinerary-feature-row{display:grid;grid-template-columns:minmax(140px,180px) 1fr auto;gap:8px;align-items:center;}
			.tourism-core-itinerary-feature-row .button{white-space:nowrap;}
			@media (max-width:782px){.tourism-core-itinerary-feature-row{grid-template-columns:1fr;}}
			#tourism-core-bring-items-container{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-bottom:12px;}
			.tourism-core-bring-item{border:1px solid #c3c4c7;background:#fff;padding:8px;}
			.tourism-core-bring-preview{position:relative;aspect-ratio:1/1;background:#f0f0f1;border:1px solid #dcdcde;margin-bottom:8px;overflow:hidden;}
			.tourism-core-bring-preview img{display:block;width:100%;height:100%;object-fit:contain;padding:8px;}
			.tourism-core-bring-actions{position:absolute;right:4px;top:4px;z-index:2;display:flex;gap:4px;}
			.tourism-core-bring-actions .button{min-width:30px;padding:0 5px;}
			.tourism-core-bring-fields label{display:block;margin:5px 0 2px;font-size:12px;font-weight:600;}
			'
		);

		$tourism_core_i18n_select_duration = esc_js(__('Select a duration', TOURISM_CORE_TD));
		$tourism_core_i18n_select_difficulty = esc_js(__('Select a difficulty', TOURISM_CORE_TD));
		$tourism_core_i18n_example_full_day = esc_js(__('Example: Full day', TOURISM_CORE_TD));
		$tourism_core_i18n_example_moderate = esc_js(__('Example: Moderate', TOURISM_CORE_TD));
		$tourism_core_i18n_delete = esc_js(__('Delete', TOURISM_CORE_TD)); 

		wp_add_inline_script('jquery', "
			jQuery(document).ready(function($){
				let frame;

				$('.tourism-upload-hero').on('click', function(e){
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
						$('#tour_hero_image').val(attachment.id);
						$('#tour-hero-preview').html('<img src=\"'+attachment.sizes.medium.url+'\" style=\"width:100%;\" />');
					});

					frame.open();
				});

				$('.tourism-remove-hero').on('click', function(){
					$('#tour_hero_image').val('');
					$('#tour-hero-preview').html('');
				});

				function tourismCoreSyncPricingBeforePrice(){
					const \$priceNow = $('#tourism_price_from');
					const \$discount = $('#tourism_price_discount_percent');
					const \$priceBefore = $('#tourism_price_before');
					if(!\$priceNow.length || !\$discount.length || !\$priceBefore.length) return;

					const now = parseFloat(\$priceNow.val());
					const discount = parseFloat(\$discount.val());

					if(isNaN(now) || isNaN(discount) || discount <= 0 || discount >= 100){
						if(!isNaN(now) && (isNaN(discount) || discount <= 0)){
							\$priceBefore.val(now.toFixed(2));
						}
						return;
					}

					const before = now / (1 - (discount / 100));
					if(isFinite(before)){
						\$priceBefore.val(before.toFixed(2));
					}
				}

				$(document).on('input change', '#tourism_price_from, #tourism_price_discount_percent', function(){
					tourismCoreSyncPricingBeforePrice();
				});

				$(document).on('click', '#tourism-core-calc-price-before', function(e){
					e.preventDefault();
					tourismCoreSyncPricingBeforePrice();
				});

				function tourismCoreRefreshDurationSelect(){
					const \$select = $('#tourism_duration');
					const \$rows = $('#tourism-core-duration-options-list .tourism-core-duration-option-input');
					if(!\$select.length) return;

					const currentValue = \$select.val();
					let optionsHtml = '<option value=\"\">{$tourism_core_i18n_select_duration}</option>';

					\$rows.each(function(){
						const value = $.trim($(this).val());
						if(!value) return;
						const safeValue = $('<div>').text(value).html();
						const selected = value === currentValue ? ' selected' : '';
						optionsHtml += '<option value=\"'+safeValue+'\"'+selected+'>'+safeValue+'</option>';
					});

					\$select.html(optionsHtml);

					if(currentValue){
						\$select.val(currentValue);
					}
				}

				function tourismCoreRenumberDurationOptions(){
					$('#tourism-core-duration-options-list .tourism-core-duration-option-row').each(function(i){
						$(this).find('.tourism-core-duration-option-input').attr('name', 'tourism_duration_options['+i+']');
					});
					tourismCoreRefreshDurationSelect();
				}

				function tourismCoreRefreshDifficultySelect(){
					const \$select = $('#tourism_difficulty');
					const \$rows = $('#tourism-core-difficulty-options-list .tourism-core-difficulty-option-input');
					if(!\$select.length) return;

					const currentValue = \$select.val();
					let optionsHtml = '<option value=\"\">{$tourism_core_i18n_select_difficulty}</option>';

					\$rows.each(function(){
						const value = $.trim($(this).val());
						if(!value) return;
						const safeValue = $('<div>').text(value).html();
						const selected = value === currentValue ? ' selected' : '';
						optionsHtml += '<option value=\"'+safeValue+'\"'+selected+'>'+safeValue+'</option>';
					});

					\$select.html(optionsHtml);

					if(currentValue){
						\$select.val(currentValue);
					}
				}

				function tourismCoreRenumberDifficultyOptions(){
					$('#tourism-core-difficulty-options-list .tourism-core-difficulty-option-row').each(function(i){
						$(this).find('.tourism-core-difficulty-option-input').attr('name', 'tourism_difficulty_options['+i+']');
					});
					tourismCoreRefreshDifficultySelect();
				}

				$(document).on('click', '#tourism-core-add-duration-option', function(e){
					e.preventDefault();
					$('#tourism-core-duration-options-list').append(
						'<div class=\"tourism-core-duration-option-row\">' +
							'<input type=\"text\" class=\"widefat tourism-core-duration-option-input\" value=\"\" placeholder=\"{$tourism_core_i18n_example_full_day}\" />' +
							'<button type=\"button\" class=\"button tourism-core-delete-duration-option\">{$tourism_core_i18n_delete}</button>' +
						'</div>'
					);
					tourismCoreRenumberDurationOptions();
				});

				$(document).on('click', '.tourism-core-delete-duration-option', function(e){
					e.preventDefault();
					$(this).closest('.tourism-core-duration-option-row').remove();
					tourismCoreRenumberDurationOptions();
				});

				$(document).on('input change', '.tourism-core-duration-option-input', function(){
					tourismCoreRefreshDurationSelect();
				});

				tourismCoreRenumberDurationOptions();

				$(document).on('click', '#tourism-core-add-difficulty-option', function(e){
					e.preventDefault();
					$('#tourism-core-difficulty-options-list').append(
						'<div class=\"tourism-core-difficulty-option-row\">' +
							'<input type=\"text\" class=\"widefat tourism-core-difficulty-option-input\" value=\"\" placeholder=\"{$tourism_core_i18n_example_moderate}\" />' +
							'<button type=\"button\" class=\"button tourism-core-delete-difficulty-option\">{$tourism_core_i18n_delete}</button>' +
						'</div>'
					);
					tourismCoreRenumberDifficultyOptions();
				});

				$(document).on('click', '.tourism-core-delete-difficulty-option', function(e){
					e.preventDefault();
					$(this).closest('.tourism-core-difficulty-option-row').remove();
					tourismCoreRenumberDifficultyOptions();
				});

				$(document).on('input change', '.tourism-core-difficulty-option-input', function(){
					tourismCoreRefreshDifficultySelect();
				});

				tourismCoreRenumberDifficultyOptions();

				function tourismCoreRenumberGallery(){
					const \$g = $('#tourism-core-gallery-images-container');
					if(!\$g.length) return;
					\$g.children('.tourism-core-gallery-image-item').each(function(i){
						const \$item = $(this);
						\$item.find('.tourism-core-gallery-field-id').attr('name', 'tourism_gallery_images['+i+'][id]');
						\$item.find('.tourism-core-gallery-field-url').attr('name', 'tourism_gallery_images['+i+'][url]');
						\$item.find('.tourism-core-image-details input[type=\"text\"]').attr('name', 'tourism_gallery_images['+i+'][title]');
						\$item.find('.tourism-core-image-details textarea').attr('name', 'tourism_gallery_images['+i+'][caption]');
					});
				}

				const \$galleryContainer = $('#tourism-core-gallery-images-container');
				if(\$galleryContainer.length){
					\$galleryContainer.sortable({
						items: '> .tourism-core-gallery-image-item',
						cancel: 'input,textarea,button,select,option',
						update: function(){
							tourismCoreRenumberGallery();
						}
					});

					$(document).on('click', '#tourism-core-add-gallery-image', function(e){
						e.preventDefault();
						const \$tpl = $('#tourism-core-gallery-image-template').children('.tourism-core-gallery-image-item').first();
						if(!\$tpl.length) return;
						const \$clone = \$tpl.clone(true, true);
						\$galleryContainer.append(\$clone);
						tourismCoreRenumberGallery();
					});

					$(document).on('click', '.tourism-core-remove-gallery-image', function(e){
						e.preventDefault();
						$(this).closest('.tourism-core-gallery-image-item').remove();
						tourismCoreRenumberGallery();
					});

					$(document).on('click', '.tourism-core-replace-image', function(e){
						e.preventDefault();
						const \$item = $(this).closest('.tourism-core-gallery-image-item');
						const media = wp.media({
							title: 'Select image',
							button: { text: 'Use image' },
							multiple: false
						});
						media.on('select', function(){
							const att = media.state().get('selection').first().toJSON();
							const url = (att.sizes && att.sizes.medium && att.sizes.medium.url) ? att.sizes.medium.url : att.url;
							\$item.find('.tourism-core-gallery-field-id').val(String(att.id));
							\$item.find('.tourism-core-gallery-field-url').val(att.url);
							\$item.find('.tourism-core-image-preview .tourism-core-no-image').remove();
							\$item.find('.tourism-core-image-preview img').remove();
							\$item.find('.tourism-core-image-preview').prepend('<img src=\"'+url+'\" alt=\"\" />');
						});
						media.open();
					});
				}

				function tourismCoreRenumberFaqs(){
					const \$c = $('#tourism-core-faqs-container');
					if(!\$c.length) return;
					\$c.children('.tourism-core-faq-item').each(function(i){
						const \$item = $(this);
						\$item.attr('data-tourism-core-index', String(i));
						\$item.find('.tourism-core-item-number').text(String(i + 1));
						\$item.find('input[name^=\"tourism_faqs[\"]').attr('name', 'tourism_faqs['+i+'][question]');
						\$item.find('textarea[name^=\"tourism_faqs[\"]').attr('name', 'tourism_faqs['+i+'][answer]');
						\$item.find('label[for^=\"tourism_core_question_\"]').attr('for', 'tourism_core_question_'+i);
						\$item.find('input[id^=\"tourism_core_question_\"]').attr('id', 'tourism_core_question_'+i);
						\$item.find('label[for^=\"tourism_core_answer_\"]').attr('for', 'tourism_core_answer_'+i);
						\$item.find('textarea[id^=\"tourism_core_answer_\"]').attr('id', 'tourism_core_answer_'+i);
					});
				}

				function tourismCoreRenumberHighlights(){
					const \$c = $('#tourism-core-highlights-container');
					if(!\$c.length) return;
					\$c.children('.tourism-core-highlight-item').each(function(i){
						const \$item = $(this);
						\$item.attr('data-tourism-core-index', String(i));
						\$item.find('.tourism-core-item-number').text(String(i + 1));
						\$item.find('textarea[name^=\"tourism_highlights[\"]').attr('name', 'tourism_highlights['+i+'][description]');
						\$item.find('label[for^=\"tourism_core_highlight_description_\"]').attr('for', 'tourism_core_highlight_description_'+i);
						\$item.find('textarea[id^=\"tourism_core_highlight_description_\"]').attr('id', 'tourism_core_highlight_description_'+i);
					});
				}

				function tourismCoreRenumberItinerary(){
					const \$c = $('#tourism-core-itinerary-container');
					if(!\$c.length) return;
					\$c.children('.tourism-core-itinerary-item').each(function(i){
						const \$item = $(this);
						\$item.attr('data-tourism-core-index', String(i));
						\$item.find('.tourism-core-item-number').text(String(i + 1));
						\$item.find('.tourism-core-itinerary-day').attr('name', 'tourism_itinerary_items['+i+'][day]').attr('id', 'tourism_core_itinerary_day_'+i);
						\$item.find('.tourism-core-itinerary-description').attr('name', 'tourism_itinerary_items['+i+'][description]').attr('id', 'tourism_core_itinerary_description_'+i);
						\$item.find('label[for^=\"tourism_core_itinerary_day_\"]').attr('for', 'tourism_core_itinerary_day_'+i);
						\$item.find('label[for^=\"tourism_core_itinerary_description_\"]').attr('for', 'tourism_core_itinerary_description_'+i);

						\$item.find('.tourism-core-itinerary-feature-row').each(function(j){
							const \$feature = $(this);
							\$feature.find('.tourism-core-itinerary-feature-label')
								.attr('name', 'tourism_itinerary_items['+i+'][features]['+j+'][label]')
								.attr('id', 'tourism_core_itinerary_feature_label_'+i+'_'+j);
							\$feature.find('.tourism-core-itinerary-feature-value')
								.attr('name', 'tourism_itinerary_items['+i+'][features]['+j+'][value]')
								.attr('id', 'tourism_core_itinerary_feature_value_'+i+'_'+j);
						});
					});
				}

				function tourismCoreRenumberIncludes(){
					const \$c = $('#tourism-core-includes-container');
					if(!\$c.length) return;
					\$c.children('.tourism-core-include-item').each(function(i){
						const \$item = $(this);
						\$item.find('.tourism-core-label-item-num').text(String(i + 1));
						\$item.find('.tourism-core-include-label-input').attr('name', 'tourism_includes_list['+i+'][label]');
						\$item.find('label[for^=\"tourism_include_label_\"]').attr('for', 'tourism_include_label_'+i);
						\$item.find('input.tourism-core-include-label-input').attr('id', 'tourism_include_label_'+i);
					});
				}

				function tourismCoreRenumberExcludes(){
					const \$c = $('#tourism-core-excludes-container');
					if(!\$c.length) return;
					\$c.children('.tourism-core-exclude-item').each(function(i){
						const \$item = $(this);
						\$item.find('.tourism-core-label-item-num').text(String(i + 1));
						\$item.find('.tourism-core-exclude-label-input').attr('name', 'tourism_excludes_list['+i+'][label]');
						\$item.find('label[for^=\"tourism_exclude_label_\"]').attr('for', 'tourism_exclude_label_'+i);
						\$item.find('input.tourism-core-exclude-label-input').attr('id', 'tourism_exclude_label_'+i);
					});
				}

				const \$incContainer = $('#tourism-core-includes-container');
				if(\$incContainer.length){
					\$incContainer.sortable({
						handle: '.tourism-core-handle',
						items: '> .tourism-core-include-item',
						cancel: 'input,textarea,button,select,option',
						update: function(){ tourismCoreRenumberIncludes(); }
					});
					$(document).on('click', '#tourism-core-add-include', function(e){
						e.preventDefault();
						const tmpl = $('#tourism-core-tmpl-include-item').html();
						if(!tmpl) return;
						const idx = \$incContainer.children('.tourism-core-include-item').length;
						const html = tmpl
							.split('{{tourism_index}}').join(String(idx))
							.split('{{tourism_index_plus_one}}').join(String(idx + 1));
						\$incContainer.append(html);
						tourismCoreRenumberIncludes();
					});
					$(document).on('click', '.tourism-core-delete-include', function(e){
						e.preventDefault();
						$(this).closest('.tourism-core-include-item').remove();
						tourismCoreRenumberIncludes();
					});
				}

				const \$excContainer = $('#tourism-core-excludes-container');
				if(\$excContainer.length){
					\$excContainer.sortable({
						handle: '.tourism-core-handle',
						items: '> .tourism-core-exclude-item',
						cancel: 'input,textarea,button,select,option',
						update: function(){ tourismCoreRenumberExcludes(); }
					});
					$(document).on('click', '#tourism-core-add-exclude', function(e){
						e.preventDefault();
						const tmpl = $('#tourism-core-tmpl-exclude-item').html();
						if(!tmpl) return;
						const idx = \$excContainer.children('.tourism-core-exclude-item').length;
						const html = tmpl
							.split('{{tourism_index}}').join(String(idx))
							.split('{{tourism_index_plus_one}}').join(String(idx + 1));
						\$excContainer.append(html);
						tourismCoreRenumberExcludes();
					});
					$(document).on('click', '.tourism-core-delete-exclude', function(e){
						e.preventDefault();
						$(this).closest('.tourism-core-exclude-item').remove();
						tourismCoreRenumberExcludes();
					});
				}

				const \$faqContainer = $('#tourism-core-faqs-container');
				if(\$faqContainer.length){
					\$faqContainer.sortable({
						handle: '.tourism-core-handle',
						items: '> .tourism-core-faq-item',
						update: function(){
							tourismCoreRenumberFaqs();
						}
					});

					$(document).on('click', '#tourism-core-add-faq', function(e){
						e.preventDefault();
						const tmpl = $('#tourism-core-tmpl-faq-item').html();
						if(!tmpl) return;
						const idx = \$faqContainer.children('.tourism-core-faq-item').length;
						const html = tmpl
							.split('{{tourism_index}}').join(String(idx))
							.split('{{tourism_index_plus_one}}').join(String(idx + 1));
						\$faqContainer.append(html);
						tourismCoreRenumberFaqs();
					});

					$(document).on('click', '.tourism-core-delete-faq', function(e){
						e.preventDefault();
						$(this).closest('.tourism-core-faq-item').remove();
						tourismCoreRenumberFaqs();
					});
				}

				const \$highlightsContainer = $('#tourism-core-highlights-container');
				if(\$highlightsContainer.length){
					\$highlightsContainer.sortable({
						handle: '.tourism-core-handle',
						items: '> .tourism-core-highlight-item',
						cancel: 'input,textarea,button,select,option',
						update: function(){
							tourismCoreRenumberHighlights();
						}
					});

					$(document).on('click', '#tourism-core-add-highlight', function(e){
						e.preventDefault();
						const tmpl = $('#tourism-core-tmpl-highlight-item').html();
						if(!tmpl) return;
						const idx = \$highlightsContainer.children('.tourism-core-highlight-item').length;
						const html = tmpl
							.split('{{tourism_index}}').join(String(idx))
							.split('{{tourism_index_plus_one}}').join(String(idx + 1));
						\$highlightsContainer.append(html);
						tourismCoreRenumberHighlights();
					});

					$(document).on('click', '.tourism-core-delete-highlight', function(e){
						e.preventDefault();
						$(this).closest('.tourism-core-highlight-item').remove();
						tourismCoreRenumberHighlights();
					});

					tourismCoreRenumberHighlights();
				}

				const \$itineraryContainer = $('#tourism-core-itinerary-container');
				if(\$itineraryContainer.length){
					\$itineraryContainer.sortable({
						handle: '.tourism-core-handle',
						items: '> .tourism-core-itinerary-item',
						cancel: 'input,textarea,button,select,option',
						update: function(){ tourismCoreRenumberItinerary(); }
					});

					$(document).on('click', '#tourism-core-add-itinerary-item', function(e){
						e.preventDefault();
						const tmpl = $('#tourism-core-tmpl-itinerary-item').html();
						if(!tmpl) return;
						const idx = \$itineraryContainer.children('.tourism-core-itinerary-item').length;
						const html = tmpl
							.split('{{tourism_index}}').join(String(idx))
							.split('{{tourism_index_plus_one}}').join(String(idx + 1));
						\$itineraryContainer.append(html);
						tourismCoreRenumberItinerary();
					});

					$(document).on('click', '.tourism-core-delete-itinerary', function(e){
						e.preventDefault();
						$(this).closest('.tourism-core-itinerary-item').remove();
						tourismCoreRenumberItinerary();
					});

					$(document).on('click', '.tourism-core-add-itinerary-feature', function(e){
						e.preventDefault();
						const \$item = $(this).closest('.tourism-core-itinerary-item');
						const tmpl = $('#tourism-core-tmpl-itinerary-feature').html();
						if(!tmpl) return;
						\$item.find('.tourism-core-itinerary-feature-list').append(tmpl);
						tourismCoreRenumberItinerary();
					});

					$(document).on('click', '.tourism-core-delete-itinerary-feature', function(e){
						e.preventDefault();
						$(this).closest('.tourism-core-itinerary-feature-row').remove();
						tourismCoreRenumberItinerary();
					});

					tourismCoreRenumberItinerary();
				}

				function tourismCoreRenumberBring(){
					const \$c = $('#tourism-core-bring-items-container');
					if(!\$c.length) return;
					\$c.children('.tourism-core-bring-item').each(function(i){
						const \$item = $(this);
						\$item.find('.tourism-core-bring-field-id').attr('name', 'tourism_bring_items['+i+'][id]');
						\$item.find('.tourism-core-bring-field-url').attr('name', 'tourism_bring_items['+i+'][url]');
						\$item.find('.tourism-core-bring-field-title').attr('name', 'tourism_bring_items['+i+'][title]').attr('id', 'tourism_bring_title_'+i);
						\$item.find('.tourism-core-bring-field-description').attr('name', 'tourism_bring_items['+i+'][description]').attr('id', 'tourism_bring_description_'+i);
						\$item.find('label[for^=\"tourism_bring_title_\"]').attr('for', 'tourism_bring_title_'+i);
						\$item.find('label[for^=\"tourism_bring_description_\"]').attr('for', 'tourism_bring_description_'+i);
					});
				}

				const \$bringContainer = $('#tourism-core-bring-items-container');
				if(\$bringContainer.length){
					\$bringContainer.sortable({
						items: '> .tourism-core-bring-item',
						cancel: 'input,textarea,button,select,option',
						update: function(){ tourismCoreRenumberBring(); }
					});

					$(document).on('click', '#tourism-core-add-bring-item', function(e){
						e.preventDefault();
						const \$tpl = $('#tourism-core-bring-item-template').children('.tourism-core-bring-item').first();
						if(!\$tpl.length) return;
						const \$clone = \$tpl.clone(true, true);
						\$bringContainer.append(\$clone);
						tourismCoreRenumberBring();
					});

					$(document).on('click', '.tourism-core-remove-bring-item', function(e){
						e.preventDefault();
						$(this).closest('.tourism-core-bring-item').remove();
						tourismCoreRenumberBring();
					});

					$(document).on('click', '.tourism-core-replace-bring-image', function(e){
						e.preventDefault();
						const \$item = $(this).closest('.tourism-core-bring-item');
						const media = wp.media({
							title: 'Select icon image',
							button: { text: 'Use image' },
							library: { type: 'image' },
							multiple: false
						});
						media.on('select', function(){
							const att = media.state().get('selection').first().toJSON();
							const url = (att.sizes && att.sizes.thumbnail && att.sizes.thumbnail.url) ? att.sizes.thumbnail.url : att.url;
							\$item.find('.tourism-core-bring-field-id').val(String(att.id));
							\$item.find('.tourism-core-bring-field-url').val(att.url);
							\$item.find('.tourism-core-no-image').remove();
							\$item.find('.tourism-core-bring-preview img').remove();
							\$item.find('.tourism-core-bring-preview').prepend('<img src=\"'+url+'\" alt=\"\" />');
						});
						media.open();
					});
				}

				// Quick edit: populate manual order field from the list table column.
				if (typeof inlineEditPost !== 'undefined') {
					const originalEdit = inlineEditPost.edit;
					inlineEditPost.edit = function(id){
						originalEdit.apply(this, arguments);
						let postId = 0;
						if (typeof id === 'object' && id !== null) {
							postId = parseInt(this.getId(id), 10);
						} else {
							postId = parseInt(id, 10);
						}
						if (!postId) return;

						const \$row = $('#post-' + postId);
						const \$editRow = $('#edit-' + postId);
						if (!\$row.length || !\$editRow.length) return;

						const current = $.trim(\$row.find('td.column-tourism_tour_order').text());
						\$editRow.find('input[name=\"tourism_tour_order\"]').val(current !== '' ? parseInt(current, 10) : 0);
					};
				}
			});
		");
	}

	public function render_quick_edit_order_field($column_name, $post_type)
	{
		if ($post_type !== 'tour' || $column_name !== 'tourism_tour_order') {
			return;
		}
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<?php wp_nonce_field('tourism_core_quickedit_tour', 'tourism_core_tour_quickedit_nonce'); ?>
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e('Orden', TOURISM_CORE_TD); ?></span>
					<input type="number" min="0" step="1" name="tourism_tour_order" value="" />
				</label>
			</div>
		</fieldset>
		<?php
	}
}