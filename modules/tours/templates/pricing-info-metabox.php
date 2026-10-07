<?php
/**
 * Tour Pricing Details metabox (sortable repeater, title + WYSIWYG content).
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_pricing_details = isset($tourism_core_pricing_details) && is_array($tourism_core_pricing_details) ? $tourism_core_pricing_details : array();
?>

<input type="hidden" name="tourism_pricing_details_metabox_present" value="1" />

<div id="tourism-core-pricing-info-container">
	<?php foreach ($tourism_core_pricing_details as $tourism_core_index => $tourism_core_item) : ?>
		<?php
		$tourism_core_title = isset($tourism_core_item['title']) ? (string) $tourism_core_item['title'] : '';
		$tourism_core_content = isset($tourism_core_item['content']) ? (string) $tourism_core_item['content'] : '';
		?>
		<div class="tourism-core-pricing-info-item tourism-core-label-item">
			<div class="tourism-core-label-row-header">
				<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
				<span class="tourism-core-label-item-title">
					<?php esc_html_e('Detalle del precio', TOURISM_CORE_TD); ?>
					#<span class="tourism-core-label-item-num"><?php echo esc_html((string) ((int) $tourism_core_index + 1)); ?></span>
				</span>
				<button type="button" class="tourism-core-delete-pricing-info button"><?php esc_html_e('Eliminar', TOURISM_CORE_TD); ?></button>
			</div>
			<div class="tourism-core-label-row-body">
				<p>
					<label for="tourism_pricing_info_title_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Título', TOURISM_CORE_TD); ?></label>
					<input
						type="text"
						id="tourism_pricing_info_title_<?php echo esc_attr((string) $tourism_core_index); ?>"
						class="widefat tourism-core-pricing-info-title-input"
						name="tourism_pricing_info_items[<?php echo esc_attr((string) $tourism_core_index); ?>][title]"
						value="<?php echo esc_attr($tourism_core_title); ?>"
						placeholder="<?php esc_attr_e('Título', TOURISM_CORE_TD); ?>"
					/>
				</p>
				<p>
					<label for="tourism_pricing_info_content_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Contenido', TOURISM_CORE_TD); ?></label>
					<textarea
						id="tourism_pricing_info_content_<?php echo esc_attr((string) $tourism_core_index); ?>"
						class="widefat tourism-core-wysiwyg"
						name="tourism_pricing_info_items[<?php echo esc_attr((string) $tourism_core_index); ?>][content]"
						rows="5"
					><?php echo wp_kses_post($tourism_core_content); ?></textarea>
				</p>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<p>
	<button type="button" id="tourism-core-add-pricing-info" class="button button-primary">
		<?php esc_html_e('Añadir detalle del precio', TOURISM_CORE_TD); ?>
	</button>
</p>

<script type="text/template" id="tourism-core-tmpl-pricing-info-item">
	<div class="tourism-core-pricing-info-item tourism-core-label-item">
		<div class="tourism-core-label-row-header">
			<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
			<span class="tourism-core-label-item-title">
				<?php echo esc_html(__('Detalle del precio', TOURISM_CORE_TD)); ?>
				#<span class="tourism-core-label-item-num">{{tourism_index_plus_one}}</span>
			</span>
			<button type="button" class="tourism-core-delete-pricing-info button"><?php echo esc_html(__('Eliminar', TOURISM_CORE_TD)); ?></button>
		</div>
		<div class="tourism-core-label-row-body">
			<p>
				<label for="tourism_pricing_info_title_{{tourism_index}}"><?php echo esc_html(__('Título', TOURISM_CORE_TD)); ?></label>
				<input type="text" id="tourism_pricing_info_title_{{tourism_index}}" class="widefat tourism-core-pricing-info-title-input" name="tourism_pricing_info_items[{{tourism_index}}][title]" value="" placeholder="<?php echo esc_attr(__('Título', TOURISM_CORE_TD)); ?>" />
			</p>
			<p>
				<label for="tourism_pricing_info_content_{{tourism_index}}"><?php echo esc_html(__('Contenido', TOURISM_CORE_TD)); ?></label>
				<textarea id="tourism_pricing_info_content_{{tourism_index}}" class="widefat tourism-core-wysiwyg" name="tourism_pricing_info_items[{{tourism_index}}][content]" rows="5"></textarea>
			</p>
		</div>
	</div>
</script>