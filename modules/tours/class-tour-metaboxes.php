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
	}

	/* =========================
	   MAIN FIELDS
	========================= */
	public function render($post)
	{
		wp_nonce_field('tourism_core_save_tour', 'tourism_core_tour_nonce');

		$price    = get_post_meta($post->ID, 'tourism_price_from', true);
		$duration = get_post_meta($post->ID, 'tourism_duration', true);
		$dest_ids = (array) get_post_meta($post->ID, 'tourism_destinations', true);
		$dest_ids = array_map('intval', $dest_ids);
		$reviews = get_post_meta($post->ID, 'tourism_reviews', true);
		$route = get_post_meta($post->ID, 'tourism_route', true);

		$destinations = get_posts([
			'post_type'      => 'destination',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
			'no_found_rows'  => true,
		]);
?>

		<p>
			<label><strong><?php esc_html_e('Price from', TOURISM_CORE_TD); ?></strong></label>
			<input type="number" step="0.01" min="0" name="tourism_price_from"
				value="<?php echo esc_attr($price); ?>" style="width:100%;" />
		</p>

		<p>
			<label><strong><?php esc_html_e('Duration', TOURISM_CORE_TD); ?></strong></label>
			<input type="text" name="tourism_duration"
				value="<?php echo esc_attr($duration); ?>" style="width:100%;" />
		</p>

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

		<p>
			<label for="tourism_reviews"><strong><?php esc_html_e('Reviews', TOURISM_CORE_TD); ?></strong></label>
			<textarea id="tourism_reviews" name="tourism_reviews" rows="5" style="width:100%;"><?php echo esc_textarea($reviews); ?></textarea>
		</p>
		<p>
			<label for="tourism_route"><strong><?php esc_html_e('Route', TOURISM_CORE_TD); ?></strong></label>
			<textarea id="tourism_route" name="tourism_route" rows="4" style="width:100%;"><?php echo esc_textarea($route); ?></textarea>
		</p>

<?php
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

	/* =========================
	   SAVE
	========================= */
	public function save($post_id, $post)
	{
		if (! isset($_POST['tourism_core_tour_nonce']) ||
			! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tourism_core_tour_nonce'])), 'tourism_core_save_tour')) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (! current_user_can('edit_post', $post_id)) return;

		// PRICE
		if (isset($_POST['tourism_price_from'])) {
			$raw = sanitize_text_field(wp_unslash($_POST['tourism_price_from']));
			update_post_meta($post_id, 'tourism_price_from', is_numeric($raw) ? (float) $raw : '');
		}

		// DURATION
		if (isset($_POST['tourism_duration'])) {
			update_post_meta($post_id, 'tourism_duration', sanitize_text_field(wp_unslash($_POST['tourism_duration'])));
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
		if (isset($_POST['tourism_reviews'])) {
			update_post_meta($post_id, 'tourism_reviews', sanitize_textarea_field(wp_unslash($_POST['tourism_reviews'])));
		}
		if (isset($_POST['tourism_route'])) {
			update_post_meta($post_id, 'tourism_route', sanitize_textarea_field(wp_unslash($_POST['tourism_route'])));
		}

		// HERO IMAGE
		if (isset($_POST['tour_hero_image'])) {
			update_post_meta($post_id, '_tour_hero_image', (int) $_POST['tour_hero_image']);
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
			});
		");
	}
}