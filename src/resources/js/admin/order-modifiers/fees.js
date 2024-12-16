/**
 * File: fees.js
 * Description: This script initializes a Select2 dropdown for the "Ticket Order Modifier Fees"
 * and ensures the dropdown remains functional when dynamically added elements are observed.
 *
 * It integrates the `tribe_dropdowns()` function from the dropdown.js file and ensures the
 * dropdown is initialized when the document is ready or when new elements are added to the DOM.
 *
 * Dependencies:
 * - jQuery
 * - tribe_dropdowns.js
 *
 * @since 5.18.0
 */

( function( $ ) {
	/**
	 * Initializes the Select2 dropdown for ticket order modifier fees.
	 * Ensures the dropdown is initialized only if it exists and hasn't already been initialized.
	 *
	 * @since 5.18.0
	 * @return void
	 */
	const initFeesDropdown = () => {
		const feesDropdown = document.querySelector( '#ticket_order_modifier_fees' );

		// Check if the dropdown exists and hasn't been initialized by Select2.
		if ( feesDropdown && ! feesDropdown.classList.contains( 'select2-hidden-accessible' ) ) {
			$( feesDropdown ).tribe_dropdowns();
		}
	};

	/**
	 * Initializes the dropdown when the document is fully loaded and ready.
	 *
	 * @since 5.18.0
	 */
	document.addEventListener( 'DOMContentLoaded',
		() => {
			initFeesDropdown();
		},
	);

	/**
	 * MutationObserver to detect when elements (such as the dropdown) are dynamically added to the DOM.
	 * If new nodes are added that include the fee dropdown, initialize Select2 on them.
	 *
	 * @since 5.18.0
	 */
	const observer = new MutationObserver(
		( mutationsList ) => {
			for ( const mutation of mutationsList ) {
				// Check if any added nodes contain the dropdown we're looking for.
				if ( mutation.addedNodes.length > 0 ) {
					initFeesDropdown(); // Initialize the dropdown when added to the DOM.
				}
			}
		},
	);

	/**
	 * Observe the document body for added child nodes and run the observer on the entire subtree.
	 * This ensures dynamically added elements are captured and initialized.
	 *
	 * @since 5.18.0
	 */
	observer.observe( document.body, {
		childList: true,
		subtree: true,
	} );
} )( jQuery );
