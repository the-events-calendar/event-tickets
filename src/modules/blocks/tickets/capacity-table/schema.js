import PropTypes from 'prop-types';

export default PropTypes.arrayOf(
	PropTypes.shape( {
		title: PropTypes.string,
		capacity: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
	} ),
)

export const getValues = ( items ) => (
	items.reduce( ( accumulator, item ) => {
		if ( item.title ) {
			accumulator.names.push( item.title );
		}
		const capacity = parseInt( item.capacity, 10 );
		if ( 'capacity' in item && ! isNaN( capacity ) ) {
			accumulator.total += capacity;
		}
		return accumulator;
	}, { names: [], total: 0 } )
)
