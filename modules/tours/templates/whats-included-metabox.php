<?php
if (! defined('ABSPATH')) exit;

$includes = isset($includes) && is_array($includes) ? $includes : array();
$excludes = isset($excludes) && is_array($excludes) ? $excludes : array();
?>

<input type="hidden" name="tourism_whats_included_metabox_present" value="1" />

<div class="tourism-core-includes-metabox">
	<div class="tourism-core-includes-metabox-grid">

		<div class="tourism-core-includes-column">
			<h3 class="tourism-core-includes-heading"><?php esc_html_e('Included', TOURISM_CORE_TD); ?></h3>
			<div id="tourism-includes-container" class="tourism-core-label-sortable">
				<?php foreach ($includes as $i => $item) : ?>
					<?php
					$item = is_array($item) ? $item : array();
					$title = isset($item['title']) ? (string) $item['title'] : '';
					$content = isset($item['content']) ? (string) $item['content'] : '';
					?>
					<div class="tourism-include-item tourism-core-label-item">
						<div class="tourism-core-label-row-header">
							<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
							<span class="tourism-core-label-item-title">
								<?php esc_html_e('Item', TOURISM_CORE_TD); ?>
								#<span class="tourism-core-label-item-num"><?php echo esc_html((string) ((int) $i + 1)); ?></span>
							</span>
							<button type="button" class="tourism-delete-include button"><?php esc_html_e('Delete', TOURISM_CORE_TD); ?></button>
						</div>
						<div class="tourism-core-label-row-body">
							<label for="tourism_includes_title_<?php echo esc_attr((string) $i); ?>"><?php esc_html_e('Title', TOURISM_CORE_TD); ?></label>
							<input
								type="text"
								id="tourism_includes_title_<?php echo esc_attr((string) $i); ?>"
								class="widefat tourism-core-include-title-input"
								name="tourism_includes[<?php echo esc_attr((string) $i); ?>][title]"
								value="<?php echo esc_attr($title); ?>"
								placeholder="<?php esc_attr_e('Title', TOURISM_CORE_TD); ?>"
							/>
							<label for="tourism_includes_content_<?php echo esc_attr((string) $i); ?>"><?php esc_html_e('Content', TOURISM_CORE_TD); ?></label>
							<textarea
								id="tourism_includes_content_<?php echo esc_attr((string) $i); ?>"
								class="widefat tourism-wysiwyg"
								name="tourism_includes[<?php echo esc_attr((string) $i); ?>][content]"
								rows="5"
							><?php echo esc_textarea($content); ?></textarea>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<p>
				<button type="button" id="tourism-add-include" class="button button-secondary">
					<?php esc_html_e('Add item', TOURISM_CORE_TD); ?>
				</button>
			</p>
		</div>

		<div class="tourism-core-excludes-column">
			<h3 class="tourism-core-includes-heading"><?php esc_html_e('Not Included', TOURISM_CORE_TD); ?></h3>
			<div id="tourism-excludes-container" class="tourism-core-label-sortable">
				<?php foreach ($excludes as $i => $item) : ?>
					<?php
					$item = is_array($item) ? $item : array();
					$title = isset($item['title']) ? (string) $item['title'] : '';
					$content = isset($item['content']) ? (string) $item['content'] : '';
					?>
					<div class="tourism-exclude-item tourism-core-label-item">
						<div class="tourism-core-label-row-header">
							<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
							<span class="tourism-core-label-item-title">
								<?php esc_html_e('Item', TOURISM_CORE_TD); ?>
								#<span class="tourism-core-label-item-num"><?php echo esc_html((string) ((int) $i + 1)); ?></span>
							</span>
							<button type="button" class="tourism-delete-exclude button"><?php esc_html_e('Delete', TOURISM_CORE_TD); ?></button>
						</div>
						<div class="tourism-core-label-row-body">
							<label for="tourism_excludes_title_<?php echo esc_attr((string) $i); ?>"><?php esc_html_e('Title', TOURISM_CORE_TD); ?></label>
							<input
								type="text"
								id="tourism_excludes_title_<?php echo esc_attr((string) $i); ?>"
								class="widefat tourism-core-exclude-title-input"
								name="tourism_excludes[<?php echo esc_attr((string) $i); ?>][title]"
								value="<?php echo esc_attr($title); ?>"
								placeholder="<?php esc_attr_e('Title', TOURISM_CORE_TD); ?>"
							/>
							<label for="tourism_excludes_content_<?php echo esc_attr((string) $i); ?>"><?php esc_html_e('Content', TOURISM_CORE_TD); ?></label>
							<textarea
								id="tourism_excludes_content_<?php echo esc_attr((string) $i); ?>"
								class="widefat tourism-wysiwyg"
								name="tourism_excludes[<?php echo esc_attr((string) $i); ?>][content]"
								rows="5"
							><?php echo esc_textarea($content); ?></textarea>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<p>
				<button type="button" id="tourism-add-exclude" class="button button-secondary">
					<?php esc_html_e('Add item', TOURISM_CORE_TD); ?>
				</button>
			</p>
		</div>

	</div>
</div>

<script type="text/template" id="tmpl-include">
	<div class="tourism-include-item tourism-core-label-item">
		<div class="tourism-core-label-row-header">
			<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
			<span class="tourism-core-label-item-title">
				<?php echo esc_html(__('Item', TOURISM_CORE_TD)); ?>
				#<span class="tourism-core-label-item-num">{{num}}</span>
			</span>
			<button type="button" class="tourism-delete-include button"><?php echo esc_html(__('Delete', TOURISM_CORE_TD)); ?></button>
		</div>
		<div class="tourism-core-label-row-body">
			<label for="tourism_includes_title_{{i}}"><?php echo esc_html(__('Title', TOURISM_CORE_TD)); ?></label>
			<input type="text" id="tourism_includes_title_{{i}}" class="widefat tourism-core-include-title-input" name="tourism_includes[{{i}}][title]" value="" placeholder="<?php echo esc_attr(__('Title', TOURISM_CORE_TD)); ?>" />
			<label for="tourism_includes_content_{{i}}"><?php echo esc_html(__('Content', TOURISM_CORE_TD)); ?></label>
			<textarea id="tourism_includes_content_{{i}}" class="widefat tourism-wysiwyg" name="tourism_includes[{{i}}][content]" rows="5"></textarea>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-exclude">
	<div class="tourism-exclude-item tourism-core-label-item">
		<div class="tourism-core-label-row-header">
			<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
			<span class="tourism-core-label-item-title">
				<?php echo esc_html(__('Item', TOURISM_CORE_TD)); ?>
				#<span class="tourism-core-label-item-num">{{num}}</span>
			</span>
			<button type="button" class="tourism-delete-exclude button"><?php echo esc_html(__('Delete', TOURISM_CORE_TD)); ?></button>
		</div>
		<div class="tourism-core-label-row-body">
			<label for="tourism_excludes_title_{{i}}"><?php echo esc_html(__('Title', TOURISM_CORE_TD)); ?></label>
			<input type="text" id="tourism_excludes_title_{{i}}" class="widefat tourism-core-exclude-title-input" name="tourism_excludes[{{i}}][title]" value="" placeholder="<?php echo esc_attr(__('Title', TOURISM_CORE_TD)); ?>" />
			<label for="tourism_excludes_content_{{i}}"><?php echo esc_html(__('Content', TOURISM_CORE_TD)); ?></label>
			<textarea id="tourism_excludes_content_{{i}}" class="widefat tourism-wysiwyg" name="tourism_excludes[{{i}}][content]" rows="5"></textarea>
		</div>
	</div>
</script>
