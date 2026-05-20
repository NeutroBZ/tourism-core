<?php
/**
 * Cover image field for departamento taxonomy (add / edit screens).
 *
 * Expected variables:
 * - array $tourism_core_departamento_cover [
 *       'cover_id' => int,
 *       'context'  => 'add'|'edit',
 *   ]
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_departamento_cover = isset($tourism_core_departamento_cover) && is_array($tourism_core_departamento_cover)
	? $tourism_core_departamento_cover
	: array();

$tourism_core_cover_id = isset($tourism_core_departamento_cover['cover_id']) ? (int) $tourism_core_departamento_cover['cover_id'] : 0;
$tourism_core_context = isset($tourism_core_departamento_cover['context']) ? (string) $tourism_core_departamento_cover['context'] : 'add';
$tourism_core_is_edit = $tourism_core_context === 'edit';

$tourism_core_cover_preview = $tourism_core_cover_id ? wp_get_attachment_image($tourism_core_cover_id, 'medium') : '';

wp_nonce_field('tourism_core_save_departamento_cover', 'tourism_departamento_cover_nonce');

$render_cover_field = static function () use ($tourism_core_cover_id, $tourism_core_cover_preview) {
	?>
	<div class="tourism-core-departamento-cover-field">
		<div id="tourism-departamento-cover-preview" class="tourism-core-departamento-cover-preview-wrap">
			<?php echo $tourism_core_cover_preview; ?>
		</div>

		<input
			type="hidden"
			id="tourism_departamento_cover_image"
			name="tourism_departamento_cover_image"
			value="<?php echo esc_attr((string) $tourism_core_cover_id); ?>"
		/>

		<p class="tourism-core-departamento-cover-actions">
			<button type="button" class="button tourism-upload-departamento-cover">
				<?php esc_html_e('Select cover image', TOURISM_CORE_TD); ?>
			</button>
			<button type="button" class="button tourism-remove-departamento-cover">
				<?php esc_html_e('Remove', TOURISM_CORE_TD); ?>
			</button>
		</p>
		<p class="description">
			<?php esc_html_e('Image used as the department cover in listings and archives.', TOURISM_CORE_TD); ?>
		</p>
	</div>
	<?php
};

if ($tourism_core_is_edit) {
	?>
	<tr class="form-field tourism-core-departamento-cover-row">
		<th scope="row">
			<label for="tourism_departamento_cover_image"><?php esc_html_e('Cover image', TOURISM_CORE_TD); ?></label>
		</th>
		<td>
			<?php $render_cover_field(); ?>
		</td>
	</tr>
	<?php
	return;
}
?>
<div class="form-field tourism-core-departamento-cover-row">
	<label for="tourism_departamento_cover_image"><?php esc_html_e('Cover image', TOURISM_CORE_TD); ?></label>
	<?php $render_cover_field(); ?>
</div>
