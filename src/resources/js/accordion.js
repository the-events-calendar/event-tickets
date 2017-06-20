(function( window, $ ) {
	var accordions = document.getElementsByClassName( 'accordion' );

	var accordionIndex = ( undefined === accordionIndex ) ? 0 : accordionIndex++;

	for ( var i = 0, aLen = accordions.length; i < aLen; i++ ) {
		var accordion = accordions[i],
			accordionContent = accordion.getElementsByClassName( 'accordion-content' ),
			accordionHeaders  = accordion.getElementsByClassName( 'accordion-header' );

			for ( var t = 0, hLen = accordionHeaders.length; t < hLen; t++ ) {
				var header = accordionHeaders[t]
				header.setAttribute( 'id', 'tab' + accordionIndex + '-' + t );
				header.setAttribute( 'aria-selected', 'false' );
				header.setAttribute( 'aria-expanded', 'false' );
				header.setAttribute( 'aria-controls', 'panel' + accordionIndex + '-' + t );
				header.setAttribute( 'role', 'tab' );

				header.addEventListener( 'click', handleAccordion );

				function handleAccordion() {
					var nextPanel = header.nextElementSibling,
					nextPanelLabel = nextPanel.querySelector( '.accordion-label' );

					header.classList.toggle( 'is-active' );
					nextPanel.classList.toggle( 'is-active' );
					nextPanelLabel.setAttribute( 'tabindex', -1 );
					nextPanelLabel.focus();


					if ( nextPanel.classList.contains( 'is-active' ) ) {
						header.setAttribute( 'aria-selected', 'true' );
						header.setAttribute( 'aria-expanded', 'true' );
						nextPanel.setAttribute( 'aria-hidden', 'false' );
					} else {
						header.setAttribute( 'aria-selected', 'false' );
						header.setAttribute( 'aria-expanded', 'false' );
						nextPanel.setAttribute( 'aria-hidden', 'true' );
					}
				}
			}
	}

	for ( var s = 0, cLen = accordionContent.length; s < cLen; s++ ) {
		var content = accordionContent[s];
		// Set ARIA and ID attributes
		content.setAttribute( 'id', 'panel' + accordionIndex + '-' + s );
		content.setAttribute( 'aria-hidden', 'true' );
		content.setAttribute( 'aria-labelledby', 'tab' + accordionIndex + '-' + s );
		content.setAttribute( 'role', 'tabpanel' );
	}
})( window, jQuery );
