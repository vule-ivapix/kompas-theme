<?php
/**
 * Render: kompas/gallery-slider
 */
$images = ! empty( $attributes['images'] ) ? $attributes['images'] : array();

if ( empty( $images ) ) {
	return '';
}

$total = count( $images );
$uid   = 'kompas-slider-' . wp_unique_id();
?>
<div class="kompas-gallery-slider" id="<?php echo esc_attr( $uid ); ?>" data-total="<?php echo esc_attr( $total ); ?>">

	<div class="kompas-gallery-slider__viewport">
		<?php foreach ( $images as $index => $img ) :
			$url     = ! empty( $img['url'] ) ? $img['url'] : '';
			$alt     = ! empty( $img['alt'] ) ? $img['alt'] : '';
			$hidden  = $index > 0 ? ' style="display:none"' : '';
		?>
		<div class="kompas-gallery-slider__slide" data-index="<?php echo $index; ?>"<?php echo $hidden; ?>>
			<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" class="kompas-gallery-slider__img" />
		</div>
		<?php endforeach; ?>

		<button class="kompas-gallery-slider__arrow kompas-gallery-slider__arrow--prev" type="button" aria-label="Претходна">&lsaquo;</button>
		<button class="kompas-gallery-slider__arrow kompas-gallery-slider__arrow--next" type="button" aria-label="Следећа">&rsaquo;</button>

		<div class="kompas-gallery-slider__fraction">
			<span class="kompas-gallery-slider__current">1</span>/<span class="kompas-gallery-slider__total"><?php echo $total; ?></span>
		</div>
	</div>

	<?php
	// Credit: show the credit of the first visible slide, JS updates it.
	$first_credit = ! empty( $images[0]['credit'] ) ? $images[0]['credit'] : '';
	?>
	<p class="kompas-gallery-slider__credit" data-credits="<?php echo esc_attr( wp_json_encode( array_map( function( $img ) {
		return ! empty( $img['credit'] ) ? $img['credit'] : '';
	}, $images ) ) ); ?>">
		<?php if ( $first_credit ) : ?>
			Фото: <?php echo esc_html( $first_credit ); ?>
		<?php endif; ?>
	</p>

</div>
