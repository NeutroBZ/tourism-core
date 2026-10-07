<?php
/** Experience content using the full native WordPress editor. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
echo '<input type="hidden" name="tourism_what_to_expect_metabox_present" value="1" />';

$experience_rows = isset( $tourism_core_what_to_expect ) && is_array( $tourism_core_what_to_expect ) ? $tourism_core_what_to_expect : array();
$experience_config = array(
	'container_id' => 'tourism-core-what-to-expect-container',
	'item_class' => 'tourism-core-repeater-item tourism-core-what-to-expect-item',
	'id_prefix' => 'tourism_core_te_what_to_expect',
	'post_key' => 'tourism_what_to_expect_items',
	'add_id' => 'tourism-core-add-what-to-expect',
	'template_id' => 'tourism-core-tmpl-what-to-expect-item',
	'delete_class' => 'tourism-core-delete-what-to-expect',
	'row_label' => 'Experiencia',
	'add_label' => 'Añadir experiencia',
	'placeholder' => 'Ejemplo: clima en zonas de altitud',
	'header_class' => 'tourism-core-repeater-item-header',
	'body_class' => 'tourism-core-repeater-item-body',
	'label_class' => 'tourism-core-repeater-item-title',
);
require TOURISM_CORE_PATH . 'modules/tours/templates/experience-repeater.php';
