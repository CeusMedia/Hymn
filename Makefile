export PWD	:= $(shell pwd)

help:
	@echo "make create-phar [MODE=(prod|dev)]"

create: create-phar

create-phar:
	@test -f hymn.phar && rm hymn.phar || true
	@php build/create.php --mode=${MODE} && chmod +x hymn.phar || true

create-phar-dev:
	@test -f hymn.phar && rm hymn.phar || true
	$(MAKE) -s create-phar MODE=dev

install: uninstall create-phar
	@echo "Installing hymn to /usr/local/bin"
	@sudo cp hymn.phar /usr/local/bin/hymn

install-link: uninstall create-phar unlink link

link: unlink
	@echo "Installing hymn symlink to /usr/local/bin"
	@sudo ln -sf $(shell pwd)/hymn.phar /usr/local/bin/hymn

uninstall:
	@test -f /usr/local/bin/hymn && echo "Removing hymn symlink in /usr/local/bin" || true
	@test -f /usr/local/bin/hymn && sudo rm -f /usr/local/bin/hymn || true

unlink:
	@test -f /usr/local/bin/hymn && echo "Removing hymn symlink in /usr/local/bin" || true
	@test -f /usr/local/bin/hymn && sudo rm /usr/local/bin/hymn || true

test-units:
	@phpunit

test-syntax:
	@echo "Checking syntax..."
#	@find src/classes -type f -print0 | xargs -0 -n1 xargs php -l
	@hymn test-syntax -r src/classes && echo "Result: OK" || echo "Result: FAILED"

update:
	@git fetch && git rebase && $(MAKE) -s create-phar
