<?php
/**
 * Render: kompas/homepage-hero
 *
 * Layout: 6 posts
 * - Post 1 → center main (big image 16/10 + title 1.75rem)
 * - Posts 2-4 → left sidebar (image 16/9 + title 1rem, border-bottom)
 * - Posts 5-6 → center sub (horizontal: image left 400px + title right)
 *
 * If fewer than 6 are manually selected, remaining slots filled by latest posts.
 */
$hero_ids = ! empty( $attributes['heroPostIds'] ) ? array_map( 'absint', $attributes['heroPostIds'] ) : array();
$total_needed = 6;

$posts = array();

// Fetch manually selected posts (preserving order).
if ( ! empty( $hero_ids ) ) {
	$posts = get_posts( array(
		'post__in'       => $hero_ids,
		'orderby'        => 'post__in',
		'posts_per_page' => count( $hero_ids ),
		'post_status'    => 'publish',
	) );
}

// Fill remaining slots with latest posts by date.
$remaining = $total_needed - count( $posts );
if ( $remaining > 0 ) {
	$exclude = wp_list_pluck( $posts, 'ID' );
	$filler  = get_posts( array(
		'posts_per_page' => $remaining,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
		'post__not_in'   => $exclude,
	) );
	$posts = array_merge( $posts, $filler );
}

if ( empty( $posts ) ) {
	return '';
}

// Distribute posts: 1 main, 2-4 sidebar, 5-6 sub.
$main_post     = isset( $posts[0] ) ? $posts[0] : null;
$sidebar_posts = array_slice( $posts, 1, 3 );
$sub_posts     = array_slice( $posts, 4, 2 );
?>
<div class="kompas-homepage-hero">

	<!-- Left sidebar (3 posts) -->
	<div class="kompas-homepage-hero__sidebar">
		<?php $sidebar_last = count( $sidebar_posts ) - 1; foreach ( $sidebar_posts as $si => $p ) : ?>
		<div class="kompas-homepage-hero__sidebar-item" style="margin-bottom:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);<?php echo $si < $sidebar_last ? 'border-bottom:1px solid var(--wp--preset--color--border)' : ''; ?>">
			<?php if ( has_post_thumbnail( $p ) ) : ?>
			<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" style="display:block;margin-bottom:var(--wp--preset--spacing--30)">
				<img src="<?php echo esc_url( get_the_post_thumbnail_url( $p, 'kompas-thumbnail' ) ); ?>"
					 alt="<?php echo esc_attr( get_the_title( $p ) ); ?>"
					 style="width:100%;aspect-ratio:16/9;object-fit:cover;display:block" />
			</a>
			<?php endif; ?>
			<h3 style="font-size:1.0625rem;font-weight:700;line-height:1.3;margin:0"<?php echo kompas_get_post_title_no_translate_data_attr( $p->ID ); ?>>
				<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" style="color:var(--wp--preset--color--dark);text-decoration:none"><?php echo esc_html( kompas_truncate_title( get_the_title( $p ) ) ); ?></a>
			</h3>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Center column -->
	<div class="kompas-homepage-hero__center">

		<!-- Main post -->
		<?php if ( $main_post ) : ?>
		<div class="kompas-homepage-hero__main" style="margin-bottom:var(--wp--preset--spacing--50)">
			<?php if ( has_post_thumbnail( $main_post ) ) : ?>
			<a href="<?php echo esc_url( get_permalink( $main_post ) ); ?>" style="display:block;margin-bottom:var(--wp--preset--spacing--40)">
				<img src="<?php echo esc_url( get_the_post_thumbnail_url( $main_post, 'kompas-hero' ) ); ?>"
					 alt="<?php echo esc_attr( get_the_title( $main_post ) ); ?>"
					 style="width:100%;aspect-ratio:16/10;object-fit:cover;display:block" />
			</a>
			<?php endif; ?>
			<h2 style="font-size:2rem;font-weight:700;line-height:1.2;margin:0"<?php echo kompas_get_post_title_no_translate_data_attr( $main_post->ID ); ?>>
				<a href="<?php echo esc_url( get_permalink( $main_post ) ); ?>" style="color:var(--wp--preset--color--dark);text-decoration:none"><?php echo esc_html( kompas_truncate_title( get_the_title( $main_post ) ) ); ?></a>
			</h2>
		</div>
		<?php endif; ?>

		<!-- Sub posts (horizontal cards) -->
		<?php $sub_last = count( $sub_posts ) - 1; foreach ( $sub_posts as $si => $p ) : ?>
		<div class="kompas-homepage-hero__sub-item" style="display:flex;flex-wrap:nowrap;align-items:flex-start;gap:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);<?php echo $si < $sub_last ? 'border-bottom:1px solid var(--wp--preset--color--border)' : ''; ?>">
			<?php if ( has_post_thumbnail( $p ) ) : ?>
			<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" style="flex:0 0 190px;max-width:190px;display:block">
				<img src="<?php echo esc_url( get_the_post_thumbnail_url( $p, 'kompas-thumbnail' ) ); ?>"
					 alt="<?php echo esc_attr( get_the_title( $p ) ); ?>"
					 style="width:100%;aspect-ratio:16/10;object-fit:cover;display:block" />
			</a>
			<?php endif; ?>
			<div style="flex:1 1 auto">
				<h3 style="font-size:1.0625rem;font-weight:700;line-height:1.3;margin:0"<?php echo kompas_get_post_title_no_translate_data_attr( $p->ID ); ?>>
					<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" style="color:var(--wp--preset--color--dark);text-decoration:none"><?php echo esc_html( kompas_truncate_title( get_the_title( $p ) ) ); ?></a>
				</h3>
			</div>
		</div>
		<?php endforeach; ?>

	</div>

</div>
