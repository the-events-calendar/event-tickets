const { execSync } = require('child_process');

export class Db {
	/**
	 * Resets the database.
	 *
	 * @return {Promise<Db>} For chaining.
	 */
	async reset() {
		if (process.env.SKIP_DB_RESET) {
			process.stdout.write('[Db.reset] SKIPPED (SKIP_DB_RESET set)\n');
			return;
		}
		try {
			const output = execSync('wp db reset --yes', {
				stdio: [null, null, null],
			});
			process.stdout.write(`[Db.reset] ${output}`);
		} catch (error) {
			process.stdout.write(`[Db.reset]: ${error.message}`);
			process.exit(1);
		}
	}

	/**
	 * Loads a dump into the database.
	 *
	 * @param {string} dumpFile The dump file path, relative to the `tests/_data` directory, without the `.sql` extension.
	 *
	 * @return {Promise<Db>} For chaining.
	 */
	async loadDump(dumpFile) {
		if (process.env.SKIP_DB_RESET) {
			process.stdout.write(`[Db.loadDump] SKIPPED (SKIP_DB_RESET set)\n`);
			return;
		}
		try {
			const output = execSync(
				`wp db import tests/_data/${dumpFile}.sql`,
				{
					stdio: [null, null, null],
				}
			);
			process.stdout.write(`[Db.loadDump] ${output}`);
		} catch (error) {
			process.stdout.write(`[Db.loadDump]: ${error.message}`);
			process.exit(1);
		}
	}

	async updateUserMeta(userId, metaKey, metaValue) {
		// Try WP-CLI first (works in CI/Docker environments).
		try {
			const output = execSync(
				`wp user meta set ${userId} ${metaKey} ${metaValue}`,
				{ stdio: [null, null, null] }
			);
			process.stdout.write(`[Db.updateUserMeta] ${output}`);
			return;
		} catch {
			// WP-CLI failed — try direct MySQL if local env vars are set.
		}

		// Direct MySQL fallback for local dev environments (e.g. MAMP).
		const host = process.env.LOCAL_DB_HOST;
		const user = process.env.LOCAL_DB_USER;
		const pass = process.env.LOCAL_DB_PASS;
		const name = process.env.LOCAL_DB_NAME;
		const sock = process.env.LOCAL_DB_SOCKET;

		if (!host && !sock) {
			process.stdout.write(`[Db.updateUserMeta] SKIPPED — set LOCAL_DB_* env vars for local MySQL\n`);
			return;
		}

		try {
			const sockFlag = sock ? ` -d mysqli.default_socket=${sock} -d pdo_mysql.default_socket=${sock}` : '';
			const php = `php${sockFlag} -r '
$c = new mysqli("${host || "localhost"}", "${user}", "${pass}", "${name}", null, ${sock ? `"${sock}"` : 'null'});
$c->query("DELETE FROM wp_usermeta WHERE user_id=${userId} AND meta_key=\\\\"${metaKey}\\\\");
$c->query("INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES (${userId}, \\\\"${metaKey}\\\\", ${metaValue})");
echo "OK";
'`;
			const output = execSync(php, { stdio: [null, null, null] });
			process.stdout.write(`[Db.updateUserMeta] ${output}`);
		} catch (error) {
			process.stdout.write(`[Db.updateUserMeta]: ${error.message}\n`);
		}
	}
}
