const plugins = {
	constants: {
		TICKETS_PLUS: 'event-tickets-plus/event-tickets-plus.php',
	},
	selectors: {
		hasPlugin: jest.fn( () => jest.fn( () => false ) ),
	},
};

module.exports = { plugins };
