<?php
/**
 * Agency details metabox.
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

$tourism_core_name     = isset( $tourism_core_site_agency['name'] ) ? (string) $tourism_core_site_agency['name'] : '';
$tourism_core_phone    = isset( $tourism_core_site_agency['phone'] ) ? (string) $tourism_core_site_agency['phone'] : '';
$tourism_core_whatsapp = isset( $tourism_core_site_agency['whatsapp'] ) ? (string) $tourism_core_site_agency['whatsapp'] : '';
$tourism_core_email    = isset( $tourism_core_site_agency['email'] ) ? (string) $tourism_core_site_agency['email'] : '';
$tourism_core_address     = isset( $tourism_core_site_agency['address'] ) ? (string) $tourism_core_site_agency['address'] : '';
$tourism_core_google_maps = isset( $tourism_core_site_agency['google_maps'] ) ? (string) $tourism_core_site_agency['google_maps'] : '';
?>

<input type="hidden" name="tourism_site_agency_metabox_present" value="1" />

<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 16px;max-width:760px;">
	<p style="margin:0;grid-column:1/-1;">
		<label for="tourism_agency_name"><strong><?php esc_html_e( 'Name', TOURISM_CORE_TD ); ?></strong></label>
		<input type="text" id="tourism_agency_name" name="tourism_agency_name" value="<?php echo esc_attr( $tourism_core_name ); ?>" class="widefat" />
	</p>

	<p style="margin:0;">
		<label for="tourism_agency_phone"><strong><?php esc_html_e( 'Phone', TOURISM_CORE_TD ); ?></strong></label>
		<input type="text" inputmode="tel" id="tourism_agency_phone" name="tourism_agency_phone" value="<?php echo esc_attr( $tourism_core_phone ); ?>" class="widefat" />
	</p>

	<p style="margin:0;">
		<label for="tourism_agency_whatsapp"><strong><?php esc_html_e( 'WhatsApp', TOURISM_CORE_TD ); ?></strong></label>
		<input type="text" inputmode="tel" id="tourism_agency_whatsapp" name="tourism_agency_whatsapp" value="<?php echo esc_attr( $tourism_core_whatsapp ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. +51 999 999 999', TOURISM_CORE_TD ); ?>" />
	</p>

	<p style="margin:0;grid-column:1/-1;">
		<label for="tourism_agency_email"><strong><?php esc_html_e( 'Email', TOURISM_CORE_TD ); ?></strong></label>
		<input type="email" id="tourism_agency_email" name="tourism_agency_email" value="<?php echo esc_attr( $tourism_core_email ); ?>" class="widefat" />
	</p>

	<p style="margin:0;grid-column:1/-1;">
		<label for="tourism_agency_address"><strong><?php esc_html_e( 'Address', TOURISM_CORE_TD ); ?></strong></label>
		<textarea id="tourism_agency_address" name="tourism_agency_address" class="widefat" rows="3"><?php echo esc_textarea( $tourism_core_address ); ?></textarea>
	</p>

	<p style="margin:0;grid-column:1/-1;">
		<label for="tourism_agency_google_maps"><strong><?php esc_html_e( 'Google Maps link', TOURISM_CORE_TD ); ?></strong></label>
		<input type="url" id="tourism_agency_google_maps" name="tourism_agency_google_maps" value="<?php echo esc_attr( $tourism_core_google_maps ); ?>" class="widefat" placeholder="https://maps.google.com/..." />
	</p>
</div>
