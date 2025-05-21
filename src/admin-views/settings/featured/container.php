<?php
/**
 * Featured settings box.
 *
 * @since 5.3.0
 * @since 5.23.0 Added additional div element.
 *
 * @var Tribe__Template $this             Template object.
 * @var string          $title            Featured settings title.
 * @var string          $description      Featured settings description/HTML.
 * @var string          $content_template Template used for the content section.
 * @var array           $content_context  Context for template used for content.
 * @var string[]        $classes          Array of classes.
 * @var array           $links            Array of arrays for links.
 * @var string[]        $container_classes Array of classes for the container.
 */

$container_classes = [
	'tec-settings-form__element--full-width' => true,
	...$container_classes,
];

$classes[] = 'tec-tickets__admin-settings-featured';

?>
<div <?php tribe_classes( $container_classes ); ?>>
	<div <?php tribe_classes( $classes ); ?> >
		<?php $this->template( 'title' ); ?>
		<?php $this->template( 'description' ); ?>
		<?php $this->template( 'content' ); ?>
		<?php $this->template( 'links' ); ?>
	</div>
</div>
