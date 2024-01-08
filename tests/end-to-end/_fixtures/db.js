const { execSync } = require('child_process');

export class Db {
	/**
	 * Resets the database.
	 *
	 * @return {Promise<Db>} For chaining.
	 */
	async reset() {
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
		try {
			const output = execSync(
				`wp user meta set ${userId} ${metaKey} ${metaValue}`,
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
}
