/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { markTicketChanged } from './common-store-bridge';
import { getFormRule } from './rule';
import { STORE_NAME } from './store/constants';

/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( './window-kinds' ).BlockWindowKind} BlockWindowKind */

/**
 * @typedef {Object} WindowDraft
 *
 * @property {SaleWindowRule|null|undefined}    rule     The ticket's draft rule of the window.
 * @property {SaleWindowRule}                   formRule The rule the options show.
 * @property {function( string, Object ): void} onChange Called with the boundary, `start` or `end`, and its changed
 *                                                       values.
 */

/**
 * Reads and edits a ticket block's draft rule of one window.
 *
 * A new rule's defaults are kept as the draft at once, so the ticket is saved with them as the classic editor saves its
 * form; a window saved without a rule keeps no draft until the admin changes an option.
 *
 * @since TBD
 *
 * @param {string}          clientId The client ID of the ticket block.
 * @param {BlockWindowKind} kind     The window kind.
 *
 * @return {WindowDraft} The draft, the rule the options show and the change handler.
 */
export function useWindowDraft( clientId, kind ) {
	const rule = useSelect( ( select ) => select( STORE_NAME ).getDraftRule( clientId, kind ), [ clientId, kind ] );
	const { setDraftRule } = useDispatch( STORE_NAME );
	const formRule = getFormRule( rule, kind );

	useEffect( () => {
		if ( undefined !== rule ) {
			return;
		}

		setDraftRule( clientId, getFormRule( rule, kind ), kind );

		if ( kind.marksNewRuleAsChange ) {
			markTicketChanged( clientId );
		}
	}, [ clientId, kind, rule, setDraftRule ] );

	const onChange = ( name, changes ) => {
		setDraftRule( clientId, { ...formRule, [ name ]: { ...formRule[ name ], ...changes } }, kind );

		// The legacy dashboard re-checks its Create or Update button, which reads this draft, only on a legacy store change.
		markTicketChanged( clientId );
	};

	return { rule, formRule, onChange };
}
