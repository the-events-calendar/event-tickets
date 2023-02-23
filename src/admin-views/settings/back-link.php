<?php
/**
 * Settings back link.
 *
 * @since TBD
 *
 * @var bool    $url   Link URL.
 * @var string  $text  Link text.
 */

if ( empty( $url ) || empty( $text ) ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-back-link">
	<a href="<?php echo esc_attr( $url ); ?>" role="link">
		&larr; <?php echo esc_html( $text ); ?>
	</a>
</div>