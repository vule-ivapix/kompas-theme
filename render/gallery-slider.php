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
<div class="kompas-gallery-slider" id="<?php echo esc_attr( $uid ); ?>">

	<div class="kompas-gallery-slider__viewport">
		<?php foreach ( $images as $index => $img ) :
			$url    = ! empty( $img['url'] )     ? $img['url']     : '';
			$thumb  = ! empty( $img['thumbUrl'] ) ? $img['thumbUrl'] : $url;
			$alt    = ! empty( $img['alt'] )     ? $img['alt']     : '';
			$credit = ! empty( $img['credit'] )  ? $img['credit']  : '';
			$hidden = $index > 0 ? ' style="display:none"' : '';
		?>
		<div
			class="kompas-gallery-slider__slide"
			data-index="<?php echo esc_attr( $index ); ?>"
			data-full="<?php echo esc_url( $url ); ?>"
			data-credit="<?php echo esc_attr( $credit ); ?>"
			<?php echo $hidden; ?>
		>
			<img
				src="<?php echo esc_url( $url ); ?>"
				alt="<?php echo esc_attr( $alt ); ?>"
				class="kompas-gallery-slider__img"
				style="cursor:zoom-in"
			/>
		</div>
		<?php endforeach; ?>

		<?php if ( $total > 1 ) : ?>
		<button class="kompas-gallery-slider__arrow kompas-gallery-slider__arrow--prev" type="button" aria-label="Претходна">&lsaquo;</button>
		<button class="kompas-gallery-slider__arrow kompas-gallery-slider__arrow--next" type="button" aria-label="Следећа">&rsaquo;</button>
		<?php endif; ?>

		<div class="kompas-gallery-slider__fraction">
			<span class="kompas-gallery-slider__current">1</span>/<?php echo esc_html( $total ); ?>
		</div>
	</div>

	<?php if ( $total > 1 ) : ?>
	<div class="kompas-gallery-slider__thumbs">
		<?php foreach ( $images as $index => $img ) :
			$thumb  = ! empty( $img['thumbUrl'] ) ? $img['thumbUrl'] : ( ! empty( $img['url'] ) ? $img['url'] : '' );
			$alt    = ! empty( $img['alt'] ) ? $img['alt'] : '';
			$active = $index === 0 ? ' is-active' : '';
		?>
		<button
			class="kompas-gallery-slider__thumb<?php echo esc_attr( $active ); ?>"
			type="button"
			data-index="<?php echo esc_attr( $index ); ?>"
			aria-label="Слика <?php echo esc_attr( $index + 1 ); ?>"
		>
			<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" />
		</button>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<p class="kompas-gallery-slider__credit">
		<?php if ( ! empty( $images[0]['credit'] ) ) : ?>
			Фото: <?php echo esc_html( $images[0]['credit'] ); ?>
		<?php endif; ?>
	</p>

</div>
