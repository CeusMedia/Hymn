export PWD	:= $(shell pwd)
export PHP  :=/usr/bin/env php
#export PHP  :=/opt/plesk/php/8.1/bin/php


help: ## show this help
#	@echo "make create-phar [MODE=(prod|dev)]"
	@cat src/locales/en/make.txt
#	@-fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/:.*##/:/'

create: create-phar ## alias for create-phar


create-phar: ## create productive version of PHAR file locally
	@test -f hymn.phar && rm hymn.phar || true
	@${PHP} build/create.php --mode=${MODE} --php="${PHP}" && chmod +x hymn.phar || true

create-phar-dev: ## create development version of PHAR file locally
	@test -f hymn.phar && rm hymn.phar || true
	@$(MAKE) -s create-phar MODE=dev

install: install-link ## alias for install-link

install-copy: uninstall ## installs PHAR file in system (/usr/local/bin) by copy
	@echo "Installing hymn to /usr/local/bin"
	@sudo cp $(shell pwd)/hymn.phar /usr/local/bin/hymn

install-link: uninstall ## installs PHAR file in system (/usr/local/bin) by link
	@echo "Installing hymn symlink to /usr/local/bin"
	@sudo ln -sf $(shell pwd)/hymn.phar /usr/local/bin/hymn

uninstall:
	@test -h /usr/local/bin/hymn && echo "Removing hymn symlink in /usr/local/bin" && sudo rm /usr/local/bin/hymn || true
	@test -f /usr/local/bin/hymn && echo "Removing hymn in /usr/local/bin" && sudo rm -f /usr/local/bin/hymn || true

test-units:
	@./vendor/bin/phpunit --configuration=tool/phpunit.xml

test-syntax:
	@hymn test-syntax && echo "Result: OK" || echo "Result: FAILED"

test-syntax-verbose:
	@hymn test-syntax -vv && echo "Result: OK" || echo "Result: FAILED"

test-phpstan:
	XDEBUG_MODE=off @vendor/bin/phpstan analyse --configuration tool/phpstan.neon --xdebug || true

test-phpstan-save-baseline:
	XDEBUG_MODE=off @vendor/bin/phpstan analyse --configuration tool/phpstan.neon --generate-baseline tool/phpstan-baseline.neon || true

update:
	@echo "Currently installed: \c" && hymn version
	@git fetch && git checkout hymn.phar && touch stashing && git stash --include-untracked -q && git rebase && git stash pop -q && rm stashing && $(MAKE) -s create-phar
