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
		$tourism_core_tour_hero = array(
			'hero_id' => (int) get_post_meta($post->ID, '_tour_hero_image', true),
		);

		$template = TOURISM_CORE_PATH . 'modules/tours/templates/hero-metabox.php';
		if (is_readable($template)) {
			include $template;
		}
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
		// HERO IMAGE (only when metabox is on the screen — avoids wiping if hidden in Screen Options).
		if (isset($_POST['tourism_tour_hero_metabox_present'])) {
			$hero_id = isset($_POST['tour_hero_image']) ? (int) wp_unslash($_POST['tour_hero_image']) : 0;
			if ($hero_id < 0) {
				$hero_id = 0;
			}
			update_post_meta($post_id, '_tour_hero_image', $hero_id);
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

		$css_path = TOURISM_CORE_PATH . 'assets/css/tour-admin-metabox.css';
		$js_path  = TOURISM_CORE_PATH . 'assets/js/tour-admin-metabox.js';
		$ver_css  = file_exists($css_path) ? (string) filemtime($css_path) : TOURISM_CORE_VERSION;
		$ver_js   = file_exists($js_path) ? (string) filemtime($js_path) : TOURISM_CORE_VERSION;

		wp_enqueue_style(
			'tourism-core-tour-admin',
			TOURISM_CORE_URL . 'assets/css/tour-admin-metabox.css',
			array(),
			$ver_css
		);

		wp_enqueue_script(
			'tourism-core-tour-admin',
			TOURISM_CORE_URL . 'assets/js/tour-admin-metabox.js',
			array( 'jquery', 'jquery-ui-sortable', 'media-editor' ),
			$ver_js,
			true
		);

		wp_localize_script(
			'tourism-core-tour-admin',
			'TourismCoreTourAdmin',
			array(
				'selectDuration'     => __( 'Select a duration', TOURISM_CORE_TD ),
				'selectDifficulty'   => __( 'Select a difficulty', TOURISM_CORE_TD ),
				'exampleFullDay'     => __( 'Example: Full day', TOURISM_CORE_TD ),
				'exampleModerate'    => __( 'Example: Moderate', TOURISM_CORE_TD ),
				'deleteLabel'        => __( 'Delete', TOURISM_CORE_TD ),
				'heroMediaTitle'     => __( 'Select Hero Image', TOURISM_CORE_TD ),
				'heroMediaButton'    => __( 'Use image', TOURISM_CORE_TD ),
				'galleryMediaTitle'  => __( 'Select image', TOURISM_CORE_TD ),
				'galleryMediaButton' => __( 'Use image', TOURISM_CORE_TD ),
				'bringMediaTitle'    => __( 'Select icon image', TOURISM_CORE_TD ),
				'bringMediaButton'   => __( 'Use image', TOURISM_CORE_TD ),
			)
		);
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