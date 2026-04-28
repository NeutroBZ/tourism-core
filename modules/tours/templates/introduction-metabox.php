<?php
/**
 * Tour introduction metabox.
 *
 * Expected variable:
 * - array $tourism_core_introduction [title, description].
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_introduction = isset($tourism_core_introduction) && is_array($tourism_core_introduction) ? $tourism_core_introduction : array();
$tourism_core_intro_title = isset($tourism_core_introduction['title']) ? (string) $tourism_core_introduction['title'] : '';
$tourism_core_intro_description = isset($tourism_core_introduction['description']) ? (string) $tourism_core_introduction['description'] : '';
?>

<input type="hidden" name="tourism_introduction_metabox_present" value="1" />

<div class="tourism-core-introduction-fields">
	<p>
		<label for="tourism_intro_title"><?php esc_html_e('Introduction title', TOURISM_CORE_TD); ?></label>
		<input
			type="text"
			id="tourism_intro_title"
			name="tourism_intro_title"
			value="<?php echo esc_attr($tourism_core_intro_title); ?>"
			class="widefat"
			placeholder="<?php esc_attr_e('Example: Discover Machu Picchu', TOURISM_CORE_TD); ?>"
		/>
	</p>

	<p>
		<label for="tourism_intro_description"><?php esc_html_e('Introduction description', TOURISM_CORE_TD); ?></label>
		<textarea
			id="tourism_intro_description"
			name="tourism_intro_description"
			rows="6"
			class="widefat"
			placeholder="<?php esc_attr_e('Write a short introduction for the tour.', TOURISM_CORE_TD); ?>"
		><?php echo esc_textarea($tourism_core_intro_description); ?></textarea>
	</p>
</div>
