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

	register_nav_menus( array(
		'kompas-header-nav'  => 'Главна навигација (категорије)',
		'kompas-header-tags' => 'Секундарна навигација (тагови)',
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
	$custom_author            = get_post_meta( $post->ID, KOMPAS_CUSTOM_AUTHOR_META_KEY, true );
	$author_link              = (bool) get_post_meta( $post->ID, KOMPAS_AUTHOR_LINK_META_KEY, true );
	$no_translate             = (bool) get_post_meta( $post->ID, KOMPAS_AUTHOR_NO_TRANSLATE_META_KEY, true );
	$title_no_translate_words = (string) get_post_meta( $post->ID, KOMPAS_TITLE_NO_TRANSLATE_WORDS_META_KEY, true );
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

	$najnovije_url  = ! empty( $attributes['najnovijeUrl'] )  ? esc_url( $attributes['najnovijeUrl'] )  : '';
	$najcitanije_url = ! empty( $attributes['najcitanijeUrl'] ) ? esc_url( $attributes['najcitanijeUrl'] ) : '';

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

	// Nav-link mode: URL-ovi su postavljeni → tab dugmad postaju linkovi, prikazuje se samo Najnovije panel.
	if ( $najnovije_url || $najcitanije_url ) {
		ob_start();
		$active_style   = 'font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding:0 0 var(--wp--preset--spacing--20);margin-right:var(--wp--preset--spacing--50);display:inline-block;border-bottom:3px solid var(--wp--preset--color--primary);color:var(--wp--preset--color--dark);text-decoration:none;font-family:inherit';
		$inactive_style = 'font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding:0 0 var(--wp--preset--spacing--20);margin-right:var(--wp--preset--spacing--50);display:inline-block;border-bottom:3px solid transparent;color:var(--wp--preset--color--muted);text-decoration:none;font-family:inherit';
		?>
		<div class="kompas-tabs-section" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">
			<div class="kompas-tabs-heading kompas-section-topline" style="margin-bottom:var(--wp--preset--spacing--50)">
				<div class="kompas-tabs-nav" style="display:flex;gap:0;border-bottom:1px solid var(--wp--preset--color--border)">
					<?php if ( $najnovije_url ) : ?>
					<a href="<?php echo $najnovije_url; ?>" style="<?php echo esc_attr( $active_style ); ?>">НАЈНОВИЈЕ</a>
					<?php else : ?>
					<span style="<?php echo esc_attr( $active_style ); ?>">НАЈНОВИЈЕ</span>
					<?php endif; ?>
					<?php if ( $najcitanije_url ) : ?>
					<a href="<?php echo $najcitanije_url; ?>" style="<?php echo esc_attr( $inactive_style ); ?>">НАЈЧИТАНИЈЕ</a>
					<?php else : ?>
					<span style="<?php echo esc_attr( $inactive_style ); ?>">НАЈЧИТАНИЈЕ</span>
					<?php endif; ?>
				</div>
			</div>
			<div class="kompas-tab-panel is-active" data-panel="najnovije">
				<?php echo kompas_render_posts_grid( $najnovije ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// Default: toggle tab ponašanje.
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
 * Render the "Povezane vesti" section as a reusable dynamic block.
 */
function kompas_render_related_posts_block( $attributes = array(), $exclude_id = 0 ) {
	$title         = isset( $attributes['title'] ) ? trim( (string) $attributes['title'] ) : 'ПОВЕЗАНЕ ВЕСТИ';
	$posts_to_show = isset( $attributes['postsToShow'] ) ? (int) $attributes['postsToShow'] : 4;
	$posts_to_show = max( 1, min( 12, $posts_to_show ) );
	$selected_ids  = ! empty( $attributes['selectedPostIds'] ) && is_array( $attributes['selectedPostIds'] )
		? array_values( array_filter( array_map( 'absint', $attributes['selectedPostIds'] ) ) )
		: array();

	$exclude_ids = array();
	if ( $exclude_id > 0 ) {
		$exclude_ids[] = $exclude_id;
	} elseif ( is_singular( 'post' ) ) {
		$qid = (int) get_queried_object_id();
		if ( $qid > 0 ) {
			$exclude_ids[] = $qid;
		}
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
		) );
	}

	// 2) Fill remaining slots with latest posts.
	$remaining = $posts_to_show - count( $posts );
	if ( $remaining > 0 ) {
		$posts = array_merge(
			$posts,
			get_posts( array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => $remaining,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post__not_in'   => array_unique( array_merge( $exclude_ids, wp_list_pluck( $posts, 'ID' ) ) ),
			) )
		);
	}

	if ( empty( $posts ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="wp-block-group alignwide" style="border-top-color:var(--wp--preset--color--border);border-top-width:1px;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">

		<?php if ( '' !== $title ) : ?>
		<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--primary);border-bottom-width:3px;margin-bottom:var(--wp--preset--spacing--50)">
			<h3 class="has-dark-color has-text-color" style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding-bottom:var(--wp--preset--spacing--20)">
				<?php echo esc_html( $title ); ?>
			</h3>
		</div>
		<?php endif; ?>

			<div class="wp-block-query kompas-related-posts-query">
				<ul class="wp-block-post-template" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:var(--wp--preset--spacing--40);margin:0;padding:0;list-style:none">
					<?php foreach ( $posts as $related_post ) : ?>
					<li class="wp-block-post">
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
						<h4 class="kompas-archive-title kompas-archive-title--sm"<?php echo kompas_get_post_title_no_translate_data_attr( $p->ID ); ?>>
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
	// If a WP menu is assigned to this location, use it (supports dropdowns).
	if ( has_nav_menu( 'kompas-header-nav' ) ) {
		ob_start();
		wp_nav_menu( array(
			'theme_location'  => 'kompas-header-nav',
			'container'       => 'nav',
			'container_class' => 'kompas-header-categories',
			'container_id'    => '',
			'menu_class'      => 'kompas-nav-menu',
			'fallback_cb'     => false,
			'depth'           => 2,
			'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
		) );
		return ob_get_clean();
	}

	// Fallback: render from selectedIds or all top-level categories.
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

	$items = array();
	foreach ( $cats as $cat ) {
		$children = get_categories( array(
			'hide_empty' => false,
			'parent'     => (int) $cat->term_id,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		$classes = array(
			'menu-item',
			'menu-item-type-taxonomy',
			'menu-item-object-category',
		);
		if ( ! empty( $children ) ) {
			$classes[] = 'menu-item-has-children';
		}

		$item  = '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$item .= '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">'
			. esc_html( mb_strtoupper( $cat->name ) ) . '</a>';

		if ( ! empty( $children ) ) {
			$item .= '<ul class="sub-menu">';
			foreach ( $children as $child ) {
				$item .= '<li class="menu-item menu-item-type-taxonomy menu-item-object-category">';
				$item .= '<a href="' . esc_url( get_category_link( $child->term_id ) ) . '">'
					. esc_html( mb_strtoupper( $child->name ) ) . '</a>';
				$item .= '</li>';
			}
			$item .= '</ul>';
		}

		$item   .= '</li>';
		$items[] = $item;
	}

	return '<nav class="kompas-header-categories" aria-label="Главна навигација">'
		. '<ul class="kompas-nav-menu">' . implode( '', $items ) . '</ul>'
		. '</nav>';
}

/**
 * Render the secondary header nav (tags).
 */
function kompas_render_header_tags( $attributes = array() ) {
	$has_explicit_selection = isset( $attributes['selectedIds'] ) && is_array( $attributes['selectedIds'] );
	$ids                    = $has_explicit_selection ? array_filter( array_map( 'absint', $attributes['selectedIds'] ) ) : array();

	// In block usage: if no tags are selected, don't render anything.
	if ( $has_explicit_selection && empty( $ids ) ) {
		return '';
	}

	// If selection is not set via block attrs and a WP menu is assigned, use menu.
	if ( ! $has_explicit_selection && has_nav_menu( 'kompas-header-tags' ) ) {
		ob_start();
		wp_nav_menu( array(
			'theme_location'  => 'kompas-header-tags',
			'container'       => 'nav',
			'container_class' => 'kompas-header-tags',
			'container_id'    => '',
			'menu_class'      => 'kompas-nav-menu',
			'fallback_cb'     => false,
			'depth'           => 1,
			'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
		) );
		return ob_get_clean();
	}

	$tags = array();
	foreach ( $ids as $id ) {
		$tag = get_tag( $id );
		if ( $tag && ! is_wp_error( $tag ) ) {
			$tags[] = $tag;
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

	return mb_substr( $title, 0, $length ) . '...';
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
 * Flush rewrite rules once after slug sanitization update (fixes tag archive 404).
 */
function kompas_maybe_flush_rewrite_after_slug_fix() {
	$key    = 'kompas_rewrite_flushed_slug_fix_v1';
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

	$source_html = '<p class="kompas-image-source">' . esc_html( $source ) . '</p>';
	return str_replace( '</figure>', $source_html . '</figure>', $block_content );
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

	$source_html = '<p class="kompas-image-source">' . esc_html( $source ) . '</p>';
	return str_replace( '</figure>', $source_html . '</figure>', $block_content );
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

	register_post_meta( 'kompas_video', 'kompas_video_attachment_id', array(
		'type'         => 'integer',
		'single'       => true,
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'kompas_register_video_cpt' );

/**
 * Add meta box for video file on kompas_video edit screen.
 */
function kompas_video_meta_box_init() {
	add_meta_box(
		'kompas_video_file',
		'Видео фајл',
		'kompas_video_meta_box_render',
		'kompas_video',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'kompas_video_meta_box_init' );

function kompas_video_meta_box_render( $post ) {
	wp_nonce_field( 'kompas_video_save', 'kompas_video_nonce' );
	$attachment_id = (int) get_post_meta( $post->ID, 'kompas_video_attachment_id', true );
	$filename      = $attachment_id ? basename( get_attached_file( $attachment_id ) ) : '';
	?>
	<p>
		<input type="hidden" id="kompas-video-attachment-id" name="kompas_video_attachment_id" value="<?php echo esc_attr( $attachment_id ); ?>" />
		<button type="button" class="button" id="kompas-video-select">Изабери видео</button>
		<span id="kompas-video-filename" style="margin-left:8px"><?php echo esc_html( $filename ); ?></span>
	</p>
	<script>
	( function() {
		var btn      = document.getElementById( 'kompas-video-select' );
		var input    = document.getElementById( 'kompas-video-attachment-id' );
		var filename = document.getElementById( 'kompas-video-filename' );
		btn.addEventListener( 'click', function() {
			var frame = wp.media( {
				title: 'Изабери видео',
				button: { text: 'Изабери' },
				library: { type: 'video' },
				multiple: false,
			} );
			frame.on( 'select', function() {
				var att = frame.state().get( 'selection' ).first().toJSON();
				input.value    = att.id;
				filename.textContent = att.filename || att.url.split( '/' ).pop();
			} );
			frame.open();
		} );
	} )();
	</script>
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
	if ( isset( $_POST['kompas_video_attachment_id'] ) ) {
		update_post_meta( $post_id, 'kompas_video_attachment_id', absint( $_POST['kompas_video_attachment_id'] ) );
	}
}
add_action( 'save_post_kompas_video', 'kompas_video_meta_box_save' );

/**
 * Enqueue WP media on kompas_video edit screen.
 */
function kompas_video_enqueue_media( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || 'kompas_video' !== $screen->post_type ) {
		return;
	}
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'kompas_video_enqueue_media' );

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
