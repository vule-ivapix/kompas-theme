<?php
/**
 * Render: kompas/video-grid
 * Prikazuje video postove u 3-kolonskom gridu za arhivsku stranicu.
 */
$posts_per_page = ! empty( $attributes['postsPerPage'] ) ? (int) $attributes['postsPerPage'] : 12;

$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

$query = new WP_Query( array(
	'post_type'      => 'kompas_video',
	'post_status'    => 'publish',
	'posts_per_page' => $posts_per_page,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'paged'          => $paged,
) );

if ( ! $query->have_posts() ) {
	echo '<p>Нема видео записа.</p>';
	return;
}
?>
<div class="kompas-video-grid">
	<?php while ( $query->have_posts() ) : $query->the_post();
		$video_id  = get_the_ID();
		$video_url = get_post_meta( $video_id, 'kompas_video_url', true );
		if ( ! $video_url ) continue;

		$yt_id     = kompas_extract_youtube_id( $video_url );
		$thumb_url = get_the_post_thumbnail_url( $video_id, 'medium_large' );
		if ( ! $thumb_url && $yt_id ) {
			$thumb_url = 'https://img.youtube.com/vi/' . $yt_id . '/hqdefault.jpg';
		}
		$title = get_the_title();
		$desc  = wp_strip_all_tags( get_the_content() );
		$date  = get_the_date( 'd.m.Y. H:i' );
	?>
	<div class="kompas-video-card"
		data-video-url="<?php echo esc_attr( $video_url ); ?>"
		data-title="<?php echo esc_attr( $title ); ?>"
		data-desc="<?php echo esc_attr( $desc ); ?>"
		data-date="<?php echo esc_attr( $date ); ?>"
	>
		<div class="kompas-video-card__thumb">
			<?php if ( $thumb_url ) : ?>
			<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
			<?php endif; ?>
			<span class="kompas-video-card__play" aria-hidden="true"></span>
		</div>
		<h4 class="kompas-video-card__title"><?php echo esc_html( $title ); ?></h4>
	</div>
	<?php endwhile; wp_reset_postdata(); ?>
</div>

<?php
$total_pages = $query->max_num_pages;
if ( $total_pages > 1 ) :
	echo '<nav class="kompas-archive-pagination" aria-label="Пагинација">';
	echo kompas_paginate_links( array(
		'total'     => $total_pages,
		'current'   => $paged,
		'mid_size'  => 1,
		'end_size'  => 0,
		'prev_text' => '&#8592;',
		'next_text' => '&#8594;',
	) );
	echo '</nav>';
endif;
