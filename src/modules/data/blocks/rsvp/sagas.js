/**
 * External Dependencies
 */
import { fork } from 'redux-saga/effects';

/**
 * Internal dependencies
 */
import sharedWatchers from '../rsvp-shared/sagas';
import headerImageWatchers from './header-image-sagas';
import postSaveWatchers from './post-save-sagas';

export {
	fetchRSVPHeaderImage,
	updateRSVPHeaderImage,
	deleteRSVPHeaderImage,
} from './header-image-sagas';
export { saveRSVPWithPostSave } from './post-save-sagas';

export default function* watchers() {
	yield fork( sharedWatchers );
	yield fork( headerImageWatchers );
	yield fork( postSaveWatchers );
}
