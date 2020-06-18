SHELL := /bin/bash

tests:
	sudo symfony console doctrine\:fixtures\:load \-\n
	sudo symfony run bin/phpunit
.PHONY: tests

async:
	sudo symfony run -d yarn encore dev --watch
	sudo symfony run -d --watch=config,src,templates,vendor symfony console messenger:consume async
.PHONY: env

start:
	sudo docker-compose up -d
	sudo symfony serve
.PHONY: start