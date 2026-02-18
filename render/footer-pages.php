<?php
/**
 * Render: kompas/footer-pages
 */
$selected = ! empty( $attributes['selectedIds'] ) ? array_map( 'absint', $attributes['selectedIds'] ) : array();
$pages    = array();

if ( ! empty( $selected ) ) {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post__in'       => $selected,
			'orderby'        => 'post__in',
			'posts_per_page' => count( $selected ),
			'post_status'    => 'publish',
		)
	);
} else {
	$preferred_slugs = array(
		'o-nama',
		'kontakt',
		'impresum',
		'marketing',
		'pravila-koriscenja',
	);

	foreach ( $preferred_slugs as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			$pages[] = $page;
		}
	}

	if ( empty( $pages ) ) {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'posts_per_page' => 5,
				'post_status'    => 'publish',
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);
	}
}

if ( empty( $pages ) ) {
	return '';
}
?>
<nav class="kompas-footer-pages-nav" aria-label="Футер странице">
	<ul class="kompas-footer-pages-nav__list">
		<?php foreach ( $pages as $page ) : ?>
			<li class="kompas-footer-pages-nav__item">
				<a href="<?php echo esc_url( get_permalink( $page ) ); ?>" class="kompas-footer-pages-nav__link">
					<?php echo esc_html( mb_strtoupper( get_the_title( $page ) ) ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
