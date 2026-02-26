<?php
/**
 * Render: kompas/mobile-nav
 *
 * Mobile header bar + full-screen overlay with hierarchical categories.
 */

// Get all top-level categories.
$parent_cats = get_categories( array(
	'hide_empty' => false,
	'parent'     => 0,
	'orderby'    => 'name',
	'order'      => 'ASC',
) );

// Get popular tags.
$popular_tags = get_tags( array(
	'orderby'    => 'count',
	'order'      => 'DESC',
	'number'     => 10,
	'hide_empty' => true,
) );
?>
<div class="kompas-mobile-nav">

	<!-- Row 1: hamburger | logo | cir/lat | search -->
	<div class="kompas-mobile-bar">
		<button class="kompas-mobile-hamburger" type="button" aria-label="Мени" aria-expanded="false">
			<span></span><span></span><span></span>
		</button>

		<div class="kompas-mobile-bar__logo">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>';
			}
			?>
		</div>

		<span class="kompas-mobile-bar__spacer"></span>

		<p class="kompas-script-toggle kompas-mobile-bar__toggle"><strong>ЋИР</strong>/ЛАТ</p>

		<a href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" class="kompas-mobile-bar__search" aria-label="Претрага">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
		</a>
	</div>

	<!-- Row 2: categories (horizontal scroll) -->
	<?php if ( ! empty( $parent_cats ) ) : ?>
	<nav class="kompas-mobile-strip kompas-mobile-strip--cats" aria-label="Категорије">
		<?php foreach ( $parent_cats as $cat ) : ?>
		<a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="kompas-mobile-strip__link kompas-mobile-strip__link--cat">
			<?php echo esc_html( mb_strtoupper( $cat->name ) ); ?>
		</a>
		<?php endforeach; ?>
	</nav>
	<?php endif; ?>

	<!-- Row 3: tags (horizontal scroll) -->
	<?php if ( ! empty( $popular_tags ) ) : ?>
	<nav class="kompas-mobile-strip kompas-mobile-strip--tags" aria-label="Тагови">
		<?php foreach ( $popular_tags as $tag ) : ?>
		<a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" class="kompas-mobile-strip__link kompas-mobile-strip__link--tag">
			<?php echo esc_html( mb_strtoupper( $tag->name ) ); ?>
		</a>
		<?php endforeach; ?>
	</nav>
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
			<nav class="kompas-mobile-cats" aria-label="Навигација">
				<?php foreach ( $parent_cats as $cat ) :
					$children = get_categories( array(
						'hide_empty' => false,
						'parent'     => $cat->term_id,
						'orderby'    => 'name',
						'order'      => 'ASC',
					) );
				?>
				<div class="kompas-mobile-cats__group">
					<a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="kompas-mobile-cats__parent">
						<?php echo esc_html( mb_strtoupper( $cat->name ) ); ?>
					</a>
					<?php if ( ! empty( $children ) ) : ?>
					<div class="kompas-mobile-cats__children">
						<?php foreach ( $children as $child ) : ?>
						<a href="<?php echo esc_url( get_category_link( $child->term_id ) ); ?>" class="kompas-mobile-cats__child">
							<?php echo esc_html( mb_strtoupper( $child->name ) ); ?>
						</a>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</nav>

		</div>
	</div>
</div>
