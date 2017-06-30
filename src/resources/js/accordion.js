( function( window, $ ) {
	var accordions = document.getElementsByClassName( 'accordion' );

	var accordion_index = ( undefined === accordion_index ) ? 0 : accordion_index++;

	for ( var i = 0, aLen = accordions.length; i < aLen; i++ ) {
		var accordion = accordions[i];
		var accordion_content = accordion.getElementsByClassName( 'accordion-content' );
		var accordion_headers  = accordion.getElementsByClassName( 'accordion-header' );

			for ( var t = 0, hLen = accordion_headers.length; t < hLen; t++ ) {
				var header = accordion_headers[t]
				header.setAttribute( 'id', 'tab' + accordion_index + '-' + t );
				header.setAttribute( 'aria-selected', 'false' );
				header.setAttribute( 'aria-expanded', 'false' );
				header.setAttribute( 'aria-controls', 'panel' + accordion_index + '-' + t );
				header.setAttribute( 'role', 'tab' );

				header.addEventListener( 'click', handle_accordion );

				/**
				 * Handles the changes (both visual and aria) for the accordion clicks
				 *
				 * @since TBD
				 */
				function handle_accordion() {
					var next_panel = header.nextElementSibling,
					next_panel_label = next_panel.querySelector( '.accordion-label' );

					header.classList.toggle( 'is-active' );
					next_panel.classList.toggle( 'is-active' );
					next_panel_label.setAttribute( 'tabindex', -1 );
					next_panel_label.focus();


					if ( next_panel.classList.contains( 'is-active' ) ) {
						header.setAttribute( 'aria-selected', 'true' );
						header.setAttribute( 'aria-expanded', 'true' );
						next_panel.setAttribute( 'aria-hidden', 'false' );
					} else {
						header.setAttribute( 'aria-selected', 'false' );
						header.setAttribute( 'aria-expanded', 'false' );
						next_panel.setAttribute( 'aria-hidden', 'true' );
					}
				}
			}

			for ( var s = 0, cLen = accordion_content.length; s < cLen; s++ ) {
				var content = accordion_content[s];
				// Set ARIA and ID attributes
				content.setAttribute( 'id', 'panel' + accordion_index + '-' + s );
				content.setAttribute( 'aria-hidden', 'true' );
				content.setAttribute( 'aria-labelledby', 'tab' + accordion_index + '-' + s );
				content.setAttribute( 'role', 'tabpanel' );
			}
	}
})( window, jQuery );
