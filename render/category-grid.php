<?php
/**
 * Render: kompas/category-grid
 *
 * For each selected category, renders a section with:
 * - Category title with red underline
 * - Row 1: 2 large posts (image + title + excerpt)
 * - Row 2: 4 small posts (image + title)
 */
$selected          = ! empty( $attributes['selectedIds'] ) ? array_map( 'absint', $attributes['selectedIds'] ) : array();
$posts_by_category = ! empty( $attributes['postsByCategory'] ) && is_array( $attributes['postsByCategory'] ) ? $attributes['postsByCategory'] : array();
$per_cat           = isset( $attributes['postsPerCategory'] ) ? (int) $attributes['postsPerCategory'] : 6;
$per_cat           = max( 1, $per_cat );

if ( empty( $selected ) ) {
	// Fallback: top-level categories.
	$cats = get_categories( array(
		'hide_empty' => false,
		'parent'     => 0,
		'number'     => 4,
	) );
	$selected = wp_list_pluck( $cats, 'term_id' );
}

if ( empty( $selected ) ) {
	return;
}

foreach ( $selected as $cat_id ) :
	$cat = get_category( $cat_id );
	if ( ! $cat || is_wp_error( $cat ) ) {
		continue;
	}

	$manual_ids = array();
	$cat_key    = (string) $cat_id;
	if ( isset( $posts_by_category[ $cat_key ] ) && is_array( $posts_by_category[ $cat_key ] ) ) {
		$manual_ids = array_values(
			array_filter(
				array_map( 'absint', $posts_by_category[ $cat_key ] )
			)
		);
	}

	$posts = array();
	if ( ! empty( $manual_ids ) ) {
		$posts = get_posts( array(
			'post__in'       => $manual_ids,
			'orderby'        => 'post__in',
			'posts_per_page' => $per_cat,
			'post_status'    => 'publish',
			'category__in'   => array( $cat_id ),
		) );
	}

	if ( count( $posts ) < $per_cat ) {
		$posts = array_merge(
			$posts,
			get_posts( array(
				'category'       => $cat_id,
				'posts_per_page' => $per_cat - count( $posts ),
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post__not_in'   => wp_list_pluck( $posts, 'ID' ),
			) )
		);
	}

	if ( empty( $posts ) ) {
		continue;
	}

	$large = array_slice( $posts, 0, 2 );
	$small = array_slice( $posts, 2, 4 );
	$cat_link = get_category_link( $cat_id );
?>
	<div class="kompas-catgrid">

		<div class="kompas-catgrid__header kompas-section-topline">
			<a href="<?php echo esc_url( $cat_link ); ?>" class="kompas-catgrid__title">
				<?php echo esc_html( mb_strtoupper( $cat->name ) ); ?>
			</a>
			<a href="<?php echo esc_url( $cat_link ); ?>" class="kompas-more-link kompas-catgrid__more">ПОГЛЕДАЈ СВЕ</a>
		</div>

	<!-- 2 large posts -->
	<div class="kompas-catgrid__row-large">
		<?php foreach ( $large as $p ) : ?>
		<div class="kompas-catgrid__item-large">
			<?php if ( has_post_thumbnail( $p ) ) : ?>
			<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="kompas-catgrid__img-link">
				<img src="<?php echo esc_url( get_the_post_thumbnail_url( $p, 'large' ) ); ?>"
					 alt="<?php echo esc_attr( get_the_title( $p ) ); ?>"
					 class="kompas-catgrid__img" />
			</a>
			<?php endif; ?>
				<h3 class="kompas-catgrid__post-title kompas-catgrid__post-title--lg"<?php echo kompas_get_post_title_no_translate_data_attr( $p->ID ); ?>>
					<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( kompas_truncate_title( get_the_title( $p ), 60 ) ); ?></a>
					</h3>
			<p class="kompas-catgrid__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $p ), 25 ) ); ?></p>
		</div>
		<?php endforeach; ?>
	</div>

	<?php if ( ! empty( $small ) ) : ?>
	<!-- 4 small posts -->
	<div class="kompas-catgrid__row-small">
		<?php foreach ( $small as $p ) : ?>
		<div class="kompas-catgrid__item-small">
			<?php if ( has_post_thumbnail( $p ) ) : ?>
			<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="kompas-catgrid__img-link">
				<img src="<?php echo esc_url( get_the_post_thumbnail_url( $p, 'medium' ) ); ?>"
					 alt="<?php echo esc_attr( get_the_title( $p ) ); ?>"
					 class="kompas-catgrid__img" />
			</a>
			<?php endif; ?>
				<h4 class="kompas-catgrid__post-title kompas-catgrid__post-title--sm"<?php echo kompas_get_post_title_no_translate_data_attr( $p->ID ); ?>>
					<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( kompas_truncate_title( get_the_title( $p ), 60 ) ); ?></a>
					</h4>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>


</div>
<?php endforeach;
