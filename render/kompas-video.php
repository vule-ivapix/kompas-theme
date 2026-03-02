<?php
/**
 * Render: kompas/video
 * Prikazuje najnovije video postove na naslovnoj strani.
 */
$count = ! empty( $attributes['count'] ) ? (int) $attributes['count'] : 3;

$videos = get_posts( array(
	'post_type'      => 'kompas_video',
	'post_status'    => 'publish',
	'posts_per_page' => $count,
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

if ( empty( $videos ) ) {
	return '';
}

$archive_url = get_post_type_archive_link( 'kompas_video' );
?>
<section class="kompas-video">
	<div class="kompas-video__heading kompas-section-topline">
		<h3 class="kompas-video__title">КОМПАС ВИДЕО</h3>
		<?php if ( $archive_url ) : ?>
		<a href="<?php echo esc_url( $archive_url ); ?>" class="kompas-video__all">ПОГЛЕДАЈ СВЕ</a>
		<?php endif; ?>
	</div>

	<div class="kompas-video__grid">
		<?php foreach ( $videos as $video ) :
			$video_url = get_post_meta( $video->ID, 'kompas_video_url', true );
			if ( ! $video_url ) continue;

			$yt_id     = kompas_extract_youtube_id( $video_url );
			$thumb_url = get_the_post_thumbnail_url( $video->ID, 'medium_large' );
			if ( ! $thumb_url && $yt_id ) {
				$thumb_url = 'https://img.youtube.com/vi/' . $yt_id . '/hqdefault.jpg';
			}
			$title = get_the_title( $video->ID );
			$desc  = get_the_excerpt( $video->ID );
			$date  = get_the_date( '', $video->ID );
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
				<span class="kompas-video-card__play" aria-hidden="true">&#9654;</span>
			</div>
			<h4 class="kompas-video-card__title"><?php echo esc_html( $title ); ?></h4>
		</div>
		<?php endforeach; ?>
	</div>
</section>
