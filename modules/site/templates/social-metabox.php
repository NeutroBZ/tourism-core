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

$tourism_core_facebook    = isset( $tourism_core_site_agency['facebook'] ) ? (string) $tourism_core_site_agency['facebook'] : '';
$tourism_core_instagram   = isset( $tourism_core_site_agency['instagram'] ) ? (string) $tourism_core_site_agency['instagram'] : '';
$tourism_core_tripadvisor = isset( $tourism_core_site_agency['tripadvisor'] ) ? (string) $tourism_core_site_agency['tripadvisor'] : '';
$tourism_core_linkedin    = isset( $tourism_core_site_agency['linkedin'] ) ? (string) $tourism_core_site_agency['linkedin'] : '';
$tourism_core_extra       = isset( $tourism_core_site_agency['social_extra'] ) ? (string) $tourism_core_site_agency['social_extra'] : '';
$tourism_core_custom      = isset( $tourism_core_site_agency['social_custom'] ) && is_array( $tourism_core_site_agency['social_custom'] )
	? array_values( $tourism_core_site_agency['social_custom'] )
	: array();

if ( empty( $tourism_core_custom ) && '' !== trim( $tourism_core_extra ) ) {
	$tourism_core_extra_lines = preg_split( '/\r\n|\r|\n/', $tourism_core_extra );
	if ( is_array( $tourism_core_extra_lines ) ) {
		foreach ( $tourism_core_extra_lines as $tourism_core_line ) {
			$tourism_core_line = trim( (string) $tourism_core_line );
			if ( '' === $tourism_core_line ) {
				continue;
			}
			$tourism_core_custom[] = array(
				'label' => '',
				'url'   => $tourism_core_line,
			);
		}
	}
}
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
		<label for="tourism_social_tripadvisor"><strong><?php esc_html_e( 'TripAdvisor', TOURISM_CORE_TD ); ?></strong></label>
		<input type="url" id="tourism_social_tripadvisor" name="tourism_social_tripadvisor" value="<?php echo esc_attr( $tourism_core_tripadvisor ); ?>" class="widefat" placeholder="https://" />
	</p>

	<p style="margin:0;">
		<label for="tourism_social_linkedin"><strong><?php esc_html_e( 'LinkedIn', TOURISM_CORE_TD ); ?></strong></label>
		<input type="url" id="tourism_social_linkedin" name="tourism_social_linkedin" value="<?php echo esc_attr( $tourism_core_linkedin ); ?>" class="widefat" placeholder="https://" />
	</p>

	<div style="margin:0;">
		<label><strong><?php esc_html_e( 'Other networks (optional)', TOURISM_CORE_TD ); ?></strong></label>
		<p class="description" style="margin:4px 0 8px;">
			<?php esc_html_e( 'Add as many social networks as needed (name + URL).', TOURISM_CORE_TD ); ?>
		</p>
		<div id="tourism-core-social-custom-container" style="display:grid;grid-template-columns:1fr;gap:8px;">
			<?php foreach ( $tourism_core_custom as $tourism_core_index => $tourism_core_item ) : ?>
				<?php
				$tourism_core_item_label = isset( $tourism_core_item['label'] ) ? (string) $tourism_core_item['label'] : '';
				$tourism_core_item_url   = isset( $tourism_core_item['url'] ) ? (string) $tourism_core_item['url'] : '';
				?>
				<div class="tourism-core-social-custom-item" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,2fr) auto;gap:8px;align-items:end;">
					<div>
						<label><span class="screen-reader-text"><?php esc_html_e( 'Network name', TOURISM_CORE_TD ); ?></span></label>
						<input type="text" name="tourism_social_custom[<?php echo esc_attr( (string) $tourism_core_index ); ?>][label]" value="<?php echo esc_attr( $tourism_core_item_label ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Network name', TOURISM_CORE_TD ); ?>" />
					</div>
					<div>
						<label><span class="screen-reader-text"><?php esc_html_e( 'Network URL', TOURISM_CORE_TD ); ?></span></label>
						<input type="url" name="tourism_social_custom[<?php echo esc_attr( (string) $tourism_core_index ); ?>][url]" value="<?php echo esc_attr( $tourism_core_item_url ); ?>" class="widefat" placeholder="https://" />
					</div>
					<button type="button" class="button-link-delete tourism-core-social-remove" style="padding-bottom:6px;"><?php esc_html_e( 'Remove', TOURISM_CORE_TD ); ?></button>
				</div>
			<?php endforeach; ?>
		</div>

		<p style="margin:8px 0 0;">
			<button type="button" id="tourism-core-social-add" class="button"><?php esc_html_e( 'Add network', TOURISM_CORE_TD ); ?></button>
		</p>
		<input type="hidden" id="tourism_social_extra" name="tourism_social_extra" value="" />
	</div>
</div>

<script type="text/html" id="tourism-core-social-custom-template">
	<div class="tourism-core-social-custom-item" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,2fr) auto;gap:8px;align-items:end;">
		<div>
			<input type="text" name="tourism_social_custom[{{index}}][label]" value="" class="widefat" placeholder="<?php esc_attr_e( 'Network name', TOURISM_CORE_TD ); ?>" />
		</div>
		<div>
			<input type="url" name="tourism_social_custom[{{index}}][url]" value="" class="widefat" placeholder="https://" />
		</div>
		<button type="button" class="button-link-delete tourism-core-social-remove" style="padding-bottom:6px;"><?php esc_html_e( 'Remove', TOURISM_CORE_TD ); ?></button>
	</div>
</script>
