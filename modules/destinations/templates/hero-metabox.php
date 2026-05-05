<?php
/**
 * Destination hero metabox (image + opcional override de texto).
 *
 * Expected variable:
 * - array $tourism_core_destination_hero [
 *       'hero_id' => int,
 *       'title' => string,
 *       'short_description' => string,
 *   ]
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_destination_hero = isset($tourism_core_destination_hero) && is_array($tourism_core_destination_hero) ? $tourism_core_destination_hero : array();
$tourism_core_hero_id = isset($tourism_core_destination_hero['hero_id']) ? (int) $tourism_core_destination_hero['hero_id'] : 0;
$tourism_core_hero_title = isset($tourism_core_destination_hero['title']) ? (string) $tourism_core_destination_hero['title'] : '';
$tourism_core_hero_short_description = isset($tourism_core_destination_hero['short_description']) ? (string) $tourism_core_destination_hero['short_description'] : '';

$tourism_core_hero_preview = $tourism_core_hero_id ? wp_get_attachment_image($tourism_core_hero_id, 'medium') : '';
?>

<input type="hidden" name="tourism_destination_hero_metabox_present" value="1" />

<div class="tourism-core-destination-hero-image-block">
	<div id="destination-hero-preview" class="tourism-core-destination-hero-preview-wrap" style="margin-bottom:10px;">
		<?php echo $tourism_core_hero_preview; ?>
	</div>

	<input type="hidden" id="destination_hero_image" name="destination_hero_image" value="<?php echo esc_attr((string) $tourism_core_hero_id); ?>" />

	<p style="margin:0 0 8px;display:flex;flex-wrap:wrap;gap:6px;">
		<button type="button" class="button tourism-upload-destination-hero">
			<?php esc_html_e('Select Image', TOURISM_CORE_TD); ?>
		</button>
		<button type="button" class="button tourism-remove-destination-hero">
			<?php esc_html_e('Remove', TOURISM_CORE_TD); ?>
		</button>
	</p>
</div>

<div class="tourism-core-destination-hero-text-fields">
	<p style="margin:12px 0 0;">
		<label for="tourism_destination_hero_title"><strong><?php esc_html_e('Hero title (optional)', TOURISM_CORE_TD); ?></strong></label>
		<input
			type="text"
			id="tourism_destination_hero_title"
			name="tourism_destination_hero_title"
			value="<?php echo esc_attr($tourism_core_hero_title); ?>"
			class="widefat"
			placeholder="<?php esc_attr_e('Leave empty to use the destination title.', TOURISM_CORE_TD); ?>"
		/>
	</p>

	<p style="margin:10px 0 0;">
		<label for="tourism_destination_hero_short_description"><strong><?php esc_html_e('Hero short description (optional)', TOURISM_CORE_TD); ?></strong></label>
		<textarea
			id="tourism_destination_hero_short_description"
			name="tourism_destination_hero_short_description"
			rows="4"
			class="widefat"
			placeholder="<?php esc_attr_e('Brief line under the title in the hero area.', TOURISM_CORE_TD); ?>"
		><?php echo esc_textarea($tourism_core_hero_short_description); ?></textarea>
	</p>
</div>
