<?php
/**
 * Render: kompas/photo-gallery
 */
$images = ! empty( $attributes['images'] ) ? $attributes['images'] : array();

if ( empty( $images ) ) {
	return '';
}
?>
<div class="kompas-photo-gallery">
	<div class="kompas-photo-gallery__grid">
		<?php
		foreach ( $images as $index => $img ) :
			$thumb  = ! empty( $img['url'] )    ? $img['url']    : '';
			$full   = ! empty( $img['fullUrl'] ) ? $img['fullUrl'] : $thumb;
			$alt    = ! empty( $img['alt'] )    ? $img['alt']    : '';
			$source = ! empty( $img['source'] )
				? $img['source']
				: ( ! empty( $img['id'] ) ? get_post_meta( (int) $img['id'], 'kompas_image_source', true ) : '' );

			if ( $index === 1 ) : // Otvori red thumbnaila pre druge slike
		?>
		<div class="kompas-photo-gallery__thumbs-row">
		<?php endif; ?>

		<button
			class="kompas-photo-gallery__thumb"
			type="button"
			data-index="<?php echo esc_attr( $index ); ?>"
			data-full="<?php echo esc_url( $full ); ?>"
			data-source="<?php echo esc_attr( $source ); ?>"
			data-alt="<?php echo esc_attr( $alt ); ?>"
		>
			<img
				src="<?php echo esc_url( $thumb ); ?>"
				alt="<?php echo esc_attr( $alt ); ?>"
				loading="lazy"
			/>
		</button>

		<?php endforeach; ?>
		<?php if ( count( $images ) > 1 ) : ?>
		</div><?php // Zatvori thumbs-row ?>
		<?php endif; ?>
	</div>
</div>
