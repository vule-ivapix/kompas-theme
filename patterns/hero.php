<?php
/**
 * Title: Hero - Naslovna
 * Slug: kompas/hero
 * Categories: kompas-hero
 * Keywords: hero, naslovna, featured
 */
?>

<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"1440px"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"className":"kompas-hero"} -->
<div class="wp-block-group alignwide kompas-hero" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}},"className":"kompas-hero__cols"} -->
<div class="wp-block-columns kompas-hero__cols">

<!-- wp:column {"width":"25%","className":"kompas-hero__sidebar"} -->
<div class="wp-block-column kompas-hero__sidebar" style="flex-basis:25%">
<!-- wp:query {"queryId":10,"query":{"perPage":3,"pages":0,"offset":1,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"},"padding":{"bottom":"var:preset|spacing|40"}},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;margin-bottom:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"1rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
<!-- /wp:query -->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%","className":"kompas-hero__center"} -->
<div class="wp-block-column kompas-hero__center" style="flex-basis:50%">

<!-- wp:query {"queryId":11,"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"},"className":"kompas-hero__main"} -->
<!-- wp:post-template -->
<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--50)">
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"1.75rem","fontWeight":"700","lineHeight":"1.25"}},"textColor":"dark"} /-->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
<!-- /wp:query -->

<!-- wp:query {"queryId":12,"query":{"perPage":2,"pages":0,"offset":4,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"},"className":"kompas-hero__sub"} -->
<!-- wp:post-template -->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"},"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"},"padding":{"bottom":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|40"},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}}} -->
<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;margin-bottom:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
<!-- wp:post-featured-image {"isLink":true,"width":"400px","aspectRatio":"16/10"} /-->
<!-- wp:group {"layout":{"type":"constrained"},"style":{"layout":{"selfStretch":"fill","flexSize":null}}} -->
<div class="wp-block-group">
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"1rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
<!-- /wp:query -->

</div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%","className":"kompas-hero__aside"} -->
<div class="wp-block-column kompas-hero__aside" style="flex-basis:25%">
<!-- wp:kompas/rec-urednika /-->
<!-- wp:kompas/kolumne /-->

<!-- wp:kompas/banner {"variant":"square"} /-->
</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->
