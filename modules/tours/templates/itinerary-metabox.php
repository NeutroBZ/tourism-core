<?php
/**
 * Tour itinerary metabox markup.
 *
 * Expected variable:
 * - array $tourism_core_itinerary_items List of itinerary rows.
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_itinerary_items = isset($tourism_core_itinerary_items) && is_array($tourism_core_itinerary_items) ? $tourism_core_itinerary_items : array();
?>

<input type="hidden" name="tourism_itinerary_metabox_present" value="1" />

<p><em><?php esc_html_e('Añade cada día con su descripción. Los detalles de lugar, hotel o actividad son opcionales.', TOURISM_CORE_TD); ?></em></p>

<div id="tourism-core-itinerary-container" class="tourism-core-sortable-container">
	<?php foreach ($tourism_core_itinerary_items as $tourism_core_index => $tourism_core_item) : ?>
		<?php
		$tourism_core_item = is_array($tourism_core_item) ? $tourism_core_item : array();
		$tourism_core_day = isset($tourism_core_item['day']) ? (string) $tourism_core_item['day'] : '';
		$tourism_core_description = isset($tourism_core_item['description']) ? (string) $tourism_core_item['description'] : '';
		$tourism_core_features = isset($tourism_core_item['features']) && is_array($tourism_core_item['features']) ? $tourism_core_item['features'] : array();
		?>
		<div class="tourism-core-itinerary-item" data-tourism-core-index="<?php echo esc_attr((string) $tourism_core_index); ?>">
			<div class="tourism-core-itinerary-header">
				<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
				<h4>
					<?php esc_html_e('Día', TOURISM_CORE_TD); ?>
					<span class="tourism-core-item-number"><?php echo esc_html((string) ((int) $tourism_core_index + 1)); ?></span>
				</h4>
				<button type="button" class="tourism-core-delete-itinerary button"><?php esc_html_e('Eliminar', TOURISM_CORE_TD); ?></button>
			</div>

			<div class="tourism-core-itinerary-body">
				<p>
					<label for="tourism_core_itinerary_day_<?php echo esc_attr((string) $tourism_core_index); ?>"><strong><?php esc_html_e('Título del día', TOURISM_CORE_TD); ?></strong></label>
					<input
						type="text"
						id="tourism_core_itinerary_day_<?php echo esc_attr((string) $tourism_core_index); ?>"
						name="tourism_itinerary_items[<?php echo esc_attr((string) $tourism_core_index); ?>][day]"
						value="<?php echo esc_attr($tourism_core_day); ?>"
						class="widefat tourism-core-itinerary-day"
						placeholder="<?php esc_attr_e('Ejemplo: Día 1 — Llegada a Cusco', TOURISM_CORE_TD); ?>"
					/>
				</p>

				<p>
					<label for="tourism_core_itinerary_description_<?php echo esc_attr((string) $tourism_core_index); ?>"><strong><?php esc_html_e('Descripción', TOURISM_CORE_TD); ?></strong></label>
					<textarea
						id="tourism_core_itinerary_description_<?php echo esc_attr((string) $tourism_core_index); ?>"
						name="tourism_itinerary_items[<?php echo esc_attr((string) $tourism_core_index); ?>][description]"
						class="widefat tourism-core-itinerary-description tourism-core-wysiwyg"
						rows="5"
						placeholder="<?php esc_attr_e('Describe las actividades de este día.', TOURISM_CORE_TD); ?>"
					><?php echo esc_textarea($tourism_core_description); ?></textarea>
				</p>

				<div class="tourism-core-itinerary-features">
					<p><strong><?php esc_html_e('Detalles adicionales (opcionales)', TOURISM_CORE_TD); ?></strong></p>
					<div class="tourism-core-itinerary-feature-list">
						<?php foreach ($tourism_core_features as $tourism_core_feature_index => $tourism_core_feature) : ?>
							<?php
							$tourism_core_feature = is_array($tourism_core_feature) ? $tourism_core_feature : array();
							$tourism_core_feature_label = isset($tourism_core_feature['label']) ? (string) $tourism_core_feature['label'] : '';
							$tourism_core_feature_value = isset($tourism_core_feature['value']) ? (string) $tourism_core_feature['value'] : '';
							?>
							<div class="tourism-core-itinerary-feature-row">
								<input
									type="text"
									class="widefat tourism-core-itinerary-feature-label"
									name="tourism_itinerary_items[<?php echo esc_attr((string) $tourism_core_index); ?>][features][<?php echo esc_attr((string) $tourism_core_feature_index); ?>][label]"
									value="<?php echo esc_attr($tourism_core_feature_label); ?>"
									placeholder="<?php esc_attr_e('Etiqueta, por ejemplo: lugar', TOURISM_CORE_TD); ?>"
								/>
								<input
									type="text"
									class="widefat tourism-core-itinerary-feature-value"
									name="tourism_itinerary_items[<?php echo esc_attr((string) $tourism_core_index); ?>][features][<?php echo esc_attr((string) $tourism_core_feature_index); ?>][value]"
									value="<?php echo esc_attr($tourism_core_feature_value); ?>"
									placeholder="<?php esc_attr_e('Valor, por ejemplo: Cusco', TOURISM_CORE_TD); ?>"
								/>
								<button type="button" class="tourism-core-delete-itinerary-feature button"><?php esc_html_e('Eliminar', TOURISM_CORE_TD); ?></button>
							</div>
						<?php endforeach; ?>
					</div>

					<p>
						<button type="button" class="tourism-core-add-itinerary-feature button button-secondary"><?php esc_html_e('Añadir detalle', TOURISM_CORE_TD); ?></button>
					</p>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<p>
	<button type="button" id="tourism-core-add-itinerary-item" class="button button-primary">
		<?php esc_html_e('Añadir día', TOURISM_CORE_TD); ?>
	</button>
</p>

<script type="text/template" id="tourism-core-tmpl-itinerary-item">
	<div class="tourism-core-itinerary-item" data-tourism-core-index="{{tourism_index}}">
		<div class="tourism-core-itinerary-header">
			<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
			<h4><?php echo esc_html(__('Día', TOURISM_CORE_TD)); ?> #<span class="tourism-core-item-number">{{tourism_index_plus_one}}</span></h4>
			<button type="button" class="tourism-core-delete-itinerary button"><?php echo esc_html(__('Eliminar', TOURISM_CORE_TD)); ?></button>
		</div>

		<div class="tourism-core-itinerary-body">
			<p>
				<label for="tourism_core_itinerary_day_{{tourism_index}}"><strong><?php echo esc_html(__('Título del día', TOURISM_CORE_TD)); ?></strong></label>
				<input type="text" id="tourism_core_itinerary_day_{{tourism_index}}" name="tourism_itinerary_items[{{tourism_index}}][day]" value="" class="widefat tourism-core-itinerary-day" placeholder="<?php echo esc_attr(__('Ejemplo: Día 1 — Llegada a Cusco', TOURISM_CORE_TD)); ?>" />
			</p>

			<p>
				<label for="tourism_core_itinerary_description_{{tourism_index}}"><strong><?php echo esc_html(__('Descripción', TOURISM_CORE_TD)); ?></strong></label>
				<textarea id="tourism_core_itinerary_description_{{tourism_index}}" name="tourism_itinerary_items[{{tourism_index}}][description]" class="widefat tourism-core-itinerary-description tourism-core-wysiwyg" rows="5" placeholder="<?php echo esc_attr(__('Describe las actividades de este día.', TOURISM_CORE_TD)); ?>"></textarea>
			</p>

			<div class="tourism-core-itinerary-features">
				<p><strong><?php echo esc_html(__('Detalles adicionales (opcionales)', TOURISM_CORE_TD)); ?></strong></p>
				<div class="tourism-core-itinerary-feature-list"></div>
				<p>
					<button type="button" class="tourism-core-add-itinerary-feature button button-secondary"><?php echo esc_html(__('Añadir detalle', TOURISM_CORE_TD)); ?></button>
				</p>
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="tourism-core-tmpl-itinerary-feature">
	<div class="tourism-core-itinerary-feature-row">
		<input type="text" class="widefat tourism-core-itinerary-feature-label" value="" placeholder="<?php echo esc_attr(__('Etiqueta, por ejemplo: lugar', TOURISM_CORE_TD)); ?>" />
		<input type="text" class="widefat tourism-core-itinerary-feature-value" value="" placeholder="<?php echo esc_attr(__('Valor, por ejemplo: Cusco', TOURISM_CORE_TD)); ?>" />
		<button type="button" class="tourism-core-delete-itinerary-feature button"><?php echo esc_html(__('Eliminar', TOURISM_CORE_TD)); ?></button>
	</div>
</script>
