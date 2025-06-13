import { addFilter, addAction, didAction, doAction } from '@wordpress/hooks';
import renderFields from './functions/renderFields';
import { STORE_NAME, storeConfig } from './store';
import { WPDataRegistry } from '@wordpress/data/build-types/registry';
import { getRegistry } from '@tec/common/classy/store';

/**
 * Hook on the Classy application initialization to add Tickets store to the Classy registry.
 *
 * @since TBD
 *
 * @return {void} The Tickets store is registered.
 */
const registerTicketsStore = (): void => {
	( getRegistry() as WPDataRegistry ).registerStore( STORE_NAME, storeConfig );

	/**
	 * Fires after the Tickets store is registered and the Tickets Classy application is initialized.
	 *
	 * @since TBD
	 *
	 * @return {void} The Tickets store is registered.
	 */
	doAction( 'tec.classy.tickets.initialized' );
};

if ( didAction( 'tec.classy.initialized' ) ) {
	registerTicketsStore();
} else {
	addAction( 'tec.classy.initialized', 'tec.classy.tickets', registerTicketsStore );
}

// Hook on the Classy fields rendering logic to render the fields.
addFilter( 'tec.classy.render', 'tec.classy.tickets', renderFields );
