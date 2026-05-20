<?php
/**
 * Tour hero metabox (image).
 *
 * Expected variable:
 * - array $tourism_core_tour_hero [
 *       'hero_id' => int,
 *   ]
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_tour_hero = isset($tourism_core_tour_hero) && is_array($tourism_core_tour_hero) ? $tourism_core_tour_hero : array();
$tourism_core_hero_id = isset($tourism_core_tour_hero['hero_id']) ? (int) $tourism_core_tour_hero['hero_id'] : 0;
$tourism_core_hero_preview = $tourism_core_hero_id ? wp_get_attachment_image($tourism_core_hero_id, 'medium') : '';
?>

<input type="hidden" name="tourism_tour_hero_metabox_present" value="1" />

<div class="tourism-core-tour-hero-image-block">
	<div id="tour-hero-preview" class="tourism-core-tour-hero-preview-wrap" style="margin-bottom:10px;">
		<?php echo $tourism_core_hero_preview; ?>
	</div>

	<input type="hidden" id="tour_hero_image" name="tour_hero_image" value="<?php echo esc_attr((string) $tourism_core_hero_id); ?>" />

	<p style="margin:0 0 8px;display:flex;flex-wrap:wrap;gap:6px;">
		<button type="button" class="button tourism-upload-hero">
			<?php esc_html_e('Select Image', TOURISM_CORE_TD); ?>
		</button>
		<button type="button" class="button tourism-remove-hero">
			<?php esc_html_e('Remove', TOURISM_CORE_TD); ?>
		</button>
	</p>
</div>