/**
 * When a window error found while the admin edits the window shows, the same in both editors.
 *
 * @since TBD
 */

/**
 * How long, in milliseconds, the admin stops typing before a new error shows.
 *
 * @since TBD
 *
 * @type {number}
 */
export const TYPING_SETTLE_DELAY = 800;

/**
 * Returns the error a window shows while the admin edits it.
 *
 * A new error waits until the admin stops typing, so a number half typed, or a window whose other end is still to be
 * changed, is not called out. An error already shown follows every keystroke, so it clears as soon as the window is
 * fixed.
 *
 * @since TBD
 *
 * @template T
 *
 * @param {T|string|null} shown    The error the window shows, or a falsy value when it shows none.
 * @param {T|null}        error    The error the window has now, or `null`.
 * @param {boolean}       isTyping Whether the admin is typing in a window field.
 *
 * @return {T|null} The error to show, or `null`.
 */
export function getShownError( shown, error, isTyping ) {
	return isTyping && ! shown ? null : error;
}

/**
 * Creates a tracker of whether the admin is typing in a window field.
 *
 * @since TBD
 *
 * @param {function(): void} onSettle Called once the admin stops typing for the settle delay.
 *
 * @return {{type: function(): void, settle: function(): void, isTyping: function(): boolean}} The tracker: `type()` on
 *         each keystroke, `settle()` once the edit is done, as on an option picked.
 */
export function createTypingTracker( onSettle ) {
	let timer = null;

	const settle = () => {
		clearTimeout( timer );
		timer = null;
	};

	return {
		type() {
			settle();
			timer = setTimeout( () => {
				timer = null;
				onSettle();
			}, TYPING_SETTLE_DELAY );
		},
		settle,
		isTyping: () => null !== timer,
	};
}
