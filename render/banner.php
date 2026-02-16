<?php
/**
 * Banner placeholder render.
 *
 * @var array $attributes Block attributes.
 */

$variant = isset( $attributes['variant'] ) ? $attributes['variant'] : 'horizontal';

if ( 'square' === $variant ) : ?>
<div class="kompas-banner kompas-banner--square" style="border:1px solid #dddddd;min-height:300px;display:flex;align-items:center;justify-content:center;background:var(--wp--preset--color--surface,#f9f9f9);margin-top:var(--wp--preset--spacing--50)">
	<p style="font-size:0.875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--wp--preset--color--muted,#999);margin:0">БАНЕР</p>
</div>
<?php else : ?>
<div class="kompas-banner kompas-banner--horizontal" style="border:1px solid #dddddd;display:flex;align-items:center;justify-content:center;background:var(--wp--preset--color--surface,#f9f9f9);padding:var(--wp--preset--spacing--60) var(--wp--preset--spacing--50)">
	<p style="font-size:0.875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--wp--preset--color--muted,#999);margin:0">БАНЕР</p>
</div>
<?php endif; ?>
