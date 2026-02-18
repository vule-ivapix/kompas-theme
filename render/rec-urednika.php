<?php
/**
 * Render: kompas/rec-urednika
 */
$post_id       = ! empty( $attributes['postId'] ) ? (int) $attributes['postId'] : 0;
$title         = ! empty( $attributes['title'] ) ? $attributes['title'] : 'РЕЧ ГЛАВНЕ УРЕДНИЦЕ';
$link_text     = ! empty( $attributes['linkText'] ) ? $attributes['linkText'] : 'ПОГЛЕДАЈ СВЕ НАСЛОВНИЦЕ';
$category_slug = ! empty( $attributes['categorySlug'] ) ? $attributes['categorySlug'] : 'rec-urednika';

if ( $post_id ) {
	$post = get_post( $post_id );
} else {
	// Fallback: latest post from the category.
	$cat  = get_category_by_slug( $category_slug );
	$args = array(
		'posts_per_page' => 1,
		'post_status'    => 'publish',
	);
	if ( $cat ) {
		$args['category'] = $cat->term_id;
	}
	$posts = get_posts( $args );
	$post  = ! empty( $posts ) ? $posts[0] : null;
}

if ( ! $post ) {
	return '';
}

$thumb_url = get_the_post_thumbnail_url( $post, 'large' );

// Category archive link for "ПОГЛЕДАЈ СВЕ НАСЛОВНИЦЕ".
$cat_link = '';
$cat      = get_category_by_slug( $category_slug );
if ( $cat ) {
	$cat_link = get_category_link( $cat->term_id );
}
?>
<div class="kompas-rec-urednika">
	<div class="kompas-rec-urednika__heading kompas-section-topline">
		<h3 class="kompas-rec-urednika__title"><?php echo esc_html( $title ); ?></h3>
	</div>

	<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="kompas-rec-urednika__image-link">
		<?php if ( $thumb_url ) : ?>
		<img src="<?php echo esc_url( $thumb_url ); ?>"
			 alt="<?php echo esc_attr( get_the_title( $post ) ); ?>"
			 class="kompas-rec-urednika__image" />
		<?php endif; ?>
	</a>

	<?php if ( $cat_link ) : ?>
	<a href="<?php echo esc_url( $cat_link ); ?>" class="kompas-rec-urednika__more"><?php echo esc_html( $link_text ); ?></a>
	<?php endif; ?>
</div>
