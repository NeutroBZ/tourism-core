<?php
/**
 * Destination introduction metabox.
 *
 * Expected variable:
 * - array $tourism_core_destination_intro [title, description]
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_destination_intro = isset($tourism_core_destination_intro) && is_array($tourism_core_destination_intro) ? $tourism_core_destination_intro : array();
$tourism_core_intro_title = isset($tourism_core_destination_intro['title']) ? (string) $tourism_core_destination_intro['title'] : '';
$tourism_core_intro_description = isset($tourism_core_destination_intro['description']) ? (string) $tourism_core_destination_intro['description'] : '';
?>

<input type="hidden" name="tourism_destination_intro_metabox_present" value="1" />

<p>
	<label for="tourism_destination_intro_title"><strong><?php esc_html_e('Title', TOURISM_CORE_TD); ?></strong></label>
	<input
		type="text"
		id="tourism_destination_intro_title"
		name="tourism_destination_intro_title"
		value="<?php echo esc_attr($tourism_core_intro_title); ?>"
		class="widefat"
		placeholder="<?php esc_attr_e('Example: About Cusco', TOURISM_CORE_TD); ?>"
	/>
</p>

<p>
	<label for="tourism_destination_intro_description"><strong><?php esc_html_e('Description', TOURISM_CORE_TD); ?></strong></label>
	<textarea
		id="tourism_destination_intro_description"
		name="tourism_destination_intro_description"
		rows="6"
		class="widefat"
		placeholder="<?php esc_attr_e('Write a short introduction for this destination.', TOURISM_CORE_TD); ?>"
	><?php echo esc_textarea($tourism_core_intro_description); ?></textarea>
</p>

