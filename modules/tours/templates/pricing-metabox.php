<?php
/**
 * Tour pricing metabox.
 *
 * Expected variable:
 * - array $tourism_core_pricing [price_now, price_before, discount_percent].
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_pricing = isset($tourism_core_pricing) && is_array($tourism_core_pricing) ? $tourism_core_pricing : array();
$tourism_core_price_now = isset($tourism_core_pricing['price_now']) ? (string) $tourism_core_pricing['price_now'] : '';
$tourism_core_price_before = isset($tourism_core_pricing['price_before']) ? (string) $tourism_core_pricing['price_before'] : '';
$tourism_core_discount_percent = isset($tourism_core_pricing['discount_percent']) ? (string) $tourism_core_pricing['discount_percent'] : '';
?>

<input type="hidden" name="tourism_pricing_metabox_present" value="1" />

<div class="tourism-core-pricing-fields">
	<p>
		<em><?php esc_html_e('Enter the current price and, if you want, choose a discount percentage to calculate the previous price automatically.', TOURISM_CORE_TD); ?></em>
	</p>

	<div class="tourism-core-pricing-grid">
		<p>
			<label for="tourism_price_from"><?php esc_html_e('Price now', TOURISM_CORE_TD); ?></label>
			<input
				type="number"
				step="0.01"
				min="0"
				id="tourism_price_from"
				name="tourism_price_from"
				value="<?php echo esc_attr($tourism_core_price_now); ?>"
				class="widefat"
				placeholder="0.00"
			/>
		</p>

		<p>
			<label for="tourism_price_discount_percent"><?php esc_html_e('Discount %', TOURISM_CORE_TD); ?></label>
			<input
				type="number"
				step="0.01"
				min="0"
				max="100"
				id="tourism_price_discount_percent"
				name="tourism_price_discount_percent"
				value="<?php echo esc_attr($tourism_core_discount_percent); ?>"
				class="widefat"
				placeholder="0"
			/>
		</p>

		<p>
			<label for="tourism_price_before"><?php esc_html_e('Price before', TOURISM_CORE_TD); ?></label>
			<input
				type="number"
				step="0.01"
				min="0"
				id="tourism_price_before"
				name="tourism_price_before"
				value="<?php echo esc_attr($tourism_core_price_before); ?>"
				class="widefat"
				placeholder="0.00"
			/>
		</p>
	</div>

	<p>
		<button type="button" id="tourism-core-calc-price-before" class="button button-secondary">
			<?php esc_html_e('Calculate previous price', TOURISM_CORE_TD); ?>
		</button>
	</p>

	<p class="tourism-core-pricing-help">
		<?php esc_html_e('Example: if the current price is 80 and the discount is 20%, the previous price will be calculated automatically.', TOURISM_CORE_TD); ?>
	</p>
</div>
