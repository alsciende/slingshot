compose := docker-compose -f docker/dev/compose.yaml
php := $(compose) exec php

build:
	$(compose) build

up:
	docker image pull php:8.3-alpine
	$(compose) up -d

down:
	$(compose) down

reload: up down

clean:
	rm -rf vendor

install:
	$(php) composer install

shell:
	$(php) bash
