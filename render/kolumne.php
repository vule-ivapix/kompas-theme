<?php
/**
 * Render: kompas/kolumne
 */
$post_ids = ! empty( $attributes['postIds'] ) ? array_map( 'absint', $attributes['postIds'] ) : array();
$count    = isset( $attributes['count'] ) ? (int) $attributes['count'] : 3;

// Fetch the kolumne category once (explicit slug arg bypasses the hidden-categories filter).
$kolumne_cat = get_terms( array(
	'taxonomy'   => 'category',
	'slug'       => 'kolumne',
	'number'     => 1,
	'hide_empty' => false,
) );
$kolumne_term = ( ! empty( $kolumne_cat ) && ! is_wp_error( $kolumne_cat ) ) ? $kolumne_cat[0] : null;

$target = 3;

// Load manually selected posts first.
if ( ! empty( $post_ids ) ) {
	$posts = get_posts( array(
		'post__in'       => $post_ids,
		'orderby'        => 'post__in',
		'posts_per_page' => count( $post_ids ),
		'post_status'    => 'publish',
	) );
} else {
	$posts = array();
}

// Fill up to $target with latest from "kolumne" category, skipping already selected.
if ( count( $posts ) < $target && $kolumne_term ) {
	$existing_ids = wp_list_pluck( $posts, 'ID' );
	$fill_args    = array(
		'category'       => $kolumne_term->term_id,
		'posts_per_page' => $target - count( $posts ),
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	if ( ! empty( $existing_ids ) ) {
		$fill_args['post__not_in'] = $existing_ids;
	}
	$fill_posts = get_posts( $fill_args );
	$posts      = array_merge( $posts, $fill_posts );
}

if ( empty( $posts ) ) {
	return '';
}

$cat_link = $kolumne_term ? get_category_link( $kolumne_term->term_id ) : '';
?>
<div class="kompas-kolumne">
	<div class="kompas-kolumne__heading kompas-section-topline">
		<h3 class="kompas-kolumne__title">КОЛУМНЕ</h3>
	</div>

	<div class="kompas-kolumne__list">
		<?php foreach ( $posts as $p ) :
			$author_id   = $p->post_author;
			$author_name = get_the_author_meta( 'display_name', $author_id );
			$avatar_url  = get_avatar_url( $author_id, array( 'size' => 120 ) );
			$item_url    = get_permalink( $p );
			$post_title  = get_the_title( $p );

			// CPT author overrides WP user data.
			$cpt_author_id = (int) get_post_meta( $p->ID, 'kompas_author_id', true );
			if ( $cpt_author_id > 0 ) {
				$cpt_post = get_post( $cpt_author_id );
				if ( $cpt_post ) {
					$author_name = get_the_title( $cpt_post );
					$cpt_photo   = get_the_post_thumbnail_url( $cpt_author_id, 'thumbnail' );
					if ( $cpt_photo ) {
						$avatar_url = $cpt_photo;
					}
				}
			}
		?>
		<a href="<?php echo esc_url( $item_url ); ?>" class="kompas-kolumne__item">
			<img src="<?php echo esc_url( $avatar_url ); ?>"
				 alt="<?php echo esc_attr( $author_name ); ?>"
				 class="kompas-kolumne__avatar" />
			<div class="kompas-kolumne__text">
				<span class="kompas-kolumne__name"><?php echo esc_html( mb_strtoupper( $author_name ) ); ?></span>
				<span class="kompas-kolumne__excerpt"><?php echo esc_html( $post_title ); ?></span>
			</div>
		</a>
		<?php endforeach; ?>
	</div>

	<?php if ( $cat_link ) : ?>
	<a href="<?php echo esc_url( $cat_link ); ?>" class="kompas-kolumne__more">ПОГЛЕДАЈ СВЕ</a>
	<?php endif; ?>
</div>
