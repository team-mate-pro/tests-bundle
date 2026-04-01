### This is a reference (complete) MAKE file setup
### Remove the functionalities you don't need

.PHONY: help

## --- Mandatory variables ---

docker-compose=docker compose
main-container-name=app
vendor-dir=vendor/team-mate-pro/make/

help: ### Display available targets and their descriptions
	@echo "Usage: make [target]"
	@echo "Targets:"
	@awk 'BEGIN {FS = ":.*?### "}; /^[a-zA-Z_-]+:.*?### / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ""


## --- Shared includes (optional - won't fail if package not installed) ---

-include $(vendor-dir)git/MAKE_GIT_v1
-include $(vendor-dir)docker/MAKE_DOCKER_v1
-include $(vendor-dir)claude/MAKE_CLAUDE_v1
-include $(vendor-dir)phpunit/MAKE_PHPUNIT_v1
-include $(vendor-dir)phpstan/MAKE_PHPSTAN_v1
-include $(vendor-dir)phpcs/MAKE_PHPCS_v1

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
	make tests_unit

check_fast: ### [cf] Should run all mandatory checks that run in CI and CD process skipping heavy ones like functional tests
	make phpcs
	make phpstan
	make tests_unit

fix: ### [f] Should run auto fix checks that run in CI and CD process
	make phpcs_fix

tests: ### [t] Run all tests defined in the project
	make tests_unit

## --- Project related scripts ---

c: check
cf: check_fast
f: fix
t: tests
