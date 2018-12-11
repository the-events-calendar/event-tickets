/* eslint-disable max-len */

/**
 * External Dependencies
 */
import { select as wpSelect, subscribe } from '@wordpress/data';
import { call } from 'redux-saga/effects';
import { eventChannel } from 'redux-saga';
import { some } from 'lodash';

/**
 * Internal dependencies
 */
import { editor } from '@moderntribe/common/data';

/*
 * Determines if current post is a tribe event
 * @export
 * @returns {Boolean} bool
 */
export function* isTribeEventPostType() {
	const postType = yield call( [ wpSelect( 'core/editor' ), 'getEditedPostAttribute' ], 'type' );
	return postType === editor.EVENT;
}

/**
 * Creates event channel subscribing to WP editor state when saving post
 *
 * @returns {Function} Channel
 */
export function createWPEditorSavingChannel() {
	return eventChannel( emit => {
		const wpEditor = wpSelect( 'core/editor' );

		const predicates = [
			() => wpEditor.isSavingPost() && ! wpEditor.isAutosavingPost(),
		];

		// Returns unsubscribe function
		return subscribe( () => {
			// Only emit when truthy
			if ( some( predicates, fn => fn() ) ) {
				emit( true ); // Emitted value is insignificant here, but cannot be left undefined
			}
		} );
	} );
}
