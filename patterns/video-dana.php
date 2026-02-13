<?php
/**
 * Title: Video Dana
 * Slug: kompas/video-dana
 * Categories: kompas-sections
 * Keywords: video, featured
 */
?>

<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"1440px"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">

<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"border":{"bottom":{"color":"var:preset|color|primary","width":"3px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--primary);border-bottom-width:3px;margin-bottom:var(--wp--preset--spacing--50)">
<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"1rem","fontWeight":"800","textTransform":"uppercase","letterSpacing":"0.02em"},"spacing":{"padding":{"bottom":"var:preset|spacing|20"}}},"textColor":"dark"} -->
<h2 class="has-dark-color has-text-color" style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding-bottom:var(--wp--preset--spacing--20)">ВИДЕО ДАНА</h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->

<!-- wp:query {"queryId":30,"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns">
<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%">
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9"} /-->
</div>
<!-- /wp:column -->
<!-- wp:column {"width":"50%","verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
<!-- wp:post-excerpt {"excerptLength":30,"style":{"typography":{"fontSize":"0.875rem"}},"textColor":"muted"} /-->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- /wp:post-template -->
<!-- /wp:query -->

</div>
<!-- /wp:group -->
