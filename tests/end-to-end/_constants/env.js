// Load env vars from .env.testing.slic (CI default), but prefer
// values already set in the shell (process.env). This keeps local
// credentials out of tracked/disk files.

const fs = require('node:fs');

const slicEnvPath = __dirname + '/../../../.env.testing.slic';

// Load the slic env file (safe defaults for CI) — process.env takes precedence.
require('dotenv').config({ path: slicEnvPath });

exports.adminUser = process.env.WP_ADMIN_USERNAME || 'admin';
exports.adminPassword = process.env.WP_ADMIN_PASSWORD || 'password';
