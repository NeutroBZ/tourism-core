<?php
/**
 * Business hours metabox.
 *
 * @package Tourism_Core
 *
 * Expected variable:
 * - array<string, string> $tourism_core_site_agency
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tourism_core_site_agency = isset( $tourism_core_site_agency ) && is_array( $tourism_core_site_agency )
	? $tourism_core_site_agency
	: array();

$tourism_core_hours = isset( $tourism_core_site_agency['hours'] ) ? (string) $tourism_core_site_agency['hours'] : '';
$tourism_core_schedule = isset( $tourism_core_site_agency['hours_schedule'] ) && is_array( $tourism_core_site_agency['hours_schedule'] )
	? $tourism_core_site_agency['hours_schedule']
	: array();

$tourism_core_days_selected = isset( $tourism_core_schedule['days'] ) && is_array( $tourism_core_schedule['days'] ) ? $tourism_core_schedule['days'] : array();
$tourism_core_open          = isset( $tourism_core_schedule['open'] ) && is_array( $tourism_core_schedule['open'] ) ? $tourism_core_schedule['open'] : array();
$tourism_core_close         = isset( $tourism_core_schedule['close'] ) && is_array( $tourism_core_schedule['close'] ) ? $tourism_core_schedule['close'] : array();

$tourism_core_open_hour   = isset( $tourism_core_open['hour'] ) ? (string) $tourism_core_open['hour'] : '9';
$tourism_core_open_minute = isset( $tourism_core_open['minute'] ) ? (string) $tourism_core_open['minute'] : '00';
$tourism_core_open_ampm   = isset( $tourism_core_open['ampm'] ) ? (string) $tourism_core_open['ampm'] : 'AM';

$tourism_core_close_hour   = isset( $tourism_core_close['hour'] ) ? (string) $tourism_core_close['hour'] : '6';
$tourism_core_close_minute = isset( $tourism_core_close['minute'] ) ? (string) $tourism_core_close['minute'] : '00';
$tourism_core_close_ampm   = isset( $tourism_core_close['ampm'] ) ? (string) $tourism_core_close['ampm'] : 'PM';

$tourism_core_days = array(
	'mon' => __( 'M', TOURISM_CORE_TD ),
	'tue' => __( 'T', TOURISM_CORE_TD ),
	'wed' => __( 'W', TOURISM_CORE_TD ),
	'thu' => __( 'T', TOURISM_CORE_TD ),
	'fri' => __( 'F', TOURISM_CORE_TD ),
	'sat' => __( 'S', TOURISM_CORE_TD ),
	'sun' => __( 'S', TOURISM_CORE_TD ),
);
?>

<input type="hidden" name="tourism_site_hours_metabox_present" value="1" />

<div style="max-width:760px;">
	<div style="margin-bottom:10px;">
		<strong><?php esc_html_e( 'On these days', TOURISM_CORE_TD ); ?></strong>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
		<?php foreach ( $tourism_core_days as $tourism_core_day_key => $tourism_core_day_label ) : ?>
			<label
				style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border:1px solid #d0d4d9;border-radius:18px;background:#fff;cursor:pointer;"
				class="tourism-core-hours-day-item"
			>
				<input
					type="checkbox"
					name="tourism_agency_hours_days[]"
					value="<?php echo esc_attr( $tourism_core_day_key ); ?>"
					<?php checked( in_array( $tourism_core_day_key, $tourism_core_days_selected, true ) ); ?>
					style="position:absolute;opacity:0;pointer-events:none;"
				/>
				<span style="font-weight:600;color:#606a73;"><?php echo esc_html( $tourism_core_day_label ); ?></span>
			</label>
		<?php endforeach; ?>
	</div>

	<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
		<div>
			<strong style="display:block;margin-bottom:6px;"><?php esc_html_e( 'Opening time', TOURISM_CORE_TD ); ?></strong>
			<div style="display:flex;gap:8px;">
				<select name="tourism_agency_hours_open[hour]" class="regular-text" style="max-width:80px;">
					<?php for ( $tourism_core_i = 1; $tourism_core_i <= 12; $tourism_core_i++ ) : ?>
						<option value="<?php echo esc_attr( (string) $tourism_core_i ); ?>" <?php selected( (string) $tourism_core_i, $tourism_core_open_hour ); ?>><?php echo esc_html( (string) $tourism_core_i ); ?></option>
					<?php endfor; ?>
				</select>
				<select name="tourism_agency_hours_open[minute]" class="regular-text" style="max-width:80px;">
					<?php foreach ( array( '00', '15', '30', '45' ) as $tourism_core_minute ) : ?>
						<option value="<?php echo esc_attr( $tourism_core_minute ); ?>" <?php selected( $tourism_core_minute, $tourism_core_open_minute ); ?>><?php echo esc_html( $tourism_core_minute ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="tourism_agency_hours_open[ampm]" class="regular-text" style="max-width:90px;">
					<option value="AM" <?php selected( 'AM', $tourism_core_open_ampm ); ?>>AM</option>
					<option value="PM" <?php selected( 'PM', $tourism_core_open_ampm ); ?>>PM</option>
				</select>
			</div>
		</div>

		<div>
			<strong style="display:block;margin-bottom:6px;"><?php esc_html_e( 'Closing time', TOURISM_CORE_TD ); ?></strong>
			<div style="display:flex;gap:8px;">
				<select name="tourism_agency_hours_close[hour]" class="regular-text" style="max-width:80px;">
					<?php for ( $tourism_core_i = 1; $tourism_core_i <= 12; $tourism_core_i++ ) : ?>
						<option value="<?php echo esc_attr( (string) $tourism_core_i ); ?>" <?php selected( (string) $tourism_core_i, $tourism_core_close_hour ); ?>><?php echo esc_html( (string) $tourism_core_i ); ?></option>
					<?php endfor; ?>
				</select>
				<select name="tourism_agency_hours_close[minute]" class="regular-text" style="max-width:80px;">
					<?php foreach ( array( '00', '15', '30', '45' ) as $tourism_core_minute ) : ?>
						<option value="<?php echo esc_attr( $tourism_core_minute ); ?>" <?php selected( $tourism_core_minute, $tourism_core_close_minute ); ?>><?php echo esc_html( $tourism_core_minute ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="tourism_agency_hours_close[ampm]" class="regular-text" style="max-width:90px;">
					<option value="AM" <?php selected( 'AM', $tourism_core_close_ampm ); ?>>AM</option>
					<option value="PM" <?php selected( 'PM', $tourism_core_close_ampm ); ?>>PM</option>
				</select>
			</div>
		</div>
	</div>

	<p style="margin:12px 0 0;">
		<label for="tourism_agency_hours"><strong><?php esc_html_e( 'Notes (optional)', TOURISM_CORE_TD ); ?></strong></label>
		<textarea id="tourism_agency_hours" name="tourism_agency_hours" class="widefat" rows="3" placeholder="<?php esc_attr_e( 'Optional: special holiday notes, exceptions, etc.', TOURISM_CORE_TD ); ?>"><?php echo esc_textarea( $tourism_core_hours ); ?></textarea>
	</p>
</div>
