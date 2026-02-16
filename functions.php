<?php
/**
 * Kompas Theme Functions
 *
 * @package Kompas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KOMPAS_VERSION', '1.0.0' );

/**
 * Enqueue theme styles.
 */
function kompas_enqueue_styles() {
	wp_enqueue_style(
		'kompas-style',
		get_stylesheet_uri(),
		array(),
		KOMPAS_VERSION
	);
	wp_enqueue_script(
		'kompas-script-toggle',
		get_theme_file_uri( 'assets/js/script-toggle.js' ),
		array(),
		KOMPAS_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kompas_enqueue_styles' );

/**
 * Register block patterns category.
 */
function kompas_register_pattern_categories() {
	register_block_pattern_category( 'kompas-hero', array(
		'label' => __( 'Kompas - Hero', 'kompas' ),
	) );
	register_block_pattern_category( 'kompas-sections', array(
		'label' => __( 'Kompas - Sekcije', 'kompas' ),
	) );
	register_block_pattern_category( 'kompas-banners', array(
		'label' => __( 'Kompas - Baneri', 'kompas' ),
	) );
}
add_action( 'init', 'kompas_register_pattern_categories' );

/**
 * Register Curated Query block variation and its server-side filtering.
 */
function kompas_register_curated_query() {
	wp_enqueue_script(
		'kompas-curated-query',
		get_theme_file_uri( 'assets/js/curated-query.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-hooks' ),
		KOMPAS_VERSION,
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'kompas_register_curated_query' );

/**
 * Filter the query for curated posts on the front end.
 */
function kompas_pre_render_curated_query( $pre_render, $parsed_block, $parent_block ) {
	if ( 'core/query' !== $parsed_block['blockName'] ) {
		return $pre_render;
	}

	$post_ids = $parsed_block['attrs']['query']['postIn'] ?? '';
	if ( empty( $post_ids ) ) {
		return $pre_render;
	}

	// postIn can be a comma-separated string or an array.
	if ( is_string( $post_ids ) ) {
		$post_ids = array_map( 'intval', array_filter( explode( ',', $post_ids ) ) );
	}

	if ( empty( $post_ids ) ) {
		return $pre_render;
	}

	add_filter( 'query_loop_block_query_vars', function ( $query_vars ) use ( $post_ids ) {
		$query_vars['post__in'] = $post_ids;
		$query_vars['orderby']  = 'post__in';
		return $query_vars;
	} );

	return $pre_render;
}
add_filter( 'pre_render_block', 'kompas_pre_render_curated_query', 10, 3 );

/**
 * Add theme support.
 */
function kompas_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-thumbnails' );

	add_image_size( 'kompas-hero', 800, 500, true );
	add_image_size( 'kompas-thumbnail', 400, 250, true );
	add_image_size( 'kompas-small', 150, 100, true );
}
add_action( 'after_setup_theme', 'kompas_setup' );

/**
 * Allow SVG uploads.
 */
function kompas_allow_svg_upload( $mimes ) {
	$mimes['svg']  = 'image/svg+xml';
	$mimes['svgz'] = 'image/svg+xml';
	return $mimes;
}
add_filter( 'upload_mimes', 'kompas_allow_svg_upload' );

/**
 * Fix SVG mime type detection on upload (wp_check_filetype_and_ext can fail).
 */
function kompas_fix_svg_filetype( $data, $file, $filename, $mimes ) {
	$ext = pathinfo( $filename, PATHINFO_EXTENSION );
	if ( 'svg' === strtolower( $ext ) ) {
		$data['type'] = 'image/svg+xml';
		$data['ext']  = 'svg';
	}
	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'kompas_fix_svg_filetype', 10, 4 );

/**
 * Sanitize SVG on upload — strip scripts and unsafe elements.
 */
function kompas_sanitize_svg( $file ) {
	if ( 'image/svg+xml' !== $file['type'] ) {
		return $file;
	}

	$svg = file_get_contents( $file['tmp_name'] );

	// Remove script tags, on* event handlers, and external references.
	$svg = preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $svg );
	$svg = preg_replace( '/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $svg );
	$svg = preg_replace( '/xlink:href\s*=\s*["\'](?!#)[^"\']*["\']/i', '', $svg );

	file_put_contents( $file['tmp_name'], $svg );

	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'kompas_sanitize_svg' );

/**
 * Register block styles.
 */
function kompas_register_block_styles() {
	register_block_style( 'core/group', array(
		'name'  => 'kompas-border-top-red',
		'label' => __( 'Crvena linija gore', 'kompas' ),
	) );

	register_block_style( 'core/group', array(
		'name'  => 'kompas-banner-placeholder',
		'label' => __( 'Banner Placeholder', 'kompas' ),
	) );

	register_block_style( 'core/navigation', array(
		'name'  => 'kompas-secondary-nav',
		'label' => __( 'Sekundarna navigacija', 'kompas' ),
	) );

	register_block_style( 'core/post-title', array(
		'name'  => 'kompas-title-bold',
		'label' => __( 'Boldovan naslov', 'kompas' ),
	) );
}
add_action( 'init', 'kompas_register_block_styles' );

/**
 * Register Gallery Slider block.
 */
function kompas_register_gallery_slider_block() {
	wp_register_script(
		'kompas-gallery-editor',
		get_theme_file_uri( 'assets/js/gallery-editor.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
		KOMPAS_VERSION,
		true
	);
	register_block_type( get_theme_file_path( 'blocks/gallery-slider' ) );
}
add_action( 'init', 'kompas_register_gallery_slider_block' );

/**
 * Enqueue gallery slider frontend script.
 */
function kompas_enqueue_gallery_slider_script() {
	if ( is_singular() ) {
		wp_enqueue_script(
			'kompas-gallery-slider',
			get_theme_file_uri( 'assets/js/gallery-slider.js' ),
			array(),
			KOMPAS_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'kompas_enqueue_gallery_slider_script' );

/**
 * Register post meta for view count (for "most read" functionality).
 */
function kompas_register_meta() {
	register_post_meta( 'post', 'kompas_views', array(
		'show_in_rest'  => true,
		'single'        => true,
		'type'          => 'integer',
		'default'       => 0,
		'auth_callback' => '__return_true',
	) );
}
add_action( 'init', 'kompas_register_meta' );

/**
 * Track post views.
 */
function kompas_track_views() {
	if ( is_singular( 'post' ) && ! is_admin() ) {
		$post_id = get_the_ID();
		$views   = (int) get_post_meta( $post_id, 'kompas_views', true );
		update_post_meta( $post_id, 'kompas_views', $views + 1 );
	}
}
add_action( 'wp', 'kompas_track_views' );

/**
 * Enqueue frontend tabs script.
 */
function kompas_enqueue_tabs_script() {
	wp_enqueue_script(
		'kompas-tabs',
		get_theme_file_uri( 'assets/js/tabs.js' ),
		array(),
		KOMPAS_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kompas_enqueue_tabs_script' );

/**
 * Render the Najnovije/Najčitanije tabs section.
 *
 * This is a dynamic block that outputs two tab panels:
 * - Najnovije: latest posts by date
 * - Najčitanije: most viewed posts by kompas_views meta
 *
 * Each panel uses the 2+4 grid layout (2 large + 4 small).
 */
function kompas_render_tabs_block( $attributes ) {
	$count = isset( $attributes['count'] ) ? (int) $attributes['count'] : 4;

	// Query: Najnovije (by date).
	$najnovije = get_posts( array(
		'posts_per_page' => $count,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	// Query: Najčitanije (by views).
	$najcitanije = get_posts( array(
		'posts_per_page' => $count,
		'post_status'    => 'publish',
		'meta_key'       => 'kompas_views',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
	) );

	ob_start();
	?>
	<div class="kompas-tabs-section" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">

		<div class="kompas-tabs-nav" style="display:flex;gap:0;margin-bottom:var(--wp--preset--spacing--50);border-bottom:1px solid var(--wp--preset--color--border)">
			<button class="kompas-tab-btn is-active" data-tab="najnovije" type="button" style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding:0 0 var(--wp--preset--spacing--20);margin-right:var(--wp--preset--spacing--50);background:none;border:none;border-bottom:3px solid var(--wp--preset--color--primary);color:var(--wp--preset--color--dark);cursor:pointer;font-family:inherit">НАЈНОВИЈЕ</button>
			<button class="kompas-tab-btn" data-tab="najcitanije" type="button" style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding:0 0 var(--wp--preset--spacing--20);margin-right:var(--wp--preset--spacing--50);background:none;border:none;border-bottom:3px solid transparent;color:var(--wp--preset--color--muted);cursor:pointer;font-family:inherit">НАЈЧИТАНИЈЕ</button>
		</div>

		<div class="kompas-tab-panel is-active" data-panel="najnovije">
			<?php echo kompas_render_posts_grid( $najnovije ); ?>
		</div>

		<div class="kompas-tab-panel" data-panel="najcitanije" style="display:none">
			<?php echo kompas_render_posts_grid( $najcitanije ); ?>
		</div>

	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render a single row of posts (4 columns: image + title).
 *
 * @param WP_Post[] $posts Array of post objects.
 * @return string          HTML markup.
 */
function kompas_render_posts_grid( $posts ) {
	if ( empty( $posts ) ) {
		return '<p style="color:var(--wp--preset--color--muted)">Нема постова за приказ.</p>';
	}

	ob_start();
	?>
	<div class="wp-block-columns" style="gap:var(--wp--preset--spacing--40)">
		<?php foreach ( $posts as $post ) : ?>
		<div class="wp-block-column">
			<?php if ( has_post_thumbnail( $post ) ) : ?>
			<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" style="display:block;margin-bottom:var(--wp--preset--spacing--20)">
				<img src="<?php echo esc_url( get_the_post_thumbnail_url( $post, 'medium' ) ); ?>"
					 alt="<?php echo esc_attr( get_the_title( $post ) ); ?>"
					 style="width:100%;aspect-ratio:16/10;object-fit:cover;display:block" />
			</a>
			<?php endif; ?>
			<h4 style="font-size:0.875rem;font-weight:700;line-height:1.3;margin:0">
				<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" style="color:var(--wp--preset--color--dark);text-decoration:none"><?php echo esc_html( get_the_title( $post ) ); ?></a>
			</h4>
		</div>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Register the tabs block via block.json.
 */
function kompas_register_tabs_block() {
	register_block_type( get_theme_file_path( 'blocks/tabs-najnovije-najcitanije' ) );
}
add_action( 'init', 'kompas_register_tabs_block' );

/**
 * Get tags used by posts within a specific category.
 *
 * Queries posts belonging to the given category and collects
 * all unique tags assigned to those posts.
 *
 * @param int $category_id The category term ID.
 * @param int $limit       Max number of tags to return.
 * @return WP_Term[]       Array of tag term objects.
 */
function kompas_get_tags_for_category( $category_id, $limit = 10 ) {
	$posts = get_posts( array(
		'category'       => $category_id,
		'posts_per_page' => 50,
		'fields'         => 'ids',
	) );

	if ( empty( $posts ) ) {
		return array();
	}

	$tags = wp_get_object_terms( $posts, 'post_tag', array(
		'orderby' => 'count',
		'order'   => 'DESC',
		'number'  => $limit,
	) );

	if ( is_wp_error( $tags ) ) {
		return array();
	}

	return $tags;
}

/**
 * Render footer category columns with associated tags.
 *
 * Reads selected category IDs from block attributes.
 */
function kompas_render_footer_categories( $attributes = array() ) {
	$selected = ! empty( $attributes['selectedIds'] ) ? array_map( 'absint', $attributes['selectedIds'] ) : array();

	if ( empty( $selected ) ) {
		// Fallback: show all top-level categories.
		$categories = get_categories( array(
			'hide_empty' => false,
			'parent'     => 0,
		) );
	} else {
		$categories = array();
		foreach ( $selected as $cat_id ) {
			$cat = get_category( $cat_id );
			if ( $cat && ! is_wp_error( $cat ) ) {
				$categories[] = $cat;
			}
		}
	}

	if ( empty( $categories ) ) {
		return '';
	}

	$output = '<div class="kompas-footer-categories" style="margin-bottom:var(--wp--preset--spacing--60)">';

	foreach ( $categories as $cat ) {
		$output .= '<div class="kompas-footer-cat-col">';
		$output .= '<h4 class="has-dark-color has-text-color" style="font-size:0.875rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;margin-bottom:0.75rem">';
		$output .= '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '" style="color:inherit;text-decoration:none">';
		$output .= esc_html( strtoupper( $cat->name ) );
		$output .= '</a></h4>';

		$tags = kompas_get_tags_for_category( $cat->term_id, 6 );

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$output .= '<p class="has-muted-color has-text-color" style="font-size:0.8125rem;margin-top:0;margin-bottom:0.4rem">';
				$output .= '<a href="' . esc_url( get_tag_link( $tag->term_id ) ) . '" style="color:inherit;text-decoration:none">';
				$output .= esc_html( strtoupper( $tag->name ) );
				$output .= '</a></p>';
			}
		}

		$output .= '</div>';
	}

	$output .= '</div>';

	return $output;
}

/**
 * Register the dynamic footer categories block via block.json.
 */
function kompas_register_footer_categories_block() {
	register_block_type( get_theme_file_path( 'blocks/footer-categories' ) );
}
add_action( 'init', 'kompas_register_footer_categories_block' );

/**
 * ── Archive Layout Block ──────────────────────────────────────
 */

/**
 * Render the archive layout: hero + banner + repeating grid with pagination.
 *
 * Layout:
 * 1. Hero: left 70% (1 big + 2 horizontal), right 30% (4 vertical cards)
 * 2. Banner placeholder
 * 3. Repeating rows: row of 4, row of 2 big, row of 4, row of 4 ...
 * 4. Pagination
 */
function kompas_render_archive_layout( $attributes = array() ) {
	global $wp_query;

	$paged    = max( 1, get_query_var( 'paged', 1 ) );
	$per_page = isset( $attributes['postsPerPage'] ) ? (int) $attributes['postsPerPage'] : 17;

	// Hero posts: manually selected or fallback to latest 7 from archive.
	$hero_ids = ! empty( $attributes['heroPostIds'] ) ? array_map( 'absint', $attributes['heroPostIds'] ) : array();

	if ( ! empty( $hero_ids ) ) {
		// Manually selected — fetch by IDs preserving order.
		$hero_posts = get_posts( array(
			'post__in'       => $hero_ids,
			'orderby'        => 'post__in',
			'posts_per_page' => count( $hero_ids ),
			'post_status'    => 'publish',
		) );
	} else {
		// Fallback: latest 7 from current archive query.
		$hero_args = array_merge( $wp_query->query_vars, array(
			'posts_per_page' => 7,
			'paged'          => 1,
			'post_status'    => 'publish',
		) );
		$hero_posts = get_posts( $hero_args );
		$hero_ids   = wp_list_pluck( $hero_posts, 'ID' );
	}

	// Grid: use main archive query but exclude hero posts.
	$args = array_merge( $wp_query->query_vars, array(
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'post_status'    => 'publish',
	) );
	if ( ! empty( $hero_ids ) ) {
		$args['post__not_in'] = $hero_ids;
	}

	$query      = new WP_Query( $args );
	$grid_posts = $query->posts;

	if ( empty( $hero_posts ) && empty( $grid_posts ) ) {
		return '<p style="color:var(--wp--preset--color--muted)">Нема постова за приказ.</p>';
	}

	ob_start();
	?>
	<div class="kompas-archive-layout">

		<?php
		// ── HERO SECTION: from manually selected posts ──────────
		$hero_main   = isset( $hero_posts[0] ) ? $hero_posts[0] : null;
		$hero_horiz  = array_slice( $hero_posts, 1, 2 );
		$hero_side   = array_slice( $hero_posts, 3, 4 );
		?>
		<div class="kompas-archive-hero">

			<!-- Left column 70% -->
			<div class="kompas-archive-hero-left">
				<?php if ( $hero_main ) : ?>
				<div class="kompas-archive-hero-main">
					<a href="<?php echo esc_url( get_permalink( $hero_main ) ); ?>">
						<?php if ( has_post_thumbnail( $hero_main ) ) : ?>
						<img src="<?php echo esc_url( get_the_post_thumbnail_url( $hero_main, 'large' ) ); ?>"
							 alt="<?php echo esc_attr( get_the_title( $hero_main ) ); ?>"
							 class="kompas-archive-img kompas-archive-img--hero" />
						<?php endif; ?>
					</a>
					<h2 class="kompas-archive-title kompas-archive-title--lg">
						<a href="<?php echo esc_url( get_permalink( $hero_main ) ); ?>"><?php echo esc_html( get_the_title( $hero_main ) ); ?></a>
					</h2>
					<p class="kompas-archive-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $hero_main ), 30 ) ); ?></p>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $hero_horiz ) ) : ?>
				<div class="kompas-archive-hero-horiz">
					<?php foreach ( $hero_horiz as $p ) : ?>
					<div class="kompas-archive-card-h">
						<?php if ( has_post_thumbnail( $p ) ) : ?>
						<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="kompas-archive-card-h__img">
							<img src="<?php echo esc_url( get_the_post_thumbnail_url( $p, 'medium' ) ); ?>"
								 alt="<?php echo esc_attr( get_the_title( $p ) ); ?>"
								 class="kompas-archive-img" />
						</a>
						<?php endif; ?>
						<div class="kompas-archive-card-h__text">
							<h3 class="kompas-archive-title kompas-archive-title--md">
								<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a>
							</h3>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>

			<!-- Right column 30% -->
			<?php if ( ! empty( $hero_side ) ) : ?>
			<div class="kompas-archive-hero-right">
				<?php foreach ( $hero_side as $p ) : ?>
				<div class="kompas-archive-card-v">
					<?php if ( has_post_thumbnail( $p ) ) : ?>
					<a href="<?php echo esc_url( get_permalink( $p ) ); ?>">
						<img src="<?php echo esc_url( get_the_post_thumbnail_url( $p, 'medium' ) ); ?>"
							 alt="<?php echo esc_attr( get_the_title( $p ) ); ?>"
							 class="kompas-archive-img" />
					</a>
					<?php endif; ?>
					<h4 class="kompas-archive-title kompas-archive-title--sm">
						<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a>
					</h4>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

		</div>

		<?php
		// ── BANNER ──────────────────────────────────────────────
		echo do_blocks( '<!-- wp:kompas/banner /-->' );

		// ── GRID: from archive query (hero posts excluded) ──────
		if ( ! empty( $grid_posts ) ) :
			$row_index = 0;
			$i         = 0;
			$total     = count( $grid_posts );
		?>
		<div class="kompas-archive-grid">
			<?php
			while ( $i < $total ) :
				// Pattern: row of 4, row of 2 big, then rows of 4 repeating
				if ( $row_index === 1 ) {
					// Second row: 2 big posts
					$row   = array_slice( $grid_posts, $i, 2 );
					$cols  = 2;
					$big   = true;
					$i    += 2;
				} else {
					// All other rows: 4 posts
					$row   = array_slice( $grid_posts, $i, 4 );
					$cols  = 4;
					$big   = false;
					$i    += 4;
				}

				if ( empty( $row ) ) {
					break;
				}
			?>
			<div class="kompas-archive-grid-row <?php echo $big ? 'kompas-archive-grid-row--2' : 'kompas-archive-grid-row--4'; ?>">
				<?php foreach ( $row as $p ) : ?>
				<div class="kompas-archive-grid-item">
					<?php if ( has_post_thumbnail( $p ) ) : ?>
					<a href="<?php echo esc_url( get_permalink( $p ) ); ?>">
						<img src="<?php echo esc_url( get_the_post_thumbnail_url( $p, $big ? 'large' : 'medium' ) ); ?>"
							 alt="<?php echo esc_attr( get_the_title( $p ) ); ?>"
							 class="kompas-archive-img" />
					</a>
					<?php endif; ?>
					<h4 class="kompas-archive-title <?php echo $big ? 'kompas-archive-title--md' : 'kompas-archive-title--sm'; ?>">
						<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a>
					</h4>
					<?php if ( $big ) : ?>
					<p class="kompas-archive-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $p ), 20 ) ); ?></p>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
			<?php
				$row_index++;
			endwhile;
			?>
		</div>
		<?php endif; ?>

		<?php
		// ── PAGINATION ──────────────────────────────────────────
		$total_pages = $query->max_num_pages;
		if ( $total_pages > 1 ) :
		?>
		<nav class="kompas-archive-pagination" aria-label="Paginacija">
			<?php
			echo paginate_links( array(
				'total'     => $total_pages,
				'current'   => $paged,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'type'      => 'list',
			) );
			?>
		</nav>
		<?php endif; ?>

	</div>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}

/**
 * Register the archive layout block.
 */
function kompas_register_archive_layout_block() {
	register_block_type( get_theme_file_path( 'blocks/archive-layout' ) );
}
add_action( 'init', 'kompas_register_archive_layout_block' );

/**
 * ── Dynamic Header Blocks ─────────────────────────────────────
 */

/**
 * Render the main header nav (categories).
 */
function kompas_render_header_nav( $attributes = array() ) {
	$ids = ! empty( $attributes['selectedIds'] ) ? array_map( 'absint', $attributes['selectedIds'] ) : array();

	if ( empty( $ids ) ) {
		$cats = get_categories( array( 'hide_empty' => false, 'parent' => 0 ) );
	} else {
		$cats = array();
		foreach ( $ids as $id ) {
			$c = get_category( $id );
			if ( $c && ! is_wp_error( $c ) ) {
				$cats[] = $c;
			}
		}
	}

	if ( empty( $cats ) ) {
		return '';
	}

	$links = array();
	foreach ( $cats as $cat ) {
		$links[] = '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '" class="kompas-header-link">'
			. esc_html( mb_strtoupper( $cat->name ) ) . '</a>';
	}

	return '<nav class="kompas-header-categories" aria-label="Glavna navigacija">'
		. implode( '', $links )
		. '</nav>';
}

/**
 * Render the secondary header nav (tags).
 */
function kompas_render_header_tags( $attributes = array() ) {
	$ids = ! empty( $attributes['selectedIds'] ) ? array_map( 'absint', $attributes['selectedIds'] ) : array();

	if ( empty( $ids ) ) {
		// Fallback: show most popular tags.
		$tags = get_tags( array(
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 10,
			'hide_empty' => true,
		) );
	} else {
		$tags = array();
		foreach ( $ids as $id ) {
			$tag = get_tag( $id );
			if ( $tag && ! is_wp_error( $tag ) ) {
				$tags[] = $tag;
			}
		}
	}

	if ( empty( $tags ) ) {
		return '';
	}

	$links = array();
	foreach ( $tags as $tag ) {
		$links[] = '<a href="' . esc_url( get_tag_link( $tag->term_id ) ) . '" class="kompas-header-tag-link">'
			. esc_html( mb_strtoupper( $tag->name ) ) . '</a>';
	}

	return '<nav class="kompas-header-tags" aria-label="Sekundarna navigacija">'
		. implode( '', $links )
		. '</nav>';
}

/**
 * Register header dynamic blocks via block.json.
 */
function kompas_register_header_blocks() {
	register_block_type( get_theme_file_path( 'blocks/header-nav' ) );
	register_block_type( get_theme_file_path( 'blocks/header-tags' ) );
}
add_action( 'init', 'kompas_register_header_blocks' );

/**
 * Register editor script for custom blocks (handle referenced by block.json).
 * Priority 5 — runs before block registration at default priority 10.
 */
function kompas_register_blocks_editor_script() {
	wp_register_script(
		'kompas-blocks-editor',
		get_theme_file_uri( 'assets/js/blocks-editor.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components', 'wp-api-fetch' ),
		KOMPAS_VERSION,
		true
	);
}
add_action( 'init', 'kompas_register_blocks_editor_script', 5 );

/**
 * Register Kolumne and Reč Urednika blocks.
 */
function kompas_register_category_grid_block() {
	register_block_type( get_theme_file_path( 'blocks/category-grid' ) );
}
add_action( 'init', 'kompas_register_category_grid_block' );

function kompas_register_kolumne_rec_blocks() {
	register_block_type( get_theme_file_path( 'blocks/kolumne' ) );
	register_block_type( get_theme_file_path( 'blocks/rec-urednika' ) );
}
add_action( 'init', 'kompas_register_kolumne_rec_blocks' );

/**
 * Register Banner block.
 */
function kompas_register_banner_block() {
	register_block_type( get_theme_file_path( 'blocks/banner' ) );
}
add_action( 'init', 'kompas_register_banner_block' );

/**
 * Register Mobile Nav block.
 */
function kompas_register_mobile_nav_block() {
	register_block_type( get_theme_file_path( 'blocks/mobile-nav' ) );
}
add_action( 'init', 'kompas_register_mobile_nav_block' );

/**
 * Enqueue mobile nav script.
 */
function kompas_enqueue_mobile_nav_script() {
	wp_enqueue_script(
		'kompas-mobile-nav',
		get_theme_file_uri( 'assets/js/mobile-nav.js' ),
		array(),
		KOMPAS_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kompas_enqueue_mobile_nav_script' );

/**
 * Auto-assign single-kolumne template for posts in the "kolumne" category.
 */
function kompas_kolumne_single_template( $templates ) {
	if ( is_singular( 'post' ) && has_category( 'kolumne' ) ) {
		array_unshift( $templates, 'single-kolumne' );
	}
	return $templates;
}
add_filter( 'single_template_hierarchy', 'kompas_kolumne_single_template' );

/**
 * Exclude "kolumne" and "rec-urednika" categories from public category queries
 * (widgets, default category lists, etc.) but NOT from admin or explicit queries.
 */
function kompas_exclude_hidden_categories( $clauses, $taxonomies, $args ) {
	if ( is_admin() ) {
		return $clauses;
	}

	// Only filter category taxonomy.
	if ( ! in_array( 'category', (array) $taxonomies, true ) ) {
		return $clauses;
	}

	// Skip if specific IDs/slugs are requested (e.g., block attributes).
	if ( ! empty( $args['include'] ) || ! empty( $args['slug'] ) ) {
		return $clauses;
	}

	global $wpdb;
	$hidden_slugs = array( 'kolumne', 'rec-urednika' );
	$placeholders = implode( ',', array_fill( 0, count( $hidden_slugs ), '%s' ) );
	$clauses['where'] .= $wpdb->prepare(
		" AND t.slug NOT IN ($placeholders)",
		$hidden_slugs
	);

	return $clauses;
}
add_filter( 'terms_clauses', 'kompas_exclude_hidden_categories', 10, 3 );
