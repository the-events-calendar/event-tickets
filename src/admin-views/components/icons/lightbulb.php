<?php
/**
 * View: Lightbulb Icon
 *
 * See more documentation about our views templating system.
 *
 * @since 5.2.0
 *
 * @version 5.2.0
 *
 * @var array<string> $classes Additional classes to add to the svg icon.
 */

$svg_classes = [ 'tribe-common-c-svgicon', 'tribe-common-c-svgicon--lightbulb' ];

if ( ! empty( $classes ) ) {
	$svg_classes = array_merge( $svg_classes, $classes );
}
?>
<svg
<?php tribe_classes( $svg_classes ); ?>
fill="none"
height="22"
viewBox="0 0 16 22"
width="16"
xmlns="http://www.w3.org/2000/svg"
>
	<path stroke="currentColor" stroke-linecap="round" d="M7.782 1C3.806 1 1 4.114 1 8.219c0 4.104 4.092 4.675 4.092 7.364v4.546c0 .536.435.97.97.97h4.288a.97.97 0 0 0 .97-.97v-4.546c0-3.397 3.68-2.835 3.68-7.364C15 3.689 11.757 1 7.782 1ZM5.463 16.834h5.511M5.463 19.166h5.511"/>
	<path fill="currentColor" d="M7.758 9.074 6 9.019 9.987 5 8.242 8.592 10 8.647 6.259 13l1.499-3.926Z"/>
</svg>
