<?php
/**
 * Tour Highlights metabox markup (sortable repeater).
 *
 * Expected variables:
 * - array $tourism_core_highlights List of [ 'description' => string ] rows.
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_highlights = isset($tourism_core_highlights) && is_array($tourism_core_highlights) ? $tourism_core_highlights : array();
?>

<input type="hidden" name="tourism_highlights_metabox_present" value="1" />

<div id="tourism-core-highlights-container" class="tourism-core-sortable-container">
	<?php foreach ($tourism_core_highlights as $tourism_core_index => $tourism_core_item) : ?>
		<?php
		$tourism_core_item = is_array($tourism_core_item) ? $tourism_core_item : array();
		$tourism_core_description = isset($tourism_core_item['description']) ? (string) $tourism_core_item['description'] : '';
		?>
		<div class="tourism-core-highlight-item tourism-core-sortable-item active" data-tourism-core-index="<?php echo esc_attr((string) $tourism_core_index); ?>">
			<div class="tourism-core-highlight-header">
				<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
				<h4>
					<?php esc_html_e('Highlight', TOURISM_CORE_TD); ?>
					#<span class="tourism-core-item-number"><?php echo esc_html((string) ((int) $tourism_core_index + 1)); ?></span>
				</h4>
				<button type="button" class="tourism-core-delete-highlight button"><?php esc_html_e('Delete', TOURISM_CORE_TD); ?></button>
			</div>

			<div class="tourism-core-highlight-content">
				<p>
					<label for="tourism_core_highlight_description_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Description:', TOURISM_CORE_TD); ?></label>
					<textarea
						id="tourism_core_highlight_description_<?php echo esc_attr((string) $tourism_core_index); ?>"
						name="tourism_highlights[<?php echo esc_attr((string) $tourism_core_index); ?>][description]"
						class="tourism-core-simple-editor widefat"
						rows="4"
					><?php echo esc_textarea($tourism_core_description); ?></textarea>
				</p>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<p>
	<button type="button" id="tourism-core-add-highlight" class="button button-primary">
		<?php esc_html_e('Add Highlight', TOURISM_CORE_TD); ?>
	</button>
</p>

<script type="text/template" id="tourism-core-tmpl-highlight-item">
	<div class="tourism-core-highlight-item tourism-core-sortable-item active" data-tourism-core-index="{{tourism_index}}">
		<div class="tourism-core-highlight-header">
			<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
			<h4><?php echo esc_html(__('Highlight', TOURISM_CORE_TD)); ?> #<span class="tourism-core-item-number">{{tourism_index_plus_one}}</span></h4>
			<button type="button" class="tourism-core-delete-highlight button"><?php echo esc_html(__('Delete', TOURISM_CORE_TD)); ?></button>
		</div>
		<div class="tourism-core-highlight-content">
			<p>
				<label for="tourism_core_highlight_description_{{tourism_index}}"><?php echo esc_html(__('Description:', TOURISM_CORE_TD)); ?></label>
				<textarea id="tourism_core_highlight_description_{{tourism_index}}" name="tourism_highlights[{{tourism_index}}][description]" class="tourism-core-simple-editor widefat" rows="4"></textarea>
			</p>
		</div>
	</div>
</script>
