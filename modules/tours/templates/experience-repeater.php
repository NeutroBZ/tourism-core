<?php
/** Shared full WordPress editor for the Experience section only. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$experience_rows = isset( $experience_rows ) && is_array( $experience_rows ) ? $experience_rows : array();
$experience_config = isset( $experience_config ) && is_array( $experience_config ) ? $experience_config : array();
if ( ! $experience_config ) { return; }

$render_experience_row = static function ( $index, $row, $template = false ) use ( $experience_config ) {
	$title = isset( $row['title'] ) ? (string) $row['title'] : '';
	$content = isset( $row['content'] ) ? (string) $row['content'] : '';
	$title_id = $experience_config['id_prefix'] . '_title_' . $index;
	$editor_id = $experience_config['id_prefix'] . '_content_' . $index;
	$number = $template ? '{{tourism_index_plus_one}}' : (string) ( (int) $index + 1 );
	?>
	<div class="postbox tourism-experience-item <?php echo esc_attr( $experience_config['item_class'] ); ?>" data-tourism-core-index="<?php echo esc_attr( (string) $index ); ?>">
		<div class="postbox-header <?php echo esc_attr( $experience_config['header_class'] ); ?>">
			<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
			<span class="<?php echo esc_attr( $experience_config['label_class'] ); ?>"><?php echo esc_html( $experience_config['row_label'] ); ?> #<span class="tourism-core-item-number tourism-core-label-item-num"><?php echo esc_html( $number ); ?></span></span>
			<button type="button" class="button <?php echo esc_attr( $experience_config['delete_class'] ); ?>"><?php esc_html_e( 'Eliminar', TOURISM_CORE_TD ); ?></button>
		</div>
		<div class="inside <?php echo esc_attr( $experience_config['body_class'] ); ?>">
			<p><label for="<?php echo esc_attr( $title_id ); ?>"><strong><?php esc_html_e( 'Título de la sección', TOURISM_CORE_TD ); ?></strong></label>
				<input type="text" id="<?php echo esc_attr( $title_id ); ?>" class="widefat tourism-experience-title tourism-core-repeater-title" name="<?php echo esc_attr( $experience_config['post_key'] . '[' . $index . '][title]' ); ?>" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr( $experience_config['placeholder'] ); ?>" /></p>
			<p><label for="<?php echo esc_attr( $editor_id ); ?>"><strong><?php esc_html_e( 'Contenido', TOURISM_CORE_TD ); ?></strong></label></p>
			<?php if ( $template ) : ?>
				<textarea id="<?php echo esc_attr( $editor_id ); ?>" class="widefat tourism-experience-content tourism-core-wysiwyg" name="<?php echo esc_attr( $experience_config['post_key'] . '[' . $index . '][content]' ); ?>" rows="8"></textarea>
			<?php else : ?>
				<?php wp_editor( $content, $editor_id, array(
					'textarea_name' => $experience_config['post_key'] . '[' . (int) $index . '][content]',
					'textarea_rows' => 8,
					'media_buttons' => true,
					'teeny' => false,
					'quicktags' => true,
					'editor_class' => 'tourism-experience-content tourism-core-wysiwyg',
				) ); ?>
			<?php endif; ?>
		</div>
	</div>
	<?php
};
?>
<div id="<?php echo esc_attr( $experience_config['container_id'] ); ?>" class="tourism-experience-repeater">
	<?php foreach ( array_values( $experience_rows ) as $experience_index => $experience_row ) { $render_experience_row( $experience_index, is_array( $experience_row ) ? $experience_row : array() ); } ?>
</div>
<p><button type="button" id="<?php echo esc_attr( $experience_config['add_id'] ); ?>" class="button button-secondary"><?php echo esc_html( $experience_config['add_label'] ); ?></button></p>
<script type="text/html" id="<?php echo esc_attr( $experience_config['template_id'] ); ?>"><?php $render_experience_row( '{{tourism_index}}', array(), true ); ?></script>
