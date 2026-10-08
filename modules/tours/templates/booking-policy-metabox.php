<?php
/** Booking policies using the shared native WordPress editor repeater. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
echo '<input type="hidden" name="tourism_booking_policy_metabox_present" value="1" />';
$experience_rows = isset( $tourism_core_booking_policy ) && is_array( $tourism_core_booking_policy ) ? $tourism_core_booking_policy : array();
$experience_config = array(
	'container_id' => 'tourism-core-booking-policy-container',
	'item_class' => 'tourism-core-repeater-item tourism-core-booking-policy-item',
	'id_prefix' => 'tourism_core_te_booking_policy',
	'post_key' => 'tourism_booking_policy_items',
	'add_id' => 'tourism-core-add-booking-policy',
	'template_id' => 'tourism-core-tmpl-booking-policy-item',
	'delete_class' => 'tourism-core-delete-booking-policy',
	'row_label' => 'Política',
	'add_label' => 'Añadir política',
	'placeholder' => 'Ejemplo: cancelaciones y reprogramaciones',
	'header_class' => 'tourism-core-repeater-item-header',
	'body_class' => 'tourism-core-repeater-item-body',
	'label_class' => 'tourism-core-repeater-item-title',
);
require TOURISM_CORE_PATH . 'modules/tours/templates/experience-repeater.php';
