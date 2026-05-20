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
?>

<input type="hidden" name="tourism_site_hours_metabox_present" value="1" />

<p style="margin:0;max-width:760px;">
	<label for="tourism_agency_hours"><strong><?php esc_html_e( 'Hours', TOURISM_CORE_TD ); ?></strong></label>
	<textarea id="tourism_agency_hours" name="tourism_agency_hours" class="widefat" rows="5" placeholder="<?php esc_attr_e( 'Mon–Fri: 9:00 – 18:00', TOURISM_CORE_TD ); ?>"><?php echo esc_textarea( $tourism_core_hours ); ?></textarea>
</p>
