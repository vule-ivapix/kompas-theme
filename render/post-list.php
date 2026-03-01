<?php
/**
 * Render: kompas/post-list
 *
 * Attributes:
 *   orderby      — 'date' (default) or 'views'
 *   postsPerPage — integer, default 12
 */

$orderby       = isset( $attributes['orderby'] ) ? $attributes['orderby'] : 'date';
$posts_per_page = isset( $attributes['postsPerPage'] ) ? max( 1, (int) $attributes['postsPerPage'] ) : 12;

$paged = max( 1, (int) ( get_query_var( 'paged' ) ?: get_query_var( 'page' ) ?: 1 ) );

$query_args = array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => $posts_per_page,
	'paged'          => $paged,
);

if ( 'views' === $orderby ) {
	$query_args['meta_key'] = 'kompas_views';
	$query_args['orderby']  = 'meta_value_num';
	$query_args['order']    = 'DESC';
} else {
	$query_args['orderby'] = 'date';
	$query_args['order']   = 'DESC';
}

$query = new WP_Query( $query_args );
?>
<div class="kompas-post-list">
	<?php if ( $query->have_posts() ) : ?>
	<div class="kompas-post-list__grid">
		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
		<article class="kompas-post-list__item">
			<?php if ( has_post_thumbnail() ) : ?>
			<a class="kompas-post-list__thumb-link" href="<?php the_permalink(); ?>">
				<img class="kompas-post-list__thumb"
					src="<?php echo esc_url( get_the_post_thumbnail_url( null, 'medium_large' ) ); ?>"
					alt="<?php the_title_attribute(); ?>" />
			</a>
			<?php endif; ?>
			<h3 class="kompas-post-list__title">
				<a href="<?php the_permalink(); ?>"><?php echo esc_html( kompas_truncate_title( get_the_title() ) ); ?></a>
			</h3>
			<?php
			$excerpt = get_the_excerpt();
			if ( $excerpt ) :
			?>
			<p class="kompas-post-list__excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 20 ) ); ?></p>
			<?php endif; ?>
			<time class="kompas-post-list__date" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
				<?php echo esc_html( get_the_date( 'd.m.Y.' ) ); ?>
			</time>
		</article>
		<?php endwhile; ?>
	</div>

	<?php
	$pagination = paginate_links( array(
		'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
		'format'    => '?paged=%#%',
		'current'   => $paged,
		'total'     => $query->max_num_pages,
		'prev_text' => '&larr;',
		'next_text' => '&rarr;',
		'type'      => 'list',
	) );
	if ( $pagination ) :
	?>
	<nav class="kompas-post-list__pagination" aria-label="Straničenje">
		<?php echo $pagination; ?>
	</nav>
	<?php endif; ?>

	<?php else : ?>
	<p style="color:var(--wp--preset--color--muted)">Нема постова за приказ.</p>
	<?php endif; ?>
</div>
<?php
wp_reset_postdata();
