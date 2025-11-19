### This is a reference (complete) MAKE file setup
### Remove the functionalities you don't need

.PHONY: help

## --- Mandatory variables ---

docker-compose=docker compose
main-container-name=app

help: ### Display available targets and their descriptions
	@echo "Usage: make [target]"
	@echo "Targets:"
	@awk 'BEGIN {FS = ":.*?### "}; /^[a-zA-Z_-]+:.*?### / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ""


## --- General ---

# General git commands
include vendor/team-mate-pro/make/git/MAKE_GIT_v1

# Docker
include vendor/team-mate-pro/make/docker/MAKE_DOCKER_v1

# Claude Code
include vendor/team-mate-pro/make/claude/MAKE_CLAUDE_v1

# --- Backend ---


# PHPCS
include vendor/team-mate-pro/make/phpcs/MAKE_PHPCS_v1

# PHPUNIT
include vendor/team-mate-pro/make/phpunit/MAKE_PHPUNIT_v1

# PHPSTAN
include vendor/team-mate-pro/make/phpstan/MAKE_PHPSTAN_v1

## --- Mandatory aliases ---

start: ### Full start and rebuild of the container
	$(docker-compose) build
	$(docker-compose) up -d

fast: ### Fast start already built containers
	$(docker-compose) up -d

stop: ### Stop all existing containers
	$(docker-compose) down

check: ### [c] Should run all mandatory checks that run in CI and CD process
	make phpcs
	make phpstan
	#make tests_unit

check_fast: ### [cf] Should run all mandatory checks that run in CI and CD process skipping heavy ones like functional tests
	make phpcs_fix
	make phpcs
	make phpstan
	#make tests_unit

fix: ### [f] Should run auto fix checks that run in CI and CD process
	make phpcs_fix

tests: ### [t] Run all tests defined in the project
	make tests_unit

## --- Project related scripts ---

c: check
cf: check_fast
f: fix
t: tests
