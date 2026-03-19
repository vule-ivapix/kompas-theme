<?php
/**
 * Render: kompas/gallery-slider
 */

echo kompas_render_gallery_slider_markup( ! empty( $attributes['images'] ) ? $attributes['images'] : array() );
