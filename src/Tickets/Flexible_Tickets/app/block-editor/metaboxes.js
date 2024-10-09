import {
	subscribe as wpDataSubscribe,
	select as wpDataSelect,
} from '@wordpress/data';

/*
 * Local state: when it changes value then the metaboxes have either started
 * or finished saving.
 *
 * @type {boolean|null}
 */
let wasSavingMetaBoxes = null;

const onMetaBoxesUpdateCompletedCallbacks = [];

// Unsubscribe function; will start as a no-op to keep with the expected type.
let unsubscribeFromMetaBoxesUpdates = () => {};

const updateUneditableTicketsOnMetaboxUpdate = function () {
	const isSavingMetaBoxes =
		wpDataSelect('core/edit-post').isSavingMetaBoxes();

	if (wasSavingMetaBoxes === null) {
		// Initialize the saving metaboxes state.
		wasSavingMetaBoxes = isSavingMetaBoxes;
		return;
	}

	if (wasSavingMetaBoxes !== isSavingMetaBoxes) {
		if (!isSavingMetaBoxes) {
			// The metaboxes have finished saving: update the uneditable tickets.

			// Avoid infinite loop: unsubscribe, update, subscribe.
			unsubscribeFromMetaBoxesUpdates();
			onMetaBoxesUpdateCompletedCallbacks.forEach((callback) =>
				callback()
			);
			unsubscribeFromMetaBoxesUpdates = subscribeToMetaBoxesUpdates();
		} else {
			// The metaboxes are saving: no-op.
		}
	}
	wasSavingMetaBoxes = isSavingMetaBoxes;
};

/**
 * Subscribe to updates to the metaboxes saving state.
 *
 * @return {Function} The unsubscribe function.
 */
const subscribeToMetaBoxesUpdates = () =>
	wpDataSubscribe(updateUneditableTicketsOnMetaboxUpdate);

// Start the subscription and replace the no-op unsubscribe function with the real one.
unsubscribeFromMetaBoxesUpdates = subscribeToMetaBoxesUpdates();

export const onMetaBoxesUpdateCompleted = (callback) => {
	onMetaBoxesUpdateCompletedCallbacks.push(callback);
	const index = onMetaBoxesUpdateCompletedCallbacks.length - 1;

	return () => onMetaBoxesUpdateCompletedCallbacks.splice(index, 1);
};
