<?php
/**
 * Attendee Registration Modal class
 *
 * @since TBD
 */
class Tribe__Tickets__Attendee_Registration__Modal {

	/**
	 * Setup Modal Cart Template
	 *
	 * @since TBD
	 */
	public function hook() {
		add_filter( 'tribe_events_tickets_attendee_registration_modal_content', [ $this, 'modal_cart_template' ], 10, 2 );
	}

	/**
	 * Add Cart Template for Modal
	 *
	 * @since TBD
	 *
	 * @param string $content a string of default content
	 * @param Tribe__Tickets__Editor__Template $template_obj the Template object
	 *
	 * @return string
	 */
	function modal_cart_template( $content, $template_obj ) {
		$template = 'modal/cart.php';
		$file = $this->locate_template( $template );

		$post_id             = $template_obj->get( 'post_id' );
		$tickets             = $template_obj->get( 'tickets', [] );
		$provider            = $template_obj->get( 'provider' );
		$provider_id         = $template_obj->get( 'provider_id' );
		$cart_url            = $template_obj->get( 'cart_url' );
		$tickets_on_sale     = $template_obj->get( 'tickets_on_sale' );
		$has_tickets_on_sale = $template_obj->get( 'has_tickets_on_sale' );
		$is_sale_past        = $template_obj->get( 'is_sale_past' );

		ob_start();
		?>
		<form
			id="tribe-tickets__modal-form"
			action=""
			method="post"
			enctype='multipart/form-data'
			data-provider="<?php echo esc_attr( $provider->class_name ); ?>"
			autocomplete="off"
			data-provider-id="<?php echo esc_attr( $provider->orm_provider ); ?>"
			novalidate
		>
			<?php
			include $file;
			$this->append_modal_ar_template( $content, $template_obj );
			?>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Add AR Template to Modal
	 *
	 * @since TBD
	 *
	 * @param string $content The content string
	 * @param Tribe__Tickets__Editor__Template $template_obj the Template object
	 *
	 * @return string The content with AR fields appended.
	 */
	function append_modal_ar_template( $unused_content, $template_obj ) {
		$template = 'modal/registration-js.php';
		$file = $this->locate_template( $template );

		$obj_tickets = $template_obj->get( 'tickets', [] );
		foreach( $obj_tickets as $ticket ) {
			$ticket_data = array(
				'id'       => $ticket->ID,
				'qty'      => 1,
				'provider' => $ticket->get_provider(),
			);

			$tickets[] = $ticket_data;
		}

		ob_start();

		include $file;

		$content = ob_get_clean();

		echo $content;
	}

	/**
	 * Add Footer Template to Modal
	 *
	 * @since TBD
	 *
	 * @param string $content The content string
	 * @param Tribe__Tickets__Editor__Template $template_obj the Template object
	 *
	 * @return string The content with AR fields appended.
	 */
	function modal_footer_template( $content, $template_obj ) {
		$template = 'modal/footer.php';
		$file = $this->locate_template( $template );

		$obj_tickets = $template_obj->get( 'tickets', [] );
		foreach( $obj_tickets as $ticket ) {
			$ticket_data = array(
				'id'       => $ticket->ID,
				'qty'      => 1,
				'provider' => $ticket->provider,
			);

			$tickets[] = $ticket_data;
		}

		$template            = $template_obj;
		$post_id             = $template_obj->get( 'post_id' );
		$provider            = $template_obj->get( 'provider' );
		$provider_id         = $template_obj->get( 'provider_id' );
		$cart_url            = $template_obj->get( 'cart_url' );
		$tickets_on_sale     = $template_obj->get( 'tickets_on_sale' );
		$has_tickets_on_sale = $template_obj->get( 'has_tickets_on_sale' );
		$is_sale_past        = $template_obj->get( 'is_sale_past' );

		ob_start();

		include $file;

		$content .= ob_get_clean();
		return $content;
	}

	/**
	 * Template finder.
	 * Allows for overriding template in theme.
	 *
	 * @param string $template Relative path to template file.
	 *
	 * @return string The template file to use.
	 */
	function locate_template( $template ) {
		$main = Tribe__Tickets__Main::instance();

		if ( $theme_file = locate_template( [ 'tribe-events/' . $template ] ) ) {
			$file = $theme_file;
		} else {
			$file = $main->plugin_path . 'src/views/' . $template;
		}

		/**
		 * Filter Modal Template
		 *
		 * @since TBD
		 *
		 * @param string $template Relative path to template file.
		 * @param string $file The template location.
		 */
		$file = apply_filters( 'tribe_events_tickets_template_' . $template, $file );

		return $file;
	}
}
