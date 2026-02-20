
PATH_FRONTEND	= Accounting

PERMS_DIR			= 775
PERMS_FILE			= 664

PWD					= $(shell pwd)
DATE				= `date +'%Y-%m-%d'`

MODS_CM_PUBLIC		= vendor/ceus-media/hydrogen-modules/src/
MODS_CM_NONFREE		= vendor/ceus-media/hydrogen-modules-nonfree/src/
MODS_PROJECT		= modules/


install:
	@test ! -f .hymn && $(MAKE) -s set-install-mode-dev || true
	@$(MAKE) -s configure
	@$(MAKE) -s composer-install && $(MAKE) -s detect-sources
	@echo "Installing modules:" && hymn app-install
	@cp .htaccess.dist .htaccess
	@hymn database-load
	@echo "Settings permissions..." && $(MAKE) -s set-permissions
	@echo "Generating autoloaders..." && $(MAKE) -s composer-update

build:
	@test ! -f .hymn && $(MAKE) -s set-install-mode-dev || true
	@$(MAKE) -s set-install-type-copy
	@$(MAKE) -s detect-application-uri && $(MAKE) -s detect-sources
	@$(MAKE) -s composer-install && $(MAKE) -s detect-sources
	@echo "Installing modules:" && hymn app-install
	@cp .htaccess.dist .htaccess
	@hymn database-load
	@hymn database-dump install.sql
	@$(MAKE) -s set-rights
	@echo "Generating autoloaders..." && $(MAKE) -s composer-update
	@test -f config/doc.xml && $(MAKE) -s doc || true
	@$(MAKE) -s .build-cleanup
	@echo "Creating archive files..."
	@cd .. && zip -r -q ${PATH_FRONTEND}_${DATE}.zip ${PATH_FRONTEND}
	@cd .. && tar -czf ${PATH_FRONTEND}_${DATE}.tgz ${PATH_FRONTEND}
	@echo "Removing project files..."
	@cd .. && rm -Rf ${PATH_FRONTEND}

update:
	@$(MAKE) -s composer-update
	@test -d ${MODS_CM_NONFREE} && cd ${MODS_CM_NONFREE} && echo "Updating protected modules..." && svn up -q . || true
#	@$(MAKE) -s set-rights
	@hymn app-update
	@$(MAKE) -s set-permissions

doc:
	@test ! -f vendor/ceus-media/doc-creator/doc-creator.php && echo "DocCreator is not installed" || true
	@test ! -f vendor/ceus-media/doc-creator/doc-creator.php && echo "No config file set for DocCreator (missing config/doc.xml)" || true
	@test -f vendor/ceus-media/doc-creator/doc-creator.php && test -f config/doc.xml && vendor/ceus-media/doc-creator/doc-creator.php --config-file=config/doc.xml || true


##  PUBLIC: PERMISSIONS
#------------------------
set-permissions:
	@$(MAKE) -s set-ownage
	@$(MAKE) -s set-rights
	@$(MAKE) -s enable-clamav
	@test -f job.php && chmod +x job.php || true

set-ownage:
	@test ${shell hymn config-get system.user} && sudo chown -R ${shell hymn config-get system.user} . || true
	@test ${shell hymn config-get system.group} && sudo chgrp -R ${shell hymn config-get system.group} . || true

set-rights:
	@find . -type d -not -path "./vendor*" -print0 | xargs -0 xargs chmod ${PERMS_DIR}
	@find . -type f -not -path "./vendor*" -print0 | xargs -0 xargs chmod ${PERMS_FILE}
#	@test -d ${MODS_CM_NONFREE} && cd ${MODS_CM_NONFREE} && find . -type f -print0 | xargs -0 xargs chmod ${PERMS_LIB} >/dev/null 2>&1 || true

enable-clamav:
	@cat /etc/passwd | grep clamav > /dev/null && ( groups clamav | grep ${shell hymn config-get system.group} > /dev/null && true || ( adduser clamav ${shell hymn config-get system.group} && sudo service clamav-daemon restart ) ) || true


##  PUBLIC: SETTERS
#------------------------
set-install-mode-dev:
	@test -f .hymn.dev && cp .hymn.dev .hymn || true
	@test -f config/config.ini.dev && cp config/config.ini.dev config/config.ini || true
	@hymn config-set application.installMode dev
	@$(MAKE) -s configure

set-install-mode-live:
	@test -f .hymn.live && cp .hymn.live .hymn || true
	@test -f config/config.ini.live && cp config/config.ini.live config/config.ini || true
	@hymn config-set application.installMode live
	@$(MAKE) -s configure

set-install-mode-test:
	@test -f .hymn.test && cp .hymn.test .hymn || true
	@test -f config/config.ini.test && cp config/config.ini.test config/config.ini || true
	@hymn config-set application.installMode test
	@$(MAKE) -s configure

set-install-type-copy:
	@hymn config-set application.installType copy

set-install-type-link:
	@hymn config-set application.installType link


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
	@echo
	@echo Set application URI to ${PWD}/ by detection.
	@hymn config-set application.uri ${PWD}/

detect-application-url:
	@test ! $(shell hymn config-get application.url) && $(MAKE) ask-application-url || true
	@$(MAKE) -s .apply-application-url-to-config

detect-sources:
	@hymn config-set sources.CeusMedia_Public.path $(shell hymn config-get application.uri)${MODS_CM_PUBLIC}
	@test -d ${MODS_CM_NONFREE} && hymn config-set sources.CeusMedia_Nonfree.path $(shell hymn config-get application.uri)${MODS_CM_NONFREE} || true
	@test -d ${MODS_PROJECT} && hymn config-set sources.Project.path $(shell hymn config-get application.uri)modules/ || true

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

.build-cleanup:
	@rm -Rf vendor/michelf
	@rm -Rf vendor/ceus-media/doc-creator
	@rm -Rf vendor/ceus-media/hydra
	@rm -Rf vendor/ceus-media/hydrogen-themes
	@rm -Rf ${MODS_CM_PUBLIC}
	@rm -Rf ${MODS_CM_NONFREE}
	@rm -Rf ${MODS_PROJECT}
	@rm -Rf config/sql
	@rm -Rf hydra Hydra
	@rm -Rf .svn ${PATH_BACKEND}/.svn
	@rm -f .hymn* ${PATH_BACKEND}/.hymn*
	@rm -f .*.dist ${PATH_BACKEND}/*.dist
	@rm -f composer.* ${PATH_BACKEND}/composer.*
	@rm -f Makefile ${PATH_BACKEND}/Makefile
	@rm -f config/doc.xml ${PATH_BACKEND}/config/doc.xml



##  HYMN
#------------------------
app-database-dump:
#	@hymn database-dump
	@hymn database-dump config/sql/$(shell hymn config-get application.installMode)/

app-database-load:
#	@hymn database-load
	@hymn database-load config/sql/$(shell hymn config-get application.installMode)/
