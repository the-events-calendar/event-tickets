const globals = {
	tecDateSettings: jest.fn( () => ( { datepickerFormat: 'Y-m-d' } ) ),
	iacVars: jest.fn( () => ( {} ) ),
	tickets: jest.fn( () => ( {
		end_sale_buffer_duration: 2,
		end_sale_buffer_years: 1,
	} ) ),
	priceSettings: jest.fn( () => ( {} ) ),
	settings: jest.fn( () => ( {} ) ),
	restNonce: jest.fn( () => ( {} ) ),
};

const time = {
	toSeconds: jest.fn( () => 0 ),
	fromSeconds: jest.fn( () => '00:00' ),
};

const moment = {
	TIME_FORMAT: 'HH:mm:ss',
	toMoment: jest.fn( ( date ) => require( 'moment' )( date ) ),
	toDate: jest.fn( ( date, format ) => {
		if ( ! date ) {
			return '';
		}
		return format ? date.format( format ) : date.format( 'YYYY-MM-DD' );
	} ),
	toDatabaseDate: jest.fn( ( date, format ) => {
		if ( ! date ) {
			return '';
		}
		return format ? date.format( format ) : date.format( 'YYYY-MM-DD' );
	} ),
	toDateTime: jest.fn( ( date ) => date ? date.format( 'YYYY-MM-DD HH:mm:ss' ) : '' ),
	toTime: jest.fn( ( date, format ) => {
		if ( ! date ) {
			return '';
		}
		return format ? date.format( format ) : date.format( 'h:mm a' );
	} ),
	toDatabaseTime: jest.fn( ( date ) => date ? date.format( 'HH:mm:ss' ) : '' ),
	toFormat: jest.fn( ( format ) => {
		if ( ! format ) {
			return format;
		}
		const replacements = {
			d: 'DD',
			D: 'ddd',
			j: 'D',
			l: 'dddd',
			N: 'E',
			S: 'o',
			w: 'e',
			z: 'DDD',
			W: 'W',
			F: 'MMMM',
			m: 'MM',
			M: 'MMM',
			n: 'M',
			o: 'YYYY',
			Y: 'YYYY',
			y: 'YY',
			a: 'a',
			A: 'A',
			g: 'h',
			G: 'H',
			h: 'hh',
			H: 'HH',
			i: 'mm',
			s: 'ss',
			u: 'SSS',
			U: 'X',
		};
		const chars = format.split( '' );
		const converted = chars.map( ( c ) => replacements[ c ] !== undefined ? replacements[ c ] : c );
		return converted.join( '' );
	} ),
	toDatePicker: jest.fn( ( date, format ) => {
		if ( ! date ) {
			return '';
		}
		return format ? date.format( format ) : date.format( 'YYYY-MM-DDTHH:mm:ss' );
	} ),
	setTimeInSeconds: jest.fn( ( m ) => m ),
};

const string = {
	isTruthy: jest.fn( ( val ) => !! val ),
};

const api = {
	wpREST: jest.fn(),
};

const createChainablePropType = () => {
	const validator = jest.fn( () => null );
	validator.isRequired = jest.fn();
	return validator;
};

const TribePropTypes = {
	timeFormat: createChainablePropType(),
	nullType: createChainablePropType(),
};

module.exports = { globals, time, moment, string, api, TribePropTypes };
