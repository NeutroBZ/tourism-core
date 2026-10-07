<?php
/**
 * Shared gallery metabox markup (sortable images: id, url, title, caption).
 *
 * Expected variables:
 * - array $gc_config {
 *     present_key: string,
 *     post_key: string,
 *     container_class: string,
 *     wrapper_class: string,
 *     item_class: string,
 *     field_id_class: string,
 *     field_url_class: string,
 *     preview_class: string,
 *     no_image_class: string,
 *     actions_class: string,
 *     details_class: string,
 *     replace_class: string,
 *     remove_class: string,
 *     add_id: string,
 *     template_id: string,
 *     image_class: string,
 *   }
 * - array $gc_images List of [id, url, title, caption]
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$gc_config = isset($gc_config) && is_array($gc_config) ? $gc_config : array();
$gc_images = isset($gc_images) && is_array($gc_images) ? $gc_images : array();

$gc = array_merge(
	array(
		'present_key'    => 'tourism_gallery_metabox_present',
		'post_key'       => 'tourism_gallery_images',
		'wrapper_class'  => 'tourism-core-gallery-metabox-container',
		'container_class'=> 'tourism-core-gallery-images-container',
		'item_class'     => 'tourism-core-gallery-image-item',
		'field_id_class' => 'tourism-core-gallery-field-id',
		'field_url_class'=> 'tourism-core-gallery-field-url',
		'preview_class'  => 'tourism-core-image-preview',
		'no_image_class' => 'tourism-core-no-image',
		'actions_class'  => 'tourism-core-image-actions',
		'details_class'  => 'tourism-core-image-details',
		'replace_class'  => 'tourism-core-replace-image',
		'remove_class'   => 'tourism-core-remove-gallery-image',
		'add_id'         => 'tourism-core-add-gallery-image',
		'template_id'    => 'tourism-core-gallery-image-template',
		'image_class'    => '',
	),
	$gc_config
);

$key = $gc['post_key'];
?>

<input type="hidden" name="<?php echo esc_attr($gc['present_key']); ?>" value="1" />

<div class="<?php echo esc_attr($gc['wrapper_class']); ?>">
	<div id="<?php echo esc_attr($gc['container_class']); ?>" class="<?php echo esc_attr($gc['container_class']); ?> tourism-core-sortable-gallery">
		<?php foreach ($gc_images as $i => $image) : ?>
			<?php
			$image = is_array($image) ? $image : array();
			$gid = isset($image['id']) ? (int) $image['id'] : 0;
			$gurl = isset($image['url']) ? (string) $image['url'] : '';
			$gtitle = isset($image['title']) ? (string) $image['title'] : '';
			$gcap = isset($image['caption']) ? (string) $image['caption'] : '';
			$i = (int) $i;
			?>
			<div class="<?php echo esc_attr($gc['item_class']); ?>">
				<input type="hidden" class="<?php echo esc_attr($gc['field_id_class']); ?>" name="<?php echo esc_attr($key); ?>[<?php echo esc_attr((string) $i); ?>][id]" value="<?php echo esc_attr((string) $gid); ?>">
				<input type="hidden" class="<?php echo esc_attr($gc['field_url_class']); ?>" name="<?php echo esc_attr($key); ?>[<?php echo esc_attr((string) $i); ?>][url]" value="<?php echo esc_attr($gurl); ?>">

				<div class="<?php echo esc_attr($gc['preview_class']); ?>">
					<?php if (! empty($gurl)) : ?>
						<img src="<?php echo esc_url($gurl); ?>" alt="" />
					<?php else : ?>
						<div class="<?php echo esc_attr($gc['no_image_class']); ?>"><?php esc_html_e('Image not available', TOURISM_CORE_TD); ?></div>
					<?php endif; ?>
					<div class="<?php echo esc_attr($gc['actions_class']); ?>">
						<button type="button" class="<?php echo esc_attr($gc['replace_class']); ?> button" aria-label="<?php esc_attr_e('Replace image', TOURISM_CORE_TD); ?>">
							<span class="dashicons dashicons-update" aria-hidden="true"></span>
						</button>
						<button type="button" class="<?php echo esc_attr($gc['remove_class']); ?> button" aria-label="<?php esc_attr_e('Remove image', TOURISM_CORE_TD); ?>">
							<span class="dashicons dashicons-trash" aria-hidden="true"></span>
						</button>
					</div>
				</div>

				<div class="<?php echo esc_attr($gc['details_class']); ?>">
					<label><?php esc_html_e('Title:', TOURISM_CORE_TD); ?></label>
					<input
						type="text"
						name="<?php echo esc_attr($key); ?>[<?php echo esc_attr((string) $i); ?>][title]"
						value="<?php echo esc_attr($gtitle); ?>"
						placeholder="<?php esc_attr_e('Image title', TOURISM_CORE_TD); ?>"
						class="widefat"
					/>

					<label><?php esc_html_e('Description:', TOURISM_CORE_TD); ?></label>
					<textarea
						name="<?php echo esc_attr($key); ?>[<?php echo esc_attr((string) $i); ?>][caption]"
						rows="3"
						class="widefat"
						placeholder="<?php esc_attr_e('Image description', TOURISM_CORE_TD); ?>"
					><?php echo esc_textarea($gcap); ?></textarea>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<p>
		<button type="button" id="<?php echo esc_attr($gc['add_id']); ?>" class="button button-primary">
			<?php esc_html_e('Add Image', TOURISM_CORE_TD); ?>
		</button>
	</p>

	<div id="<?php echo esc_attr($gc['template_id']); ?>" style="display: none;">
		<div class="<?php echo esc_attr($gc['item_class']); ?>">
			<input type="hidden" class="<?php echo esc_attr($gc['field_id_class']); ?>" name="<?php echo esc_attr($key); ?>[__tourism_index__][id]" value="">
			<input type="hidden" class="<?php echo esc_attr($gc['field_url_class']); ?>" name="<?php echo esc_attr($key); ?>[__tourism_index__][url]" value="">

			<div class="<?php echo esc_attr($gc['preview_class']); ?>">
				<div class="<?php echo esc_attr($gc['no_image_class']); ?>"><?php esc_html_e('Image not available', TOURISM_CORE_TD); ?></div>
				<div class="<?php echo esc_attr($gc['actions_class']); ?>">
					<button type="button" class="<?php echo esc_attr($gc['replace_class']); ?> button" aria-label="<?php esc_attr_e('Replace image', TOURISM_CORE_TD); ?>">
						<span class="dashicons dashicons-update" aria-hidden="true"></span>
					</button>
					<button type="button" class="<?php echo esc_attr($gc['remove_class']); ?> button" aria-label="<?php esc_attr_e('Remove image', TOURISM_CORE_TD); ?>">
						<span class="dashicons dashicons-trash" aria-hidden="true"></span>
					</button>
				</div>
			</div>

			<div class="<?php echo esc_attr($gc['details_class']); ?>">
				<label><?php esc_html_e('Title:', TOURISM_CORE_TD); ?></label>
				<input type="text" name="<?php echo esc_attr($key); ?>[__tourism_index__][title]" value="" placeholder="<?php echo esc_attr(__('Image title', TOURISM_CORE_TD)); ?>" class="widefat" />

				<label><?php esc_html_e('Description:', TOURISM_CORE_TD); ?></label>
				<textarea name="<?php echo esc_attr($key); ?>[__tourism_index__][caption]" rows="3" class="widefat" placeholder="<?php echo esc_attr(__('Image description', TOURISM_CORE_TD)); ?>"></textarea>
			</div>
		</div>
	</div>
</div>
