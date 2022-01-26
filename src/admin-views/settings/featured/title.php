<?
/**
 * Title for featured settings box.
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views $this           Template object.
 * @var string                       $title          Featured settings title.
 */

if ( empty( $title ) ) {
    return;
}

?>
<div class="tec-tickets__admin-settings-featured-title"><?php echo esc_html( $title ); ?></div>