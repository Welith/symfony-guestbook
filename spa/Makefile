SHELL := /bin/bash

async_spa:
	sudo API_ENDPOINT=`symfony var:export SYMFONY_DEFAULT_ROUTE_URL --dir=..` symfony run -d --watch=webpack.config.js yarn encore dev --watch
.PHONY: async_spa

start_spa:
	sudo symfony server:start -d --passthru=index.html
.PHONY: start_spa