<?php
/**
 * Render: kompas/rec-urednika
 */
// Čitaj iz wp_options → fallback na block atribut.
$option_post_id = (int) get_option( 'kompas_rec_urednika_post_id', 0 );
$post_id        = $option_post_id > 0 ? $option_post_id : ( ! empty( $attributes['postId'] ) ? (int) $attributes['postId'] : 0 );
$title         = ! empty( $attributes['title'] ) ? $attributes['title'] : 'РЕЧ УРЕДНИКА';
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

$post_link = get_permalink( $post );

// Slika: wp_options (settings) → block atribut → ništa.
$option_image = (string) get_option( 'kompas_rec_urednika_image_url', '' );
$image_url    = $option_image !== '' ? $option_image : ( ! empty( $attributes['imageUrl'] ) ? $attributes['imageUrl'] : '' );

// Category archive link for "ПОГЛЕДАЈ СВЕ НАСЛОВНИЦЕ".
$cat_link = '';
$cat      = get_category_by_slug( $category_slug );
if ( $cat ) {
	$cat_link = get_category_link( $cat->term_id );
}
?>
<div class="kompas-rec-urednika">
	<div class="kompas-rec-urednika__heading kompas-section-topline">
		<a href="<?php echo esc_url( $post_link ); ?>" class="kompas-rec-urednika__title-link">
			<h3 class="kompas-rec-urednika__title"><?php echo esc_html( $title ); ?></h3>
		</a>
		<a href="<?php echo esc_url( $post_link ); ?>" class="kompas-rec-urednika__read">
			Прочитај <span class="kompas-rec-urednika__arrow">→</span>
		</a>
	</div>

	<?php if ( $cat_link ) : ?>
	<a href="<?php echo esc_url( $cat_link ); ?>" class="kompas-rec-urednika__more"><?php echo esc_html( $link_text ); ?></a>
	<?php endif; ?>

	<?php if ( $image_url ) : ?>
	<img src="<?php echo esc_url( $image_url ); ?>" alt="" class="kompas-rec-urednika__image" style="width:100%;height:auto;display:block;margin-top:1rem;" />
	<?php endif; ?>
</div>
