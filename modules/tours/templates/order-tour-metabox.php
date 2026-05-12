<?php
/**
 * Tour manual order metabox.
 *
 * Expected variable:
 * - int $tourism_core_tour_order
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_tour_order = isset($tourism_core_tour_order) ? (int) $tourism_core_tour_order : 0;
if ($tourism_core_tour_order < 0) {
	$tourism_core_tour_order = 0;
}
?>

<input type="hidden" name="tourism_tour_order_metabox_present" value="1" />

<p style="margin:0;">
	<label for="tourism_tour_order"><strong><?php esc_html_e('Orden en listados', TOURISM_CORE_TD); ?></strong></label>
	<input
		type="number"
		min="0"
		step="1"
		id="tourism_tour_order"
		name="tourism_tour_order"
		value="<?php echo esc_attr((string) $tourism_core_tour_order); ?>"
		class="small-text"
		style="margin-right:6px;"
	/>
	<span class="description">
		<?php esc_html_e('Usa números enteros (0, 1, 2…). Menor número = aparece antes en el listado de tours.', TOURISM_CORE_TD); ?>
	</span>
</p>
