<?php
/**
 * Site modules metabox.
 *
 * @package Tourism_Core
 *
 * Expected variables:
 * - array<string, bool> $tourism_core_site_modules
 * - array<string, array{label: string, description: string, default: bool}> $tourism_core_module_registry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tourism_core_site_modules    = isset( $tourism_core_site_modules ) && is_array( $tourism_core_site_modules ) ? $tourism_core_site_modules : array();
$tourism_core_module_registry = isset( $tourism_core_module_registry ) && is_array( $tourism_core_module_registry ) ? $tourism_core_module_registry : array();
?>

<input type="hidden" name="tourism_site_modules_metabox_present" value="1" />

<p class="description" style="margin-top:0;">
	<?php esc_html_e( 'Choose which features are active on this website. Disabled modules hide their menus; existing content stays in the database.', TOURISM_CORE_TD ); ?>
</p>

<table class="widefat striped" style="max-width:720px;margin-top:12px;">
	<thead>
		<tr>
			<th style="width:48px;"><?php esc_html_e( 'On', TOURISM_CORE_TD ); ?></th>
			<th><?php esc_html_e( 'Module', TOURISM_CORE_TD ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $tourism_core_module_registry as $tourism_core_module_key => $tourism_core_module_def ) : ?>
			<?php
			$tourism_core_enabled = ! empty( $tourism_core_site_modules[ $tourism_core_module_key ] );
			$tourism_core_label   = isset( $tourism_core_module_def['label'] ) ? (string) $tourism_core_module_def['label'] : $tourism_core_module_key;
			$tourism_core_desc    = isset( $tourism_core_module_def['description'] ) ? (string) $tourism_core_module_def['description'] : '';
			?>
			<tr>
				<td>
					<input
						type="checkbox"
						id="tourism_core_module_<?php echo esc_attr( $tourism_core_module_key ); ?>"
						name="tourism_core_modules[<?php echo esc_attr( $tourism_core_module_key ); ?>]"
						value="1"
						<?php checked( $tourism_core_enabled ); ?>
					/>
				</td>
				<td>
					<label for="tourism_core_module_<?php echo esc_attr( $tourism_core_module_key ); ?>">
						<strong><?php echo esc_html( $tourism_core_label ); ?></strong>
					</label>
					<?php if ( $tourism_core_desc ) : ?>
						<p class="description" style="margin:4px 0 0;"><?php echo esc_html( $tourism_core_desc ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
