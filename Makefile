build:
	docker-compose -f docker/dev/compose.yaml build

up:
	docker image pull php:8.3-alpine
	docker-compose -f docker/dev/compose.yaml up

clean:
	rm -rf vendor

install:
	docker-compose -f docker/dev/compose.yaml exec php composer install

shell:
	docker-compose -f docker/dev/compose.yaml exec php bash

