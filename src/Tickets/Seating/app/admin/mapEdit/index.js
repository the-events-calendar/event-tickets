import './style.pcss';
import { onReady } from '../../utils';
import { initServiceIframe, getIframeElement, handleResize } from '../../service/iframe';
import { registerAction, INBOUND_SET_ELEMENT_HEIGHT } from '../../service/api';

/**
 * Initializes iframe and the communication with the service.
 *
 * @since 5.16.0
 *
 * @param {HTMLDocument|null} dom The document to use to search for the iframe element.
 *
 * @return {Promise<void>} A promise that resolves when the iframe is initialized.
 */
export async function init( dom ) {
	dom = dom || document;

	registerAction( INBOUND_SET_ELEMENT_HEIGHT, ( data ) => handleResize( data, dom ) );

	await initServiceIframe( getIframeElement( dom ) );
}
onReady( () => {
	init( document );
} );
