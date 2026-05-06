<?php
/**
 * Destination season / best time metabox.
 *
 * Expected variables:
 * - array $tourism_core_destination_season [ 'best_months' => int[], 'travel_tip' => string ]
 * - array<int, string> $tourism_core_destination_month_labels month number (1–12) => localized label
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_destination_season = isset($tourism_core_destination_season) && is_array($tourism_core_destination_season) ? $tourism_core_destination_season : array();
$tourism_core_best_months = isset($tourism_core_destination_season['best_months']) && is_array($tourism_core_destination_season['best_months'])
	? $tourism_core_destination_season['best_months']
	: array();
$tourism_core_travel_tip = isset($tourism_core_destination_season['travel_tip']) ? (string) $tourism_core_destination_season['travel_tip'] : '';

$tourism_core_destination_month_labels = isset($tourism_core_destination_month_labels) && is_array($tourism_core_destination_month_labels)
	? $tourism_core_destination_month_labels
	: array();
?>

<input type="hidden" name="tourism_destination_season_metabox_present" value="1" />

<p class="tourism-core-season-best-months-heading">
	<strong><?php esc_html_e('Best months', TOURISM_CORE_TD); ?></strong>
</p>
<p class="description" style="margin-top:0;">
	<?php esc_html_e('Select one or more months that are especially good to visit this destination.', TOURISM_CORE_TD); ?>
</p>

<div class="tourism-core-season-months-grid" role="group" aria-label="<?php esc_attr_e('Best months', TOURISM_CORE_TD); ?>">
	<?php foreach ($tourism_core_destination_month_labels as $tourism_core_month_num => $tourism_core_month_label) : ?>
		<?php
		$tourism_core_month_num = (int) $tourism_core_month_num;
		if ($tourism_core_month_num < 1 || $tourism_core_month_num > 12) {
			continue;
		}
		?>
		<label class="tourism-core-season-month-label">
			<input
				type="checkbox"
				name="tourism_destination_best_months[]"
				value="<?php echo esc_attr((string) $tourism_core_month_num); ?>"
				<?php checked(in_array($tourism_core_month_num, array_map('intval', $tourism_core_best_months), true)); ?>
			/>
			<?php echo esc_html($tourism_core_month_label); ?>
		</label>
	<?php endforeach; ?>
</div>

<p style="margin-top:16px;margin-bottom:4px;">
	<label for="tourism_destination_travel_tip"><strong><?php esc_html_e('Travel tip', TOURISM_CORE_TD); ?></strong></label>
</p>
<p class="description" style="margin-top:0;margin-bottom:6px;">
	<?php esc_html_e('A short seasonal or practical tip for travellers.', TOURISM_CORE_TD); ?>
</p>
<input
	type="text"
	id="tourism_destination_travel_tip"
	name="tourism_destination_travel_tip"
	value="<?php echo esc_attr($tourism_core_travel_tip); ?>"
	class="widefat"
	placeholder="<?php esc_attr_e('Example: Carry a light jacket for evenings year-round.', TOURISM_CORE_TD); ?>"
/>
