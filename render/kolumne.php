<?php
/**
 * Render: kompas/kolumne
 */
$post_ids = ! empty( $attributes['postIds'] ) ? array_map( 'absint', $attributes['postIds'] ) : array();
$count    = isset( $attributes['count'] ) ? (int) $attributes['count'] : 3;

if ( ! empty( $post_ids ) ) {
	$posts = get_posts( array(
		'post__in'       => $post_ids,
		'orderby'        => 'post__in',
		'posts_per_page' => count( $post_ids ),
		'post_status'    => 'publish',
	) );
} else {
	// Fallback: latest posts from "kolumne" category.
	$cat = get_category_by_slug( 'kolumne' );
	$posts = get_posts( array(
		'category'       => $cat ? $cat->term_id : 0,
		'posts_per_page' => $count,
		'post_status'    => 'publish',
	) );
}

if ( empty( $posts ) ) {
	return '';
}

// Get the kolumne category link for "POGLEDAJ SVE".
$cat_link = '';
$cat = get_category_by_slug( 'kolumne' );
if ( $cat ) {
	$cat_link = get_category_link( $cat->term_id );
}
?>
<div class="kompas-kolumne">
	<h3 class="kompas-kolumne__title">КОЛУМНЕ</h3>

	<div class="kompas-kolumne__list">
		<?php foreach ( $posts as $p ) :
			$author_id   = $p->post_author;
			$author_name = get_the_author_meta( 'display_name', $author_id );
			$avatar_url  = get_avatar_url( $author_id, array( 'size' => 120 ) );
			$excerpt     = wp_trim_words( get_the_excerpt( $p ), 12 );
		?>
		<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="kompas-kolumne__item">
			<img src="<?php echo esc_url( $avatar_url ); ?>"
				 alt="<?php echo esc_attr( $author_name ); ?>"
				 class="kompas-kolumne__avatar" />
			<div class="kompas-kolumne__text">
				<span class="kompas-kolumne__name"><?php echo esc_html( mb_strtoupper( $author_name ) ); ?></span>
				<span class="kompas-kolumne__excerpt"><?php echo esc_html( $excerpt ); ?></span>
			</div>
		</a>
		<?php endforeach; ?>
	</div>

	<?php if ( $cat_link ) : ?>
	<a href="<?php echo esc_url( $cat_link ); ?>" class="kompas-kolumne__more">ПОГЛЕДАЈ СВЕ</a>
	<?php endif; ?>
</div>
