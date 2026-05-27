<?php
/**
 * Tour WeTravel UUID metabox.
 *
 * @package Tourism_Core
 *
 * Expected variable:
 * - string $tourism_core_wetravel_uuid
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_wetravel_uuid = isset($tourism_core_wetravel_uuid) ? (string) $tourism_core_wetravel_uuid : '';
$tourism_core_wetravel_preview_url = $tourism_core_wetravel_uuid !== ''
	? 'https://www.wetravel.com/checkout_embed?uuid=' . rawurlencode($tourism_core_wetravel_uuid)
	: '';
?>

<input type="hidden" name="tourism_wetravel_metabox_present" value="1" />

<div style="padding:12px 0;">
	<p style="margin-bottom:12px;color:#555;font-size:13px;">
		<?php esc_html_e('Pega el código que te da WeTravel y extrae solo el número del atributo', TOURISM_CORE_TD); ?>
		<code style="background:#f0f0f0;padding:2px 5px;border-radius:3px;">data-uuid="XXXXXXXX"</code>.
	</p>

	<label
		for="tourism_wetravel_button_code"
		style="font-weight:600;display:block;margin-bottom:6px;font-size:13px;"
	>
		<?php esc_html_e('UUID de WeTravel:', TOURISM_CORE_TD); ?>
	</label>

	<input
		type="text"
		id="tourism_wetravel_button_code"
		name="tourism_wetravel_button_code"
		value="<?php echo esc_attr($tourism_core_wetravel_uuid); ?>"
		style="width:320px;font-family:monospace;font-size:14px;padding:6px 10px;"
		placeholder="<?php esc_attr_e('Ej: 51840477', TOURISM_CORE_TD); ?>"
		inputmode="numeric"
		pattern="[0-9]*"
	/>

	<small style="color:#888;display:block;margin-top:8px;font-size:12px;">
		<?php esc_html_e('Solo el número, sin espacios ni texto extra.', TOURISM_CORE_TD); ?>
	</small>

	<?php if ($tourism_core_wetravel_preview_url !== '') : ?>
		<p style="margin-top:14px;font-size:12px;color:#2271b1;">
			<?php esc_html_e('UUID guardado.', TOURISM_CORE_TD); ?>
			<a href="<?php echo esc_url($tourism_core_wetravel_preview_url); ?>" target="_blank" rel="noopener">
				<?php esc_html_e('Ver checkout en WeTravel ↗', TOURISM_CORE_TD); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
