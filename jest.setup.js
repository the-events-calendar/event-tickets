/**
 * External dependencies
 */
import moment from 'moment-timezone';
import React from 'react';
import $ from 'jquery';
import renderer from 'react-test-renderer';

global.jQuery = $;
global.$ = $;
global.wp = {
	element: React,
	api: {},
	apiRequest: {},
	components: {},
	data: {},
	blockEditor: {},
	editor: {},
	hooks: {},
	i18n: {
		_x: (input) => input,
	},
};

global.renderer = renderer;

moment.tz.setDefault( 'UTC' );

import '@tec/tickets/seating/tests/_bootstrap.js';
