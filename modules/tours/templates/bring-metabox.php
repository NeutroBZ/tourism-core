<?php
/**
 * Tour bring items metabox.
 *
 * Expected variable:
 * - array $tourism_core_bring_items rows with id, url, title, description.
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_bring_items = isset($tourism_core_bring_items) && is_array($tourism_core_bring_items) ? $tourism_core_bring_items : array();
?>

<input type="hidden" name="tourism_bring_metabox_present" value="1" />
<p><em><?php esc_html_e('Recommended: transparent PNG icons.', TOURISM_CORE_TD); ?></em></p>

<div id="tourism-core-bring-items-container">
	<?php foreach ($tourism_core_bring_items as $tourism_core_index => $tourism_core_item) : ?>
		<?php
		$tourism_core_item = is_array($tourism_core_item) ? $tourism_core_item : array();
		$tourism_core_id = isset($tourism_core_item['id']) ? (int) $tourism_core_item['id'] : 0;
		$tourism_core_url = isset($tourism_core_item['url']) ? (string) $tourism_core_item['url'] : '';
		$tourism_core_title = isset($tourism_core_item['title']) ? (string) $tourism_core_item['title'] : '';
		$tourism_core_description = isset($tourism_core_item['description']) ? (string) $tourism_core_item['description'] : '';
		?>
		<div class="tourism-core-bring-item">
			<input type="hidden" class="tourism-core-bring-field-id" name="tourism_bring_items[<?php echo esc_attr((string) $tourism_core_index); ?>][id]" value="<?php echo esc_attr((string) $tourism_core_id); ?>" />
			<input type="hidden" class="tourism-core-bring-field-url" name="tourism_bring_items[<?php echo esc_attr((string) $tourism_core_index); ?>][url]" value="<?php echo esc_attr($tourism_core_url); ?>" />
			<div class="tourism-core-bring-preview">
				<?php if ($tourism_core_url !== '') : ?>
					<img src="<?php echo esc_url($tourism_core_url); ?>" alt="" />
				<?php else : ?>
					<div class="tourism-core-no-image"><?php esc_html_e('No icon selected', TOURISM_CORE_TD); ?></div>
				<?php endif; ?>
				<div class="tourism-core-bring-actions">
					<button type="button" class="tourism-core-replace-bring-image button" aria-label="<?php esc_attr_e('Select icon image', TOURISM_CORE_TD); ?>">
						<span class="dashicons dashicons-format-image"></span>
					</button>
					<button type="button" class="tourism-core-remove-bring-item button" aria-label="<?php esc_attr_e('Delete item', TOURISM_CORE_TD); ?>">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</div>
			</div>
			<div class="tourism-core-bring-fields">
				<label for="tourism_bring_title_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Title', TOURISM_CORE_TD); ?></label>
				<input type="text" class="widefat tourism-core-bring-field-title" id="tourism_bring_title_<?php echo esc_attr((string) $tourism_core_index); ?>" name="tourism_bring_items[<?php echo esc_attr((string) $tourism_core_index); ?>][title]" value="<?php echo esc_attr($tourism_core_title); ?>" />
				<label for="tourism_bring_description_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Short description', TOURISM_CORE_TD); ?></label>
				<textarea rows="3" class="widefat tourism-core-bring-field-description" id="tourism_bring_description_<?php echo esc_attr((string) $tourism_core_index); ?>" name="tourism_bring_items[<?php echo esc_attr((string) $tourism_core_index); ?>][description]"><?php echo esc_textarea($tourism_core_description); ?></textarea>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<p>
	<button type="button" id="tourism-core-add-bring-item" class="button button-primary"><?php esc_html_e('Add bring item', TOURISM_CORE_TD); ?></button>
</p>

<div id="tourism-core-bring-item-template" style="display:none;">
	<div class="tourism-core-bring-item">
		<input type="hidden" class="tourism-core-bring-field-id" name="tourism_bring_items[__index__][id]" value="" />
		<input type="hidden" class="tourism-core-bring-field-url" name="tourism_bring_items[__index__][url]" value="" />
		<div class="tourism-core-bring-preview">
			<div class="tourism-core-no-image"><?php esc_html_e('No icon selected', TOURISM_CORE_TD); ?></div>
			<div class="tourism-core-bring-actions">
				<button type="button" class="tourism-core-replace-bring-image button" aria-label="<?php esc_attr_e('Select icon image', TOURISM_CORE_TD); ?>">
					<span class="dashicons dashicons-format-image"></span>
				</button>
				<button type="button" class="tourism-core-remove-bring-item button" aria-label="<?php esc_attr_e('Delete item', TOURISM_CORE_TD); ?>">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>
		</div>
		<div class="tourism-core-bring-fields">
			<label for="tourism_bring_title___index__"><?php esc_html_e('Title', TOURISM_CORE_TD); ?></label>
			<input type="text" class="widefat tourism-core-bring-field-title" id="tourism_bring_title___index__" name="tourism_bring_items[__index__][title]" value="" />
			<label for="tourism_bring_description___index__"><?php esc_html_e('Short description', TOURISM_CORE_TD); ?></label>
			<textarea rows="3" class="widefat tourism-core-bring-field-description" id="tourism_bring_description___index__" name="tourism_bring_items[__index__][description]"></textarea>
		</div>
	</div>
</div>
