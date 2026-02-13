<?php
/**
 * Title: Povezane vesti
 * Slug: kompas/related-posts
 * Categories: kompas-sections
 * Keywords: related, povezano, slicno
 */
?>

<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"1440px"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}},"border":{"top":{"color":"var:preset|color|border","width":"1px"}}}} -->
<div class="wp-block-group alignwide" style="border-top-color:var(--wp--preset--color--border);border-top-width:1px;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">

<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"border":{"bottom":{"color":"var:preset|color|primary","width":"3px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--primary);border-bottom-width:3px;margin-bottom:var(--wp--preset--spacing--50)">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1rem","fontWeight":"800","textTransform":"uppercase","letterSpacing":"0.02em"},"spacing":{"padding":{"bottom":"var:preset|spacing|20"}}},"textColor":"dark"} -->
<h3 class="has-dark-color has-text-color" style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em;padding-bottom:var(--wp--preset--spacing--20)">ПОВЕЗАНЕ ВЕСТИ</h3>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->

<!-- wp:query {"queryId":50,"query":{"perPage":4,"pages":1,"offset":0,"postType":"post","order":"desc","orderBy":"date"}} -->
<!-- wp:post-template {"layout":{"type":"grid","columnCount":4}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"0.875rem","fontWeight":"700","lineHeight":"1.3"}},"textColor":"dark"} /-->
<!-- /wp:post-template -->
<!-- /wp:query -->

</div>
<!-- /wp:group -->