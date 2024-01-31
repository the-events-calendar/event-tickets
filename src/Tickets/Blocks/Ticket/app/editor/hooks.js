import { store } from '@moderntribe/common/store';
import { actions } from '@moderntribe/tickets/data/blocks/ticket';
import { wpHooks } from '@moderntribe/common/utils/globals';

const filterBlockEdit = ( BlockEdit ) => ( props ) => {
	const { dispatch } = store;

	wp.element.useEffect( () => {
		const { name, clientId } = props;

		return () => {
			if ( name === 'tribe/tickets-item' ) {
				dispatch( actions.deleteTicket( clientId, false ) );
			}
		};
	}, [] );

	return wp.element.createElement(
		wp.element.Fragment,
		null,
		wp.element.createElement( BlockEdit, props ),
	);
};

/**
 * Filter to determine if a block was deleted using the delete block option,
 * also validates if its a ticket block, then call deleteTicket on unmount
 */
export const initHook = () => {
	wpHooks.addFilter(
		'editor.BlockEdit',
		'event-tickets',
		filterBlockEdit,
	);
};
