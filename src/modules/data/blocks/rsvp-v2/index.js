/**
 * V2 RSVP Data Layer
 *
 * Re-exports the shared RSVP data layer with V2-specific thunks.
 * This allows the V2 block to use the same Redux store structure
 * while making API calls to V2 endpoints.
 */

/**
 * Internal dependencies - Import from shared RSVP data layer.
 */
import { types, actions, selectors, sagas } from '../rsvp-shared';
import reducer from '../rsvp-shared/reducer';

/**
 * V2-specific thunks.
 */
import * as thunks from './thunks';

/**
 * V2 config utilities.
 */
import * as config from './config';

export default reducer;
export { types, actions, sagas, selectors, thunks, config };
