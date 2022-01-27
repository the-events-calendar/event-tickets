<?
/**
 * Title for featured settings box.
 *
 * @since TBD
 *
 * @var Tribe__Template              $this           Template object.
 * @var string                       $description    Featured settings description.
 */

if ( empty( $description ) ) {
    return;
}

?>
<div class="tec-tickets__admin-settings-featured-description">
    <?php echo $description; ?>
</div>