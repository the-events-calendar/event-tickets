<?php
/**
 * Help link for featured settings box.
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views    $this   Template object.
 * @var string[]                        $link  Array of link arguments.
 */

 $defaults = [
    'slug'     => 'help-1',
    'priority' => 10,
    'link'     => '',
    'html'     => '',
    'target'   => '_blank',
    'classes'  => [],
 ];

 $link = array_merge( $defaults, (array) $link );
 $classes = array_merge( ['tec-tickets__admin-settings-featured-link'], (array) $defaults['classes'] );

?>
<div <?php tribe_classes( $classes ); ?> >
	<?php $this->template( 'components/icons/lightbulb' ); ?>
	<a
		href="<?php echo esc_attr( $link['link'] ); ?>"
		target="<?php echo esc_attr( $link['target'] ); ?>"
		rel="noopener noreferrer"
		class="tec-tickets__admin-settings-featured-link-url"
	><?php esc_html_e( 'Learn more about configuring PayPal payments', 'event-tickets' ); ?></a>
</div>