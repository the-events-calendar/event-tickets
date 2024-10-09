const envFilePath = __dirname + '/../../../.env.testing.slic';

// Just requiring the file will load the environment variables in it in the current process.
require('dotenv').config({
	path: envFilePath,
});

exports.adminUser = process.env.WP_ADMIN_USERNAME;
exports.adminPassword = process.env.WP_ADMIN_PASSWORD;
