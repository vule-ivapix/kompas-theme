<?php
/**
 * Title: Hero - Naslovna
 * Slug: kompas/hero
 * Categories: kompas-hero
 * Keywords: hero, naslovna, featured
 */
?>

<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"1440px"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns">

<!-- wp:column {"width":"25%"} -->
<div class="wp-block-column" style="flex-basis:25%">
<!-- wp:query {"queryId":10,"query":{"perPage":4,"pages":0,"offset":1,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"},"padding":{"bottom":"var:preset|spacing|40"}},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;margin-bottom:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"0.875rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
<!-- /wp:query -->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%">
<!-- wp:query {"queryId":11,"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--50)">
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"700","lineHeight":"1.25"}},"textColor":"dark"} /-->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
<!-- /wp:query -->

<!-- wp:query {"queryId":12,"query":{"perPage":3,"pages":0,"offset":5,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"},"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"},"padding":{"bottom":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|40"},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}}} -->
<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;margin-bottom:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
<!-- wp:post-featured-image {"isLink":true,"width":"140px","aspectRatio":"4/3"} /-->
<!-- wp:group {"layout":{"type":"constrained"},"style":{"layout":{"selfStretch":"fill","flexSize":null}}} -->
<div class="wp-block-group">
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"0.875rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
<!-- /wp:query -->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%"} -->
<div class="wp-block-column" style="flex-basis:25%">

<!-- wp:kompas/rec-urednika /-->

<!-- wp:kompas/kolumne /-->

</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->
