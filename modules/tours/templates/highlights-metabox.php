<?php
/** Experience content using the full native WordPress editor. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
echo '<input type="hidden" name="tourism_highlights_metabox_present" value="1" />';

$experience_rows = isset( $tourism_core_highlights ) && is_array( $tourism_core_highlights ) ? $tourism_core_highlights : array();
$experience_config = array(
	'container_id' => 'tourism-core-highlights-container',
	'item_class' => 'tourism-core-repeater-item tourism-core-highlight-item',
	'id_prefix' => 'tourism_core_te_highlights',
	'post_key' => 'tourism_highlights_items',
	'add_id' => 'tourism-core-add-highlight',
	'template_id' => 'tourism-core-tmpl-highlight-item',
	'delete_class' => 'tourism-core-delete-highlight',
	'row_label' => 'Destacado',
	'add_label' => 'Añadir destacado',
	'placeholder' => 'Ejemplo: vistas panorámicas de Machu Picchu',
	'header_class' => 'tourism-core-repeater-item-header',
	'body_class' => 'tourism-core-repeater-item-body',
	'label_class' => 'tourism-core-repeater-item-title',
);
require TOURISM_CORE_PATH . 'modules/tours/templates/experience-repeater.php';
