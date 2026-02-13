<?php
/**
 * Title: Kategorija Grid
 * Slug: kompas/category-grid
 * Categories: kompas-sections
 * Keywords: category, grid, kategorija
 */
?>

<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"1440px"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">

<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"border":{"bottom":{"color":"var:preset|color|primary","width":"3px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--primary);border-bottom-width:3px;margin-bottom:var(--wp--preset--spacing--50)">
<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"1rem","fontWeight":"800","textTransform":"uppercase","letterSpacing":"0.02em"},"spacing":{"padding":{"bottom":"var:preset|spacing|20"}}},"textColor":"dark"} -->
<h2 class="has-dark-color has-text-color" style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding-bottom:var(--wp--preset--spacing--20)">ВЕСТИ</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->

<!-- Red 1: 2 velike vesti sa excerptom -->
<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"},"margin":{"bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns" style="margin-bottom:var(--wp--preset--spacing--50)">

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:query {"queryId":20,"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
<!-- wp:post-excerpt {"excerptLength":25,"style":{"typography":{"fontSize":"0.875rem","lineHeight":"1.6"}},"textColor":"muted"} /-->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
<!-- /wp:query -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:query {"queryId":21,"query":{"perPage":1,"pages":0,"offset":1,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
<!-- wp:post-excerpt {"excerptLength":25,"style":{"typography":{"fontSize":"0.875rem","lineHeight":"1.6"}},"textColor":"muted"} /-->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
<!-- /wp:query -->
</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

<!-- Red 2: 4 manje vesti samo slika + naslov -->
<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns">

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:query {"queryId":22,"query":{"perPage":1,"pages":0,"offset":2,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"0.875rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
<!-- /wp:post-template -->
<!-- /wp:query -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:query {"queryId":23,"query":{"perPage":1,"pages":0,"offset":3,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"0.875rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
<!-- /wp:post-template -->
<!-- /wp:query -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:query {"queryId":24,"query":{"perPage":1,"pages":0,"offset":4,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"0.875rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
<!-- /wp:post-template -->
<!-- /wp:query -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:query {"queryId":25,"query":{"perPage":1,"pages":0,"offset":5,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"0.875rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
<!-- /wp:post-template -->
<!-- /wp:query -->
</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->
