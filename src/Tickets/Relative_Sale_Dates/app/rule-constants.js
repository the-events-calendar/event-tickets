/**
 * The modes, anchors, units and range of a window rule, as the server's `Rule` and `Boundary` define them.
 *
 * @since TBD
 */

export const MODE_DEFAULT = 'default';
export const MODE_RELATIVE = 'relative';
export const MODE_SPECIFIC = 'specific';
export const MODE_NOW = 'now';

export const ANCHOR_START = 'start';
export const ANCHOR_END = 'end';

/*
 * WordPress defines these in PHP only; `@wordpress/date` keeps its own copies private. The names match the PHP
 * constants the server stores each unit as.
 */
const MINUTE_IN_SECONDS = 60;
const HOUR_IN_SECONDS = 60 * MINUTE_IN_SECONDS;
const DAY_IN_SECONDS = 24 * HOUR_IN_SECONDS;
const WEEK_IN_SECONDS = 7 * DAY_IN_SECONDS;

export const UNIT_MINUTES = MINUTE_IN_SECONDS;
export const UNIT_HOURS = HOUR_IN_SECONDS;
export const UNIT_DAYS = DAY_IN_SECONDS;
export const UNIT_WEEKS = WEEK_IN_SECONDS;

export const MIN_VALUE = 1;
export const MAX_VALUE = 60;

/**
 * The highest number of days or weeks a relative boundary of the sale price window accepts, as the server's
 * `Window_Kind::SALE_PRICE_MAX_VALUE` defines it.
 *
 * @since TBD
 *
 * @type {number}
 */
export const SALE_PRICE_MAX_VALUE = 30;
