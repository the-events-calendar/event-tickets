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
import {
	globals,
	moment as momentUtil,
} from '@moderntribe/common/utils';

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
 * Creates event channel subscribing to WP editor state when post type is loaded.
 * Used as post type is not available upon load in some cases, so some false negatives
 *
 * @returns {Function} Channel
 */
export function hasPostTypeChannel() {
	return eventChannel( emit => {
		const wpEditor = wpSelect( 'core/editor' );

		const predicates = [
			() => !! wpEditor.getEditedPostAttribute( 'type' ),
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

/**
 * Creates event channel subscribing to WP editor state when saving post
 *
 * @returns {Function} Channel
 */
export function createWPEditorSavingChannel() {
	return eventChannel( ( emit ) => {
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

/**
 * Creates event channel subscribing to WP editor state when not saving post
 *
 * @returns {Function} Channel
 */
export function createWPEditorNotSavingChannel() {
	return eventChannel( ( emit ) => {
		const wpEditor = wpSelect( 'core/editor' );

		const predicates = [
			() => ! ( wpEditor.isSavingPost() && ! wpEditor.isAutosavingPost() ),
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

/**
 * Create date objects used throughout sagas
 *
 * @export
 * @param {String} date datetime string
 * @returns {Object} Object of dates/moments
 */
export function* createDates( date ) {
	const { datepickerFormat } = yield call( [ globals, 'tecDateSettings' ] );
	const moment = yield call( momentUtil.toMoment, date );
	const currentDate = yield call( momentUtil.toDatabaseDate, moment );
	const dateInput = yield datepickerFormat
		? call( momentUtil.toDate, moment, datepickerFormat )
		: call( momentUtil.toDate, moment );
	const time = yield call( momentUtil.toDatabaseTime, moment );
	const timeInput = yield call( momentUtil.toTime, moment );

	return {
		moment,
		date: currentDate,
		dateInput,
		time,
		timeInput,
	};
}
