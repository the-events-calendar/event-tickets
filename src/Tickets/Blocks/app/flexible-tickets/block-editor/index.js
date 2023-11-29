/**
 * The main bundle for the Flexible Tickets feature in Block Editor context.
 */

import './hooks/filters.js';
import './update-control.js';

// Discovery.
import {
	subscribe as wpDataSubscribe,
	select as wpDataSelect,
} from '@wordpress/data';

let wasSavingMetaBoxes = null;
wpDataSubscribe(function () {
	const isSavingMetaBoxes =
		wpDataSelect('core/edit-post').isSavingMetaBoxes();

	if (wasSavingMetaBoxes === null) {
		// Initialize the saving metaboxes state.
		wasSavingMetaBoxes = isSavingMetaBoxes;
		return;
	}

	if (wasSavingMetaBoxes !== isSavingMetaBoxes) {
		if (!isSavingMetaBoxes) {
			// The metaboxes have finished saving.
			console.log('The metaboxes have finished saving.');
		} else {
			// The metaboxes are saving.
			console.log('The metaboxes are saving.');
		}
	}
	wasSavingMetaBoxes = isSavingMetaBoxes;
});
