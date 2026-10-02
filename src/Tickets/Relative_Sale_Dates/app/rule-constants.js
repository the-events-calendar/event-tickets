/**
 * The modes, anchors, units and range of a sales window rule, as the server's `Rule` and `Boundary` define them.
 *
 * @since TBD
 */

export const MODE_DEFAULT = 'default';
export const MODE_RELATIVE = 'relative';
export const MODE_SPECIFIC = 'specific';

// Only the start of a sale price window takes it, as the server's `Sale_Price_Rule` defines it.
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
