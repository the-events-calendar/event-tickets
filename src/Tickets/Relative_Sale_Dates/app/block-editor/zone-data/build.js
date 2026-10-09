/* eslint-env node */
/**
 * Writes the zone data the Ticket block script loads onto WordPress's `moment.tz`, from the installed moment-timezone.
 *
 * Run it again whenever moment-timezone is updated: `npm run build:relative-sale-dates-zone-data`.
 *
 * @since TBD
 */
const fs = require( 'fs' );
const path = require( 'path' );
const moment = require( 'moment-timezone' );
require( 'moment-timezone/moment-timezone-utils' );
const latest = require( 'moment-timezone/data/packed/latest.json' );

/*
 * WordPress formats dates of any year with these zones, so the data keeps every clock change from 1970, as WordPress's
 * own data does, and runs well past the events tickets are sold for. Fixed years keep a rebuild's output the same.
 */
const START_YEAR = 1970;
const END_YEAR = 2051;

const data = {
	version: latest.version,
	zones: latest.zones.map( ( packed ) =>
		moment.tz.pack( moment.tz.filterYears( moment.tz.unpack( packed ), START_YEAR, END_YEAR ) )
	),
	links: latest.links,
	countries: [],
};

fs.writeFileSync( path.join( __dirname, 'zone-data.json' ), JSON.stringify( data ) + '\n' );
