SHELL :=/bin/bash

setup_env: .env
	# Sourcing the .env file will make sure we use, in the environment configuration, the same variables the tests are using.
	set -o allexport; source .env; set +o allexport;
	source dev/functions.sh

clone_required_plugins: setup_env
	git_clone_required_plugins
