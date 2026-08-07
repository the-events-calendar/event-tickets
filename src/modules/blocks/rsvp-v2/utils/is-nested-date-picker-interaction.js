import {
	isAnyDatePickerOpen,
	isDatePickerInteractionPending,
} from '@moderntribe/common/utils/date-picker-popover-state';

export const DATE_PICKER_FOCUS_OUTSIDE_IGNORE_SELECTORS = [
	'.tribe-editor__date-input__container',
	'.tribe-editor__date-input',
	'.tribe-editor__date-input__popover',
	'.rdp',
].join( ', ' );

const DATE_PICKER_SELECTORS = DATE_PICKER_FOCUS_OUTSIDE_IGNORE_SELECTORS.split( ', ' );

/**
 * @param {Element|EventTarget|null|undefined} node A DOM node to inspect.
 * @return {boolean} Whether the node is inside a date-picker UI.
 */
const isDatePickerNode = ( node ) => {
	if ( ! node?.closest ) {
		return false;
	}

	return DATE_PICKER_SELECTORS.some( ( selector ) => node.closest( selector ) );
};

/**
 * Returns true when a dismiss event originated from a nested date-picker UI.
 *
 * @param {Event|undefined} event The dismiss event, if available.
 * @return {boolean} Whether the interaction should be ignored by parent popovers.
 */
export const isNestedDatePickerInteraction = ( event ) => {
	if ( isDatePickerInteractionPending() || isAnyDatePickerOpen() ) {
		return true;
	}

	const candidates = [ event?.target, event?.relatedTarget, document.activeElement ];

	return candidates.some( isDatePickerNode );
};
