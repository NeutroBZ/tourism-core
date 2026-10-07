<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$post_id = isset( $post_id ) ? absint( $post_id ) : get_the_ID();
$card = isset( $card ) ? $card : tourism_core_get_tour_card_data( $post_id );
if ( ! $card ) { return; }
$fields = tourism_core_tour_card_fields( isset( $fields ) ? $fields : array( 'image', 'title', 'duration', 'price' ) );
$labels = array( 'duration' => __( 'Duración', TOURISM_CORE_TD ), 'difficulty' => __( 'Dificultad', TOURISM_CORE_TD ), 'group_size' => __( 'Grupo', TOURISM_CORE_TD ), 'max_elevation' => __( 'Altitud máxima', TOURISM_CORE_TD ) );
?>
<article class="tourism-core-card">
	<a class="tourism-core-card__link" href="<?php echo esc_url( $card['url'] ); ?>" aria-label="<?php echo esc_attr( $card['title'] ); ?>">
		<?php if ( in_array( 'image', $fields, true ) && ( $card['image_id'] || $card['image_url'] ) ) : ?>
			<div class="tourism-core-card__image">
				<?php if ( $card['image_id'] ) : ?>
					<?php echo wp_get_attachment_image( $card['image_id'], 'medium_large', false, array( 'alt' => '', 'loading' => 'lazy' ) ); ?>
				<?php else : ?>
					<img src="<?php echo esc_url( $card['image_url'] ); ?>" alt="" loading="lazy" decoding="async" />
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="tourism-core-card__body">
			<?php if ( in_array( 'category', $fields, true ) && '' !== $card['category'] ) : ?>
				<div class="tourism-core-card__category"><?php echo esc_html( $card['category'] ); ?></div>
			<?php endif; ?>
			<?php if ( in_array( 'title', $fields, true ) ) : ?>
				<h3 class="tourism-core-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
			<?php endif; ?>
			<?php if ( in_array( 'excerpt', $fields, true ) && '' !== $card['excerpt'] ) : ?>
				<p class="tourism-core-card__excerpt"><?php echo esc_html( $card['excerpt'] ); ?></p>
			<?php endif; ?>
			<?php foreach ( $labels as $field => $label ) : ?>
				<?php if ( in_array( $field, $fields, true ) && '' !== $card[ $field ] ) : ?>
					<div class="tourism-core-card__meta"><span><?php echo esc_html( $label ); ?>:</span> <?php echo esc_html( $card[ $field ] ); ?></div>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php if ( in_array( 'price', $fields, true ) && '' !== $card['price'] ) : ?>
				<div class="tourism-core-card__price"><?php echo esc_html__( 'Desde', TOURISM_CORE_TD ) . ' ' . esc_html( $card['price'] ); ?></div>
			<?php endif; ?>
		</div>
	</a>
</article>
