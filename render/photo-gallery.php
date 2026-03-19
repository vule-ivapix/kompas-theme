<?php
/**
 * Render: kompas/photo-gallery
 */

echo kompas_render_gallery_slider_markup( ! empty( $attributes['images'] ) ? $attributes['images'] : array() );
