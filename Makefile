
create-phar:
	@echo "Creating hymn.phar"
	@php build/create.php
	@chmod +x hymn.phar

install: uninstall create-phar
	@echo "Installing hymn to /usr/local/bin"
	@sudo mv hymn.phar /usr/local/bin/hymn

uninstall:
	@sudo rm -f /usr/local/bin/hymn
