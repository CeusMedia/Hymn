export PWD	:= $(shell pwd)

create-phar:
	@echo "Creating hymn.phar"
	@php build/create.php
	@chmod +x hymn.phar

install: uninstall create-phar
	@echo "Installing hymn to /usr/local/bin"
#	@sudo mv hymn.phar /usr/local/bin/hymn
	@sudo cp hymn.phar /usr/local/bin/hymn

install-link: uninstall create-phar
	@echo "Installing hymn symlink to /usr/local/bin"
	@sudo ln -sf $(shell pwd)/hymn.phar /usr/local/bin/hymn


uninstall:
	@sudo rm -f /usr/local/bin/hymn


test-syntax:
	@find src/classes -type f -print0 | xargs -0 -n1 xargs php -l
