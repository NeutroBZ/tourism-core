<?php
/**
 * Destination ubication (location) metabox.
 *
 * Expected variable:
 * - array $tourism_core_destination_ubication [
 *       'departamento_term_id' => int,
 *       'provincia_term_id' => int,
 *       'distrito_term_id' => int,
 *       'departamentos' => WP_Term[],
 *       'provincias' => WP_Term[],
 *       'distritos' => WP_Term[],
 *       'latitud' => string,
 *       'longitud' => string,
 *       'altitud' => string,
 *   ]
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_destination_ubication = isset($tourism_core_destination_ubication) && is_array($tourism_core_destination_ubication)
	? $tourism_core_destination_ubication
	: array();

$tourism_core_departamento_term_id = isset($tourism_core_destination_ubication['departamento_term_id'])
	? (int) $tourism_core_destination_ubication['departamento_term_id']
	: 0;
$tourism_core_provincia_term_id = isset($tourism_core_destination_ubication['provincia_term_id'])
	? (int) $tourism_core_destination_ubication['provincia_term_id']
	: 0;
$tourism_core_distrito_term_id = isset($tourism_core_destination_ubication['distrito_term_id'])
	? (int) $tourism_core_destination_ubication['distrito_term_id']
	: 0;

$tourism_core_departamentos = isset($tourism_core_destination_ubication['departamentos']) && is_array($tourism_core_destination_ubication['departamentos'])
	? $tourism_core_destination_ubication['departamentos']
	: array();
$tourism_core_provincias = isset($tourism_core_destination_ubication['provincias']) && is_array($tourism_core_destination_ubication['provincias'])
	? $tourism_core_destination_ubication['provincias']
	: array();
$tourism_core_distritos = isset($tourism_core_destination_ubication['distritos']) && is_array($tourism_core_destination_ubication['distritos'])
	? $tourism_core_destination_ubication['distritos']
	: array();

$tourism_core_latitud = isset($tourism_core_destination_ubication['latitud']) ? (string) $tourism_core_destination_ubication['latitud'] : '';
$tourism_core_longitud = isset($tourism_core_destination_ubication['longitud']) ? (string) $tourism_core_destination_ubication['longitud'] : '';
$tourism_core_altitud = isset($tourism_core_destination_ubication['altitud']) ? (string) $tourism_core_destination_ubication['altitud'] : '';
$tourism_core_order = isset($tourism_core_destination_ubication['order']) ? (int) $tourism_core_destination_ubication['order'] : 0;
?>

<input type="hidden" name="tourism_destination_ubication_metabox_present" value="1" />

<p style="margin:0 0 10px;" class="description">
	<?php esc_html_e('Selecciona el ámbito administrativo y completa las coordenadas si aplica.', TOURISM_CORE_TD); ?>
</p>

<p style="margin:0 0 10px;">
	<label for="tourism_destination_departamento_term"><strong><?php esc_html_e('Departamento', TOURISM_CORE_TD); ?></strong></label>
	<select id="tourism_destination_departamento_term" name="tourism_destination_departamento_term" class="widefat">
		<option value="0"><?php esc_html_e('— Seleccionar —', TOURISM_CORE_TD); ?></option>
		<?php foreach ($tourism_core_departamentos as $tourism_core_term) : ?>
			<?php if (! ($tourism_core_term instanceof WP_Term)) continue; ?>
			<option value="<?php echo esc_attr((string) $tourism_core_term->term_id); ?>" <?php selected($tourism_core_departamento_term_id, (int) $tourism_core_term->term_id); ?>>
				<?php echo esc_html($tourism_core_term->name); ?>
			</option>
		<?php endforeach; ?>
	</select>
</p>

<p style="margin:0 0 10px;">
	<label for="tourism_destination_provincia_term"><strong><?php esc_html_e('Provincia', TOURISM_CORE_TD); ?></strong></label>
	<select id="tourism_destination_provincia_term" name="tourism_destination_provincia_term" class="widefat">
		<option value="0"><?php esc_html_e('— Seleccionar —', TOURISM_CORE_TD); ?></option>
		<?php foreach ($tourism_core_provincias as $tourism_core_term) : ?>
			<?php if (! ($tourism_core_term instanceof WP_Term)) continue; ?>
			<option value="<?php echo esc_attr((string) $tourism_core_term->term_id); ?>" <?php selected($tourism_core_provincia_term_id, (int) $tourism_core_term->term_id); ?>>
				<?php echo esc_html($tourism_core_term->name); ?>
			</option>
		<?php endforeach; ?>
	</select>
</p>

<p style="margin:0 0 14px;">
	<label for="tourism_destination_distrito_term"><strong><?php esc_html_e('Distrito', TOURISM_CORE_TD); ?></strong></label>
	<select id="tourism_destination_distrito_term" name="tourism_destination_distrito_term" class="widefat">
		<option value="0"><?php esc_html_e('— Seleccionar —', TOURISM_CORE_TD); ?></option>
		<?php foreach ($tourism_core_distritos as $tourism_core_term) : ?>
			<?php if (! ($tourism_core_term instanceof WP_Term)) continue; ?>
			<option value="<?php echo esc_attr((string) $tourism_core_term->term_id); ?>" <?php selected($tourism_core_distrito_term_id, (int) $tourism_core_term->term_id); ?>>
				<?php echo esc_html($tourism_core_term->name); ?>
			</option>
		<?php endforeach; ?>
	</select>
</p>

<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;max-width:760px;">
	<p style="margin:0;">
		<label for="tourism_destination_latitud"><strong><?php esc_html_e('Latitud', TOURISM_CORE_TD); ?></strong></label>
		<input
			type="text"
			inputmode="decimal"
			id="tourism_destination_latitud"
			name="tourism_destination_latitud"
			value="<?php echo esc_attr($tourism_core_latitud); ?>"
			class="widefat"
			placeholder="<?php esc_attr_e('Ej: -12.046374', TOURISM_CORE_TD); ?>"
		/>
	</p>

	<p style="margin:0;">
		<label for="tourism_destination_longitud"><strong><?php esc_html_e('Longitud', TOURISM_CORE_TD); ?></strong></label>
		<input
			type="text"
			inputmode="decimal"
			id="tourism_destination_longitud"
			name="tourism_destination_longitud"
			value="<?php echo esc_attr($tourism_core_longitud); ?>"
			class="widefat"
			placeholder="<?php esc_attr_e('Ej: -77.042793', TOURISM_CORE_TD); ?>"
		/>
	</p>

	<p style="margin:0;">
		<label for="tourism_destination_altitud"><strong><?php esc_html_e('Altitud (m)', TOURISM_CORE_TD); ?></strong></label>
		<input
			type="text"
			inputmode="numeric"
			id="tourism_destination_altitud"
			name="tourism_destination_altitud"
			value="<?php echo esc_attr($tourism_core_altitud); ?>"
			class="widefat"
			placeholder="<?php esc_attr_e('Ej: 154', TOURISM_CORE_TD); ?>"
		/>
	</p>
</div>

<p style="margin-top:14px;margin-bottom:0;">
	<label for="tourism_destination_order"><strong><?php esc_html_e('Orden en listados', TOURISM_CORE_TD); ?></strong></label>
	<input
		type="number"
		min="0"
		step="1"
		id="tourism_destination_order"
		name="tourism_destination_order"
		value="<?php echo esc_attr((string) $tourism_core_order); ?>"
		class="small-text"
		style="margin-right:6px;"
	/>
	<span class="description">
		<?php esc_html_e('Usa números enteros (0, 1, 2…). Menor número = aparece antes en el listado de destinos.', TOURISM_CORE_TD); ?>
	</span>
</p>
