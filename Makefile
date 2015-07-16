.PHONY: install

install:
	composer install

test:
	./console --quiet
