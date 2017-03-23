export PWD	:= $(shell pwd)

create: create-phar

create-phar:
	@echo "Creating hymn.phar @ Production"
	@test -f hymn.phar && rm hymn.phar || true
	@php build/create.php
	@chmod +x hymn.phar

create-phar-dev:
	@echo "Creating hymn.phar @ Development"
	@test -f hymn.phar && rm hymn.phar || true
	@php build/create.php dev
	@chmod +x hymn.phar


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
	echo "Checking syntax..."
#	@find src/classes -type f -print0 | xargs -0 -n1 xargs php -l
	@hymn test -r src/classes && echo "Result: OK" || echo "Result: FAILED" 

