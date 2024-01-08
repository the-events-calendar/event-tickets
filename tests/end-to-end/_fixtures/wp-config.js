const { execSync } = require('child_process');

export class WPConfig {
	async deleteOrContinue(name) {
		try {
			const output = execSync(`wp config delete ${name}`, {
				stdio: [null, null, null],
			});
			process.stdout.write(`[WPConfig.deleteOrContinue] ${output}`);
		} catch (error) {
			process.stdout.write(
				`[WPConfig.deleteOrContinue] ${name} was not set in config file.`
			);
		}
	}
}
