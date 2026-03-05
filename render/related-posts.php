<?php
/**
 * Render: kompas/related-posts
 */
$exclude_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

// Per-post meta overrides block-level selectedPostIds.
if ( $exclude_id > 0 ) {
	$meta_ids = get_post_meta( $exclude_id, 'kompas_related_post_ids', true );
	if ( is_array( $meta_ids ) && ! empty( $meta_ids ) ) {
		$attributes['selectedPostIds'] = array_values( array_filter( array_map( 'absint', $meta_ids ) ) );
	}
}

echo kompas_render_related_posts_block( $attributes, $exclude_id );
