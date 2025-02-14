/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { couponSelectors as selectors } from './selectors';

/**
 *
 * @param {String} discount The discount value to update.
 */
const updateCouponDiscount = ( discount ) => {
	const couponValueElement = document.querySelector( selectors.appliedValue );

	// Use DOMParser to unescape the discount value
	const parser = new DOMParser();

	// Update the coupon value element with the unescaped discount value
	couponValueElement.textContent = parser.parseFromString(
		`<!doctype html><body>${ discount }`,
		'text/html'
	).body.textContent;
}

const hideInput = () => {
	const couponInputElement = document.querySelector( selectors.input );
	const couponInputLabelElement = document.querySelector( selectors.inputLabel );
	const couponApplyButtonElement = document.querySelector( selectors.applyButton );

	couponInputElement.style.display = 'none';
	couponInputLabelElement.style.display = 'none';
	couponApplyButtonElement.style.display = 'none';
}

const showInput = () => {
	const couponInputElement = document.querySelector( selectors.input );
	const couponInputLabelElement = document.querySelector( selectors.inputLabel );
	const couponApplyButtonElement = document.querySelector( selectors.applyButton );

	couponInputElement.style.display = 'block';
	couponInputLabelElement.style.display = 'block';
	couponApplyButtonElement.style.display = 'block';
}

const clearError = () => {
	const couponErrorElement = document.querySelector( selectors.errorMessage );

	couponErrorElement.textContent = '';
	couponErrorElement.attributes.style.display = 'none';
};
