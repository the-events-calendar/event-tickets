import latest from 'moment-timezone/data/packed/latest.json';
import zoneData from '@tec/tickets/relative-sale-dates/zone-data/zone-data.json';

describe( 'zone data', () => {
	it( 'should be built from the installed moment-timezone, so a dependency update does not leave it behind', () => {
		// Rebuild it with `npm run build:relative-sale-dates-zone-data`.
		expect( zoneData.version ).toBe( latest.version );
	} );
} );
