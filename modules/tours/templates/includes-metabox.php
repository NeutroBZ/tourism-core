<?php
/**
 * Tour includes / not-includes metabox (two columns, label-only repeater rows).
 *
 * Expected variables:
 * - array<int, string> $tourism_core_includes_list
 * - array<int, string> $tourism_core_excludes_list
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_includes_list = isset($tourism_core_includes_list) && is_array($tourism_core_includes_list) ? $tourism_core_includes_list : array();
$tourism_core_excludes_list = isset($tourism_core_excludes_list) && is_array($tourism_core_excludes_list) ? $tourism_core_excludes_list : array();
?>

<input type="hidden" name="tourism_includes_excludes_metabox_present" value="1" />

<div class="tourism-core-includes-metabox">
	<div class="tourism-core-includes-metabox-grid">
		<div class="tourism-core-includes-column">
			<h3 class="tourism-core-includes-heading"><?php esc_html_e('Included', TOURISM_CORE_TD); ?></h3>
			<div id="tourism-core-includes-container" class="tourism-core-label-sortable">
				<?php foreach ($tourism_core_includes_list as $tourism_core_index => $tourism_core_label) : ?>
					<div class="tourism-core-include-item tourism-core-label-item">
						<div class="tourism-core-label-row-header">
							<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
							<span class="tourism-core-label-item-title">
								<?php esc_html_e('Item', TOURISM_CORE_TD); ?>
								#<span class="tourism-core-label-item-num"><?php echo esc_html((string) ((int) $tourism_core_index + 1)); ?></span>
							</span>
							<button type="button" class="tourism-core-delete-include button"><?php esc_html_e('Delete', TOURISM_CORE_TD); ?></button>
						</div>
						<div class="tourism-core-label-row-body">
							<label class="screen-reader-text" for="tourism_include_label_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Label', TOURISM_CORE_TD); ?></label>
							<input
								type="text"
								id="tourism_include_label_<?php echo esc_attr((string) $tourism_core_index); ?>"
								class="widefat tourism-core-include-label-input"
								name="tourism_includes_list[<?php echo esc_attr((string) $tourism_core_index); ?>][label]"
								value="<?php echo esc_attr((string) $tourism_core_label); ?>"
								placeholder="<?php esc_attr_e('Label', TOURISM_CORE_TD); ?>"
							/>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<p>
				<button type="button" id="tourism-core-add-include" class="button button-secondary">
					<?php esc_html_e('Add item', TOURISM_CORE_TD); ?>
				</button>
			</p>
		</div>

		<div class="tourism-core-excludes-column">
			<h3 class="tourism-core-includes-heading"><?php esc_html_e('Not included', TOURISM_CORE_TD); ?></h3>
			<div id="tourism-core-excludes-container" class="tourism-core-label-sortable">
				<?php foreach ($tourism_core_excludes_list as $tourism_core_index => $tourism_core_label) : ?>
					<div class="tourism-core-exclude-item tourism-core-label-item">
						<div class="tourism-core-label-row-header">
							<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
							<span class="tourism-core-label-item-title">
								<?php esc_html_e('Item', TOURISM_CORE_TD); ?>
								#<span class="tourism-core-label-item-num"><?php echo esc_html((string) ((int) $tourism_core_index + 1)); ?></span>
							</span>
							<button type="button" class="tourism-core-delete-exclude button"><?php esc_html_e('Delete', TOURISM_CORE_TD); ?></button>
						</div>
						<div class="tourism-core-label-row-body">
							<label class="screen-reader-text" for="tourism_exclude_label_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Label', TOURISM_CORE_TD); ?></label>
							<input
								type="text"
								id="tourism_exclude_label_<?php echo esc_attr((string) $tourism_core_index); ?>"
								class="widefat tourism-core-exclude-label-input"
								name="tourism_excludes_list[<?php echo esc_attr((string) $tourism_core_index); ?>][label]"
								value="<?php echo esc_attr((string) $tourism_core_label); ?>"
								placeholder="<?php esc_attr_e('Label', TOURISM_CORE_TD); ?>"
							/>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<p>
				<button type="button" id="tourism-core-add-exclude" class="button button-secondary">
					<?php esc_html_e('Add item', TOURISM_CORE_TD); ?>
				</button>
			</p>
		</div>
	</div>
</div>

<script type="text/template" id="tourism-core-tmpl-include-item">
	<div class="tourism-core-include-item tourism-core-label-item">
		<div class="tourism-core-label-row-header">
			<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
			<span class="tourism-core-label-item-title">
				<?php echo esc_html(__('Item', TOURISM_CORE_TD)); ?>
				#<span class="tourism-core-label-item-num">{{tourism_index_plus_one}}</span>
			</span>
			<button type="button" class="tourism-core-delete-include button"><?php echo esc_html(__('Delete', TOURISM_CORE_TD)); ?></button>
		</div>
		<div class="tourism-core-label-row-body">
			<label class="screen-reader-text" for="tourism_include_label_{{tourism_index}}"><?php echo esc_html(__('Label', TOURISM_CORE_TD)); ?></label>
			<input type="text" id="tourism_include_label_{{tourism_index}}" class="widefat tourism-core-include-label-input" name="tourism_includes_list[{{tourism_index}}][label]" value="" placeholder="<?php echo esc_attr(__('Label', TOURISM_CORE_TD)); ?>" />
		</div>
	</div>
</script>

<script type="text/template" id="tourism-core-tmpl-exclude-item">
	<div class="tourism-core-exclude-item tourism-core-label-item">
		<div class="tourism-core-label-row-header">
			<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
			<span class="tourism-core-label-item-title">
				<?php echo esc_html(__('Item', TOURISM_CORE_TD)); ?>
				#<span class="tourism-core-label-item-num">{{tourism_index_plus_one}}</span>
			</span>
			<button type="button" class="tourism-core-delete-exclude button"><?php echo esc_html(__('Delete', TOURISM_CORE_TD)); ?></button>
		</div>
		<div class="tourism-core-label-row-body">
			<label class="screen-reader-text" for="tourism_exclude_label_{{tourism_index}}"><?php echo esc_html(__('Label', TOURISM_CORE_TD)); ?></label>
			<input type="text" id="tourism_exclude_label_{{tourism_index}}" class="widefat tourism-core-exclude-label-input" name="tourism_excludes_list[{{tourism_index}}][label]" value="" placeholder="<?php echo esc_attr(__('Label', TOURISM_CORE_TD)); ?>" />
		</div>
	</div>
</script>
