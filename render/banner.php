<?php
/**
 * Banner render.
 *
 * @var array $attributes Block attributes.
 */

$variant     = isset( $attributes['variant'] ) && 'square' === $attributes['variant'] ? 'square' : 'horizontal';
$image_id    = ! empty( $attributes['imageId'] ) ? absint( $attributes['imageId'] ) : 0;
$image_url   = ! empty( $attributes['imageUrl'] ) ? esc_url_raw( $attributes['imageUrl'] ) : '';
$image_alt   = ! empty( $attributes['imageAlt'] ) ? $attributes['imageAlt'] : '';
$link_url    = ! empty( $attributes['linkUrl'] ) ? esc_url( $attributes['linkUrl'] ) : '';
$link_target = ! empty( $attributes['linkTarget'] ) ? $attributes['linkTarget'] : '_blank';

if ( ! $image_url && $image_id ) {
	$image_url = wp_get_attachment_image_url( $image_id, 'full' );
}

if ( ! $image_alt && $image_id ) {
	$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
}

$classes = array(
	'kompas-banner',
	'kompas-banner--' . $variant,
);

if ( $image_url ) {
	$classes[] = 'kompas-banner--has-image';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<?php if ( $image_url ) : ?>
		<?php if ( $link_url ) : ?>
		<a href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>" rel="noopener noreferrer nofollow">
		<?php endif; ?>
		<img
			src="<?php echo esc_url( $image_url ); ?>"
			alt="<?php echo esc_attr( $image_alt ); ?>"
			class="kompas-banner__image"
			loading="lazy"
		/>
		<?php if ( $link_url ) : ?>
		</a>
		<?php endif; ?>
	<?php else : ?>
		<div class="kompas-banner__placeholder">БАНЕР</div>
	<?php endif; ?>
</div>
