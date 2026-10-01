/**
 * Returns true when the block is in first-time RSVP creation mode.
 *
 * @param {Object}  state               Block state flags.
 * @param {boolean} state.created       Whether the RSVP has been saved.
 * @param {boolean} state.isAddEditOpen Whether the create form is open.
 * @return {boolean} True when creating a new RSVP.
 */
export const isCreating = ( { created, isAddEditOpen } ) => ! created && isAddEditOpen;

/**
 * Returns true when the block should show the saved RSVP summary view.
 *
 * @param {Object}  state               Block state flags.
 * @param {boolean} state.created       Whether the RSVP has been saved.
 * @param {boolean} state.isAddEditOpen Whether the create form is open.
 * @return {boolean} True when showing the saved summary.
 */
export const isSavedSummary = ( { created, isAddEditOpen } ) => created && ! isAddEditOpen;

/**
 * Returns true when inline edit affordances (pencil icons) should be shown.
 *
 * @param {Object}  state            Block state flags.
 * @param {boolean} state.created    Whether the RSVP has been saved.
 * @param {boolean} state.isSelected Whether the block is selected in the editor.
 * @return {boolean} True when edit affordances should be visible.
 */
export const showEditAffordances = ( { created, isSelected } ) => created && isSelected;
