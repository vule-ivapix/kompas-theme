<?php
/**
 * Render: kompas/mobile-nav
 *
 * Mobile header bar + full-screen overlay with hierarchical categories.
 */

// Load menu items assigned to the desktop header locations.
$menu_locations = get_nav_menu_locations();

$header_nav_menu_items = array();
if ( ! empty( $menu_locations['kompas-header-nav'] ) ) {
	$header_nav_menu_items = wp_get_nav_menu_items(
		(int) $menu_locations['kompas-header-nav'],
		array(
			'update_post_term_cache' => false,
		)
	);
	if ( ! is_array( $header_nav_menu_items ) ) {
		$header_nav_menu_items = array();
	}
}

$header_tag_menu_items = array();
if ( ! empty( $menu_locations['kompas-header-tags'] ) ) {
	$header_tag_menu_items = wp_get_nav_menu_items(
		(int) $menu_locations['kompas-header-tags'],
		array(
			'update_post_term_cache' => false,
		)
	);
	if ( ! is_array( $header_tag_menu_items ) ) {
		$header_tag_menu_items = array();
	}
}

$mobile_header_parent_items = array();
$mobile_header_child_items  = array();
foreach ( $header_nav_menu_items as $menu_item ) {
	if ( ! $menu_item instanceof WP_Post ) {
		continue;
	}

	$parent_id = (int) $menu_item->menu_item_parent;
	if ( $parent_id > 0 ) {
		if ( ! isset( $mobile_header_child_items[ $parent_id ] ) ) {
			$mobile_header_child_items[ $parent_id ] = array();
		}
		$mobile_header_child_items[ $parent_id ][] = $menu_item;
		continue;
	}

	$mobile_header_parent_items[] = $menu_item;
}

$mobile_tag_items = array();
foreach ( $header_tag_menu_items as $menu_item ) {
	if ( ! $menu_item instanceof WP_Post ) {
		continue;
	}
	if ( 0 !== (int) $menu_item->menu_item_parent ) {
		continue;
	}
	$mobile_tag_items[] = $menu_item;
}
// Detect kolumne archive context (both supported slugs).
$is_kolumne_archive = false;
if ( is_category() ) {
	$term = get_queried_object();
	if ( $term instanceof WP_Term ) {
		$term_slug = mb_strtolower( (string) $term->slug );
		$term_name = mb_strtolower( (string) $term->name );
		$slug_latin = function_exists( 'kompas_cyrillic_to_latin_for_slug' ) ? kompas_cyrillic_to_latin_for_slug( $term_slug ) : $term_slug;
		$name_latin = function_exists( 'kompas_cyrillic_to_latin_for_slug' ) ? kompas_cyrillic_to_latin_for_slug( $term_name ) : $term_name;
		$is_kolumne_archive = in_array( $term_slug, array( 'kolumne', 'kolumna' ), true )
			|| false !== strpos( $slug_latin, 'kolumn' )
			|| false !== strpos( $name_latin, 'kolumn' );
	}
}

// Use the custom mobile header layout in author/kolumne single contexts.
$use_author_mobile_header = false;
if ( is_author() ) {
	$use_author_mobile_header = true;
} elseif ( is_singular( 'kompas_autor' ) ) {
	$use_author_mobile_header = true;
} elseif ( is_singular( 'post' ) && function_exists( 'kompas_is_kolumne_post' ) ) {
	$use_author_mobile_header = kompas_is_kolumne_post( (int) get_queried_object_id() );
} elseif ( $is_kolumne_archive ) {
	$use_author_mobile_header = true;
}

// Use kolumne/autori brand logo when needed.
$use_kolumne_brand_logo = $use_author_mobile_header || $is_kolumne_archive;
?>
<div class="kompas-mobile-nav<?php echo $use_author_mobile_header ? ' kompas-mobile-nav--author' : ''; ?>">

	<!-- Row 1: hamburger | cir/lat | search (logo only on default variant) -->
	<div class="kompas-mobile-bar">
		<button class="kompas-mobile-hamburger" type="button" aria-label="Мени" aria-expanded="false">
			<span></span><span></span><span></span>
		</button>

		<?php if ( ! $use_author_mobile_header ) : ?>
			<div class="kompas-mobile-bar__logo">
				<?php
				if ( $use_kolumne_brand_logo ) {
					echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="kompas-mobile-bar__logo-link kompas-mobile-bar__logo-link--kolumne">';
					echo '<img src="' . esc_url( get_theme_file_uri( 'logo-autori.svg' ) ) . '" alt="' . esc_attr__( 'Kompas autori logo', 'kompas' ) . '" />';
					echo '</a>';
				} elseif ( has_custom_logo() ) {
					the_custom_logo();
				} else {
					echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>';
			}
			?>
		</div>
		<?php endif; ?>

		<span class="kompas-mobile-bar__spacer"></span>

		<p class="kompas-script-toggle kompas-mobile-bar__toggle"><strong>ЋИР</strong>/ЛАТ</p>

		<button type="button" class="kompas-mobile-bar__search" aria-label="Претрага">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
		</button>
	</div>

	<?php if ( $use_author_mobile_header ) : ?>
	<div class="kompas-mobile-author-brand" aria-label="Лого">
		<div class="kompas-mobile-author-brand__inner">
			<span class="kompas-mobile-author-brand__line" aria-hidden="true"></span>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="kompas-mobile-author-brand__logo-link">
				<img src="<?php echo esc_url( get_theme_file_uri( 'logo-autori.svg' ) ); ?>" alt="<?php echo esc_attr__( 'Kompas autori logo', 'kompas' ); ?>" class="kompas-mobile-author-brand__logo" />
			</a>
			<span class="kompas-mobile-author-brand__line" aria-hidden="true"></span>
		</div>
	</div>
	<?php else : ?>
		<!-- Row 2: categories (horizontal scroll) -->
		<?php if ( ! empty( $mobile_header_parent_items ) ) : ?>
		<nav class="kompas-mobile-strip kompas-mobile-strip--cats" aria-label="Категорије">
			<?php foreach ( $mobile_header_parent_items as $menu_item ) : ?>
			<a href="<?php echo esc_url( $menu_item->url ); ?>" class="kompas-mobile-strip__link kompas-mobile-strip__link--cat">
				<?php echo esc_html( wp_strip_all_tags( $menu_item->title ) ); ?>
			</a>
			<?php endforeach; ?>
		</nav>
		<?php endif; ?>

		<!-- Row 3: tags (horizontal scroll) -->
		<?php if ( ! empty( $mobile_tag_items ) ) : ?>
		<nav class="kompas-mobile-strip kompas-mobile-strip--tags" aria-label="Тагови">
			<?php foreach ( $mobile_tag_items as $menu_item ) : ?>
			<a href="<?php echo esc_url( $menu_item->url ); ?>" class="kompas-mobile-strip__link kompas-mobile-strip__link--tag">
				<?php echo esc_html( wp_strip_all_tags( $menu_item->title ) ); ?>
			</a>
			<?php endforeach; ?>
		</nav>
		<?php endif; ?>
	<?php endif; ?>

	<!-- Full-screen overlay -->
	<div class="kompas-mobile-overlay" aria-hidden="true">
		<div class="kompas-mobile-overlay__inner">

			<!-- Search -->
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="kompas-mobile-search">
				<input type="search" name="s" placeholder="Унесите термин претраге." class="kompas-mobile-search__input" />
				<button type="submit" class="kompas-mobile-search__btn" aria-label="Претрага">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
				</button>
			</form>

				<!-- Hierarchical categories -->
				<?php if ( ! empty( $mobile_header_parent_items ) ) : ?>
				<nav class="kompas-mobile-cats" aria-label="Навигација">
					<?php foreach ( $mobile_header_parent_items as $menu_item ) : ?>
					<?php $children = isset( $mobile_header_child_items[ (int) $menu_item->ID ] ) ? $mobile_header_child_items[ (int) $menu_item->ID ] : array(); ?>
					<div class="kompas-mobile-cats__group">
						<a href="<?php echo esc_url( $menu_item->url ); ?>" class="kompas-mobile-cats__parent">
							<?php echo esc_html( wp_strip_all_tags( $menu_item->title ) ); ?>
						</a>
						<?php if ( ! empty( $children ) ) : ?>
						<div class="kompas-mobile-cats__children">
							<?php foreach ( $children as $child_item ) : ?>
							<a href="<?php echo esc_url( $child_item->url ); ?>" class="kompas-mobile-cats__child">
								<?php echo esc_html( wp_strip_all_tags( $child_item->title ) ); ?>
							</a>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</nav>
				<?php endif; ?>

		</div>
	</div>
</div>
