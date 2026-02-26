<?php
/**
 * Render: kompas/related-posts
 */
$exclude_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
echo kompas_render_related_posts_block( $attributes, $exclude_id );
