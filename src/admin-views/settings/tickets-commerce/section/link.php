<?php
/**
 * Template to display a list of featured gateways.
 *
 * @since TBD
 *
 * @var Tribe__Template              $this              Template object.
 * @var array                        $classes           Array of classes.
 * @var string                       $url               Link URL.
 * @var string                       $text              Link text.
 */

 if ( empty( $text ) || empty( $url ) ) {
     return;
 }
 
 $classes[] = 'tec-tickets__admin-settings-tickets-commerce-section-menu-link';

?>
<a <?php tribe_classes( $classes ); ?> href="<?php echo esc_attr( $url ); ?>">
    <?php echo esc_html( $text ); ?>
</a>