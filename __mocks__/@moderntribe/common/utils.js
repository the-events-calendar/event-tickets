const globals = {
	tecDateSettings: jest.fn( () => ( { datepickerFormat: 'Y-m-d' } ) ),
	iacVars: jest.fn( () => ( {} ) ),
	tickets: jest.fn( () => ( {
		end_sale_buffer_duration: 2,
		end_sale_buffer_years: 1,
	} ) ),
	priceSettings: jest.fn( () => ( {} ) ),
	settings: jest.fn( () => ( {} ) ),
};

const time = {
	toSeconds: jest.fn( () => 0 ),
	fromSeconds: jest.fn( () => '00:00' ),
};

const moment = {
	TIME_FORMAT: 'HH:mm:ss',
	toMoment: jest.fn( () => require( 'moment' )() ),
	toDate: jest.fn( () => '' ),
	toDatabaseDate: jest.fn( () => '' ),
	toDateTime: jest.fn( () => '' ),
	toTime: jest.fn( () => '' ),
	toDatabaseTime: jest.fn( () => '' ),
	toFormat: jest.fn( ( format ) => format ),
	setTimeInSeconds: jest.fn( ( m ) => m ),
};

const string = {
	isTruthy: jest.fn( ( val ) => !! val ),
};

const api = {
	wpREST: jest.fn(),
};

module.exports = { globals, time, moment, string, api };
