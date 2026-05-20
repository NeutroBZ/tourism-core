<?php
/**
 * Social networks metabox.
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

$tourism_core_facebook  = isset( $tourism_core_site_agency['facebook'] ) ? (string) $tourism_core_site_agency['facebook'] : '';
$tourism_core_instagram = isset( $tourism_core_site_agency['instagram'] ) ? (string) $tourism_core_site_agency['instagram'] : '';
$tourism_core_tiktok    = isset( $tourism_core_site_agency['tiktok'] ) ? (string) $tourism_core_site_agency['tiktok'] : '';
?>

<input type="hidden" name="tourism_site_social_metabox_present" value="1" />

<div style="display:grid;grid-template-columns:1fr;gap:12px;max-width:760px;">
	<p style="margin:0;">
		<label for="tourism_social_facebook"><strong><?php esc_html_e( 'Facebook', TOURISM_CORE_TD ); ?></strong></label>
		<input type="url" id="tourism_social_facebook" name="tourism_social_facebook" value="<?php echo esc_attr( $tourism_core_facebook ); ?>" class="widefat" placeholder="https://" />
	</p>

	<p style="margin:0;">
		<label for="tourism_social_instagram"><strong><?php esc_html_e( 'Instagram', TOURISM_CORE_TD ); ?></strong></label>
		<input type="url" id="tourism_social_instagram" name="tourism_social_instagram" value="<?php echo esc_attr( $tourism_core_instagram ); ?>" class="widefat" placeholder="https://" />
	</p>

	<p style="margin:0;">
		<label for="tourism_social_tiktok"><strong><?php esc_html_e( 'TikTok', TOURISM_CORE_TD ); ?></strong></label>
		<input type="url" id="tourism_social_tiktok" name="tourism_social_tiktok" value="<?php echo esc_attr( $tourism_core_tiktok ); ?>" class="widefat" placeholder="https://" />
	</p>
</div>
