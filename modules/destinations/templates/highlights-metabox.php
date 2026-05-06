<?php
/**
 * Destination Highlights metabox (sortable repeater).
 *
 * Expected variable:
 * - array $tourism_core_destination_highlights List of [ 'title' => string, 'description' => string ] rows.
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_destination_highlights = isset($tourism_core_destination_highlights) && is_array($tourism_core_destination_highlights) ? $tourism_core_destination_highlights : array();
?>

<input type="hidden" name="tourism_destination_highlights_metabox_present" value="1" />

<div id="tourism-core-destination-highlights-container" class="tourism-core-sortable-container">
	<?php foreach ($tourism_core_destination_highlights as $tourism_core_index => $tourism_core_item) : ?>
		<?php
		$tourism_core_item = is_array($tourism_core_item) ? $tourism_core_item : array();
		$tourism_core_highlight_title = isset($tourism_core_item['title']) ? (string) $tourism_core_item['title'] : '';
		$tourism_core_highlight_description = isset($tourism_core_item['description']) ? (string) $tourism_core_item['description'] : '';
		?>
		<div class="tourism-core-destination-highlight-item tourism-core-sortable-item active" data-tourism-core-index="<?php echo esc_attr((string) $tourism_core_index); ?>">
			<div class="tourism-core-destination-highlight-header">
				<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
				<h4>
					<?php esc_html_e('Highlight', TOURISM_CORE_TD); ?>
					#<span class="tourism-core-item-number"><?php echo esc_html((string) ((int) $tourism_core_index + 1)); ?></span>
				</h4>
				<button type="button" class="tourism-core-delete-destination-highlight button"><?php esc_html_e('Delete', TOURISM_CORE_TD); ?></button>
			</div>

			<div class="tourism-core-destination-highlight-content">
				<p>
					<label for="tourism_core_destination_highlight_title_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Title:', TOURISM_CORE_TD); ?></label>
					<input
						type="text"
						id="tourism_core_destination_highlight_title_<?php echo esc_attr((string) $tourism_core_index); ?>"
						name="tourism_destination_highlights[<?php echo esc_attr((string) $tourism_core_index); ?>][title]"
						value="<?php echo esc_attr($tourism_core_highlight_title); ?>"
						class="widefat"
					/>
				</p>
				<p>
					<label for="tourism_core_destination_highlight_description_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Description:', TOURISM_CORE_TD); ?></label>
					<textarea
						id="tourism_core_destination_highlight_description_<?php echo esc_attr((string) $tourism_core_index); ?>"
						name="tourism_destination_highlights[<?php echo esc_attr((string) $tourism_core_index); ?>][description]"
						class="tourism-core-simple-editor widefat"
						rows="4"
					><?php echo esc_textarea($tourism_core_highlight_description); ?></textarea>
				</p>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<p>
	<button type="button" id="tourism-core-add-destination-highlight" class="button button-primary">
		<?php esc_html_e('Add Highlight', TOURISM_CORE_TD); ?>
	</button>
</p>

<script type="text/template" id="tourism-core-tmpl-destination-highlight-item">
	<div class="tourism-core-destination-highlight-item tourism-core-sortable-item active" data-tourism-core-index="{{tourism_index}}">
		<div class="tourism-core-destination-highlight-header">
			<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
			<h4><?php echo esc_html(__('Highlight', TOURISM_CORE_TD)); ?> #<span class="tourism-core-item-number">{{tourism_index_plus_one}}</span></h4>
			<button type="button" class="tourism-core-delete-destination-highlight button"><?php echo esc_html(__('Delete', TOURISM_CORE_TD)); ?></button>
		</div>
		<div class="tourism-core-destination-highlight-content">
			<p>
				<label for="tourism_core_destination_highlight_title_{{tourism_index}}"><?php echo esc_html(__('Title:', TOURISM_CORE_TD)); ?></label>
				<input type="text" id="tourism_core_destination_highlight_title_{{tourism_index}}" name="tourism_destination_highlights[{{tourism_index}}][title]" value="" class="widefat" />
			</p>
			<p>
				<label for="tourism_core_destination_highlight_description_{{tourism_index}}"><?php echo esc_html(__('Description:', TOURISM_CORE_TD)); ?></label>
				<textarea id="tourism_core_destination_highlight_description_{{tourism_index}}" name="tourism_destination_highlights[{{tourism_index}}][description]" class="tourism-core-simple-editor widefat" rows="4"></textarea>
			</p>
		</div>
	</div>
</script>
