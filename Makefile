SHELL := /bin/bash

tests:
	sudo symfony console doctrine\:fixtures\:load \-\n
	sudo symfony run bin/phpunit
.PHONY: tests