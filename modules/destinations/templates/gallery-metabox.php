<?php
/**
 * Destination gallery metabox (sortable repeater with per-image title & caption).
 *
 * Expected variable:
 * - array $tourism_core_destination_gallery_images List of [ 'id' => int, 'url' => string, 'title' => string, 'caption' => string ].
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_destination_gallery_images = isset($tourism_core_destination_gallery_images) && is_array($tourism_core_destination_gallery_images) ? $tourism_core_destination_gallery_images : array();
?>

<input type="hidden" name="tourism_destination_gallery_metabox_present" value="1" />

<div class="tourism-core-destination-gallery-metabox-container">
	<div id="tourism-core-destination-gallery-images-container" class="tourism-core-sortable-gallery">
		<?php foreach ($tourism_core_destination_gallery_images as $tourism_core_index => $tourism_core_image) : ?>
			<?php
			$tourism_core_image = is_array($tourism_core_image) ? $tourism_core_image : array();
			$tourism_core_img_id = isset($tourism_core_image['id']) ? (int) $tourism_core_image['id'] : 0;
			$tourism_core_img_url = isset($tourism_core_image['url']) ? (string) $tourism_core_image['url'] : '';
			$tourism_core_img_title = isset($tourism_core_image['title']) ? (string) $tourism_core_image['title'] : '';
			$tourism_core_img_caption = isset($tourism_core_image['caption']) ? (string) $tourism_core_image['caption'] : '';
			?>
			<div class="tourism-core-destination-gallery-image-item">
				<input type="hidden" class="tourism-core-destination-gallery-field-id" name="tourism_destination_gallery_images[<?php echo esc_attr((string) $tourism_core_index); ?>][id]" value="<?php echo esc_attr((string) $tourism_core_img_id); ?>">
				<input type="hidden" class="tourism-core-destination-gallery-field-url" name="tourism_destination_gallery_images[<?php echo esc_attr((string) $tourism_core_index); ?>][url]" value="<?php echo esc_attr($tourism_core_img_url); ?>">

				<div class="tourism-core-destination-image-preview">
					<?php if (! empty($tourism_core_img_url)) : ?>
						<img src="<?php echo esc_url($tourism_core_img_url); ?>" alt="" />
					<?php else : ?>
						<div class="tourism-core-destination-no-image"><?php esc_html_e('Image not available', TOURISM_CORE_TD); ?></div>
					<?php endif; ?>
					<div class="tourism-core-destination-image-actions">
						<button type="button" class="tourism-core-replace-destination-image button" aria-label="<?php esc_attr_e('Replace image', TOURISM_CORE_TD); ?>">
							<span class="dashicons dashicons-update" aria-hidden="true"></span>
						</button>
						<button type="button" class="tourism-core-remove-destination-gallery-image button" aria-label="<?php esc_attr_e('Remove image', TOURISM_CORE_TD); ?>">
							<span class="dashicons dashicons-trash" aria-hidden="true"></span>
						</button>
					</div>
				</div>

				<div class="tourism-core-destination-image-details">
					<label><?php esc_html_e('Title:', TOURISM_CORE_TD); ?></label>
					<input
						type="text"
						name="tourism_destination_gallery_images[<?php echo esc_attr((string) $tourism_core_index); ?>][title]"
						value="<?php echo esc_attr($tourism_core_img_title); ?>"
						placeholder="<?php esc_attr_e('Image title', TOURISM_CORE_TD); ?>"
						class="widefat"
					/>

					<label><?php esc_html_e('Description:', TOURISM_CORE_TD); ?></label>
					<textarea
						name="tourism_destination_gallery_images[<?php echo esc_attr((string) $tourism_core_index); ?>][caption]"
						rows="3"
						class="widefat"
						placeholder="<?php esc_attr_e('Image description', TOURISM_CORE_TD); ?>"
					><?php echo esc_textarea($tourism_core_img_caption); ?></textarea>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<p>
		<button type="button" id="tourism-core-add-destination-gallery-image" class="button button-primary">
			<?php esc_html_e('Add Image', TOURISM_CORE_TD); ?>
		</button>
	</p>

	<div id="tourism-core-destination-gallery-image-template" style="display: none;">
		<div class="tourism-core-destination-gallery-image-item">
			<input type="hidden" class="tourism-core-destination-gallery-field-id" name="tourism_destination_gallery_images[__tourism_index__][id]" value="">
			<input type="hidden" class="tourism-core-destination-gallery-field-url" name="tourism_destination_gallery_images[__tourism_index__][url]" value="">

			<div class="tourism-core-destination-image-preview">
				<div class="tourism-core-destination-no-image"><?php esc_html_e('Image not available', TOURISM_CORE_TD); ?></div>
				<div class="tourism-core-destination-image-actions">
					<button type="button" class="tourism-core-replace-destination-image button" aria-label="<?php esc_attr_e('Replace image', TOURISM_CORE_TD); ?>">
						<span class="dashicons dashicons-update" aria-hidden="true"></span>
					</button>
					<button type="button" class="tourism-core-remove-destination-gallery-image button" aria-label="<?php esc_attr_e('Remove image', TOURISM_CORE_TD); ?>">
						<span class="dashicons dashicons-trash" aria-hidden="true"></span>
					</button>
				</div>
			</div>

			<div class="tourism-core-destination-image-details">
				<label><?php esc_html_e('Title:', TOURISM_CORE_TD); ?></label>
				<input type="text" name="tourism_destination_gallery_images[__tourism_index__][title]" value="" placeholder="<?php echo esc_attr(__('Image title', TOURISM_CORE_TD)); ?>" class="widefat" />

				<label><?php esc_html_e('Description:', TOURISM_CORE_TD); ?></label>
				<textarea name="tourism_destination_gallery_images[__tourism_index__][caption]" rows="3" class="widefat" placeholder="<?php echo esc_attr(__('Image description', TOURISM_CORE_TD)); ?>"></textarea>
			</div>
		</div>
	</div>
</div>

