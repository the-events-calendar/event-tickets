/**
 * Following an update that will commit the metaboxes values, update the
 * uneditable tickets in the Tickets block, if required.
 */

import { updateUneditableTickets } from '@moderntribe/tickets/data/blocks/ticket/actions';
import { store } from '@moderntribe/common/store';
import { onMetaBoxesUpdateCompleted } from '../metaboxes';

onMetaBoxesUpdateCompleted(() => store.dispatch(updateUneditableTickets()));
