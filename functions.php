<?php
/**
 * Kompas Theme Functions
 *
 * @package Kompas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KOMPAS_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'KOMPAS_CUSTOM_AUTHOR_META_KEY', 'kompas_custom_author' );

/**
 * Enqueue theme styles.
 */
function kompas_enqueue_styles() {
	$style_path = get_theme_file_path( 'style.css' );
	$style_ver  = KOMPAS_VERSION;
	if ( file_exists( $style_path ) ) {
		$style_ver .= '.' . (string) filemtime( $style_path );
	}

	$toggle_script_path = get_theme_file_path( 'assets/js/script-toggle.js' );
	$toggle_script_ver  = KOMPAS_VERSION;
	if ( file_exists( $toggle_script_path ) ) {
		$toggle_script_ver .= '.' . (string) filemtime( $toggle_script_path );
	}

	wp_enqueue_style(
		'kompas-style',
		get_stylesheet_uri(),
		array(),
		$style_ver
	);
	wp_enqueue_script(
		'kompas-script-toggle',
		get_theme_file_uri( 'assets/js/script-toggle.js' ),
		array(),
		$toggle_script_ver,
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
	$path = get_theme_file_path( 'assets/js/curated-query.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}
	wp_enqueue_script(
		'kompas-curated-query',
		get_theme_file_uri( 'assets/js/curated-query.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-hooks', 'wp-compose', 'wp-api-fetch' ),
		$ver,
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
 * Exclude current single post from the "related posts" query block.
 */
function kompas_exclude_current_from_related_posts_query( $query, $block, $page ) {
	if ( ! is_singular( 'post' ) ) {
		return $query;
	}

	$class_name = $block->parsed_block['attrs']['className'] ?? '';
	if ( false === strpos( $class_name, 'kompas-related-posts-query' ) ) {
		return $query;
	}

	$current_post_id = (int) get_queried_object_id();
	if ( $current_post_id <= 0 ) {
		return $query;
	}

	$query['post__not_in'] = isset( $query['post__not_in'] ) && is_array( $query['post__not_in'] )
		? $query['post__not_in']
		: array();

	if ( ! in_array( $current_post_id, $query['post__not_in'], true ) ) {
		$query['post__not_in'][] = $current_post_id;
	}

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'kompas_exclude_current_from_related_posts_query', 10, 3 );

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
		$path = get_theme_file_path( 'assets/js/gallery-slider.js' );
		$ver  = KOMPAS_VERSION;
		if ( file_exists( $path ) ) {
			$ver .= '.' . (string) filemtime( $path );
		}
		wp_enqueue_script(
			'kompas-gallery-slider',
			get_theme_file_uri( 'assets/js/gallery-slider.js' ),
			array(),
			$ver,
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

	register_post_meta( 'post', KOMPAS_CUSTOM_AUTHOR_META_KEY, array(
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'string',
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => function () {
			return current_user_can( 'edit_posts' );
		},
	) );
}
add_action( 'init', 'kompas_register_meta' );

/**
 * Add a custom author name field to post edit screens.
 */
function kompas_add_custom_author_meta_box() {
	add_meta_box(
		'kompas-custom-author',
		'Аутор (ручни унос)',
		'kompas_render_custom_author_meta_box',
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_post', 'kompas_add_custom_author_meta_box' );

/**
 * Render custom author name field for posts.
 */
function kompas_render_custom_author_meta_box( $post ) {
	$custom_author = get_post_meta( $post->ID, KOMPAS_CUSTOM_AUTHOR_META_KEY, true );
	wp_nonce_field( 'kompas_save_custom_author', 'kompas_custom_author_nonce' );
	?>
	<p>
		<label for="kompas-custom-author-input">Име аутора (опционо)</label>
		<input
			type="text"
			id="kompas-custom-author-input"
			name="kompas_custom_author"
			value="<?php echo esc_attr( $custom_author ); ?>"
			class="widefat"
		/>
	</p>
	<p class="description">
		Ако је попуњено, ово име ће се приказивати уместо WordPress аутора.
	</p>
	<?php
}

/**
 * Save custom author name for posts.
 */
function kompas_save_custom_author_meta( $post_id ) {
	if ( ! isset( $_POST['kompas_custom_author_nonce'] ) || ! wp_verify_nonce( $_POST['kompas_custom_author_nonce'], 'kompas_save_custom_author' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['kompas_custom_author'] ) ) {
		return;
	}

	$custom_author = sanitize_text_field( wp_unslash( $_POST['kompas_custom_author'] ) );
	if ( '' === $custom_author ) {
		delete_post_meta( $post_id, KOMPAS_CUSTOM_AUTHOR_META_KEY );
		return;
	}

	update_post_meta( $post_id, KOMPAS_CUSTOM_AUTHOR_META_KEY, $custom_author );
}
add_action( 'save_post_post', 'kompas_save_custom_author_meta' );

/**
 * Replace post author name block output with manually entered author name.
 */
function kompas_replace_post_author_name_block( $block_content, $block, $instance ) {
	$post_id = 0;

	if ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) {
		$post_id = (int) $instance->context['postId'];
	} elseif ( get_the_ID() ) {
		$post_id = (int) get_the_ID();
	}

	if ( $post_id <= 0 ) {
		return $block_content;
	}

	$custom_author = trim( (string) get_post_meta( $post_id, KOMPAS_CUSTOM_AUTHOR_META_KEY, true ) );
	if ( '' === $custom_author ) {
		return $block_content;
	}

	$custom_author = esc_html( $custom_author );

	if ( false !== stripos( $block_content, '<a ' ) ) {
		$updated = preg_replace_callback(
			'/(<a\b[^>]*>).*?(<\/a>)/is',
			static function ( $matches ) use ( $custom_author ) {
				return $matches[1] . $custom_author . $matches[2];
			},
			$block_content,
			1
		);

		return $updated ?: $block_content;
	}

	$updated = preg_replace_callback(
		'/(<div\b[^>]*>).*?(<\/div>)/is',
		static function ( $matches ) use ( $custom_author ) {
			return $matches[1] . $custom_author . $matches[2];
		},
		$block_content,
		1
	);

	return $updated ?: $block_content;
}
add_filter( 'render_block_core/post-author-name', 'kompas_replace_post_author_name_block', 10, 3 );

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
	$path = get_theme_file_path( 'assets/js/tabs.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}
	wp_enqueue_script(
		'kompas-tabs',
		get_theme_file_uri( 'assets/js/tabs.js' ),
		array(),
		$ver,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kompas_enqueue_tabs_script' );

/**
 * Render the Najnovije/Najčitanije tabs section.
 *
 * Both tabs support manual post selection.
 * Fallback behavior: if no manual selection is provided (or list is shorter than count),
 * remaining slots are filled with latest posts by date.
 */
function kompas_render_tabs_block( $attributes ) {
	$count = isset( $attributes['count'] ) ? max( 1, (int) $attributes['count'] ) : 6;

	$najnovije_ids  = ! empty( $attributes['najnovijePostIds'] ) ? array_map( 'absint', $attributes['najnovijePostIds'] ) : array();
	$najcitanije_ids = ! empty( $attributes['najcitanijePostIds'] ) ? array_map( 'absint', $attributes['najcitanijePostIds'] ) : array();

	$najnovije = array();
	if ( ! empty( $najnovije_ids ) ) {
		$najnovije = get_posts( array(
			'post__in'       => $najnovije_ids,
			'orderby'        => 'post__in',
			'posts_per_page' => $count,
			'post_status'    => 'publish',
		) );
	}
	if ( count( $najnovije ) < $count ) {
		$exclude   = wp_list_pluck( $najnovije, 'ID' );
		$najnovije = array_merge(
			$najnovije,
			get_posts( array(
				'posts_per_page' => $count - count( $najnovije ),
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post__not_in'   => $exclude,
			) )
		);
	}

	$najcitanije = array();
	if ( ! empty( $najcitanije_ids ) ) {
		$najcitanije = get_posts( array(
			'post__in'       => $najcitanije_ids,
			'orderby'        => 'post__in',
			'posts_per_page' => $count,
			'post_status'    => 'publish',
		) );
	}
	if ( count( $najcitanije ) < $count ) {
		$exclude     = wp_list_pluck( $najcitanije, 'ID' );
		$najcitanije = array_merge(
			$najcitanije,
			get_posts( array(
				'posts_per_page' => $count - count( $najcitanije ),
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post__not_in'   => $exclude,
			) )
		);
	}

	ob_start();
	?>
	<div class="kompas-tabs-section" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">

		<div class="kompas-tabs-heading kompas-section-topline" style="margin-bottom:var(--wp--preset--spacing--50)">
			<div class="kompas-tabs-nav" style="display:flex;gap:0;border-bottom:1px solid var(--wp--preset--color--border)">
				<button class="kompas-tab-btn is-active" data-tab="najnovije" type="button" style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding:0 0 var(--wp--preset--spacing--20);margin-right:var(--wp--preset--spacing--50);background:none;border:none;border-bottom:3px solid var(--wp--preset--color--primary);color:var(--wp--preset--color--dark);cursor:pointer;font-family:inherit">НАЈНОВИЈЕ</button>
				<button class="kompas-tab-btn" data-tab="najcitanije" type="button" style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding:0 0 var(--wp--preset--spacing--20);margin-right:var(--wp--preset--spacing--50);background:none;border:none;border-bottom:3px solid transparent;color:var(--wp--preset--color--muted);cursor:pointer;font-family:inherit">НАЈЧИТАНИЈЕ</button>
			</div>
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
			<h4 style="font-size:0.9375rem;font-weight:700;line-height:1.3;margin:0">
				<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" style="color:var(--wp--preset--color--dark);text-decoration:none"><?php echo esc_html( kompas_truncate_title( get_the_title( $post ) ) ); ?></a>
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

	$output = '<div class="kompas-footer-categories">';

	foreach ( $categories as $cat ) {
		$output .= '<div class="kompas-footer-cat-col">';
		$output .= '<h4 class="has-dark-color has-text-color" style="font-size:0.875rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;margin-bottom:0.75rem">';
		$output .= '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '" style="color:inherit;text-decoration:none">';
			$output .= esc_html( mb_strtoupper( $cat->name ) );
		$output .= '</a></h4>';

		$subcats = get_categories( array(
			'parent'     => $cat->term_id,
			'hide_empty' => false,
			'number'     => 6,
		) );

		if ( ! empty( $subcats ) ) {
			foreach ( $subcats as $subcat ) {
				$output .= '<p class="has-muted-color has-text-color" style="font-size:0.8125rem;margin-top:0;margin-bottom:0.4rem">';
				$output .= '<a href="' . esc_url( get_category_link( $subcat->term_id ) ) . '" style="color:inherit;text-decoration:none">';
					$output .= esc_html( mb_strtoupper( $subcat->name ) );
				$output .= '</a></p>';
			}
		}

		$output .= '</div>';
	}

	$output .= '</div>';

	return $output;
}

/**
 * Register footer dynamic blocks via block.json.
 */
function kompas_register_footer_categories_block() {
	register_block_type( get_theme_file_path( 'blocks/footer-categories' ) );
	register_block_type( get_theme_file_path( 'blocks/footer-pages' ) );
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

	// Hero posts: per-category term meta → block attribute → fallback to latest 7.
	$hero_ids = array();

	if ( is_category() ) {
		$cat_hero = get_term_meta( get_queried_object_id(), 'kompas_hero_posts', true );
		if ( is_array( $cat_hero ) && ! empty( $cat_hero ) ) {
			$hero_ids = array_map( 'absint', $cat_hero );
		}
	}

	if ( empty( $hero_ids ) && ! empty( $attributes['heroPostIds'] ) ) {
		$hero_ids = array_map( 'absint', $attributes['heroPostIds'] );
	}

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

	// Grid: use main archive query; exclude hero posts only on page 1.
	$args = array_merge( $wp_query->query_vars, array(
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'post_status'    => 'publish',
	) );
	if ( $paged === 1 && ! empty( $hero_ids ) ) {
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

		<?php if ( $paged === 1 ) : ?>
		<?php
		// ── HERO SECTION: samo na prvoj strani ──────────────────
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
						<a href="<?php echo esc_url( get_permalink( $hero_main ) ); ?>"><?php echo esc_html( kompas_truncate_title( get_the_title( $hero_main ) ) ); ?></a>
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
								<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( kompas_truncate_title( get_the_title( $p ) ) ); ?></a>
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
						<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( kompas_truncate_title( get_the_title( $p ) ) ); ?></a>
					</h4>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

		</div>

		<?php
		// ── BANNER ──────────────────────────────────────────────
		echo do_blocks( '<!-- wp:kompas/banner /-->' );
		?>
		<?php endif; // $paged === 1 ?>

		<?php
		// ── GRID: from archive query ─────────────────────────────
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
						<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( kompas_truncate_title( get_the_title( $p ) ) ); ?></a>
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
		<nav class="kompas-archive-pagination" aria-label="Пагинација">
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

	return '<nav class="kompas-header-categories" aria-label="Главна навигација">'
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

	return '<nav class="kompas-header-tags" aria-label="Секундарна навигација">'
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
	$editor_script_path = get_theme_file_path( 'assets/js/blocks-editor.js' );
	$editor_script_ver  = KOMPAS_VERSION;
	if ( file_exists( $editor_script_path ) ) {
		$editor_script_ver .= '.' . (string) filemtime( $editor_script_path );
	}

	wp_register_script(
		'kompas-blocks-editor',
		get_theme_file_uri( 'assets/js/blocks-editor.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components', 'wp-api-fetch' ),
		$editor_script_ver,
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
 * Register Homepage Hero block.
 */
function kompas_register_homepage_hero_block() {
	register_block_type( get_theme_file_path( 'blocks/homepage-hero' ) );
}
add_action( 'init', 'kompas_register_homepage_hero_block' );

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
	$path = get_theme_file_path( 'assets/js/mobile-nav.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}
	wp_enqueue_script(
		'kompas-mobile-nav',
		get_theme_file_uri( 'assets/js/mobile-nav.js' ),
		array(),
		$ver,
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

/**
 * ── Custom Author Photo ──────────────────────────────────────
 */

/**
 * Render author photo upload field on user profile page.
 */
function kompas_author_photo_field( $user ) {
	$photo_id  = get_user_meta( $user->ID, 'kompas_author_photo', true );
	$photo_url = $photo_id ? wp_get_attachment_image_url( $photo_id, 'thumbnail' ) : '';
	?>
	<h3>Фотографија аутора</h3>
	<table class="form-table">
		<tr>
			<th><label for="kompas-author-photo-id">Фотографија</label></th>
			<td>
				<img id="kompas-author-photo-preview"
					 src="<?php echo esc_url( $photo_url ); ?>"
					 style="max-width:150px;height:auto;display:<?php echo $photo_url ? 'block' : 'none'; ?>;margin-bottom:8px;border-radius:50%" />
				<input type="hidden" id="kompas-author-photo-id" name="kompas_author_photo" value="<?php echo esc_attr( $photo_id ); ?>" />
				<button type="button" class="button" id="kompas-author-photo-select">Изабери фотографију</button>
				<button type="button" class="button" id="kompas-author-photo-remove" style="display:<?php echo $photo_url ? 'inline-block' : 'none'; ?>">Уклони</button>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'kompas_author_photo_field' );
add_action( 'edit_user_profile', 'kompas_author_photo_field' );

/**
 * Save author photo attachment ID to user meta.
 */
function kompas_save_author_photo( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	if ( isset( $_POST['kompas_author_photo'] ) ) {
		update_user_meta( $user_id, 'kompas_author_photo', absint( $_POST['kompas_author_photo'] ) );
	}
}
add_action( 'personal_options_update', 'kompas_save_author_photo' );
add_action( 'edit_user_profile_update', 'kompas_save_author_photo' );

/**
 * Filter avatar URL to use custom photo if set.
 */
function kompas_custom_avatar_url( $url, $id_or_email, $args ) {
	$user_id = 0;

	if ( is_numeric( $id_or_email ) ) {
		$user_id = (int) $id_or_email;
	} elseif ( is_string( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
		if ( $user ) {
			$user_id = $user->ID;
		}
	} elseif ( $id_or_email instanceof WP_User ) {
		$user_id = $id_or_email->ID;
	} elseif ( $id_or_email instanceof WP_Post ) {
		$user_id = $id_or_email->post_author;
	} elseif ( $id_or_email instanceof WP_Comment ) {
		if ( $id_or_email->user_id ) {
			$user_id = (int) $id_or_email->user_id;
		}
	}

	if ( ! $user_id ) {
		return $url;
	}

	$photo_id = get_user_meta( $user_id, 'kompas_author_photo', true );
	if ( $photo_id ) {
		$photo_url = wp_get_attachment_image_url( $photo_id, 'thumbnail' );
		if ( $photo_url ) {
			return $photo_url;
		}
	}

	return $url;
}
add_filter( 'get_avatar_url', 'kompas_custom_avatar_url', 10, 3 );

/**
 * Enqueue media uploader script on profile pages.
 */
function kompas_enqueue_author_photo_script( $hook ) {
	if ( 'profile.php' !== $hook && 'user-edit.php' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	$path = get_theme_file_path( 'assets/js/author-photo.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}
	wp_enqueue_script(
		'kompas-author-photo',
		get_theme_file_uri( 'assets/js/author-photo.js' ),
		array( 'jquery' ),
		$ver,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'kompas_enqueue_author_photo_script' );

/**
 * ── Per-Category Hero Posts ──────────────────────────────────
 */

/**
 * Render hero post picker on category edit screen.
 */
function kompas_category_hero_fields( $term ) {
	$hero_ids = get_term_meta( $term->term_id, 'kompas_hero_posts', true );
	$hero_ids = is_array( $hero_ids ) ? $hero_ids : array();
	?>
	<tr class="form-field">
		<th scope="row"><label>Hero postovi (7)</label></th>
		<td>
			<div id="kompas-category-hero-wrap">
				<input type="hidden" id="kompas-category-hero-ids" name="kompas_hero_posts" value="<?php echo esc_attr( implode( ',', $hero_ids ) ); ?>" />
				<div id="kompas-category-hero-list" style="margin-bottom:10px"></div>
				<input type="text" id="kompas-category-hero-search" placeholder="Претражи постове..." class="regular-text" autocomplete="off" />
				<div id="kompas-category-hero-results" style="max-height:200px;overflow-y:auto;margin-top:4px"></div>
				<p class="description">Изаберите до 7 постова за hero секцију ове категорије.</p>
			</div>
		</td>
	</tr>
	<?php
}
add_action( 'category_edit_form_fields', 'kompas_category_hero_fields' );

/**
 * Save hero post IDs as term meta.
 */
function kompas_save_category_hero( $term_id ) {
	if ( ! isset( $_POST['kompas_hero_posts'] ) ) {
		return;
	}
	$ids = array_map( 'absint', array_filter( explode( ',', sanitize_text_field( $_POST['kompas_hero_posts'] ) ) ) );
	update_term_meta( $term_id, 'kompas_hero_posts', $ids );
}
add_action( 'edited_category', 'kompas_save_category_hero' );

/**
 * Enqueue category hero picker script on term edit pages.
 */
function kompas_enqueue_category_hero_script( $hook ) {
	if ( 'term.php' !== $hook && 'edit-tags.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'category' !== $screen->taxonomy ) {
		return;
	}
	$path = get_theme_file_path( 'assets/js/category-hero.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}
	wp_enqueue_script(
		'kompas-category-hero',
		get_theme_file_uri( 'assets/js/category-hero.js' ),
		array(),
		$ver,
		true
	);
	wp_localize_script( 'kompas-category-hero', 'kompasHeroData', array(
		'nonce' => wp_create_nonce( 'wp_rest' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'kompas_enqueue_category_hero_script' );

/**
 * ── Format "Не преводи" (script-toggle zaštita) ──────────────
 */

/**
 * Enqueue the inline format registration script for the block editor.
 */
function kompas_enqueue_format_neprevedi() {
	$path = get_theme_file_path( 'assets/js/format-neprevedi.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}

	wp_enqueue_script(
		'kompas-format-neprevedi',
		get_theme_file_uri( 'assets/js/format-neprevedi.js' ),
		array( 'wp-rich-text', 'wp-block-editor', 'wp-element' ),
		$ver,
		true
	);

	// Editor-only style: subtle highlight so the editor sees marked text.
	wp_add_inline_style(
		'wp-edit-blocks',
		'.kompas-neprevedi { background: rgba(232,45,52,0.12); border-bottom: 2px solid #e82d34; border-radius: 2px; padding: 0 1px; }'
	);
}
add_action( 'enqueue_block_editor_assets', 'kompas_enqueue_format_neprevedi' );

/**
 * ── Admin: Statistika ─────────────────────────────────────────
 */

/**
 * Register "Statistika" admin menu page.
 */
function kompas_add_statistics_page() {
	add_menu_page(
		'Kompas Statistika',
		'Statistika',
		'edit_posts',
		'kompas-statistics',
		'kompas_render_statistics_page',
		'dashicons-chart-bar',
		3
	);
}
add_action( 'admin_menu', 'kompas_add_statistics_page' );

/**
 * Render callback – loads the statistics template.
 */
function kompas_render_statistics_page() {
	require get_theme_file_path( 'admin/statistics.php' );
}

/**
 * ── Srpski format datuma ──────────────────────────────────────
 */

/**
 * Format post date in Serbian: "24. januar 2026."
 */
function kompas_format_date_serbian( $the_date, $format, $post ) {
	if ( ! empty( $format ) ) {
		return $the_date;
	}

	static $months = array(
		1  => 'јануар',
		2  => 'фебруар',
		3  => 'март',
		4  => 'април',
		5  => 'мај',
		6  => 'јун',
		7  => 'јул',
		8  => 'август',
		9  => 'септембар',
		10 => 'октобар',
		11 => 'новембар',
		12 => 'децембар',
	);

	if ( ! $post instanceof WP_Post ) {
		$post = get_post( $post );
	}
	if ( ! $post ) {
		return $the_date;
	}

	$timestamp = get_post_time( 'U', false, $post );
	$day       = (int) gmdate( 'j', $timestamp );
	$month     = isset( $months[ (int) gmdate( 'n', $timestamp ) ] ) ? $months[ (int) gmdate( 'n', $timestamp ) ] : $the_date;
	$year      = gmdate( 'Y', $timestamp );

	return $day . '. ' . $month . ' ' . $year . '.';
}
add_filter( 'get_the_date', 'kompas_format_date_serbian', 10, 3 );

/**
 * ── Sakrivanje autora ako nije unet ──────────────────────────
 */

/**
 * Hide the author label+name group when no custom author is set for the post.
 *
 * The inner group in single.html has className "kompas-author-wrap".
 * When no custom author meta is present, the entire group is suppressed.
 */
function kompas_hide_author_wrap( $block_content, $block, $instance ) {
	if ( empty( $block['attrs']['className'] ) || false === strpos( $block['attrs']['className'], 'kompas-author-wrap' ) ) {
		return $block_content;
	}

	$post_id = 0;
	if ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) {
		$post_id = (int) $instance->context['postId'];
	} elseif ( get_the_ID() ) {
		$post_id = (int) get_the_ID();
	}

	if ( $post_id <= 0 ) {
		return $block_content;
	}

	$custom_author = trim( (string) get_post_meta( $post_id, KOMPAS_CUSTOM_AUTHOR_META_KEY, true ) );
	if ( '' === $custom_author ) {
		return '';
	}

	return $block_content;
}
add_filter( 'render_block_core/group', 'kompas_hide_author_wrap', 10, 3 );

/**
 * ── Izvor fotografije (caption) ispod featured image ─────────
 */

/**
 * Append the featured image caption (attachment excerpt) below the image in single posts.
 */
function kompas_featured_image_caption( $block_content, $block, $instance ) {
	if ( ! is_singular( 'post' ) ) {
		return $block_content;
	}

	$post_id = 0;
	if ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) {
		$post_id = (int) $instance->context['postId'];
	} elseif ( get_the_ID() ) {
		$post_id = (int) get_the_ID();
	}

	if ( $post_id <= 0 ) {
		return $block_content;
	}

	// Samo za glavni post, ne za slike u povezanim vestima.
	if ( $post_id !== (int) get_queried_object_id() ) {
		return $block_content;
	}

	$thumbnail_id = get_post_thumbnail_id( $post_id );
	if ( ! $thumbnail_id ) {
		return $block_content;
	}

	$attachment = get_post( $thumbnail_id );
	if ( ! $attachment ) {
		return $block_content;
	}

	$caption = trim( $attachment->post_excerpt );
	if ( '' === $caption ) {
		return $block_content;
	}

	$caption_html = '<p class="kompas-featured-caption">' . esc_html( $caption ) . '</p>';
	return str_replace( '</figure>', $caption_html . '</figure>', $block_content );
}
add_filter( 'render_block_core/post-featured-image', 'kompas_featured_image_caption', 10, 3 );

/**
 * Add data-full attribute to featured image for lightbox full-size display.
 */
function kompas_featured_image_full_url( $block_content, $block, $instance ) {
	if ( ! is_singular( 'post' ) ) {
		return $block_content;
	}

	$post_id = 0;
	if ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) {
		$post_id = (int) $instance->context['postId'];
	} elseif ( get_the_ID() ) {
		$post_id = (int) get_the_ID();
	}

	if ( $post_id <= 0 ) {
		return $block_content;
	}

	$thumbnail_id = get_post_thumbnail_id( $post_id );
	if ( ! $thumbnail_id ) {
		return $block_content;
	}

	$full_url = wp_get_attachment_url( $thumbnail_id );
	if ( $full_url ) {
		$block_content = str_replace( '<img ', '<img data-full="' . esc_attr( $full_url ) . '" ', $block_content );
	}

	return $block_content;
}
add_filter( 'render_block_core/post-featured-image', 'kompas_featured_image_full_url', 9, 3 );

/**
 * ── Share linkovi ─────────────────────────────────────────────
 */

/**
 * Replace placeholder "#" share URLs with functional share links for the current post.
 */
function kompas_fix_share_links( $block_content, $block ) {
	if ( ! is_singular( 'post' ) ) {
		return $block_content;
	}

	if ( empty( $block['attrs']['url'] ) || '#' !== $block['attrs']['url'] ) {
		return $block_content;
	}

	$post_url   = rawurlencode( (string) get_permalink() );
	$post_title = rawurlencode( (string) get_the_title() );
	$service    = ! empty( $block['attrs']['service'] ) ? $block['attrs']['service'] : '';

	switch ( $service ) {
		case 'facebook':
			$share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . $post_url;
			break;
		case 'x':
			$share_url = 'https://x.com/intent/tweet?url=' . $post_url . '&text=' . $post_title;
			break;
		case 'linkedin':
			$share_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . $post_url;
			break;
		case 'mail':
			$share_url = 'mailto:?subject=' . $post_title . '&body=' . $post_url;
			break;
		default:
			return $block_content;
	}

	return str_replace( 'href="#"', 'href="' . esc_attr( $share_url ) . '"', $block_content );
}
add_filter( 'render_block_core/social-link', 'kompas_fix_share_links', 10, 2 );

/**
 * ── Lightbox skripte ─────────────────────────────────────────
 */

/**
 * Enqueue lightbox script only on single post pages.
 */
function kompas_enqueue_lightbox() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$path = get_theme_file_path( 'assets/js/lightbox.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}

	wp_enqueue_script(
		'kompas-lightbox',
		get_theme_file_uri( 'assets/js/lightbox.js' ),
		array(),
		$ver,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kompas_enqueue_lightbox' );

/**
 * ── Pomoćna funkcija za skraćivanje naslova ──────────────────
 */

/**
 * Truncate a title to a maximum number of characters, appending ellipsis if needed.
 *
 * @param string $title  The post title.
 * @param int    $length Maximum character count (default 60).
 * @return string        Truncated title.
 */
function kompas_truncate_title( $title, $length = 60 ) {
	if ( mb_strlen( $title ) <= $length ) {
		return $title;
	}
	return mb_substr( $title, 0, $length ) . '…';
}
