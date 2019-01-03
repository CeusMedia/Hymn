export PWD	:= $(shell pwd)


help:
#	@echo "make create-phar [MODE=(prod|dev)]"
	@cat src/locales/en/make.txt

create: create-phar

create-phar:
	@test -f hymn.phar && rm hymn.phar || true
	@php build/create.php --mode=${MODE} && chmod +x hymn.phar || true

create-phar-dev:
	@test -f hymn.phar && rm hymn.phar || true
	@$(MAKE) -s create-phar MODE=dev

install: install-link

install-copy: uninstall unlink
	@echo "Installing hymn to /usr/local/bin"
	@sudo cp $(shell pwd)/hymn.phar /usr/local/bin/hymn

install-link: uninstall
	@echo "Installing hymn symlink to /usr/local/bin"
	@sudo ln -sf $(shell pwd)/hymn.phar /usr/local/bin/hymn

uninstall:
	@test -f /usr/local/bin/hymn && echo "Removing hymn in /usr/local/bin" && sudo rm -f /usr/local/bin/hymn || true
	@test -l /usr/local/bin/hymn && echo "Removing hymn symlink in /usr/local/bin" && sudo rm /usr/local/bin/hymn || true

test-units:
	@phpunit

test-syntax:
	@echo "Checking syntax..."
#	@find src/classes -type f -print0 | xargs -0 -n1 xargs php -l
	@hymn test-syntax -r src/classes && echo "Result: OK" || echo "Result: FAILED"

update:
	@echo "Currently installed: \c" && hymn version
	@git fetch && git checkout hymn.phar && touch stashing && git stash --include-untracked -q && git rebase && git stash pop -q && rm stashing && $(MAKE) -s create-phar
