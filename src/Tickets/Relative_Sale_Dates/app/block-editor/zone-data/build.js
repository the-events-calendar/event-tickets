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

const START_YEAR = new Date().getUTCFullYear() - 1;
const END_YEAR = START_YEAR + 26;

const zones = new Map(
	latest.zones.map( ( packed ) => {
		const zone = moment.tz.unpack( packed );

		return [ zone.name, zone ];
	} )
);

/*
 * A link is written as a zone of its own: WordPress's older data can hold the same name as a zone, which `moment.tz`
 * reads before any link.
 */
latest.links.forEach( ( link ) => {
	const [ target, alias ] = link.split( '|' );

	zones.set( alias, { ...zones.get( target ), name: alias } );
} );

const data = {
	version: latest.version,
	zones: [ ...zones.values() ].map( ( zone ) =>
		moment.tz.pack( moment.tz.filterYears( zone, START_YEAR, END_YEAR ) )
	),
	links: [],
	countries: [],
};

fs.writeFileSync( path.join( __dirname, 'zone-data.json' ), JSON.stringify( data ) + '\n' );
