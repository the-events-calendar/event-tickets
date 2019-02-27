SHELL :=/bin/bash

setup_env: .env dev/functions.sh
	# Sourcing the .env file will make sure we use, in the environment configuration, the same variables the tests are using.
	set -o allexport; source .env; set +o allexport;
	# Load the common functions we'll use in the file.
	source dev/functions.sh

clone_required_plugins: setup_env
	git_clone_required_plugins
