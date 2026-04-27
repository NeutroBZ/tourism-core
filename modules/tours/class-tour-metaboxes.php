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
			});
		");
	}
}