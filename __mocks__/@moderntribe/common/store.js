const middlewares = {
	request: {
		actions: {
			wpRequest: jest.fn( ( options ) => ( { type: 'WP_REQUEST', payload: options } ) ),
		},
	},
};

module.exports = { middlewares };
