<?php
/**
 * Featured settings box.
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views $this              Template object.
 * @var string                       $title             Featured settings title.
 * @var string                       $description       Featured settings description/HTML.
 * @var string                       $content_template  Template used for the content section.
 * @var array                        $content_context   Context for template used for content.
 * @var string[]                     $classes           Array of classes.
 * @var array                        $links             Array of arrays for links.
 */

$default_classes = ['tec-tickets__admin-settings-featured'];

?>
<div <?php tribe_classes( array_merge( $default_classes, (array) $classes ) ); ?> >
    <?php $this->template( 'settings/featured/title' ); ?>
    <?php $this->template( 'settings/featured/description' ); ?>
    <?php if( ! empty ( $content_template ) ) : ?>
        <div class="tec-tickets__admin-settings-featured-content">
            <?php $this->template( $content_template, $content_context ); ?>
        </div>
    <?php endif; ?>
    <?php $this->template( 'settings/featured/links' ); ?>
</div>