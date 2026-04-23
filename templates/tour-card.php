<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = isset( $post_id ) ? (int) $post_id : get_the_ID();
if ( ! $post_id ) {
	return;
}

$title    = get_the_title( $post_id );
$permalink = get_permalink( $post_id );
$price    = get_post_meta( $post_id, 'tourism_price_from', true );
$duration = get_post_meta( $post_id, 'tourism_duration', true );
?>

<article class="tourism-core-card">
	<a class="tourism-core-card__link" href="<?php echo esc_url( $permalink ); ?>">
		<h3 class="tourism-core-card__title"><?php echo esc_html( $title ); ?></h3>
		<?php if ( $duration ) : ?>
			<div class="tourism-core-card__meta">
				<?php echo esc_html( $duration ); ?>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $price && null !== $price ) : ?>
			<div class="tourism-core-card__price">
				<?php echo esc_html( $price ); ?>
			</div>
		<?php endif; ?>
	</a>
</article>

