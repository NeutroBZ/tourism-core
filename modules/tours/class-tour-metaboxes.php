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
		$gallery_ids_raw = (array) get_post_meta($post->ID, 'tourism_gallery_ids', true);
		$gallery_ids = array_values(array_filter(array_map('intval', $gallery_ids_raw)));
		$itinerary = get_post_meta($post->ID, 'tourism_itinerary', true);
		$includes = get_post_meta($post->ID, 'tourism_includes', true);
		$excludes = get_post_meta($post->ID, 'tourism_excludes', true);
		$things_to_carry = get_post_meta($post->ID, 'tourism_things_to_carry', true);
		$faqs = get_post_meta($post->ID, 'tourism_faqs', true);
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

		<hr />
		<p><strong><?php esc_html_e('Photo Gallery', TOURISM_CORE_TD); ?></strong></p>
		<div id="tour-gallery-preview" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px;">
			<?php foreach ($gallery_ids as $gallery_id) : ?>
				<?php
				$thumb = wp_get_attachment_image($gallery_id, 'thumbnail');
				if (! empty($thumb)) {
					echo wp_kses_post($thumb);
				}
				?>
			<?php endforeach; ?>
		</div>
		<input type="hidden" id="tour_gallery_ids" name="tourism_gallery_ids" value="<?php echo esc_attr(implode(',', $gallery_ids)); ?>" />
		<p>
			<button type="button" class="button tourism-upload-gallery"><?php esc_html_e('Select Gallery Images', TOURISM_CORE_TD); ?></button>
			<button type="button" class="button tourism-remove-gallery"><?php esc_html_e('Clear Gallery', TOURISM_CORE_TD); ?></button>
		</p>

		<hr />
		<p>
			<label for="tourism_itinerary"><strong><?php esc_html_e('Itinerary', TOURISM_CORE_TD); ?></strong></label>
			<textarea id="tourism_itinerary" name="tourism_itinerary" rows="5" style="width:100%;"><?php echo esc_textarea($itinerary); ?></textarea>
		</p>
		<p>
			<label for="tourism_includes"><strong><?php esc_html_e('What includes', TOURISM_CORE_TD); ?></strong></label>
			<textarea id="tourism_includes" name="tourism_includes" rows="4" style="width:100%;"><?php echo esc_textarea($includes); ?></textarea>
		</p>
		<p>
			<label for="tourism_excludes"><strong><?php esc_html_e('What does not include', TOURISM_CORE_TD); ?></strong></label>
			<textarea id="tourism_excludes" name="tourism_excludes" rows="4" style="width:100%;"><?php echo esc_textarea($excludes); ?></textarea>
		</p>
		<p>
			<label for="tourism_things_to_carry"><strong><?php esc_html_e('Things to carry', TOURISM_CORE_TD); ?></strong></label>
			<textarea id="tourism_things_to_carry" name="tourism_things_to_carry" rows="4" style="width:100%;"><?php echo esc_textarea($things_to_carry); ?></textarea>
		</p>
		<p>
			<label for="tourism_faqs"><strong><?php esc_html_e('FAQs', TOURISM_CORE_TD); ?></strong></label>
			<textarea id="tourism_faqs" name="tourism_faqs" rows="5" style="width:100%;"><?php echo esc_textarea($faqs); ?></textarea>
		</p>
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

		// GALLERY
		if (isset($_POST['tourism_gallery_ids'])) {
			$gallery_ids_raw = sanitize_text_field(wp_unslash($_POST['tourism_gallery_ids']));
			$parts = array_filter(array_map('trim', explode(',', $gallery_ids_raw)));
			$gallery_ids = array_values(array_unique(array_filter(array_map('intval', $parts))));
			update_post_meta($post_id, 'tourism_gallery_ids', $gallery_ids);
		} else {
			update_post_meta($post_id, 'tourism_gallery_ids', []);
		}

		if (isset($_POST['tourism_itinerary'])) {
			update_post_meta($post_id, 'tourism_itinerary', sanitize_textarea_field(wp_unslash($_POST['tourism_itinerary'])));
		}
		if (isset($_POST['tourism_includes'])) {
			update_post_meta($post_id, 'tourism_includes', sanitize_textarea_field(wp_unslash($_POST['tourism_includes'])));
		}
		if (isset($_POST['tourism_excludes'])) {
			update_post_meta($post_id, 'tourism_excludes', sanitize_textarea_field(wp_unslash($_POST['tourism_excludes'])));
		}
		if (isset($_POST['tourism_things_to_carry'])) {
			update_post_meta($post_id, 'tourism_things_to_carry', sanitize_textarea_field(wp_unslash($_POST['tourism_things_to_carry'])));
		}
		if (isset($_POST['tourism_faqs'])) {
			update_post_meta($post_id, 'tourism_faqs', sanitize_textarea_field(wp_unslash($_POST['tourism_faqs'])));
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

		wp_add_inline_script('jquery', "
			jQuery(document).ready(function($){
				let frame;
				let galleryFrame;

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

				$('.tourism-upload-gallery').on('click', function(e){
					e.preventDefault();

					if(galleryFrame){
						galleryFrame.open();
						return;
					}

					galleryFrame = wp.media({
						title: 'Select Gallery Images',
						button: { text: 'Use images' },
						multiple: true
					});

					galleryFrame.on('select', function(){
						const selection = galleryFrame.state().get('selection');
						const ids = [];
						let html = '';

						selection.each(function(item){
							const attachment = item.toJSON();
							ids.push(attachment.id);
							const thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
							html += '<img src=\"'+thumb+'\" style=\"width:80px;height:80px;object-fit:cover;border:1px solid #ddd;\" />';
						});

						$('#tour_gallery_ids').val(ids.join(','));
						$('#tour-gallery-preview').html(html);
					});

					galleryFrame.open();
				});

				$('.tourism-remove-gallery').on('click', function(){
					$('#tour_gallery_ids').val('');
					$('#tour-gallery-preview').html('');
				});
			});
		");
	}
}