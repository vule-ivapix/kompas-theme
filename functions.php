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
define( 'KOMPAS_AUTHOR_LINK_META_KEY', 'kompas_author_link' );
define( 'KOMPAS_AUTHOR_NO_TRANSLATE_META_KEY', 'kompas_author_no_translate' );
define( 'KOMPAS_TITLE_NO_TRANSLATE_WORDS_META_KEY', 'kompas_title_no_translate_words' );
define( 'KOMPAS_GLOBAL_NO_TRANSLATE_WORDS_OPTION', 'kompas_global_no_translate_words' );
define( 'KOMPAS_AUTHOR_ID_META_KEY', 'kompas_author_id' );

/**
 * Enqueue Google Fonts with full latin-ext/cyrillic subsets.
 */
function kompas_enqueue_google_fonts() {
	$fonts_url = 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Source+Serif+4:ital,wght@0,400;0,600;0,700;1,400&display=swap&subset=latin-ext,cyrillic,cyrillic-ext';

	wp_enqueue_style(
		'kompas-google-fonts',
		$fonts_url,
		array(),
		null
	);
}
add_action( 'wp_enqueue_scripts', 'kompas_enqueue_google_fonts', 1 );
add_action( 'enqueue_block_editor_assets', 'kompas_enqueue_google_fonts', 1 );

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

	// Do not run transliteration script in editor previews/canvas requests.
	$is_editor_preview = false;
	if ( isset( $_GET['context'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['context'] ) ) ) {
		$is_editor_preview = true;
	}
	if ( isset( $_GET['wp_theme_preview'] ) ) {
		$is_editor_preview = true;
	}

	if ( ! $is_editor_preview ) {
		wp_enqueue_script(
			'kompas-script-toggle',
			get_theme_file_uri( 'assets/js/script-toggle.js' ),
			array(),
			$toggle_script_ver,
			true
		);

		wp_localize_script(
			'kompas-script-toggle',
			'kompasScriptToggleData',
			array(
				'globalNoTranslateWords' => kompas_get_global_no_translate_words(),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'kompas_enqueue_styles' );

/**
 * ── CPT: kompas_autor ─────────────────────────────────────────
 */

/**
 * Register Custom Post Type for authors.
 */
function kompas_register_autor_cpt() {
	register_post_type( 'kompas_autor', array(
		'labels' => array(
			'name'               => 'Аутори',
			'singular_name'      => 'Аутор',
			'add_new'            => 'Додај аутора',
			'add_new_item'       => 'Додај новог аутора',
			'edit_item'          => 'Уреди аутора',
			'new_item'           => 'Нови аутор',
			'view_item'          => 'Погледај аутора',
			'search_items'       => 'Претражи ауторе',
			'not_found'          => 'Нема аутора',
			'not_found_in_trash' => 'Нема аутора у смећу',
		),
		'public'              => false,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => true,
		'rest_base'           => 'kompas_autor',
		'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'menu_icon'           => 'dashicons-businessperson',
		'menu_position'       => 5,
		'capability_type'     => 'post',
		'has_archive'         => false,
		'exclude_from_search' => true,
		'query_var'           => 'kompas_autor',
		'rewrite'             => array(
			'slug'       => 'autori',
			'with_front' => false,
		),
		'show_in_nav_menus'   => false,
	) );
}
add_action( 'init', 'kompas_register_autor_cpt' );

/**
 * Expose featured_image_url field on kompas_autor REST endpoint.
 */
function kompas_register_autor_rest_fields() {
	register_rest_field( 'kompas_autor', 'featured_image_url', array(
		'get_callback' => function ( $post_arr ) {
			if ( empty( $post_arr['featured_media'] ) ) {
				return '';
			}
			$url = get_the_post_thumbnail_url( (int) $post_arr['id'], 'thumbnail' );
			return $url ?: '';
		},
		'schema' => array(
			'type'        => 'string',
			'description' => 'URL of the author thumbnail photo.',
		),
	) );
}
add_action( 'rest_api_init', 'kompas_register_autor_rest_fields' );

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
 * Restrict query loop on custom CPT author pages to posts assigned via kompas_author_id.
 */
function kompas_filter_cpt_author_archive_query( $query, $block, $page ) {
	if ( ! is_singular( 'kompas_autor' ) ) {
		return $query;
	}

	$cpt_author_id = (int) get_queried_object_id();
	if ( $cpt_author_id <= 0 ) {
		return $query;
	}

	$meta_query   = isset( $query['meta_query'] ) && is_array( $query['meta_query'] ) ? $query['meta_query'] : array();
	$meta_query[] = array(
		'key'     => KOMPAS_AUTHOR_ID_META_KEY,
		'value'   => $cpt_author_id,
		'compare' => '=',
		'type'    => 'NUMERIC',
	);

	$query['post_type']           = 'post';
	$query['post_status']         = 'publish';
	$query['meta_query']          = $meta_query;
	$query['ignore_sticky_posts'] = true;

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'kompas_filter_cpt_author_archive_query', 10, 3 );

/**
 * Add theme support.
 */
function kompas_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-thumbnails' );
	add_editor_style( 'style.css' );

	add_image_size( 'kompas-hero', 800, 500, true );
	add_image_size( 'kompas-thumbnail', 400, 250, true );
	add_image_size( 'kompas-small', 150, 100, true );

	register_nav_menus( array(
		'kompas-header-nav'  => 'Главна навигација (категорије)',
		'kompas-header-tags' => 'Секундарна навигација (тагови)',
		'kompas-footer-nav'  => 'Футер навигација (категорије/линкови)',
	) );
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

	register_post_meta( 'post', KOMPAS_AUTHOR_ID_META_KEY, array(
		'show_in_rest'  => true,
		'single'        => true,
		'type'          => 'integer',
		'default'       => 0,
		'auth_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
	) );

	register_post_meta( 'post', KOMPAS_AUTHOR_NO_TRANSLATE_META_KEY, array(
		'show_in_rest'  => true,
		'single'        => true,
		'type'          => 'boolean',
		'default'       => false,
		'auth_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
	) );

	register_post_meta( 'post', KOMPAS_TITLE_NO_TRANSLATE_WORDS_META_KEY, array(
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'string',
		'default'           => '',
		'sanitize_callback' => 'sanitize_textarea_field',
		'auth_callback'     => function () {
			return current_user_can( 'edit_posts' );
		},
	) );
}
add_action( 'init', 'kompas_register_meta' );

/**
 * Parse comma/newline separated no-translate words.
 *
 * @param string $raw Raw input.
 * @return string[]
 */
function kompas_parse_no_translate_words( $raw ) {
	$parts = preg_split( '/[\r\n,;]+/u', (string) $raw );
	$words = array();

	if ( ! is_array( $parts ) ) {
		return $words;
	}

	foreach ( $parts as $part ) {
		$word = trim( wp_strip_all_tags( (string) $part ) );
		if ( '' === $word ) {
			continue;
		}
		if ( ! in_array( $word, $words, true ) ) {
			$words[] = $word;
		}
	}

	return $words;
}

/**
 * Get global "do not translate" words from theme settings.
 *
 * @return string[]
 */
function kompas_get_global_no_translate_words() {
	$raw = (string) get_option( KOMPAS_GLOBAL_NO_TRANSLATE_WORDS_OPTION, '' );
	return kompas_parse_no_translate_words( $raw );
}

/**
 * Sanitize global no-translate words option.
 *
 * @param string $value Raw option value.
 * @return string
 */
function kompas_sanitize_global_no_translate_words_option( $value ) {
	$words = kompas_parse_no_translate_words( (string) $value );
	if ( empty( $words ) ) {
		return '';
	}
	return implode( ', ', $words );
}

/**
 * Register Kompas settings (Settings > Kompas).
 */
function kompas_register_theme_settings() {
	register_setting(
		'kompas_settings',
		KOMPAS_GLOBAL_NO_TRANSLATE_WORDS_OPTION,
		array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'kompas_sanitize_global_no_translate_words_option',
		)
	);

	add_settings_section(
		'kompas_transliteration_settings',
		'ЋИР/ЛАТ подешавања',
		static function () {
			echo '<p>Подеси речи које никада не треба аутоматски преводити у другом писму.</p>';
		},
		'kompas-settings'
	);

	add_settings_field(
		'kompas_global_no_translate_words_field',
		'Глобалне речи које се не преводе',
		'kompas_render_global_no_translate_words_field',
		'kompas-settings',
		'kompas_transliteration_settings'
	);
}
add_action( 'admin_init', 'kompas_register_theme_settings' );

/**
 * Render global no-translate words field.
 */
function kompas_render_global_no_translate_words_field() {
	$value = (string) get_option( KOMPAS_GLOBAL_NO_TRANSLATE_WORDS_OPTION, '' );
	?>
	<textarea
		name="<?php echo esc_attr( KOMPAS_GLOBAL_NO_TRANSLATE_WORDS_OPTION ); ?>"
		rows="6"
		class="large-text"
		placeholder="OpenAI, NATO, iPhone"><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description">Речи раздвајај зарезом или новим редом.</p>
	<?php
}

/**
 * Register Settings > Kompas page.
 */
function kompas_register_settings_page() {
	add_options_page(
		'Kompas Podešavanja',
		'Kompas',
		'manage_options',
		'kompas-settings',
		'kompas_render_settings_page'
	);
}
add_action( 'admin_menu', 'kompas_register_settings_page' );

/**
 * Render Settings > Kompas page.
 */
function kompas_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Kompas Podešavanja</h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'kompas_settings' );
			do_settings_sections( 'kompas-settings' );
			submit_button( 'Sačuvaj podešavanja' );
			?>
		</form>
	</div>
	<?php
}

/**
 * Get "do not translate" words configured for a post title.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function kompas_get_post_title_no_translate_words( $post_id ) {
	if ( $post_id <= 0 ) {
		return array();
	}

	$raw = get_post_meta( $post_id, KOMPAS_TITLE_NO_TRANSLATE_WORDS_META_KEY, true );
	return kompas_parse_no_translate_words( $raw );
}

/**
 * Build data attribute string for title no-translate words.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function kompas_get_post_title_no_translate_data_attr( $post_id ) {
	$words = kompas_get_post_title_no_translate_words( (int) $post_id );
	if ( empty( $words ) ) {
		return '';
	}

	return ' data-kompas-no-translate-words="' . esc_attr( implode( '||', $words ) ) . '"';
}

/**
 * Add a custom author name field to post edit screens.
 */
function kompas_add_custom_author_meta_box() {
	add_meta_box(
		'kompas-custom-author',
		'Аутор',
		'kompas_render_custom_author_meta_box',
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_post', 'kompas_add_custom_author_meta_box' );

/**
 * Enqueue live-search script for the author meta box on post edit screens.
 */
function kompas_enqueue_author_meta_box_script( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->post_type ) {
		return;
	}
	$path = get_theme_file_path( 'assets/js/author-meta-box.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}
	wp_enqueue_script(
		'kompas-author-meta-box',
		get_theme_file_uri( 'assets/js/author-meta-box.js' ),
		array(),
		$ver,
		true
	);
	wp_localize_script( 'kompas-author-meta-box', 'kompasAuthorMeta', array(
		'nonce'   => wp_create_nonce( 'wp_rest' ),
		'restUrl' => rest_url( 'wp/v2/kompas_autor' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'kompas_enqueue_author_meta_box_script' );

/**
 * Render custom author name field for posts.
 */
function kompas_render_custom_author_meta_box( $post ) {
	$author_cpt_id            = (int) get_post_meta( $post->ID, KOMPAS_AUTHOR_ID_META_KEY, true );
	$custom_author            = get_post_meta( $post->ID, KOMPAS_CUSTOM_AUTHOR_META_KEY, true );
	$author_link              = (bool) get_post_meta( $post->ID, KOMPAS_AUTHOR_LINK_META_KEY, true );
	$no_translate             = (bool) get_post_meta( $post->ID, KOMPAS_AUTHOR_NO_TRANSLATE_META_KEY, true );
	$title_no_translate_words = (string) get_post_meta( $post->ID, KOMPAS_TITLE_NO_TRANSLATE_WORDS_META_KEY, true );
	wp_nonce_field( 'kompas_save_custom_author', 'kompas_custom_author_nonce' );

	$cpt_author_name  = '';
	$cpt_author_photo = '';
	if ( $author_cpt_id > 0 ) {
		$cpt_post = get_post( $author_cpt_id );
		if ( $cpt_post ) {
			$cpt_author_name  = get_the_title( $cpt_post );
			$cpt_author_photo = get_the_post_thumbnail_url( $author_cpt_id, 'thumbnail' );
		}
	}
	?>

	<div id="kompas-author-cpt-section">
		<p style="font-weight:600;margin:0 0 6px">Аутор из базе аутора:</p>
		<input
			type="hidden"
			id="kompas-author-id-input"
			name="<?php echo esc_attr( KOMPAS_AUTHOR_ID_META_KEY ); ?>"
			value="<?php echo esc_attr( $author_cpt_id > 0 ? $author_cpt_id : '' ); ?>"
		/>
		<div id="kompas-author-selected" style="margin-bottom:6px;<?php echo $author_cpt_id > 0 ? '' : 'display:none;'; ?>">
			<img
				id="kompas-author-selected-photo"
				src="<?php echo esc_url( $cpt_author_photo ); ?>"
				alt=""
				style="width:40px;height:40px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:8px;<?php echo $cpt_author_photo ? '' : 'display:none;'; ?>"
			/>
			<strong id="kompas-author-selected-name"><?php echo esc_html( $cpt_author_name ); ?></strong>
		</div>
		<input
			type="text"
			id="kompas-author-search-input"
			placeholder="Претражи ауторе..."
			class="widefat"
			autocomplete="off"
			style="<?php echo $author_cpt_id > 0 ? 'display:none;' : ''; ?>"
		/>
		<div id="kompas-author-search-results" style="background:#fff;border:1px solid #ddd;border-radius:3px;margin-top:2px;display:none"></div>
		<button
			type="button"
			id="kompas-author-remove"
			class="button button-small"
			style="margin-top:6px;<?php echo $author_cpt_id > 0 ? '' : 'display:none;'; ?>"
		>Уклони аутора</button>
	</div>

	<hr style="margin:12px 0" />
	<p style="color:#666;font-size:11px;margin:0 0 8px">Резервни унос (само ако нема CPT аутора):</p>

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
	<p style="margin-top:0.75rem">
		<label>
			<input
				type="checkbox"
				name="kompas_author_link"
				value="1"
				<?php checked( $author_link ); ?>
			/>
			Линкуј аутора ка ауторској страници
		</label>
	</p>
	<p style="margin-top:0.5rem">
		<label>
			<input
				type="checkbox"
				name="kompas_author_no_translate"
				value="1"
				<?php checked( $no_translate ); ?>
			/>
			Не преводи ime аутора
			</label>
	</p>
	<p style="margin-top:0.75rem">
		<label for="kompas-title-no-translate-input">Речи у наслову које се не преводе</label>
		<input
			type="text"
			id="kompas-title-no-translate-input"
			name="kompas_title_no_translate_words"
			value="<?php echo esc_attr( $title_no_translate_words ); ?>"
			class="widefat"
			placeholder="OpenAI, NATO, iPhone"
		/>
	</p>
	<p class="description">
		Унеси речи раздвојене зарезом. Биће приказане у изворном писму без обзира на ЋИР/ЛАТ прекидач.
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

	// Save kompas_author_id (CPT-based author).
	$author_cpt_id = isset( $_POST[ KOMPAS_AUTHOR_ID_META_KEY ] ) ? absint( $_POST[ KOMPAS_AUTHOR_ID_META_KEY ] ) : 0;
	if ( $author_cpt_id > 0 ) {
		update_post_meta( $post_id, KOMPAS_AUTHOR_ID_META_KEY, $author_cpt_id );
	} else {
		delete_post_meta( $post_id, KOMPAS_AUTHOR_ID_META_KEY );
	}

	if ( ! isset( $_POST['kompas_custom_author'] ) ) {
		return;
	}

	$custom_author = sanitize_text_field( wp_unslash( $_POST['kompas_custom_author'] ) );
	if ( '' === $custom_author ) {
		delete_post_meta( $post_id, KOMPAS_CUSTOM_AUTHOR_META_KEY );
	} else {
		update_post_meta( $post_id, KOMPAS_CUSTOM_AUTHOR_META_KEY, $custom_author );
	}

	$author_link = ! empty( $_POST['kompas_author_link'] ) ? '1' : '0';
	update_post_meta( $post_id, KOMPAS_AUTHOR_LINK_META_KEY, $author_link );

	$no_translate = ! empty( $_POST['kompas_author_no_translate'] ) ? '1' : '0';
	update_post_meta( $post_id, KOMPAS_AUTHOR_NO_TRANSLATE_META_KEY, $no_translate );

	$title_no_translate_raw = isset( $_POST['kompas_title_no_translate_words'] )
		? sanitize_textarea_field( wp_unslash( $_POST['kompas_title_no_translate_words'] ) )
		: '';
	$title_no_translate_words = kompas_parse_no_translate_words( $title_no_translate_raw );
	if ( empty( $title_no_translate_words ) ) {
		delete_post_meta( $post_id, KOMPAS_TITLE_NO_TRANSLATE_WORDS_META_KEY );
	} else {
		update_post_meta( $post_id, KOMPAS_TITLE_NO_TRANSLATE_WORDS_META_KEY, implode( ', ', $title_no_translate_words ) );
	}
}
add_action( 'save_post_post', 'kompas_save_custom_author_meta' );

/**
 * Get public archive-like URL for a custom CPT author.
 */
function kompas_get_cpt_author_archive_url( $cpt_author_id ) {
	$cpt_author_id = (int) $cpt_author_id;
	if ( $cpt_author_id <= 0 ) {
		return '';
	}

	$cpt_post = get_post( $cpt_author_id );
	if ( ! $cpt_post || 'kompas_autor' !== $cpt_post->post_type || 'publish' !== $cpt_post->post_status ) {
		return '';
	}

	$url = get_permalink( $cpt_post );
	return $url ? (string) $url : '';
}

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

	// CPT-based author takes priority.
	$cpt_author_id = (int) get_post_meta( $post_id, KOMPAS_AUTHOR_ID_META_KEY, true );
	if ( $cpt_author_id > 0 ) {
		$cpt_post = get_post( $cpt_author_id );
		if ( $cpt_post && 'publish' === $cpt_post->post_status ) {
			$custom_author = esc_html( get_the_title( $cpt_post ) );
			$cpt_author_url = kompas_get_cpt_author_archive_url( $cpt_author_id );
			$replacement    = $custom_author;
			if ( '' !== $cpt_author_url ) {
				$replacement = '<a href="' . esc_url( $cpt_author_url ) . '">' . $custom_author . '</a>';
			}

			if ( false !== stripos( $block_content, '<a ' ) ) {
				// CPT authors always link to their custom archive page when URL exists.
				$updated = preg_replace(
					'/<a\b[^>]*>(.*?)<\/a>/is',
					$replacement,
					$block_content,
					1
				);
				return $updated ?: $block_content;
			}

			$updated = preg_replace_callback(
				'/(<div\b[^>]*>).*?(<\/div>)/is',
				static function ( $matches ) use ( $replacement ) {
					return $matches[1] . $replacement . $matches[2];
				},
				$block_content,
				1
			);
			return $updated ?: $block_content;
		}
	}

	$custom_author = trim( (string) get_post_meta( $post_id, KOMPAS_CUSTOM_AUTHOR_META_KEY, true ) );
	if ( '' === $custom_author ) {
		return $block_content;
	}

	$custom_author = esc_html( $custom_author );
	$author_link   = (bool) get_post_meta( $post_id, KOMPAS_AUTHOR_LINK_META_KEY, true );
	$no_translate  = (bool) get_post_meta( $post_id, KOMPAS_AUTHOR_NO_TRANSLATE_META_KEY, true );

	// If no-translate is set, wrap the entire block in a kompas-neprevedi span.
	if ( $no_translate ) {
		$block_content = '<span class="kompas-neprevedi">' . $block_content . '</span>';
	}

	if ( false !== stripos( $block_content, '<a ' ) ) {
		if ( $author_link ) {
			// Keep the link, just replace the text.
			$updated = preg_replace_callback(
				'/(<a\b[^>]*>).*?(<\/a>)/is',
				static function ( $matches ) use ( $custom_author ) {
					return $matches[1] . $custom_author . $matches[2];
				},
				$block_content,
				1
			);
		} else {
			// Strip the link, output plain text inside the wrapper.
			$updated = preg_replace(
				'/<a\b[^>]*>(.*?)<\/a>/is',
				$custom_author,
				$block_content,
				1
			);
		}

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
 * Add per-post title no-translate words to post-title block output.
 */
function kompas_mark_post_title_block_no_translate_words( $block_content, $block, $instance ) {
	$post_id = 0;

	if ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) {
		$post_id = (int) $instance->context['postId'];
	} elseif ( get_the_ID() ) {
		$post_id = (int) get_the_ID();
	}

	if ( $post_id <= 0 ) {
		return $block_content;
	}

	$data_attr = kompas_get_post_title_no_translate_data_attr( $post_id );
	if ( '' === $data_attr ) {
		return $block_content;
	}

	if ( false !== strpos( $block_content, 'data-kompas-no-translate-words=' ) ) {
		return $block_content;
	}

	$updated = preg_replace(
		'/(<[a-zA-Z0-9:-]+\b)([^>]*>)/',
		'$1' . $data_attr . '$2',
		$block_content,
		1
	);

	return $updated ?: $block_content;
}
add_filter( 'render_block_core/post-title', 'kompas_mark_post_title_block_no_translate_words', 10, 3 );

/**
 * Truncate post-title block only in frontend list/query contexts.
 *
 * Keeps full titles on single post pages and in admin/editor.
 */
function kompas_truncate_post_title_block_in_lists( $block_content, $block, $instance ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
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

	$is_query_context = $instance instanceof WP_Block && ! empty( $instance->context['queryId'] );
	$is_archive_like  = is_home() || is_archive() || is_search();

	if ( ! $is_query_context && ! $is_archive_like ) {
		return $block_content;
	}

	$full_title      = (string) get_the_title( $post_id );
	$truncated_title = kompas_truncate_title( $full_title, 60 );
	if ( $truncated_title === $full_title ) {
		return $block_content;
	}

	$replacement = esc_html( $truncated_title );

	if ( false !== stripos( $block_content, '<a ' ) ) {
		$updated = preg_replace(
			'/(<a\b[^>]*>).*?(<\/a>)/is',
			'$1' . $replacement . '$2',
			$block_content,
			1
		);
		return $updated ?: $block_content;
	}

	$updated = preg_replace(
		'/(>)[^<]*(<\/[a-zA-Z0-9:-]+>)/',
		'$1' . $replacement . '$2',
		$block_content,
		1
	);

	return $updated ?: $block_content;
}
add_filter( 'render_block_core/post-title', 'kompas_truncate_post_title_block_in_lists', 20, 3 );

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

	$najnovije_url   = ! empty( $attributes['najnovijeUrl'] )   ? esc_url( $attributes['najnovijeUrl'] )   : '';
	$najcitanije_url = ! empty( $attributes['najcitanijeUrl'] ) ? esc_url( $attributes['najcitanijeUrl'] ) : '';

	if ( ! $najnovije_url ) {
		$page_for_posts = (int) get_option( 'page_for_posts' );
		$najnovije_url  = $page_for_posts ? esc_url( get_permalink( $page_for_posts ) ) : esc_url( home_url( '/' ) );
	}

	$najnovije_ids   = ! empty( $attributes['najnovijePostIds'] )   ? array_map( 'absint', $attributes['najnovijePostIds'] )   : array();
	$najcitanije_ids = ! empty( $attributes['najcitanijePostIds'] ) ? array_map( 'absint', $attributes['najcitanijePostIds'] ) : array();

	// Fetch najnovije posts.
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

	// Fetch najcitanije posts.
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

	$btn_base   = 'font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding:0 0 var(--wp--preset--spacing--20);margin-right:var(--wp--preset--spacing--50);background:none;border:none;cursor:pointer;font-family:inherit;';
	$link_style = 'font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--wp--preset--color--muted);text-decoration:none;white-space:nowrap;padding-bottom:var(--wp--preset--spacing--20);display:inline-block;';

	ob_start();
	?>
	<div class="kompas-tabs-section" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">

		<div class="kompas-tabs-heading kompas-section-topline" style="margin-bottom:var(--wp--preset--spacing--50)">
			<div style="display:flex;justify-content:space-between;align-items:flex-end;border-bottom:1px solid var(--wp--preset--color--border)">
				<div class="kompas-tabs-nav" style="display:flex;gap:0">
					<button class="kompas-tab-btn is-active" data-tab="najnovije" type="button" style="<?php echo esc_attr( $btn_base ); ?>border-bottom:3px solid var(--wp--preset--color--primary);color:var(--wp--preset--color--dark)">НАЈНОВИЈЕ</button>
					<button class="kompas-tab-btn" data-tab="najcitanije" type="button" style="<?php echo esc_attr( $btn_base ); ?>border-bottom:3px solid transparent;color:var(--wp--preset--color--muted)">НАЈЧИТАНИЈЕ</button>
				</div>
				<?php if ( $najnovije_url || $najcitanije_url ) : ?>
				<div class="kompas-tabs-viewall" style="display:flex;gap:var(--wp--preset--spacing--40)">
					<?php if ( $najnovije_url ) : ?>
					<a href="<?php echo $najnovije_url; ?>" class="kompas-tabs-viewall__link" data-for="najnovije" style="<?php echo esc_attr( $link_style ); ?>">СВЕ НАЈНОВИЈЕ →</a>
					<?php endif; ?>
					<?php if ( $najcitanije_url ) : ?>
					<a href="<?php echo $najcitanije_url; ?>" class="kompas-tabs-viewall__link" data-for="najcitanije" style="<?php echo esc_attr( $link_style ); ?>">СВЕ НАЈЧИТАНИЈЕ →</a>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="kompas-tab-panel is-active" data-panel="najnovije">
			<?php echo kompas_render_posts_grid( $najnovije ); ?>
		</div>

		<div class="kompas-tab-panel" data-panel="najcitanije" style="display:none">
			<?php echo kompas_render_posts_grid( $najcitanije ); ?>
		</div>

	<?php if ( $najnovije_url || $najcitanije_url ) : ?>
	<div class="kompas-tabs-viewall kompas-tabs-viewall--bottom">
		<?php if ( $najnovije_url ) : ?>
		<a href="<?php echo $najnovije_url; ?>" class="kompas-tabs-viewall__link" data-for="najnovije" style="<?php echo esc_attr( $link_style ); ?>">СВЕ НАЈНОВИЈЕ →</a>
		<?php endif; ?>
		<?php if ( $najcitanije_url ) : ?>
		<a href="<?php echo $najcitanije_url; ?>" class="kompas-tabs-viewall__link" data-for="najcitanije" style="<?php echo esc_attr( $link_style ); ?>">СВЕ НАЈЧИТАНИЈЕ →</a>
		<?php endif; ?>
	</div>
	<?php endif; ?>

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
			<h4 style="font-size:0.9375rem;font-weight:700;line-height:1.3;margin:0"<?php echo kompas_get_post_title_no_translate_data_attr( $post->ID ); ?>>
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
	register_block_type( get_theme_file_path( 'blocks/post-list' ) );
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
 * Render footer navigation columns from the dedicated WP menu location.
 */
function kompas_render_footer_categories( $attributes = array() ) {
	unset( $attributes );

	if ( ! has_nav_menu( 'kompas-footer-nav' ) ) {
		return '';
	}

	$menu = wp_nav_menu( array(
		'theme_location'  => 'kompas-footer-nav',
		'container'       => 'nav',
		'container_class' => 'kompas-footer-categories kompas-footer-categories--menu',
		'container_id'    => '',
		'menu_class'      => 'kompas-footer-categories__menu',
		'fallback_cb'     => false,
		'depth'           => 2,
		'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
		'echo'            => false,
	) );

	return ! empty( $menu ) ? $menu : '';
}

/**
 * Render the "Povezane vesti" section as a reusable dynamic block.
 */
function kompas_render_related_posts_block( $attributes = array(), $exclude_id = 0 ) {
	$title         = isset( $attributes['title'] ) ? trim( (string) $attributes['title'] ) : 'ПОВЕЗАНЕ ВЕСТИ';
	$posts_to_show = isset( $attributes['postsToShow'] ) ? (int) $attributes['postsToShow'] : 4;
	$posts_to_show = max( 1, min( 12, $posts_to_show ) );
	$selected_ids  = ! empty( $attributes['selectedPostIds'] ) && is_array( $attributes['selectedPostIds'] )
		? array_values( array_filter( array_map( 'absint', $attributes['selectedPostIds'] ) ) )
		: array();

	$source_post_id = 0;
	if ( $exclude_id > 0 ) {
		$source_post_id = $exclude_id;
	} elseif ( is_singular( 'post' ) ) {
		$qid = (int) get_queried_object_id();
		if ( $qid > 0 ) {
			$source_post_id = $qid;
		}
	}

	$exclude_ids = array();
	if ( $source_post_id > 0 ) {
		$exclude_ids[] = $source_post_id;
	}

	$posts = array();

	// 1) Manually selected posts (preserve order).
	if ( ! empty( $selected_ids ) ) {
		$posts = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'post__in'       => $selected_ids,
			'orderby'        => 'post__in',
			'posts_per_page' => $posts_to_show,
			'post__not_in'   => $exclude_ids,
			'ignore_sticky_posts' => true,
		) );
	}

	$source_category_ids = array();
	$source_tag_ids      = array();

	if ( $source_post_id > 0 ) {
		$source_category_ids = wp_get_post_categories( $source_post_id, array( 'fields' => 'ids' ) );
		$source_tag_ids      = wp_get_post_tags( $source_post_id, array( 'fields' => 'ids' ) );

		$source_category_ids = is_array( $source_category_ids ) ? $source_category_ids : array();
		$source_tag_ids      = is_array( $source_tag_ids ) ? $source_tag_ids : array();

		$source_category_ids = array_values( array_filter( array_unique( array_map( 'absint', $source_category_ids ) ) ) );
		$source_tag_ids      = array_values( array_filter( array_unique( array_map( 'absint', $source_tag_ids ) ) ) );

		$default_category_id = (int) get_option( 'default_category', 1 );
		if ( $default_category_id > 0 && ! empty( $source_category_ids ) ) {
			$source_category_ids = array_values(
				array_filter(
					$source_category_ids,
					static function( $term_id ) use ( $default_category_id ) {
						return (int) $term_id !== $default_category_id;
					}
				)
			);
		}
	}

	$exclude_ids = array_unique( array_merge( $exclude_ids, wp_list_pluck( $posts, 'ID' ) ) );
	$remaining = $posts_to_show - count( $posts );

	$append_related_posts = function( $query_args ) use ( &$posts, &$remaining, &$exclude_ids, $posts_to_show ) {
		if ( $remaining <= 0 ) {
			return;
		}

		$base_args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $remaining,
			'post__not_in'        => $exclude_ids,
			'ignore_sticky_posts' => true,
		);

		$next_posts = get_posts( array_merge( $base_args, $query_args ) );
		if ( empty( $next_posts ) ) {
			return;
		}

		$posts       = array_merge( $posts, $next_posts );
		$exclude_ids = array_unique( array_merge( $exclude_ids, wp_list_pluck( $next_posts, 'ID' ) ) );
		$remaining   = $posts_to_show - count( $posts );
	};

	// 2) Semantic fill order: category+tag -> category -> tag -> latest fallback.
	if ( $remaining > 0 && ! empty( $source_category_ids ) && ! empty( $source_tag_ids ) ) {
		$append_related_posts( array(
			'orderby'   => 'date',
			'order'     => 'DESC',
			'tax_query' => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => $source_category_ids,
				),
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $source_tag_ids,
				),
			),
		) );
	}

	if ( $remaining > 0 && ! empty( $source_category_ids ) ) {
		$append_related_posts( array(
			'orderby'     => 'date',
			'order'       => 'DESC',
			'category__in' => $source_category_ids,
		) );
	}

	if ( $remaining > 0 && ! empty( $source_tag_ids ) ) {
		$append_related_posts( array(
			'orderby' => 'date',
			'order'   => 'DESC',
			'tag__in' => $source_tag_ids,
		) );
	}

	if ( $remaining > 0 ) {
		$append_related_posts( array(
			'orderby' => 'date',
			'order'   => 'DESC',
		) );
	}

	if ( empty( $posts ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="wp-block-group alignwide" style="border-top-color:var(--wp--preset--color--border);border-top-width:1px;border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">

		<?php if ( '' !== $title ) : ?>
		<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--primary);border-bottom-width:3px;margin-bottom:var(--wp--preset--spacing--50)">
			<h3 class="has-dark-color has-text-color" style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding-bottom:var(--wp--preset--spacing--20)">
				<?php echo esc_html( $title ); ?>
			</h3>
		</div>
		<?php endif; ?>

				<div class="wp-block-query kompas-related-posts-query">
					<ul class="wp-block-post-template" style="margin:0;padding:0;list-style:none">
					<?php foreach ( $posts as $related_post ) : ?>
					<li class="wp-block-post" style="margin:0;width:auto;min-width:0">
						<?php if ( has_post_thumbnail( $related_post ) ) : ?>
						<figure class="wp-block-post-featured-image" style="margin-bottom:var(--wp--preset--spacing--20)">
							<a href="<?php echo esc_url( get_permalink( $related_post ) ); ?>">
								<?php
								echo get_the_post_thumbnail(
									$related_post,
									'large',
									array(
										'style' => 'aspect-ratio:16/10;object-fit:cover;width:100%;height:auto',
									)
								);
								?>
							</a>
						</figure>
						<?php endif; ?>
						<h2 class="wp-block-post-title has-dark-color has-text-color" style="font-size:0.875rem;font-weight:700;line-height:1.3;margin:0"<?php echo kompas_get_post_title_no_translate_data_attr( $related_post->ID ); ?>>
							<a href="<?php echo esc_url( get_permalink( $related_post ) ); ?>"><?php echo esc_html( kompas_truncate_title( get_the_title( $related_post ), 60 ) ); ?></a>
						</h2>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>

		</div>
		<?php
	return ob_get_clean();
}

/**
 * Register footer/related dynamic blocks via block.json.
 */
function kompas_register_footer_categories_block() {
	register_block_type( get_theme_file_path( 'blocks/footer-categories' ) );
	register_block_type( get_theme_file_path( 'blocks/footer-pages' ) );
	register_block_type( get_theme_file_path( 'blocks/related-posts' ) );
}
add_action( 'init', 'kompas_register_footer_categories_block' );

/**
 * ── Archive Layout Block ──────────────────────────────────────
 */

/**
 * Wrapper za paginate_links() koji uklanja poslednji broj stranice kada postoje dots.
 * WordPress ignoriše end_size=>0 i i dalje renderuje poslednju stranicu pored "...".
 */
function kompas_paginate_links( $args ) {
	$args['type'] = 'list';
	$out = paginate_links( $args );
	if ( ! $out || strpos( $out, 'dots' ) === false ) {
		return $out;
	}
	// Ukloni prvi broj stranice između ← prev i dots
	$out = preg_replace(
		'~(<li>\s*<a[^>]+class="prev page-numbers"[^>]*>.*?</a>\s*</li>)\s*<li>\s*<a[^>]+class="page-numbers"[^>]*>\d+</a>\s*</li>(\s*<li>\s*<span[^>]+class="page-numbers dots")~is',
		'$1$2',
		$out
	);
	// Ukloni poslednji broj stranice koji se pojavljuje posle dots, pre → next
	$out = preg_replace(
		'~<li>\s*<a[^>]+class="page-numbers"[^>]*>\d+</a>\s*</li>(\s*<li>\s*<a[^>]+class="next page-numbers")~i',
		'$1',
		$out
	);
	return $out;
}

/**
 * Render kolumne archive layout: 3-kolona grid bez hero sekcije.
 */
function kompas_render_archive_layout_kolumne( $attributes = array() ) {
	global $wp_query;

	$paged    = max( 1, get_query_var( 'paged', 1 ) );
	$per_page = isset( $attributes['postsPerPage'] ) ? (int) $attributes['postsPerPage'] : 12;

	$args  = array_merge( $wp_query->query_vars, array(
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'post_status'    => 'publish',
	) );
	$query = new WP_Query( $args );
	$posts = $query->posts;

	if ( empty( $posts ) ) {
		return '<p style="color:var(--wp--preset--color--muted)">Нема постова за приказ.</p>';
	}

	ob_start();
	?>
	<div class="kompas-archive-layout kompas-archive-layout--kolumne">
		<div class="kompas-archive-grid-row kompas-archive-grid-row--3">
			<?php foreach ( $posts as $p ) :
				$author_id   = $p->post_author;
				$author_name = get_the_author_meta( 'display_name', $author_id );
				$cpt_author_id = (int) get_post_meta( $p->ID, 'kompas_author_id', true );
				if ( $cpt_author_id > 0 ) {
					$cpt_post = get_post( $cpt_author_id );
					if ( $cpt_post ) {
						$author_name = get_the_title( $cpt_post );
					}
				}
				$custom_author = get_post_meta( $p->ID, KOMPAS_CUSTOM_AUTHOR_META_KEY, true );
				if ( ! empty( $custom_author ) ) {
					$author_name = $custom_author;
				}
			?>
			<div class="kompas-archive-grid-item">
				<?php if ( has_post_thumbnail( $p ) ) : ?>
				<a href="<?php echo esc_url( get_permalink( $p ) ); ?>">
					<img src="<?php echo esc_url( get_the_post_thumbnail_url( $p, 'medium' ) ); ?>"
						 alt="<?php echo esc_attr( get_the_title( $p ) ); ?>"
						 class="kompas-archive-img" />
				</a>
				<?php endif; ?>
				<h4 class="kompas-archive-title kompas-archive-title--sm"<?php echo kompas_get_post_title_no_translate_data_attr( $p->ID ); ?>>
					<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( kompas_truncate_title( get_the_title( $p ) ) ); ?></a>
				</h4>
				<?php if ( $author_name ) : ?>
				<span class="kompas-archive-author"><?php echo esc_html( $author_name ); ?></span>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>

		<?php
		$total_pages = $query->max_num_pages;
		if ( $total_pages > 1 ) :
		?>
		<nav class="kompas-archive-pagination" aria-label="Пагинација">
			<?php
			echo kompas_paginate_links( array(
				'total'     => $total_pages,
				'current'   => $paged,
				'mid_size'  => 1,
				'end_size'  => 0,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
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

	$layout_type = isset( $attributes['layoutType'] ) ? $attributes['layoutType'] : 'default';

	// ── KOLUMNE LAYOUT (3-kolona grid, bez hero sekcije) ──────────
	if ( $layout_type === 'kolumne' ) {
		return kompas_render_archive_layout_kolumne( $attributes );
	}

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
		// Fallback: latest 5 from current archive query (1 main + 4 horiz).
		$hero_args = array_merge( $wp_query->query_vars, array(
			'posts_per_page' => 5,
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

		<?php if ( $paged === 1 ) : ?>
		<?php
		// ── HERO SECTION: samo na prvoj strani ──────────────────
		$hero_main  = isset( $hero_posts[0] ) ? $hero_posts[0] : null;
		$hero_horiz = array_slice( $hero_posts, 1, 4 );
		?>
		<div class="kompas-archive-hero">

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
					<h2 class="kompas-archive-title kompas-archive-title--lg"<?php echo kompas_get_post_title_no_translate_data_attr( $hero_main->ID ); ?>>
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
							<h3 class="kompas-archive-title kompas-archive-title--md"<?php echo kompas_get_post_title_no_translate_data_attr( $p->ID ); ?>>
								<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( kompas_truncate_title( get_the_title( $p ) ) ); ?></a>
							</h3>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>

			<div class="kompas-archive-hero-right">
				<?php echo do_blocks( '<!-- wp:kompas/rec-urednika /-->' ); ?>
				<?php echo do_blocks( '<!-- wp:kompas/kolumne /-->' ); ?>
				<?php echo do_blocks( '<!-- wp:kompas/banner {"variant":"square"} /-->' ); ?>
			</div>

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
						<h4 class="kompas-archive-title <?php echo $big ? 'kompas-archive-title--md' : 'kompas-archive-title--sm'; ?>"<?php echo kompas_get_post_title_no_translate_data_attr( $p->ID ); ?>>
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
			echo kompas_paginate_links( array(
				'total'     => $total_pages,
				'current'   => $paged,
				'mid_size'  => 1,
				'end_size'  => 0,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
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
	unset( $attributes );

	if ( ! has_nav_menu( 'kompas-header-nav' ) ) {
		return '';
	}

	$menu = wp_nav_menu( array(
		'theme_location'  => 'kompas-header-nav',
		'container'       => 'nav',
		'container_class' => 'kompas-header-categories',
		'container_id'    => '',
		'menu_class'      => 'kompas-nav-menu',
		'fallback_cb'     => false,
		'depth'           => 2,
		'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
		'echo'            => false,
	) );

	return ! empty( $menu ) ? $menu : '';
}

/**
 * Render the secondary header nav (tags).
 */
function kompas_render_header_tags( $attributes = array() ) {
	unset( $attributes );

	if ( ! has_nav_menu( 'kompas-header-tags' ) ) {
		return '';
	}

	$menu = wp_nav_menu( array(
		'theme_location'  => 'kompas-header-tags',
		'container'       => 'nav',
		'container_class' => 'kompas-header-tags',
		'container_id'    => '',
		'menu_class'      => 'kompas-nav-menu',
		'fallback_cb'     => false,
		'depth'           => 1,
		'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
		'echo'            => false,
	) );

	return ! empty( $menu ) ? $menu : '';
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
 * Lokalizuj editor skriptu sa URL-om settings stranice.
 */
function kompas_localize_blocks_editor_script() {
	wp_localize_script( 'kompas-blocks-editor', 'kompasBlocksData', array(
		'settingsUrl' => admin_url( 'admin.php?page=kompas-homepage-settings' ),
	) );
}
add_action( 'enqueue_block_editor_assets', 'kompas_localize_blocks_editor_script' );

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
	register_block_type( get_theme_file_path( 'blocks/autor-card' ) );
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
 * Auto-assign single-kolumne template for posts in kolumne/kolumna categories.
 */
function kompas_is_kolumne_post( $post_id = 0 ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		$post_id = (int) get_queried_object_id();
	}
	if ( $post_id <= 0 && get_the_ID() ) {
		$post_id = (int) get_the_ID();
	}
	if ( $post_id <= 0 || 'post' !== get_post_type( $post_id ) ) {
		return false;
	}

	// Fast path for expected slugs.
	if ( has_term( array( 'kolumne', 'kolumna' ), 'category', $post_id ) ) {
		return true;
	}

	$terms = get_the_terms( $post_id, 'category' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return false;
	}

	foreach ( $terms as $term ) {
		$slug       = mb_strtolower( (string) $term->slug );
		$name       = mb_strtolower( (string) $term->name );
		$slug_latin = kompas_cyrillic_to_latin_for_slug( $slug );
		$name_latin = kompas_cyrillic_to_latin_for_slug( $name );

		if ( false !== strpos( $slug_latin, 'kolumn' ) || false !== strpos( $name_latin, 'kolumn' ) ) {
			return true;
		}
	}

	return false;
}

function kompas_kolumne_single_template( $templates ) {
	if ( ! is_singular( 'post' ) ) {
		return $templates;
	}

	$post_id = (int) get_queried_object_id();
	if ( kompas_is_kolumne_post( $post_id ) ) {
		array_unshift( $templates, 'single-kolumne' );
	}

	return $templates;
}
add_filter( 'single_template_hierarchy', 'kompas_kolumne_single_template' );

/**
 * Fallback: force header-author template part on single Kolumne posts.
 *
 * This guarantees the custom header even when WordPress resolves the base single template.
 */
function kompas_force_header_author_template_part( $block_content, $block, $instance ) {
	if ( ! is_singular( 'post' ) ) {
		return $block_content;
	}

	$post_id = 0;
	if ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) {
		$post_id = (int) $instance->context['postId'];
	}
	if ( $post_id <= 0 ) {
		$post_id = (int) get_queried_object_id();
	}
	if ( ! kompas_is_kolumne_post( $post_id ) ) {
		return $block_content;
	}

	$slug = (string) ( $block['attrs']['slug'] ?? '' );
	$area = (string) ( $block['attrs']['area'] ?? '' );

	if ( 'header-author' === $slug ) {
		return $block_content;
	}
	if ( 'header' !== $area && ! str_starts_with( $slug, 'header' ) ) {
		return $block_content;
	}

	return do_blocks( '<!-- wp:template-part {"slug":"header-author","tagName":"header","area":"header"} /-->' );
}
add_filter( 'render_block_core/template-part', 'kompas_force_header_author_template_part', 10, 3 );

/**
 * Exclude "kolumne/kolumna" and "rec-urednika" categories from public category queries
 * (widgets, default category lists, etc.) but NOT from admin or explicit queries.
 */
function kompas_exclude_hidden_categories( $clauses, $taxonomies, $args ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
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

	// Never hide categories when querying terms assigned to specific objects/posts.
	if ( ! empty( $args['object_ids'] ) ) {
		return $clauses;
	}

	global $wpdb;
	$hidden_slugs = array( 'kolumne', 'kolumna', 'rec-urednika' );
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
 * ── Povezane vesti – per-post meta box ───────────────────────
 */

/**
 * Registruj meta box za izbor povezanih vesti po postu.
 */
function kompas_register_related_posts_meta_box() {
	add_meta_box(
		'kompas-related-posts',
		'Povezane vesti',
		'kompas_render_related_posts_meta_box',
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'kompas_register_related_posts_meta_box' );

/**
 * Render HTML za meta box.
 */
function kompas_render_related_posts_meta_box( $post ) {
	wp_nonce_field( 'kompas_related_posts_save', 'kompas_related_posts_nonce' );
	$ids = get_post_meta( $post->ID, 'kompas_related_post_ids', true );
	$ids = is_array( $ids ) ? $ids : array();
	?>
	<div id="kompas-related-posts-wrap">
		<input type="hidden" id="kompas-related-posts-ids" name="kompas_related_post_ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" />
		<div id="kompas-related-posts-list" style="margin-bottom:8px"></div>
		<input type="text" id="kompas-related-posts-search" placeholder="Pretraži postove..." class="widefat" autocomplete="off" style="margin-bottom:4px" />
		<div id="kompas-related-posts-results" style="max-height:200px;overflow-y:auto"></div>
		<p class="description" style="margin-top:6px">Do 6 postova. Ako nije ništa izabrano, koristi se automatski algoritam.</p>
	</div>
	<?php
}

/**
 * Sačuvaj meta.
 */
function kompas_save_related_posts_meta( $post_id ) {
	if ( ! isset( $_POST['kompas_related_posts_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['kompas_related_posts_nonce'], 'kompas_related_posts_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$ids = array();
	if ( ! empty( $_POST['kompas_related_post_ids'] ) ) {
		$ids = array_values( array_filter( array_map( 'absint', explode( ',', sanitize_text_field( $_POST['kompas_related_post_ids'] ) ) ) ) );
		$ids = array_slice( $ids, 0, 6 );
	}

	if ( empty( $ids ) ) {
		delete_post_meta( $post_id, 'kompas_related_post_ids' );
	} else {
		update_post_meta( $post_id, 'kompas_related_post_ids', $ids );
	}
}
add_action( 'save_post', 'kompas_save_related_posts_meta' );

/**
 * Enkjuuj JS za meta box samo na post edit strani.
 */
function kompas_enqueue_related_posts_meta_box_script( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->post_type ) {
		return;
	}
	$path = get_theme_file_path( 'assets/js/related-posts-meta-box.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}
	wp_enqueue_script(
		'kompas-related-posts-meta-box',
		get_theme_file_uri( 'assets/js/related-posts-meta-box.js' ),
		array(),
		$ver,
		true
	);
	wp_localize_script( 'kompas-related-posts-meta-box', 'kompasRelatedData', array(
		'nonce' => wp_create_nonce( 'wp_rest' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'kompas_enqueue_related_posts_meta_box_script' );

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

	$cpt_author_id = (int) get_post_meta( $post_id, KOMPAS_AUTHOR_ID_META_KEY, true );
	if ( $cpt_author_id > 0 ) {
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
 * Replace avatar block image with CPT author photo when kompas_author_id is set.
 * Handles the core/avatar block used in single-kolumne.html.
 */
function kompas_replace_avatar_block_with_cpt_photo( $block_content, $block, $instance ) {
	$post_id = 0;
	if ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) {
		$post_id = (int) $instance->context['postId'];
	} elseif ( get_the_ID() ) {
		$post_id = (int) get_the_ID();
	}

	if ( $post_id <= 0 ) {
		return $block_content;
	}

	$cpt_id = (int) get_post_meta( $post_id, KOMPAS_AUTHOR_ID_META_KEY, true );
	if ( $cpt_id <= 0 ) {
		return $block_content;
	}

	$photo_url = get_the_post_thumbnail_url( $cpt_id, 'thumbnail' );
	if ( ! $photo_url ) {
		return $block_content;
	}

	$escaped_photo_url = esc_url( $photo_url );

	// Replace existing src whether avatar HTML uses single or double quotes.
	$updated = preg_replace( '/\bsrc=(["\']).*?\1/i', 'src="' . $escaped_photo_url . '"', $block_content, 1 );
	if ( $updated && $updated !== $block_content ) {
		// Remove srcset so browser does not keep gravatar candidates.
		$updated = preg_replace( '/\bsrcset=(["\']).*?\1/i', '', $updated, 1 );
		return $updated;
	}

	// Fallback: inject src directly into the first img tag.
	$updated = preg_replace( '/<img\b/i', '<img src="' . $escaped_photo_url . '" ', $block_content, 1 );
	return $updated ?: $block_content;
}
add_filter( 'render_block_core/avatar', 'kompas_replace_avatar_block_with_cpt_photo', 10, 3 );

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

	// Nikad ne dodaj caption za featured slike unutar query loop-a (npr. Povezane vesti).
	if ( $instance instanceof WP_Block && ! empty( $instance->context['queryId'] ) ) {
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
 * Add data-full attribute to the main featured image on single posts.
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

	if ( $post_id <= 0 || $post_id !== (int) get_queried_object_id() ) {
		return $block_content;
	}

	// Nikad ne dodaj data-full za featured slike unutar query loop-a (npr. Povezane vesti).
	if ( $instance instanceof WP_Block && ! empty( $instance->context['queryId'] ) ) {
		return $block_content;
	}

	$thumbnail_id = get_post_thumbnail_id( $post_id );
	if ( ! $thumbnail_id ) {
		return $block_content;
	}

	$full_url = wp_get_attachment_image_url( $thumbnail_id, 'full' );
	if ( ! $full_url ) {
		$full_url = wp_get_attachment_url( $thumbnail_id );
	}
	if ( ! $full_url ) {
		return $block_content;
	}

	if ( false === strpos( $block_content, ' data-full=' ) ) {
		$block_content = preg_replace(
			'/<img\s/i',
			'<img data-full="' . esc_attr( $full_url ) . '" ',
			$block_content,
			1
		);
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
 * ── Pomoćna funkcija za skraćivanje naslova ──────────────────
 */

/**
 * Truncate a title to N chars + ellipsis (e.g. 60 + "...").
 *
 * @param string $title  The post title.
 * @param int    $length Max visible chars before ellipsis (default 60).
 * @return string
 */
function kompas_truncate_title( $title, $length = 60 ) {
	$title  = (string) $title;
	$length = (int) $length;

	if ( $length <= 0 ) {
		return '';
	}

	if ( mb_strlen( $title ) <= $length ) {
		return $title;
	}

	return mb_substr( $title, 0, $length );
}

/**
 * Transliterate Serbian Cyrillic text to Latin (ASCII-friendly for slugs).
 *
 * @param string $text Input text.
 * @return string
 */
function kompas_cyrillic_to_latin_for_slug( $text ) {
	static $map = array(
		'А' => 'A',  'Б' => 'B',  'В' => 'V',  'Г' => 'G',  'Д' => 'D',
		'Ђ' => 'Dj', 'Е' => 'E',  'Ж' => 'Z',  'З' => 'Z',  'И' => 'I',
		'Ј' => 'J',  'К' => 'K',  'Л' => 'L',  'Љ' => 'Lj', 'М' => 'M',
		'Н' => 'N',  'Њ' => 'Nj', 'О' => 'O',  'П' => 'P',  'Р' => 'R',
		'С' => 'S',  'Т' => 'T',  'Ћ' => 'C',  'У' => 'U',  'Ф' => 'F',
		'Х' => 'H',  'Ц' => 'C',  'Ч' => 'C',  'Џ' => 'Dz', 'Ш' => 'S',
		'а' => 'a',  'б' => 'b',  'в' => 'v',  'г' => 'g',  'д' => 'd',
		'ђ' => 'dj', 'е' => 'e',  'ж' => 'z',  'з' => 'z',  'и' => 'i',
		'ј' => 'j',  'к' => 'k',  'л' => 'l',  'љ' => 'lj', 'м' => 'm',
		'н' => 'n',  'њ' => 'nj', 'о' => 'o',  'п' => 'p',  'р' => 'r',
		'с' => 's',  'т' => 't',  'ћ' => 'c',  'у' => 'u',  'ф' => 'f',
		'х' => 'h',  'ц' => 'c',  'ч' => 'c',  'џ' => 'dz', 'ш' => 's',
	);

	return strtr( (string) $text, $map );
}

/**
 * Convert Latin script to Cyrillic (Serbian).
 */
function kompas_latin_to_cyrillic( $text ) {
	static $map = null;
	if ( null === $map ) {
		$map = array(
			// Digraphs — moraju biti pre jednoslovnih da bi strtr dao prioritet dužim ključevima.
			'Lj' => 'Љ', 'LJ' => 'Љ', 'lj' => 'љ',
			'Nj' => 'Њ', 'NJ' => 'Њ', 'nj' => 'њ',
			'Dž' => 'Џ', 'DŽ' => 'Џ', 'dž' => 'џ',
			// Velika slova.
			'A'=>'А','B'=>'Б','V'=>'В','G'=>'Г','D'=>'Д','Đ'=>'Ђ','E'=>'Е',
			'Ž'=>'Ж','Z'=>'З','I'=>'И','J'=>'Ј','K'=>'К','L'=>'Л','M'=>'М',
			'N'=>'Н','O'=>'О','P'=>'П','R'=>'Р','S'=>'С','T'=>'Т','Ć'=>'Ћ',
			'U'=>'У','F'=>'Ф','H'=>'Х','C'=>'Ц','Č'=>'Ч','Š'=>'Ш',
			// Mala slova.
			'a'=>'а','b'=>'б','v'=>'в','g'=>'г','d'=>'д','đ'=>'ђ','e'=>'е',
			'ž'=>'ж','z'=>'з','i'=>'и','j'=>'ј','k'=>'к','l'=>'л','m'=>'м',
			'n'=>'н','o'=>'о','p'=>'п','r'=>'р','s'=>'с','t'=>'т','ć'=>'ћ',
			'u'=>'у','f'=>'ф','h'=>'х','c'=>'ц','č'=>'ч','š'=>'ш',
		);
	}
	return strtr( (string) $text, $map );
}

/**
 * Transliterate search query from Latin to Cyrillic before WP queries the DB.
 */
function kompas_search_transliterate( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}
	$s = (string) $query->get( 's' );
	if ( '' === $s || preg_match( '/[\x{0400}-\x{04FF}]/u', $s ) ) {
		return;
	}

	$query->set( 'kompas_search_latin_raw', $s );
	$query->set( 's', kompas_latin_to_cyrillic( $s ) );
}
add_action( 'pre_get_posts', 'kompas_search_transliterate' );

/**
 * Parse raw search input into terms (supports quoted phrases).
 *
 * @param string $search Raw search string.
 * @return string[]
 */
function kompas_search_extract_terms( $search ) {
	$search = trim( (string) $search );
	if ( '' === $search ) {
		return array();
	}

	$terms = array();

	if ( preg_match_all( '/"([^"]+)"|(\S+)/u', $search, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			$term = '';
			if ( isset( $match[1] ) && '' !== $match[1] ) {
				$term = $match[1];
			} elseif ( isset( $match[2] ) ) {
				$term = $match[2];
			}

			$term = trim( (string) $term );
			if ( '' !== $term ) {
				$terms[] = $term;
			}
		}
	}

	if ( empty( $terms ) ) {
		$terms[] = $search;
	}

	return array_slice( array_values( array_unique( $terms ) ), 0, 6 );
}

/**
 * Build Cyrillic variants for a Latin search term.
 * Examples: c -> ц/ч/ћ, s -> с/ш, z -> з/ж, dj -> ђ/дј.
 *
 * @param string $term         Single search term.
 * @param int    $max_variants Upper limit to keep SQL compact.
 * @return string[]
 */
function kompas_latin_term_cyrillic_variants( $term, $max_variants = 36 ) {
	$term = mb_strtolower( trim( (string) $term ), 'UTF-8' );
	if ( '' === $term ) {
		return array();
	}

	$max_variants = max( 1, (int) $max_variants );

	$digraph_choices = array(
		'lj' => array( 'љ' ),
		'nj' => array( 'њ' ),
		'dž' => array( 'џ' ),
		'dj' => array( 'ђ', 'дј' ),
		'dz' => array( 'џ', 'дз' ),
	);

	$single_choices = array(
		'a' => array( 'а' ),  'b' => array( 'б' ),  'v' => array( 'в' ),  'g' => array( 'г' ),
		'd' => array( 'д' ),  'đ' => array( 'ђ' ),  'e' => array( 'е' ),  'ž' => array( 'ж' ),
		'z' => array( 'з', 'ж' ), 'i' => array( 'и' ),  'j' => array( 'ј' ),  'k' => array( 'к' ),
		'l' => array( 'л' ),  'm' => array( 'м' ),  'n' => array( 'н' ),  'o' => array( 'о' ),
		'p' => array( 'п' ),  'r' => array( 'р' ),  's' => array( 'с', 'ш' ), 't' => array( 'т' ),
		'ć' => array( 'ћ' ),  'u' => array( 'у' ),  'f' => array( 'ф' ),  'h' => array( 'х' ),
		'c' => array( 'ц', 'ч', 'ћ' ), 'č' => array( 'ч' ),  'š' => array( 'ш' ),  'q' => array( 'к' ),
		'w' => array( 'в' ),  'x' => array( 'кс' ), 'y' => array( 'ј' ),
	);

	$segments = array();
	$length   = mb_strlen( $term, 'UTF-8' );

	for ( $i = 0; $i < $length; $i++ ) {
		$pair = '';
		if ( $i + 1 < $length ) {
			$pair = mb_substr( $term, $i, 2, 'UTF-8' );
		}

		if ( '' !== $pair && isset( $digraph_choices[ $pair ] ) ) {
			$segments[] = $digraph_choices[ $pair ];
			$i++;
			continue;
		}

		$char = mb_substr( $term, $i, 1, 'UTF-8' );
		if ( isset( $single_choices[ $char ] ) ) {
			$segments[] = $single_choices[ $char ];
		} else {
			$segments[] = array( $char );
		}
	}

	$variants = array( '' );

	foreach ( $segments as $choices ) {
		$next = array();

		foreach ( $variants as $prefix ) {
			foreach ( $choices as $choice ) {
				$candidate         = $prefix . $choice;
				$next[ $candidate ] = true;

				if ( count( $next ) >= $max_variants ) {
					break 2;
				}
			}
		}

		$variants = array_keys( $next );
		if ( empty( $variants ) ) {
			break;
		}
	}

	if ( empty( $variants ) ) {
		return array( kompas_latin_to_cyrillic( $term ) );
	}

	$exact = kompas_latin_to_cyrillic( $term );
	if ( ! in_array( $exact, $variants, true ) ) {
		array_unshift( $variants, $exact );
	}

	return array_values( array_slice( array_unique( $variants ), 0, $max_variants ) );
}

/**
 * Expand main search query with Cyrillic variants for Latin input.
 *
 * @param string   $search Existing SQL search clause.
 * @param WP_Query $query  Current query object.
 * @return string
 */
function kompas_search_expand_latin_variants( $search, $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return $search;
	}

	$raw = (string) $query->get( 'kompas_search_latin_raw' );
	if ( '' === $raw ) {
		return $search;
	}

	$terms = kompas_search_extract_terms( $raw );
	if ( empty( $terms ) ) {
		return $search;
	}

	global $wpdb;

	$term_clauses = array();

	foreach ( $terms as $term ) {
		$is_exclusion = '-' === substr( $term, 0, 1 );
		if ( $is_exclusion ) {
			$term = ltrim( $term, '-' );
		}

		$term = trim( $term );
		if ( '' === $term ) {
			continue;
		}

		$variants = kompas_latin_term_cyrillic_variants( $term, 36 );
		if ( empty( $variants ) ) {
			continue;
		}

		$variant_clauses = array();

		foreach ( $variants as $variant ) {
			$like = '%' . $wpdb->esc_like( $variant ) . '%';
			$variant_clauses[] = $wpdb->prepare(
				"({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR {$wpdb->posts}.post_content LIKE %s)",
				$like,
				$like,
				$like
			);
		}

		if ( empty( $variant_clauses ) ) {
			continue;
		}

		$joined_clause = '( ' . implode( ' OR ', $variant_clauses ) . ' )';
		$term_clauses[] = $is_exclusion ? 'NOT ' . $joined_clause : $joined_clause;
	}

	if ( empty( $term_clauses ) ) {
		return $search;
	}

	$search = ' AND (' . implode( ' AND ', $term_clauses ) . ')';

	if ( ! is_user_logged_in() ) {
		$search .= " AND ({$wpdb->posts}.post_password = '')";
	}

	return $search;
}
add_filter( 'posts_search', 'kompas_search_expand_latin_variants', 20, 2 );

/**
 * Force Latin slug generation even when title is written in Cyrillic.
 */
function kompas_force_latin_slug( $title, $raw_title, $context ) {
	if ( 'save' !== $context ) {
		return $title;
	}

	$source = '' !== (string) $raw_title ? (string) $raw_title : (string) $title;

	if ( preg_match( '/[\x{0400}-\x{04FF}]/u', $source ) ) {
		$latin = kompas_cyrillic_to_latin_for_slug( $source );
		return sanitize_title_with_dashes( remove_accents( $latin ), '', 'save' );
	}

	return $title;
}
add_filter( 'sanitize_title', 'kompas_force_latin_slug', 9, 3 );

/**
 * Flush rewrite rules once after slug/CPT permalink updates.
 */
function kompas_maybe_flush_rewrite_after_slug_fix() {
	$key    = 'kompas_rewrite_flushed_slug_fix_v2';
	$target = '1';

	if ( get_option( $key ) === $target ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( $key, $target, false );
}
add_action( 'admin_init', 'kompas_maybe_flush_rewrite_after_slug_fix' );

// Intentionally no global title mutation on save/output:
// full title must stay intact in backend, single post, and slug generation.

/**
 * Enqueue lightbox script on single posts.
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
 * Enqueue publish guard script in the block editor (posts only).
 */
function kompas_enqueue_publish_guard() {
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->post_type ) {
		return;
	}

	$path = get_theme_file_path( 'assets/js/publish-guard.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}

	wp_enqueue_script(
		'kompas-publish-guard',
		get_theme_file_uri( 'assets/js/publish-guard.js' ),
		array( 'wp-data', 'wp-notices', 'wp-edit-post' ),
		$ver,
		true
	);

	wp_localize_script(
		'kompas-publish-guard',
		'kompasPublishGuard',
		array(
			'uncategorizedId' => (int) get_option( 'default_category', 1 ),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'kompas_enqueue_publish_guard' );

/**
 * Enqueue hard title limit script on post edit screens (classic + block).
 */
function kompas_enqueue_admin_title_limit( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || ! post_type_supports( $screen->post_type, 'title' ) ) {
		return;
	}

	$path = get_theme_file_path( 'assets/js/admin-title-limit.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}

	wp_enqueue_script(
		'kompas-admin-title-limit',
		get_theme_file_uri( 'assets/js/admin-title-limit.js' ),
		array(),
		$ver,
		true
	);

	wp_localize_script(
		'kompas-admin-title-limit',
		'kompasAdminTitleLimit',
		array(
			'max' => 60,
		)
	);
}
add_action( 'admin_enqueue_scripts', 'kompas_enqueue_admin_title_limit' );

/**
 * Build publish-guard issues for required post fields.
 */
function kompas_get_publish_requirements_issues( $title, $featured_image_id, $categories ) {
	$uncategorized_id = (int) get_option( 'default_category', 1 );
	$title            = wp_strip_all_tags( (string) $title );
	$title_len        = function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title );
	$categories       = array_values( array_unique( array_map( 'absint', (array) $categories ) ) );
	$real_categories  = array_values(
		array_filter(
			$categories,
			static function ( $cat_id ) use ( $uncategorized_id ) {
				return $cat_id > 0 && $cat_id !== $uncategorized_id;
			}
		)
	);

	$issues = array();

	if ( absint( $featured_image_id ) <= 0 ) {
		$issues[] = 'naslovna slika nije postavljena';
	}

	if ( $title_len > 60 ) {
		$issues[] = 'naslov je duži od 60 karaktera';
	}

	if ( 0 === count( $real_categories ) ) {
		$issues[] = 'kategorija nije izabrana';
	}

	return $issues;
}

/**
 * Save a one-time admin notice for failed publish requirements.
 */
function kompas_set_publish_requirements_notice( $issues ) {
	if ( empty( $issues ) || ! is_user_logged_in() ) {
		return;
	}

	$message = 'Vest ne može biti objavljena: ' . implode( ', ', $issues ) . '.';
	set_transient( 'kompas_publish_requirements_notice_' . get_current_user_id(), $message, 60 );
}

/**
 * Render admin notice when publish requirements fail in classic save flow.
 */
function kompas_render_publish_requirements_notice() {
	if ( ! is_admin() || ! is_user_logged_in() ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->post_type ) {
		return;
	}

	$key     = 'kompas_publish_requirements_notice_' . get_current_user_id();
	$message = get_transient( $key );
	if ( ! $message ) {
		return;
	}

	delete_transient( $key );
	echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
}
add_action( 'admin_notices', 'kompas_render_publish_requirements_notice' );

/**
 * Hard server-side guard for classic post save/publish flow.
 *
 * If conditions are not met, force status back to draft.
 */
function kompas_enforce_publish_requirements_on_insert( $data, $postarr ) {
	if ( empty( $data['post_type'] ) || 'post' !== $data['post_type'] ) {
		return $data;
	}

	if ( empty( $data['post_status'] ) || ! in_array( $data['post_status'], array( 'publish', 'future' ), true ) ) {
		return $data;
	}

	$title = isset( $data['post_title'] ) ? $data['post_title'] : '';

	$featured_image_id = 0;
	if ( isset( $postarr['_thumbnail_id'] ) ) {
		$featured_image_id = absint( $postarr['_thumbnail_id'] );
	} elseif ( isset( $_POST['_thumbnail_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validation-only read.
		$featured_image_id = absint( wp_unslash( $_POST['_thumbnail_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validation-only read.
	} elseif ( ! empty( $postarr['ID'] ) ) {
		$featured_image_id = (int) get_post_thumbnail_id( (int) $postarr['ID'] );
	}

	$categories = array();
	if ( isset( $postarr['post_category'] ) && is_array( $postarr['post_category'] ) ) {
		$categories = $postarr['post_category'];
	} elseif ( isset( $_POST['post_category'] ) && is_array( $_POST['post_category'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validation-only read.
		$categories = wp_unslash( $_POST['post_category'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- validation-only read.
	} elseif ( ! empty( $postarr['ID'] ) ) {
		$categories = wp_get_post_categories( (int) $postarr['ID'], array( 'fields' => 'ids' ) );
	}

	$issues = kompas_get_publish_requirements_issues( $title, $featured_image_id, $categories );
	if ( empty( $issues ) ) {
		return $data;
	}

	$data['post_status'] = 'draft';
	kompas_set_publish_requirements_notice( $issues );
	return $data;
}
add_filter( 'wp_insert_post_data', 'kompas_enforce_publish_requirements_on_insert', 20, 2 );

/**
 * Hard server-side guard for block editor REST publish/schedule flow.
 */
function kompas_enforce_publish_requirements_on_rest( $prepared_post, $request ) {
	if ( ! is_object( $prepared_post ) || empty( $prepared_post->post_type ) || 'post' !== $prepared_post->post_type ) {
		return $prepared_post;
	}

	$status = $request->has_param( 'status' ) ? (string) $request->get_param( 'status' ) : (string) $prepared_post->post_status;
	if ( ! in_array( $status, array( 'publish', 'future' ), true ) ) {
		return $prepared_post;
	}

	$title_param = $request->get_param( 'title' );
	$title       = $prepared_post->post_title;
	if ( is_array( $title_param ) && isset( $title_param['raw'] ) ) {
		$title = (string) $title_param['raw'];
	} elseif ( is_string( $title_param ) ) {
		$title = $title_param;
	}

	$featured_image_id = 0;
	if ( $request->has_param( 'featured_media' ) ) {
		$featured_image_id = absint( $request->get_param( 'featured_media' ) );
	} elseif ( ! empty( $prepared_post->ID ) ) {
		$featured_image_id = (int) get_post_thumbnail_id( (int) $prepared_post->ID );
	}

	$categories = array();
	if ( $request->has_param( 'categories' ) ) {
		$categories = (array) $request->get_param( 'categories' );
	} elseif ( ! empty( $prepared_post->ID ) ) {
		$categories = wp_get_post_categories( (int) $prepared_post->ID, array( 'fields' => 'ids' ) );
	}

	$issues = kompas_get_publish_requirements_issues( $title, $featured_image_id, $categories );
	if ( empty( $issues ) ) {
		return $prepared_post;
	}

	return new WP_Error(
		'kompas_publish_requirements_failed',
		'Vest ne može biti objavljena: ' . implode( ', ', $issues ) . '.',
		array( 'status' => 400 )
	);
}
add_filter( 'rest_pre_insert_post', 'kompas_enforce_publish_requirements_on_rest', 20, 2 );

/**
 * Dodaj "Izvor fotografije" polje u media editor.
 */
function kompas_attachment_source_field( $form_fields, $post ) {
	$form_fields['kompas_image_source'] = array(
		'label' => 'Izvor fotografije',
		'input' => 'text',
		'value' => get_post_meta( $post->ID, 'kompas_image_source', true ),
		'helps' => 'Autor ili izvor fotografije (npr. "Foto: Petar Petrović / Tanjug")',
	);
	return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'kompas_attachment_source_field', 10, 2 );

function kompas_save_attachment_source( $post, $attachment ) {
	if ( isset( $attachment['kompas_image_source'] ) ) {
		update_post_meta(
			$post['ID'],
			'kompas_image_source',
			sanitize_text_field( $attachment['kompas_image_source'] )
		);
	}
	return $post;
}
add_filter( 'attachment_fields_to_save', 'kompas_save_attachment_source', 10, 2 );

/**
 * Inject image source overlay directly on the first image in block HTML.
 *
 * Keeps source attached to the image area (not the figure/caption flow) and
 * avoids duplicate insertion when markup is already processed.
 */
function kompas_inject_image_source_overlay( $block_content, $source ) {
	$source = trim( (string) $source );
	if ( '' === $source ) {
		return $block_content;
	}

	// Idempotency: do not inject twice.
	if ( false !== strpos( $block_content, 'kompas-image-frame' ) || false !== strpos( $block_content, 'kompas-image-source' ) ) {
		return $block_content;
	}

	$source_html = '<span class="kompas-image-source"><span class="kompas-image-source-label">ФОТО:</span> ' . esc_html( $source ) . '</span>';
	$updated     = preg_replace_callback(
		'/<img\b[^>]*>/i',
		static function ( $matches ) use ( $source_html ) {
			return '<span class="kompas-image-frame">' . $matches[0] . $source_html . '</span>';
		},
		$block_content,
		1
	);

	return $updated ?: $block_content;
}

/**
 * Prikaži izvor fotografije ispod featured image na single postovima.
 */
function kompas_featured_image_source( $block_content, $block, $instance ) {
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

	if ( $instance instanceof WP_Block && ! empty( $instance->context['queryId'] ) ) {
		return $block_content;
	}

	if ( $post_id !== (int) get_queried_object_id() ) {
		return $block_content;
	}

	$thumbnail_id = get_post_thumbnail_id( $post_id );
	if ( ! $thumbnail_id ) {
		return $block_content;
	}

	$source = get_post_meta( $thumbnail_id, 'kompas_image_source', true );
	if ( ! $source ) {
		return $block_content;
	}

	return kompas_inject_image_source_overlay( $block_content, $source );
}
add_filter( 'render_block_core/post-featured-image', 'kompas_featured_image_source', 11, 3 );

/**
 * Prikaži izvor fotografije ispod inline slika u sadržaju single posta.
 */
function kompas_content_image_source( $block_content, $block ) {
	if ( ! is_singular( 'post' ) ) {
		return $block_content;
	}

	$attachment_id = isset( $block['attrs']['id'] ) ? (int) $block['attrs']['id'] : 0;
	if ( ! $attachment_id ) {
		return $block_content;
	}

	$source = get_post_meta( $attachment_id, 'kompas_image_source', true );
	if ( ! $source ) {
		return $block_content;
	}

	return kompas_inject_image_source_overlay( $block_content, $source );
}
add_filter( 'render_block_core/image', 'kompas_content_image_source', 10, 2 );

/**
 * ── Kompas Video CPT ─────────────────────────────────────────
 */

/**
 * Register kompas_video custom post type.
 */
function kompas_register_video_cpt() {
	register_post_type( 'kompas_video', array(
		'labels'       => array(
			'name'         => 'Видео',
			'singular_name' => 'Видео',
			'add_new_item'  => 'Додај видео',
			'edit_item'     => 'Измени видео',
			'new_item'      => 'Нови видео',
			'view_item'     => 'Погледај видео',
			'search_items'  => 'Претражи видео',
			'not_found'     => 'Нема видео записа.',
		),
		'public'       => true,
		'has_archive'  => true,
		'supports'     => array( 'title', 'thumbnail', 'excerpt', 'editor' ),
		'rewrite'      => array( 'slug' => 'kompas-video' ),
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-video-alt3',
	) );

	register_post_meta( 'kompas_video', 'kompas_video_url', array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'esc_url_raw',
	) );
}
add_action( 'init', 'kompas_register_video_cpt' );

/**
 * Add meta box for video file on kompas_video edit screen.
 */
function kompas_video_meta_box_init() {
	add_meta_box(
		'kompas_video_url',
		'YouTube линк',
		'kompas_video_meta_box_render',
		'kompas_video',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'kompas_video_meta_box_init' );

function kompas_video_meta_box_render( $post ) {
	wp_nonce_field( 'kompas_video_save', 'kompas_video_nonce' );
	$video_url = esc_attr( get_post_meta( $post->ID, 'kompas_video_url', true ) );
	$yt_id     = kompas_extract_youtube_id( $video_url );
	?>
	<p>
		<label for="kompas-video-url" style="display:block;margin-bottom:6px;font-weight:600;">
			Налепи YouTube линк:
		</label>
		<input
			type="url"
			id="kompas-video-url"
			name="kompas_video_url"
			value="<?php echo $video_url; ?>"
			placeholder="https://www.youtube.com/watch?v=..."
			style="width:100%;"
		/>
	</p>
	<?php if ( $yt_id ) : ?>
	<p>
		<img
			src="https://img.youtube.com/vi/<?php echo esc_attr( $yt_id ); ?>/hqdefault.jpg"
			alt="YouTube thumbnail"
			style="max-width:320px;border-radius:3px;"
		/>
	</p>
	<?php endif; ?>
	<?php
}

/**
 * Save video attachment ID.
 */
function kompas_video_meta_box_save( $post_id ) {
	if ( ! isset( $_POST['kompas_video_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kompas_video_nonce'] ) ), 'kompas_video_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['kompas_video_url'] ) ) {
		$url = esc_url_raw( wp_unslash( $_POST['kompas_video_url'] ) );
		update_post_meta( $post_id, 'kompas_video_url', $url );
	}
}
add_action( 'save_post_kompas_video', 'kompas_video_meta_box_save' );

/**
 * Extract YouTube video ID from any YouTube URL format.
 *
 * Handles:
 *   https://www.youtube.com/watch?v=VIDEO_ID
 *   https://youtu.be/VIDEO_ID
 *   https://www.youtube.com/embed/VIDEO_ID
 */
function kompas_extract_youtube_id( $url ) {
	if ( empty( $url ) ) {
		return '';
	}
	if ( preg_match( '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * Register kompas/video and kompas/video-grid blocks.
 */
function kompas_register_video_blocks() {
	register_block_type( get_theme_file_path( 'blocks/kompas-video' ) );
	register_block_type( get_theme_file_path( 'blocks/video-grid' ) );
}
add_action( 'init', 'kompas_register_video_blocks' );

/**
 * Enqueue video lightbox script on front page and video archive.
 */
function kompas_enqueue_video_lightbox() {
	if ( ! is_front_page() && ! is_post_type_archive( 'kompas_video' ) ) {
		return;
	}
	$path = get_theme_file_path( 'assets/js/video-lightbox.js' );
	$ver  = KOMPAS_VERSION;
	if ( file_exists( $path ) ) {
		$ver .= '.' . (string) filemtime( $path );
	}
	wp_enqueue_script(
		'kompas-video-lightbox',
		get_theme_file_uri( 'assets/js/video-lightbox.js' ),
		array(),
		$ver,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kompas_enqueue_video_lightbox' );

/**
 * Register kompas/logo-autori block.
 */
function kompas_register_logo_autori_block() {
	register_block_type(
		'kompas/logo-autori',
		array(
			'render_callback' => function() {
				$home_url = esc_url( home_url( '/' ) );
				$logo_url = esc_url( get_theme_file_uri( 'logo-autori.svg' ) );

				return '<div style="text-align:center;padding:var(--wp--preset--spacing--60) 0">'
					 . '<a href="' . $home_url . '" style="display:inline-block">'
					 . '<img src="' . $logo_url . '" alt="Kompas Autori" style="max-height:60px;width:auto;display:inline-block" />'
					 . '</a>'
					 . '</div>';
			},
		)
	);
}
add_action( 'init', 'kompas_register_logo_autori_block' );

// ─────────────────────────────────────────────────────────────────────────────
// ── Kompas Homepage Settings stranica ────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Registruj admin menu stranicu.
 */
function kompas_register_homepage_settings_page() {
	add_menu_page(
		'Kompas naslovna',
		'Kompas naslovna',
		'edit_posts',
		'kompas-homepage-settings',
		'kompas_render_homepage_settings_page',
		'dashicons-grid-view',
		30
	);
}
add_action( 'admin_menu', 'kompas_register_homepage_settings_page' );

/**
 * Enkjuuj JS samo na settings strani.
 */
function kompas_enqueue_homepage_settings_assets( $hook ) {
	if ( $hook !== 'toplevel_page_kompas-homepage-settings' ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script(
		'kompas-homepage-settings',
		get_theme_file_uri( 'assets/js/homepage-settings.js' ),
		array( 'jquery', 'jquery-ui-sortable' ),
		'1.0',
		true
	);
	wp_localize_script( 'kompas-homepage-settings', 'kompasSettings', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'kompas_search_posts' ),
	) );
	wp_add_inline_style( 'wp-admin', kompas_homepage_settings_inline_css() );
}
add_action( 'admin_enqueue_scripts', 'kompas_enqueue_homepage_settings_assets' );

/**
 * Inline CSS za settings stranicu.
 */
function kompas_homepage_settings_inline_css() {
	return '
.kompas-settings-section { background:#fff; border:1px solid #ddd; border-radius:4px; padding:20px; margin-bottom:24px; }
.kompas-settings-section h2 { margin-top:0; font-size:1.1rem; border-bottom:2px solid #e82d34; padding-bottom:8px; margin-bottom:16px; }
.kompas-post-search-wrap { position:relative; margin-bottom:12px; }
.kompas-post-search-wrap input[type=text] { width:100%; max-width:400px; }
.kompas-autocomplete-list { position:absolute; z-index:999; background:#fff; border:1px solid #ccc; max-height:220px; overflow-y:auto; width:400px; list-style:none; margin:0; padding:0; }
.kompas-autocomplete-list li { padding:8px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; font-size:0.9rem; }
.kompas-autocomplete-list li:hover { background:#f0f0f0; }
.kompas-selected-list { list-style:none; margin:0; padding:0; }
.kompas-selected-list li { display:flex; align-items:center; gap:10px; padding:6px 10px; background:#f9f9f9; border:1px solid #e0e0e0; border-radius:3px; margin-bottom:6px; cursor:grab; }
.kompas-selected-list li:active { cursor:grabbing; }
.kompas-selected-list .kompas-drag-handle { color:#aaa; font-size:1.1rem; }
.kompas-selected-list .kompas-remove { margin-left:auto; color:#cc0000; cursor:pointer; font-size:1.1rem; line-height:1; background:none; border:none; padding:0; }
.kompas-catgrid-cat-block { border:1px solid #e0e0e0; border-radius:4px; padding:14px; margin-bottom:12px; background:#fafafa; }
.kompas-catgrid-cat-block h4 { margin:0 0 10px; font-size:0.95rem; color:#e82d34; }
';
}

/**
 * AJAX handler: pretraži postove.
 */
function kompas_ajax_search_posts() {
	check_ajax_referer( 'kompas_search_posts', 'nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( -1 );
	}
	$query = sanitize_text_field( wp_unslash( isset( $_GET['q'] ) ? $_GET['q'] : '' ) );
	if ( mb_strlen( $query ) < 2 ) {
		wp_send_json_success( array() );
	}
	$posts = get_posts( array(
		's'              => $query,
		'posts_per_page' => 10,
		'post_status'    => 'publish',
	) );
	$results = array_map( function( $p ) {
		return array( 'id' => $p->ID, 'title' => get_the_title( $p ) );
	}, $posts );
	wp_send_json_success( $results );
}
add_action( 'wp_ajax_kompas_search_posts', 'kompas_ajax_search_posts' );

/**
 * Sačuvaj settings (admin_post hook).
 */
function kompas_save_homepage_settings() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	check_admin_referer( 'kompas_homepage_settings_save' );

	// Hero IDs
	$hero_ids = array();
	if ( ! empty( $_POST['kompas_hero_post_ids'] ) && is_array( $_POST['kompas_hero_post_ids'] ) ) {
		$hero_ids = array_values( array_filter( array_map( 'absint', $_POST['kompas_hero_post_ids'] ) ) );
	}
	update_option( 'kompas_hero_post_ids', array_slice( $hero_ids, 0, 6 ) );

	// Reč urednika
	$rec_id = isset( $_POST['kompas_rec_urednika_post_id'] ) ? absint( $_POST['kompas_rec_urednika_post_id'] ) : 0;
	update_option( 'kompas_rec_urednika_post_id', $rec_id );
	$rec_image = isset( $_POST['kompas_rec_urednika_image_url'] ) ? esc_url_raw( wp_unslash( $_POST['kompas_rec_urednika_image_url'] ) ) : '';
	update_option( 'kompas_rec_urednika_image_url', $rec_image );

	// Kolumne IDs
	$kolumne_ids = array();
	if ( ! empty( $_POST['kompas_kolumne_post_ids'] ) && is_array( $_POST['kompas_kolumne_post_ids'] ) ) {
		$kolumne_ids = array_values( array_filter( array_map( 'absint', $_POST['kompas_kolumne_post_ids'] ) ) );
	}
	update_option( 'kompas_kolumne_post_ids', array_slice( $kolumne_ids, 0, 3 ) );

	// Category grid settings
	$catgrid = array( 'selected_ids' => array(), 'posts_by_category' => array() );
	if ( ! empty( $_POST['kompas_catgrid_selected_ids'] ) && is_array( $_POST['kompas_catgrid_selected_ids'] ) ) {
		$catgrid['selected_ids'] = array_values( array_filter( array_map( 'absint', $_POST['kompas_catgrid_selected_ids'] ) ) );
	}
	if ( ! empty( $_POST['kompas_catgrid_posts'] ) && is_array( $_POST['kompas_catgrid_posts'] ) ) {
		foreach ( $_POST['kompas_catgrid_posts'] as $cat_id => $pids ) {
			$cat_id = absint( $cat_id );
			if ( $cat_id > 0 && is_array( $pids ) ) {
				$catgrid['posts_by_category'][ $cat_id ] = array_values( array_filter( array_map( 'absint', $pids ) ) );
			}
		}
	}
	update_option( 'kompas_category_grid_settings', $catgrid );

	wp_redirect( admin_url( 'admin.php?page=kompas-homepage-settings&saved=1' ) );
	exit;
}
add_action( 'admin_post_kompas_save_homepage_settings', 'kompas_save_homepage_settings' );

/**
 * Prikaži settings stranicu.
 */
function kompas_render_homepage_settings_page() {
	$saved = isset( $_GET['saved'] );

	$hero_ids        = (array) get_option( 'kompas_hero_post_ids', array() );
	$rec_id          = (int) get_option( 'kompas_rec_urednika_post_id', 0 );
	$rec_image_url   = (string) get_option( 'kompas_rec_urednika_image_url', '' );
	$kolumne_ids     = (array) get_option( 'kompas_kolumne_post_ids', array() );
	$catgrid         = (array) get_option( 'kompas_category_grid_settings', array( 'selected_ids' => array(), 'posts_by_category' => array() ) );
	$catgrid_selected = ! empty( $catgrid['selected_ids'] ) ? (array) $catgrid['selected_ids'] : array();
	$catgrid_posts    = ! empty( $catgrid['posts_by_category'] ) ? (array) $catgrid['posts_by_category'] : array();
	?>
	<div class="wrap">
		<h1>Kompas naslovna – podešavanja</h1>
		<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible"><p>Podešavanja sačuvana.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'kompas_homepage_settings_save' ); ?>
			<input type="hidden" name="action" value="kompas_save_homepage_settings" />

			<!-- HERO -->
			<div class="kompas-settings-section">
				<h2>Hero sekcija (6 postova)</h2>
				<?php kompas_settings_post_list( 'kompas_hero_post_ids', $hero_ids, 6, 'hero' ); ?>
			</div>

			<!-- REČ UREDNIKA -->
			<div class="kompas-settings-section">
				<h2>Reč urednika (1 post)</h2>
				<?php kompas_settings_post_list( 'kompas_rec_urednika_post_id', $rec_id ? array( $rec_id ) : array(), 1, 'rec' ); ?>
				<div style="margin-top:16px">
					<p style="font-weight:600;margin-bottom:8px">Slika uz Reč urednika</p>
					<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
						<button type="button" id="kompas-rec-image-btn" class="button">
							<?php echo $rec_image_url ? 'Promeni sliku' : 'Odaberi sliku'; ?>
						</button>
						<?php if ( $rec_image_url ) : ?>
						<button type="button" id="kompas-rec-image-remove" class="button" style="color:#cc0000">Ukloni sliku</button>
						<?php else : ?>
						<button type="button" id="kompas-rec-image-remove" class="button" style="color:#cc0000;display:none">Ukloni sliku</button>
						<?php endif; ?>
					</div>
					<div id="kompas-rec-image-preview" style="margin-top:10px<?php echo $rec_image_url ? '' : ';display:none'; ?>">
						<img src="<?php echo esc_url( $rec_image_url ); ?>" style="max-width:300px;height:auto;display:block;border:1px solid #ddd;border-radius:3px" />
					</div>
					<input type="hidden" name="kompas_rec_urednika_image_url" id="kompas-rec-image-url" value="<?php echo esc_attr( $rec_image_url ); ?>" />
				</div>
			</div>

			<!-- KOLUMNE -->
			<div class="kompas-settings-section">
				<h2>Kolumne (3 posta)</h2>
				<?php kompas_settings_post_list( 'kompas_kolumne_post_ids', $kolumne_ids, 3, 'kolumne' ); ?>
			</div>

			<!-- CATEGORY GRID -->
			<div class="kompas-settings-section">
				<h2>Category Grid – kategorije i postovi</h2>
				<p style="color:#666;margin-bottom:12px">Odaberi kategorije (redosled određuje redosled prikaza). Za svaku kategoriju možeš ručno da odabereš postove.</p>

				<div class="kompas-post-search-wrap" id="catgrid-cat-search-wrap">
					<input type="text" id="catgrid-cat-search" placeholder="Pretraži kategorije..." autocomplete="off" style="max-width:400px;width:100%" />
					<ul class="kompas-autocomplete-list" id="catgrid-cat-autocomplete" style="display:none"></ul>
				</div>

				<div id="catgrid-cat-blocks">
				<?php foreach ( $catgrid_selected as $cid ) :
					$cat = get_category( $cid );
					if ( ! $cat || is_wp_error( $cat ) ) { continue; }
					$pids = isset( $catgrid_posts[ $cid ] ) ? (array) $catgrid_posts[ $cid ] : array();
				?>
					<div class="kompas-catgrid-entry" data-cat-id="<?php echo esc_attr( $cid ); ?>">
						<input type="hidden" name="kompas_catgrid_selected_ids[]" value="<?php echo esc_attr( $cid ); ?>" />
						<div class="kompas-catgrid-cat-block">
							<h4><?php echo esc_html( $cat->name ); ?> <button type="button" class="kompas-remove-cat button button-small" style="float:right;color:#cc0000">Ukloni kategoriju</button></h4>
							<?php kompas_settings_post_list( 'kompas_catgrid_posts[' . $cid . ']', $pids, 6, 'catgrid_' . $cid ); ?>
						</div>
					</div>
				<?php endforeach; ?>
				</div>
			</div>

			<?php submit_button( 'Sačuvaj podešavanja', 'primary large' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Helper: prikaži listu postova sa searchom i sortable (bez inline JS).
 */
function kompas_settings_post_list( $field_name, $selected, $max, $uid ) {
	$list_id    = 'kompas-list-' . $uid;
	$auto_id    = 'kompas-auto-' . $uid;
	$is_single  = ( $max === 1 );
	$input_name = ( $is_single || strpos( $field_name, '[]' ) !== false ) ? $field_name : $field_name . '[]';
	?>
	<div class="kompas-post-search-wrap">
		<input type="text"
			   class="kompas-post-search"
			   data-list="#<?php echo esc_attr( $list_id ); ?>"
			   data-auto="#<?php echo esc_attr( $auto_id ); ?>"
			   placeholder="Pretraži postove..." autocomplete="off"
			   style="max-width:400px;width:100%" />
		<ul class="kompas-autocomplete-list" id="<?php echo esc_attr( $auto_id ); ?>" style="display:none"></ul>
	</div>
	<ul class="kompas-selected-list" id="<?php echo esc_attr( $list_id ); ?>"
		data-max="<?php echo esc_attr( $max ); ?>"
		data-single="<?php echo $is_single ? '1' : '0'; ?>"
		data-input-name="<?php echo esc_attr( $input_name ); ?>">
		<?php foreach ( (array) $selected as $pid ) :
			if ( ! $pid ) { continue; }
			$title = get_the_title( $pid );
			if ( ! $title ) { continue; }
		?>
		<li data-id="<?php echo esc_attr( $pid ); ?>">
			<span class="kompas-drag-handle">&#9776;</span>
			<span><?php echo esc_html( $title ); ?></span>
			<button type="button" class="kompas-remove">&#x2715;</button>
			<input type="hidden" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $pid ); ?>" />
		</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * AJAX handler: pretraži kategorije.
 */
function kompas_ajax_search_categories() {
	check_ajax_referer( 'kompas_search_posts', 'nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( -1 );
	}
	$query = sanitize_text_field( wp_unslash( isset( $_GET['q'] ) ? $_GET['q'] : '' ) );
	$terms = get_terms( array(
		'taxonomy'   => 'category',
		'name__like' => $query,
		'number'     => 10,
		'hide_empty' => false,
	) );
	$results = array();
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$results[] = array( 'id' => $t->term_id, 'name' => $t->name );
		}
	}
	wp_send_json_success( $results );
}
add_action( 'wp_ajax_kompas_search_categories', 'kompas_ajax_search_categories' );
