<?php
/** Included and excluded content share the Experience editor. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
echo '<input type="hidden" name="tourism_whats_included_metabox_present" value="1" />';
echo '<div class="tourism-experience-inclusions"><div><h3>Incluido</h3>';

$experience_rows = isset( $includes ) && is_array( $includes ) ? $includes : array();
$experience_config = array(
	'container_id' => 'tourism-includes-container',
	'item_class' => 'tourism-include-item tourism-core-label-item',
	'id_prefix' => 'tourism_includes',
	'post_key' => 'tourism_includes',
	'add_id' => 'tourism-add-include',
	'template_id' => 'tmpl-include',
	'delete_class' => 'tourism-delete-include',
	'row_label' => 'Incluido',
	'add_label' => 'Añadir incluido',
	'placeholder' => 'Ejemplo: guía profesional',
	'header_class' => 'tourism-core-label-row-header',
	'body_class' => 'tourism-core-label-row-body',
	'label_class' => 'tourism-core-label-item-title',
);
require TOURISM_CORE_PATH . 'modules/tours/templates/experience-repeater.php';
echo '</div><div><h3>No incluido</h3>';

$experience_rows = isset( $excludes ) && is_array( $excludes ) ? $excludes : array();
$experience_config = array(
	'container_id' => 'tourism-excludes-container',
	'item_class' => 'tourism-exclude-item tourism-core-label-item',
	'id_prefix' => 'tourism_excludes',
	'post_key' => 'tourism_excludes',
	'add_id' => 'tourism-add-exclude',
	'template_id' => 'tmpl-exclude',
	'delete_class' => 'tourism-delete-exclude',
	'row_label' => 'No incluido',
	'add_label' => 'Añadir excluido',
	'placeholder' => 'Ejemplo: gastos personales',
	'header_class' => 'tourism-core-label-row-header',
	'body_class' => 'tourism-core-label-row-body',
	'label_class' => 'tourism-core-label-item-title',
);
require TOURISM_CORE_PATH . 'modules/tours/templates/experience-repeater.php';
echo '</div></div>';
