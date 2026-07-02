/**
 * External dependencies
 */
import { useEffect, useRef } from 'react';

export const RSVP_CLOSE_OVERLAYS_EVENT = 'tec.tickets.rsvp.closeOverlays';

const OVERLAY_CLICK_IGNORE_SELECTORS = [
	'.tribe-editor__rsvp-limit-popover',
	'.tribe-editor__rsvp-window-popover',
	'.tribe-editor__date-input__container',
	'.tribe-editor__date-input',
	'.tribe-editor__date-input__popover',
	'.tribe-editor__timepicker__content',
	'.components-modal__frame',
	'.components-modal__screen-overlay',
	'.tribe-editor__tickets__move-modal',
];

/**
 * Returns true when a click originated inside a portaled RSVP overlay.
 *
 * @param {EventTarget|null} target The event target to inspect.
 * @return {boolean} Whether the click should be ignored by outside-block handlers.
 */
export const isRsvpOverlayClick = ( target ) => {
	if ( ! target?.closest ) {
		return false;
	}

	return OVERLAY_CLICK_IGNORE_SELECTORS.some( ( selector ) => target.closest( selector ) );
};

/**
 * Notifies RSVP UI layers to dismiss local overlays (popovers, etc.).
 */
export const dispatchCloseRsvpOverlays = () => {
	document.dispatchEvent( new CustomEvent( RSVP_CLOSE_OVERLAYS_EVENT ) );
};

/**
 * Runs a callback when the RSVP block dispatches a close-overlays event.
 *
 * @param {Function} onClose Callback invoked when overlays should close.
 */
export const useListenForCloseOverlays = ( onClose ) => {
	useEffect( () => {
		document.addEventListener( RSVP_CLOSE_OVERLAYS_EVENT, onClose );

		return () => document.removeEventListener( RSVP_CLOSE_OVERLAYS_EVENT, onClose );
	}, [ onClose ] );
};

/**
 * Runs a callback when the RSVP block is deselected in the editor.
 *
 * @param {boolean}  isSelected Whether the block is currently selected.
 * @param {Function} onClose    Callback invoked when the block is deselected.
 */
export const useCloseOverlaysOnDeselect = ( isSelected, onClose ) => {
	const wasSelectedRef = useRef( isSelected );

	useEffect( () => {
		if ( wasSelectedRef.current && ! isSelected ) {
			onClose();
		}

		wasSelectedRef.current = isSelected;
	}, [ isSelected, onClose ] );
};
