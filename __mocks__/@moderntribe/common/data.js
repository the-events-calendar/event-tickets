const plugins = {
	constants: {
		TICKETS_PLUS: 'event-tickets-plus/event-tickets-plus.php',
	},
	selectors: {
		hasPlugin: jest.fn( () => jest.fn( () => false ) ),
	},
};

const editor = {
	EVENT: 'tribe_events',
	VENUE: 'tribe_venue',
	ORGANIZER: 'tribe_organizer',
};

module.exports = { plugins, editor };
