
PATH_FRONTEND		= %appName%
PATH_BACKEND		= admin

PERMS_DIR			= 750
PERMS_FILE			= 640

PWD					= $(shell pwd)
DATE				= `date +'%Y-%m-%d'`

MODS_CM_PUBLIC		= vendor/ceus-media/hydrogen-modules/
MODS_CM_NONFREE		= vendor/ceus-media/hydrogen-modules-nonfree/
MODS_VENDOR_NAME	= vendor/vendor/name/
MODS_PROJECT		= modules/

#install: install-complete-and-empty


install-complete-and-empty: .fallback-to-dev
	@echo "Installing packages ..." && composer install --no-autoloader
	@$(MAKE) -s configure
	@echo "Installing modules:" && hymn app-install -f
	@test -d ${PATH_BACKEND} && $(MAKE) -s .backend-install || true
	@test -d ${PATH_BACKEND} && $(MAKE) -s .backend-secure || true
	@echo "Setting permissions ..." && $(MAKE) -s set-permissions
	@echo "Checking packages ..." && composer update
#	@echo "Generating autoloaders ..." && composer dump-autoload -o
	@$(MAKE) -s enable-clamav

install-files-only: .fallback-to-dev
	@echo "Installing packages ..." && composer install --no-dev --no-autoloader
	@$(MAKE) -s configure
	@echo "Installing modules:" && hymn app-install -f --db=no
	@test -d ${PATH_BACKEND} && $(MAKE) -s .backend-install || true
	@test -d ${PATH_BACKEND} && $(MAKE) -s .backend-secure || true
	@echo "Setting permissions ..." && $(MAKE) -s set-permissions
	@echo "Checking packages ..." && composer update --no-dev
#	@echo "Generating autoloaders ..." && composer dump-autoload -o

install-files-and-load-database: .fallback-to-dev
	@echo "Installing packages ..." && composer install --no-autoloader
	@$(MAKE) -s configure
	@echo "Installing modules:" && hymn app-install -f --db=no
	@test -d ${PATH_BACKEND} && $(MAKE) -s .backend-install || true
#	@test -d ${PATH_BACKEND} && $(MAKE) -s .backend-secure || true
	@echo "Setting permissions ..." && $(MAKE) -s set-permissions
	@echo "Checking packages ..." && composer update
#	@echo "Generating autoloaders ..." && composer dump-autoload -o
	@echo "Importing database dump ..." && hymn database-load
	@$(MAKE) -s enable-clamav

build: .fallback-to-dev
	@echo "Installing packages ..." && composer install --no-dev --no-autoloader
	@$(MAKE) -s set-install-type-copy
	@$(MAKE) -s configure
	@echo "Installing modules:" && hymn app-install -f --db=no
	@test -d ${PATH_BACKEND} && $(MAKE) -s .backend-install || true
#	@test -d ${PATH_BACKEND} && $(MAKE) -s .backend-secure || true
	@echo "Checking packages ..." && composer update --no-dev
	@echo "Generating autoloaders ..." && composer dump-autoload
#	@echo "Creating database import ..." && hymn database-load && hymn database-dump install.sql
	@test -f config/doc.xml && echo "Generation docs ..." && $(MAKE) -s doc || true
	@$(MAKE) -s set-rights && cp .htaccess.dist .htaccess
	@$(MAKE) -s .build-cleanup
	@echo "Creating archive files ..."
	@cd .. && zip -r -q ${PATH_FRONTEND}_${DATE}.zip ${PATH_FRONTEND}
	@cd .. && tar -czf ${PATH_FRONTEND}_${DATE}.tgz ${PATH_FRONTEND}
	@echo "Removing project files ..." && cd .. && rm -Rf ${PATH_FRONTEND}

update:
	@$(MAKE) -s composer-update
	@hymn app-update
	@test -d ${PATH_BACKEND} && cd ${PATH_BACKEND} && hymn app-update
	@$(MAKE) -s set-permissions

doc:
	@test ! -f vendor/ceus-media/doc-creator/doc-creator.php && echo "DocCreator is not installed" || true
	@test ! -f config/doc.xml && echo "No config file set for DocCreator (missing config/doc.xml)" || true
	@test -f vendor/ceus-media/doc-creator/doc-creator.php && test -f config/doc.xml &&\
		echo "Generation docs ..." && vendor/ceus-media/doc-creator/doc-creator.php --config-file=config/doc.xml || true

dump-static:
#	wget -q --show-progress --progress=dot -m https://DOMAIN.TLD/
	@echo "Downloading ..." && wget -q -m https://DOMAIN.TLD/
	@echo "Archiving ..." && tar -czf DOMAIN.TLD.tgz DOMAIN.TLD
	@echo "Cleaning up ..." && rm -Rf DOMAIN.TLD
	@echo "Done: DOMAIN.TLD.tgz"


##  PUBLIC: PERMISSIONS
#------------------------
set-permissions:
	@$(MAKE) -s set-ownage
	@$(MAKE) -s set-rights
	@test ! -f .htaccess && test -f .htaccess.dist && cp .htaccess.dist .htaccess || true

set-ownage:
	@test ${shell hymn config-get system.user} && sudo chown -R ${shell hymn config-get system.user} . || true
	@test ${shell hymn config-get system.group} && sudo chgrp -R ${shell hymn config-get system.group} . || true

set-rights:
	@find . -type d -not -path "./vendor*" -print0 | xargs -0 xargs chmod ${PERMS_DIR}
	@find . -type f -not -path "./vendor*" -print0 | xargs -0 xargs chmod ${PERMS_FILE}
	@test -f job.php && chmod +x job.php || true
	@test -e logs && chmod -R  g+w logs

enable-clamav:
	@cat /etc/passwd | grep clamav > /dev/null && test -f .hymn && (\
		groups clamav | grep ${shell hymn config-get system.group} > /dev/null && true || (\
			adduser clamav ${shell hymn config-get system.group} && sudo service clamav-daemon restart ) ) || true


##  PUBLIC: SETTERS
#------------------------
set-install-mode-dev:
	@test -f .hymn.dev && cp .hymn.dev .hymn || true
	@test -f config/config.ini.dev && cp config/config.ini.dev config/config.ini || true
	@hymn config-set application.installMode dev
	@test -f ${PATH_BACKEND}/.hymn.dev && cp ${PATH_BACKEND}/.hymn.dev ${PATH_BACKEND}/.hymn || true
	@test -f ${PATH_BACKEND}/config/config.ini.dev && cp ${PATH_BACKEND}/config/config.ini.dev ${PATH_BACKEND}/config/config.ini || true
	@test -f ${PATH_BACKEND}/.hymn && cd ${PATH_BACKEND} && hymn config-set application.installMode dev || true
	@$(MAKE) -s configure

set-install-mode-live:
	@test -f .hymn.live && cp .hymn.live .hymn || true
	@test -f config/config.ini.live && cp config/config.ini.live config/config.ini || true
	@hymn config-set application.installMode live
	@test -f ${PATH_BACKEND}/.hymn.live && cp ${PATH_BACKEND}/.hymn.live ${PATH_BACKEND}/.hymn || true
	@test -f ${PATH_BACKEND}/config/config.ini.live && cp ${PATH_BACKEND}/config/config.ini.live ${PATH_BACKEND}/config/config.ini || true
	@test -f ${PATH_BACKEND}/.hymn && cd ${PATH_BACKEND} && hymn config-set application.installMode live || true
	@$(MAKE) -s configure

set-install-mode-test:
	@test -f .hymn.test && cp .hymn.test .hymn || true
	@test -f config/config.ini.test && cp config/config.ini.test config/config.ini || true
	@hymn config-set application.installMode test
	@test -f ${PATH_BACKEND}/.hymn.test && cp ${PATH_BACKEND}/.hymn.test ${PATH_BACKEND}/.hymn || true
	@test -f ${PATH_BACKEND}/config/config.ini.test && cp ${PATH_BACKEND}/config/config.ini.test ${PATH_BACKEND}/config/config.ini || true
	@test -f ${PATH_BACKEND}/.hymn && cd ${PATH_BACKEND} && hymn config-set application.installMode test || true
	@$(MAKE) -s configure

set-install-type-copy:
	@hymn config-set application.installType copy
	@test -f ${PATH_BACKEND}/.hymn && cd ${PATH_BACKEND} && hymn config-set application.installType copy || true

set-install-type-link:
	@hymn config-set application.installType link
	@test -f ${PATH_BACKEND}/.hymn && cd ${PATH_BACKEND} && hymn config-set application.installType link || true


##  PUBLIC: CONFIGURATION
#------------------------
configure: detect-application-uri detect-application-url detect-sources
	@$(MAKE) -s .apply-application-url-to-config

configure-ask: detect-application-uri detect-application-url detect-sources
	@$(MAKE) -s ask-system-user
	@$(MAKE) -s ask-system-group
	@$(MAKE) -s ask-application-uri
	@$(MAKE) -s ask-application-url
	@$(MAKE) -s ask-database
	@$(MAKE) -s .apply-application-url-to-config

detect-application-uri:
#	@echo Set application URI to ${PWD}/.
	@hymn config-set application.uri ${PWD}/
	@test -f ${PATH_BACKEND}/.hymn && cd ${PATH_BACKEND} && hymn config-set application.uri ${PWD}/${PATH_BACKEND}/

detect-application-url:
	@test ! $(shell hymn config-get application.url) && $(MAKE) ask-application-url || true
	@$(MAKE) -s .apply-application-url-to-config

detect-sources:
	@test ! -z ${MODS_CM_PUBLIC} && test -d ${MODS_CM_PUBLIC} &&\
		hymn config-set sources.CeusMedia_Public.path $(shell hymn config-get application.uri)${MODS_CM_PUBLIC} || true
	@test ! -z ${MODS_CM_NONFREE} && test -d ${MODS_CM_NONFREE} &&\
		hymn config-set sources.CeusMedia_Protected.path $(shell hymn config-get application.uri)${MODS_CM_NONFREE} || true
	@test ! -z ${MODS_VENDOR_NAME} && test -d ${MODS_VENDOR_NAME} &&\
		hymn config-set sources.Project.path $(shell hymn config-get application.uri)${MODS_VENDOR_NAME} || true
	@test ! -z ${MODS_PROJECT} && test -d ${MODS_PROJECT} &&\
		hymn config-set sources.Project.path $(shell hymn config-get application.uri)${MODS_PROJECT} || true
	@test ! -z ${PATH_BACKEND} && test -f ${PATH_BACKEND}/.hymn && test ! -z ${MODS_CM_PUBLIC} && test -d ${MODS_CM_PUBLIC} && cd ${PATH_BACKEND} &&\
		hymn config-set sources.CeusMedia_Public.path $(shell hymn config-get application.uri)${MODS_CM_PUBLIC} || true
	@test ! -z ${PATH_BACKEND} && test -f ${PATH_BACKEND}/.hymn && test ! -z ${MODS_CM_NONFREE} && test -d ${MODS_CM_NONFREE} && cd ${PATH_BACKEND} &&\
		hymn config-set sources.CeusMedia_Protected.path $(shell hymn config-get application.uri)${MODS_CM_NONFREE} || true
	@test ! -z ${PATH_BACKEND} && test -f ${PATH_BACKEND}/.hymn && test ! -z ${MODS_VENDOR_NAME} && test -d ${MODS_VENDOR_NAME} && cd ${PATH_BACKEND} &&\
		hymn config-set sources.CeusMedia_Protected.path $(shell hymn config-get application.uri)${MODS_VENDOR_NAME} || true
	@test ! -z ${PATH_BACKEND} && test -f ${PATH_BACKEND}/.hymn && test ! -z ${MODS_PROJECT} && test -d ${MODS_PROJECT} && cd ${PATH_BACKEND} &&\
		hymn config-set sources.Project.path $(shell hymn config-get application.uri)${MODS_PROJECT} || true

ask-system-group:
	@echo
	@echo "Please define the filesystem group to be allowed!"
	@hymn config-set system.group

ask-system-user:
	@echo
	@echo "Please define the filesystem user to be owner!"
	@hymn config-set system.user

ask-application-uri:
	@echo
	@echo "Please define the absolute filesystem URI to this application! Attention: Mind the trailing slash!"
	@hymn config-set application.uri

ask-application-url:
	@test ! $(shell hymn config-get application.url) && echo "Please define the absolute network URL to this application! Attention: Mind the trailing slash!" && hymn config-set application.url || true
	@$(MAKE) -s .apply-application-url-to-config

ask-database:
	@echo
	@echo Please configure database access! Attention: Database MUST BE existing.
	@hymn database-config


##  COMPOSER
#------------------------
composer-install:
	@test -d vendor && echo && echo "Updating libraries:" && composer update --no-dev || true
	@test ! -d vendor && echo && echo "Loading libraries:" && composer install --no-dev || true
	@$(MAKE) -s set-rights

composer-install-dev:
	@test vendor && echo && echo "Updating libraries:" && composer update || true
	@test ! vendor && echo && echo "Loading libraries:" && composer install || true
	@$(MAKE) -s set-rights

composer-install-force:
	@test -d vendor && echo && echo "Removing current libraries..." && rm -Rf vendor || true
	@composer install --no-dev
	@$(MAKE) -s set-rights

composer-update:
	@composer update --no-dev
	@$(MAKE) -s set-rights

composer-update-dev:
	@composer update
	@$(MAKE) -s set-rights


##  PROTECTED
#------------------------

.apply-application-url-to-config:
	@hymn app-base-config-set app.base.url $(shell hymn config-get application.url) || true
	@hymn app-base-config-enable app.base.url > /dev/null || true
	@test -f ${PATH_BACKEND}/.hymn && cd ${PATH_BACKEND} && hymn config-set application.url $(shell hymn config-get application.url)${PATH_BACKEND}/ || true
	@test -f ${PATH_BACKEND}/.hymn && cd ${PATH_BACKEND} && hymn app-base-config-set app.base.url $(shell hymn config-get application.url)${PATH_BACKEND}/ || true
	@test -f ${PATH_BACKEND}/.hymn && cd ${PATH_BACKEND} && hymn app-base-config-enable app.base.url > /dev/null || true

.backend-install:
	@cd ${PATH_BACKEND} && ln -sf $(shell hymn config-get application.uri)vendor vendor
	@cd ${PATH_BACKEND} && hymn config-set database.driver $(shell hymn config-get database.driver)
	@cd ${PATH_BACKEND} && hymn config-set database.host "$(shell hymn config-get database.host)"
	@cd ${PATH_BACKEND} && hymn config-set database.port "$(shell hymn config-get database.port)"
	@cd ${PATH_BACKEND} && hymn config-set database.username "$(shell hymn config-get database.username)"
	@cd ${PATH_BACKEND} && hymn config-set database.password "$(shell hymn config-get database.password)"
	@cd ${PATH_BACKEND} && hymn config-set database.name "$(shell hymn config-get database.name)"
	@cd ${PATH_BACKEND} && hymn config-set database.prefix "$(shell hymn config-get database.prefix)"
	@cd ${PATH_BACKEND} && echo && echo "Installing backend modules:" && hymn app-install
	@cd ${PATH_BACKEND} && rm -f config/modules/Resource_Library_cmModules.xml
	@cd ${PATH_BACKEND} && hymn app-base-config-enable system.cache.modules
	@cd ${PATH_BACKEND} && cp .htaccess.dist .htaccess

.backend-secure:
	@cd ${PATH_BACKEND} && cp .htaccess.dist .htaccess
	@cd ${PATH_BACKEND} && sed -i 's#/path/to/my/project#${PWD}/${PATH_BACKEND}/#' .htaccess
	@cd ${PATH_BACKEND} && sed -i 's/^#Auth/Auth/' .htaccess
	@cd ${PATH_BACKEND} && sed -i 's/^#Require/Require/' .htaccess
	@cd ${PATH_BACKEND} && sed -i 's/^#FileETag/FileETag/' .htaccess
	@cd ${PATH_BACKEND} && sed -i 's/My Project/Admin/' .htaccess
	@cd ${PATH_BACKEND} && htpasswd -cBb .htpasswd USER1 PASSWORD1
	@cd ${PATH_BACKEND} && htpasswd -Bb .htpasswd USER2 PASSWORD2

.build-cleanup:
	@test ! -z ${MODS_CM_PUBLIC} && rm -Rf ${MODS_CM_PUBLIC} || true
	@test ! -z ${MODS_CM_NONFREE} && rm -Rf ${MODS_CM_NONFREE} || true
	@test ! -z ${MODS_VENDOR_NAME} && rm -Rf ${MODS_VENDOR_NAME} || true
	@test ! -z ${MODS_PROJECT} && rm -Rf ${MODS_PROJECT} || true
	@rm -Rf config/sql config/dumps .git .svn
	@rm -f .hymn* composer.* Makefile .*.dist config/doc.xml
	@cd ${PATH_BACKEND} && rm -Rf .svn
	@cd ${PATH_BACKEND} && rm -f .hymn* composer.* Makefile .*.dist config/doc.xml
	@rm -Rf vendor/ceus-media/doc-creator
	@rm -Rf vendor/ceus-media/hydrogen-themes

.fallback-to-dev:
	@test ! -f .hymn && $(MAKE) -s set-install-mode-dev && echo "Setting dev mode by fallback" || true
