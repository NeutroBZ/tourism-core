<?php
/**
 * Tour Highlights metabox markup (sortable repeater, title + WYSIWYG content).
 *
 * Expected variables:
 * - array<int, array{title: string, content: string}> $tourism_core_highlights
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_highlights = isset($tourism_core_highlights) && is_array($tourism_core_highlights) ? $tourism_core_highlights : array();

$tourism_core_editor_settings = array(
	'textarea_rows' => 4,
	'media_buttons' => false,
	'quicktags'     => true,
	'tinymce'       => array(
		'wpautop'  => true,
		'plugins'  => 'lists link paste wordpress',
		'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink',
		'toolbar2' => '',
		'height'   => 100,
	),
	'editor_class'  => 'tourism-core-wysiwyg tourism-core-repeater-content',
);
?>

<input type="hidden" name="tourism_highlights_metabox_present" value="1" />

<div id="tourism-core-highlights-container" class="tourism-core-editor-repeater" data-tourism-core-key="highlights">
	<?php foreach ($tourism_core_highlights as $tourism_core_index => $tourism_core_item) : ?>
		<?php
		$tourism_core_title = isset($tourism_core_item['title']) ? (string) $tourism_core_item['title'] : '';
		$tourism_core_content = isset($tourism_core_item['content']) ? (string) $tourism_core_item['content'] : '';
		$tourism_core_editor_id = 'tourism_core_te_highlights_content_' . (int) $tourism_core_index;
		?>
		<div class="tourism-core-repeater-item tourism-core-highlight-item" data-tourism-core-index="<?php echo esc_attr((string) $tourism_core_index); ?>">
			<div class="tourism-core-repeater-item-header">
				<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
				<span class="tourism-core-repeater-item-title">
					<?php esc_html_e('Destacado', TOURISM_CORE_TD); ?>
					#<span class="tourism-core-item-number"><?php echo esc_html((string) ((int) $tourism_core_index + 1)); ?></span>
				</span>
				<button type="button" class="tourism-core-delete-highlight button"><?php esc_html_e('Eliminar', TOURISM_CORE_TD); ?></button>
			</div>
			<div class="tourism-core-repeater-item-body">
				<p>
					<label for="tourism_core_te_highlights_title_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Título', TOURISM_CORE_TD); ?></label>
					<input
						type="text"
						id="tourism_core_te_highlights_title_<?php echo esc_attr((string) $tourism_core_index); ?>"
						class="widefat tourism-core-repeater-title"
						name="tourism_highlights_items[<?php echo esc_attr((string) $tourism_core_index); ?>][title]"
						value="<?php echo esc_attr($tourism_core_title); ?>"
						placeholder="<?php esc_attr_e('Ejemplo: vistas panorámicas de Machu Picchu', TOURISM_CORE_TD); ?>"
					/>
				</p>
				<p>
					<label for="<?php echo esc_attr($tourism_core_editor_id); ?>"><?php esc_html_e('Contenido', TOURISM_CORE_TD); ?></label>
					<?php
					wp_editor(
						$tourism_core_content,
						$tourism_core_editor_id,
						array_merge(
							$tourism_core_editor_settings,
							array('textarea_name' => 'tourism_highlights_items[' . (int) $tourism_core_index . '][content]')
						)
					);
					?>
				</p>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<p>
	<button type="button" id="tourism-core-add-highlight" class="button button-primary">
		<?php esc_html_e('Añadir destacado', TOURISM_CORE_TD); ?>
	</button>
</p>

<script type="text/template" id="tourism-core-tmpl-highlight-item">
	<div class="tourism-core-repeater-item tourism-core-highlight-item" data-tourism-core-index="{{tourism_index}}">
		<div class="tourism-core-repeater-item-header">
			<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
			<span class="tourism-core-repeater-item-title">
				<?php echo esc_html(__('Destacado', TOURISM_CORE_TD)); ?>
				#<span class="tourism-core-item-number">{{tourism_index_plus_one}}</span>
			</span>
			<button type="button" class="tourism-core-delete-highlight button"><?php echo esc_html(__('Eliminar', TOURISM_CORE_TD)); ?></button>
		</div>
		<div class="tourism-core-repeater-item-body">
			<p>
				<label for="tourism_core_te_highlights_title_{{tourism_index}}"><?php echo esc_html(__('Título', TOURISM_CORE_TD)); ?></label>
				<input type="text" id="tourism_core_te_highlights_title_{{tourism_index}}" class="widefat tourism-core-repeater-title" name="tourism_highlights_items[{{tourism_index}}][title]" value="" placeholder="<?php echo esc_attr(__('Ejemplo: vistas panorámicas de Machu Picchu', TOURISM_CORE_TD)); ?>" />
			</p>
			<p>
				<label for="tourism_core_te_highlights_content_{{tourism_index}}"><?php echo esc_html(__('Contenido', TOURISM_CORE_TD)); ?></label>
				<textarea id="tourism_core_te_highlights_content_{{tourism_index}}" name="tourism_highlights_items[{{tourism_index}}][content]" class="tourism-core-wysiwyg tourism-core-repeater-content widefat" rows="4"></textarea>
			</p>
		</div>
	</div>
</script>