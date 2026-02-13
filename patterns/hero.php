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

<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"},"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"}},"border":{"top":{"color":"var:preset|color|primary","width":"3px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-top-color:var(--wp--preset--color--primary);border-top-width:3px;margin-bottom:var(--wp--preset--spacing--60);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"0.875rem","fontWeight":"800","textTransform":"uppercase","letterSpacing":"0.02em"}},"textColor":"primary"} -->
<h3 class="has-primary-color has-text-color" style="font-size:0.875rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em">РЕЧ ГЛАВНЕ УРЕДНИЦЕ</h3>
<!-- /wp:heading -->
<!-- wp:image {"sizeSlug":"medium","style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<figure class="wp-block-image size-medium"><img src="" alt="Реч уреднице" /></figure>
<!-- /wp:image -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.8125rem"}},"textColor":"muted"} -->
<p class="has-muted-color has-text-color" style="font-size:0.8125rem">ПОГЛЕДАЈ СВЕ НАСЛОВНИЦЕ</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40"}},"border":{"top":{"color":"var:preset|color|primary","width":"3px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-top-color:var(--wp--preset--color--primary);border-top-width:3px;padding-top:var(--wp--preset--spacing--40)">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"0.875rem","fontWeight":"800","textTransform":"uppercase","letterSpacing":"0.02em"}},"textColor":"primary"} -->
<h3 class="has-primary-color has-text-color" style="font-size:0.875rem;font-weight:800;text-transform:uppercase;letter-spacing:0.02em">КОЛУМНЕ</h3>
<!-- /wp:heading -->

<!-- wp:query {"queryId":13,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","taxQuery":{"category":[]}},"displayLayout":{"type":"list"}} -->
<!-- wp:post-template -->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"},"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--40)">
<!-- wp:post-author {"showAvatar":true,"showBio":false,"avatarSize":40,"byline":"","isLink":true} /-->
<!-- wp:group {"layout":{"type":"constrained"},"style":{"layout":{"selfStretch":"fill","flexSize":null}}} -->
<div class="wp-block-group">
<!-- wp:post-author-name {"isLink":true,"style":{"typography":{"fontSize":"0.75rem","fontWeight":"700","textTransform":"uppercase"}},"textColor":"dark"} /-->
<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"0.75rem","fontWeight":"400","lineHeight":"1.4"}},"textColor":"muted"} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
<!-- /wp:query -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.75rem","textTransform":"uppercase","letterSpacing":"0.02em"},"spacing":{"margin":{"top":"var:preset|spacing|30"}},"border":{"top":{"color":"var:preset|color|primary","width":"2px"}},"padding":{"top":"var:preset|spacing|20"}},"textColor":"muted"} -->
<p class="has-muted-color has-text-color" style="border-top-color:var(--wp--preset--color--primary);border-top-width:2px;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.02em;margin-top:var(--wp--preset--spacing--30)">ПОГЛЕДАЈ СВЕ</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->
