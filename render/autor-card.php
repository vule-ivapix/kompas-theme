<?php
/**
 * Render: kompas/autor-card
 */
$autor_id = ! empty( $attributes['autorId'] ) ? (int) $attributes['autorId'] : 0;

if ( $autor_id <= 0 ) {
	return '';
}

$autor = get_post( $autor_id );
if ( ! $autor || 'publish' !== $autor->post_status || 'kompas_autor' !== $autor->post_type ) {
	return '';
}

$name  = get_the_title( $autor );
$bio   = trim( get_the_excerpt( $autor ) );
$photo = get_the_post_thumbnail_url( $autor_id, 'thumbnail' );
?>
<div class="kompas-autor-card">
	<?php if ( $photo ) : ?>
	<img
		src="<?php echo esc_url( $photo ); ?>"
		alt="<?php echo esc_attr( $name ); ?>"
		class="kompas-autor-card__photo"
	/>
	<?php endif; ?>
	<div class="kompas-autor-card__info">
		<strong class="kompas-autor-card__name"><?php echo esc_html( mb_strtoupper( $name ) ); ?></strong>
		<?php if ( $bio ) : ?>
		<p class="kompas-autor-card__bio"><?php echo esc_html( $bio ); ?></p>
		<?php endif; ?>
	</div>
</div>
