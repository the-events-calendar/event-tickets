/**
 * Shows the error of a window in the classic ticket form, and marks the field it is about.
 *
 * @since TBD
 */

/** @typedef {import( './window-kinds' ).ClassicWindowKind} ClassicWindowKind */

/**
 * Removes an id from a field's `aria-describedby`, keeping every other description, such as its helper text.
 *
 * @since TBD
 *
 * @param {HTMLElement} field The field.
 * @param {string}      id    The id to remove.
 *
 * @return {void}
 */
function removeDescription( field, id ) {
	const ids = ( field.getAttribute( 'aria-describedby' ) || '' )
		.split( /\s+/ )
		.filter( ( token ) => token && token !== id );

	if ( ids.length ) {
		field.setAttribute( 'aria-describedby', ids.join( ' ' ) );
	} else {
		field.removeAttribute( 'aria-describedby' );
	}
}

/**
 * Adds an id to a field's `aria-describedby`, ahead of its other descriptions so the error is read first.
 *
 * @since TBD
 *
 * @param {HTMLElement} field The field.
 * @param {string}      id    The id to add.
 *
 * @return {void}
 */
function addDescription( field, id ) {
	removeDescription( field, id );
	field.setAttribute(
		'aria-describedby',
		[ id, field.getAttribute( 'aria-describedby' ) ].filter( Boolean ).join( ' ' )
	);
}

/**
 * Shows an error under a window of a kind, marking the field it is about invalid, or clears both.
 *
 * The field is marked with `aria-invalid` rather than common's `tribe-validation-error` class: common validates the
 * form again after the save click and strips that class from every field it does not flag itself.
 *
 * @since TBD
 *
 * @param {ClassicWindowKind} kind           The kind of the window.
 * @param {string}            message        The error, or an empty string to clear it.
 * @param {string|null}       [fieldId=null] The id of the field the error is about, or `null` for an error that is not
 *                                           about a field.
 *
 * @return {HTMLElement|null} The field marked invalid, or `null` when none is.
 */
export function showError( kind, message, fieldId = null ) {
	const error = document.getElementById( kind.errorId );

	if ( ! error ) {
		return null;
	}

	// The error is an alert: writing the same text again can announce it again.
	if ( error.textContent !== message ) {
		error.textContent = message;
	}

	document.querySelectorAll( `[aria-describedby~="${ error.id }"]` ).forEach( ( marked ) => {
		marked.removeAttribute( 'aria-invalid' );
		removeDescription( marked, error.id );
	} );

	const field = '' === message || ! fieldId ? null : document.getElementById( fieldId );

	if ( ! field ) {
		return null;
	}

	field.setAttribute( 'aria-invalid', 'true' );
	addDescription( field, error.id );

	return field;
}
