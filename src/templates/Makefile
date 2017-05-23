
PATH_FRONTEND	= %appName%
PATH_BACKEND	= admin

PWD				= $(shell pwd)
DATE			= `date +'%Y-%m-%d'`

install:
	@test ! -f .hymn && $(MAKE) -s set-mode-dev || true
	@$(MAKE) -s set-install-type-link
	@$(MAKE) -s composer-install
	@$(MAKE) -s set-rights
	@echo "Installing frontend modules:"
	@hymn app-install
	@test -f ${PATH_BACKEND}/.hymn && $(MAKE) -s .backend-install || true
#	@test -f ${PATH_BACKEND}/.hymn && $(MAKE) -s .backend-secure
	@hymn database-load
	@echo "Settings permissions..."
	@$(MAKE) -s set-permissions
	@echo "Generating autoloaders..."
	@$(MAKE) -s composer-update
#	@test -f vendor/ceus-media/hydra && make install-hydra || true

build:
	@$(MAKE) -s set-install-type-copy
	@$(MAKE) -s detect-application-uri && $(MAKE) -s detect-sources
	@$(MAKE) -s composer-install
	@$(MAKE) -s set-rights
	@echo "Installing frontend modules:"
	@hymn app-install
	@$(MAKE) -s .backend-install
#	@$(MAKE) -s .backend-secure
	@hymn database-load
	@hymn database-dump install.sql
	@$(MAKE) -s set-rights
	@echo "Generating autoloaders..."
	@$(MAKE) -s composer-update
	@test -f config/doc.xml && $(MAKE) -s doc || true
#	@ln -s vendor/ceus-media/hydra
	@$(MAKE) -s .build-cleanup
	@echo "Creating archive files..."
	@cd .. && zip -r -q ${PATH_FRONTEND}_${DATE}.zip ${PATH_FRONTEND}
	@cd .. && tar -czf ${PATH_FRONTEND}_${DATE}.tgz ${PATH_FRONTEND}
	@echo "Removing project files..."
	@cd .. && rm -Rf ${PATH_FRONTEND}

doc:
	@test ! -f vendor/ceus-media/doc-creator/doc-creator.php && echo DocCreator is not installed || true
	@test ! -f vendor/ceus-media/doc-creator/doc-creator.php && echo "No config file set for DocCreator (missing config/doc.xml)" || true
	@test -f vendor/ceus-media/doc-creator/doc-creator.php && test -f config/doc.xml && vendor/ceus-media/doc-creator/doc-creator.php --config-file=config/doc.xml || true

update:
	@$(MAKE) -s composer-update
	@svn up vendor/ceus-media/hydrogen-modules-nonfree
	@hymn app-update


##  PROTECTED
#------------------------
.backend-install:
	@cd ${PATH_BACKEND} && ln -sf $(shell hymn config-get application.uri)vendor vendor
	@cd ${PATH_BACKEND} && hymn config-set application.uri $(shell hymn config-get application.uri)${PATH_BACKEND}/
	@cd ${PATH_BACKEND} && hymn config-set application.url $(shell hymn config-get application.url)${PATH_BACKEND}/
	@cd ${PATH_BACKEND} && hymn config-set database.driver $(shell hymn config-get database.driver)
	@cd ${PATH_BACKEND} && hymn config-set database.host "$(shell hymn config-get database.host)"
	@cd ${PATH_BACKEND} && hymn config-set database.port "$(shell hymn config-get database.port)"
	@cd ${PATH_BACKEND} && hymn config-set database.username "$(shell hymn config-get database.username)"
	@cd ${PATH_BACKEND} && hymn config-set database.password "$(shell hymn config-get database.password)"
	@cd ${PATH_BACKEND} && hymn config-set database.name "$(shell hymn config-get database.name)"
	@cd ${PATH_BACKEND} && hymn config-set database.prefix "$(shell hymn config-get database.prefix)"
	@cd ${PATH_BACKEND} && hymn config-set sources.Local_CM_Public.path $(shell hymn config-get application.uri)vendor/ceus-media/hydrogen-modules/
	@cd ${PATH_BACKEND} && hymn config-set sources.Local_CM_Protected.path $(shell hymn config-get application.uri)vendor/ceus-media/hydrogen-modules-nonfree/
	@cd ${PATH_BACKEND} && echo && echo "Installing backend modules:" && hymn app-install
	@cd ${PATH_BACKEND} && rm -f config/modules/Resource_Library_cmModules.xml
	@cd ${PATH_BACKEND} && hymn app-base-config-set app.base.url $(shell hymn config-get application.url)${PATH_BACKEND}/
	@cd ${PATH_BACKEND} && hymn app-base-config-enable app.base.url
	@cd ${PATH_BACKEND} && hymn app-base-config-enable system.cache.modules

.backend-secure:
	@cd ${PATH_BACKEND} && cp .htaccess.dist .htaccess
	@cd ${PATH_BACKEND} && sed -i 's#/path/to/my/project#${PWD}/${PATH_BACKEND}/#' .htaccess
	@cd ${PATH_BACKEND} && sed -i 's/^#Auth/Auth/' .htaccess
	@cd ${PATH_BACKEND} && sed -i 's/^#Require/Require/' .htaccess
	@cd ${PATH_BACKEND} && sed -i 's/^#FileETag/FileETag/' .htaccess
	@cd ${PATH_BACKEND} && sed -i 's/My Project/Verwaltung/' .htaccess
	@cd ${PATH_BACKEND} && htpasswd -cBb .htpasswd cmadmin nimda1

.build-cleanup:
	@rm -Rf vendor/michelf
	@rm -Rf vendor/ceus-media/doc-creator
	@rm -Rf vendor/ceus-media/hydra
	@rm -Rf vendor/ceus-media/hydrogen-themes
	@rm -Rf vendor/ceus-media/hydrogen-modules
	@rm -Rf vendor/ceus-media/hydrogen-modules-nonfree
	@rm -Rf .svn ${PATH_BACKEND}/.svn
	@rm -Rf config/sql
	@rm -f .hymn* ${PATH_BACKEND}/.hymn*
	@rm -f .*.dist ${PATH_BACKEND}/*.dist
	@rm -f composer.* ${PATH_BACKEND}/composer.*
	@rm -f Makefile ${PATH_BACKEND}/Makefile
	@rm -f config/doc.xml ${PATH_BACKEND}/config/doc.xml
	@rm -Rf hydra Hydra


##  PUBLIC: PERMISSIONS
#------------------------
set-permissions:
	@$(MAKE) -s set-ownage
	@$(MAKE) -s set-rights

set-ownage:
	@sudo chown -R ${shell hymn config-get system.user} .
	@sudo chgrp -R ${shell hymn config-get system.group} .

set-rights:
	@find . -type d -not -path "./vendor*" -print0 | xargs -0 xargs chmod 775
	@find . -type f -not -path "./vendor*" -print0 | xargs -0 xargs chmod 664
	@test -d vendor/ceus-media/hydrogen-modules-nonfree && find . -type f -path "./vendor/ceus-media/hydrogen-modules-nonfree/*" -print0 | xargs -0 xargs chmod 775 || true


##  PUBLIC: SETTERS
#------------------------
set-mode-dev:
	@test -f .hymn.dev && cp .hymn.dev .hymn || true
	@test -f ${PATH_BACKEND}/.hymn.dev && cp ${PATH_BACKEND}/.hymn.dev ${PATH_BACKEND}/.hymn || true
	@$(MAKE) -s configure-ask

set-mode-live:
	@test -f .hymn.dev && cp .hymn.live .hymn || true
	@test -f ${PATH_BACKEND}.hymn.live && cp ${PATH_BACKEND}/.hymn.live ${PATH_BACKEND}/.hymn || true
	@$(MAKE) -s configure

set-mode-test:
	@test -f .hymn.test && cp .hymn.test .hymn || true
	@test -f ${PATH_BACKEND}.hymn.test && cp ${PATH_BACKEND}/.hymn.test ${PATH_BACKEND}/.hymn || true
	@$(MAKE) -s configure

set-install-type-copy:
	@hymn config-set modules.@installType copy
	@test -f ${PATH_BACKEND}/.hymn && cd ${PATH_BACKEND} && hymn config-set modules.@installType copy || true

set-install-type-link:
	@hymn config-set modules.@installType link
	@test -f ${PATH_BACKEND}/.hymn && cd ${PATH_BACKEND} && hymn config-set modules.@installType link || true


##  PUBLIC: CONFIGURATION
#------------------------
configure: detect-application-uri detect-sources
	@$(MAKE) -s .apply-application-url-to-config

configure-ask: detect-application-uri detect-sources
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

detect-sources:
	@hymn config-set sources.Local_CM_Public.path $(shell hymn config-get application.uri)vendor/ceus-media/hydrogen-modules/
	@hymn config-set sources.Local_CM_Protected.path $(shell hymn config-get application.uri)vendor/ceus-media/hydrogen-modules-nonfree/

ask-system-group:
	@echo
	@echo Please define the filesystem group to be allowed!
	@hymn config-set system.group

ask-system-user:
	@echo
	@echo Please define the filesystem user to be owner!
	@hymn config-set system.user

ask-application-uri:
	@echo
	@echo Please define the absolute filesystem URI to this application! Attention: Mind the trailing slash!
	@hymn config-set application.uri

ask-application-url:
	@echo
	@echo Please define the absolute network URL to this application! Attention: Mind the trailing slash!
	@hymn config-set application.url

.apply-application-url-to-config:
	@hymn app-base-config-set app.base.url $(shell hymn config-get application.url)
	@hymn app-base-config-enable app.base.url

ask-database:
	@echo
	@echo Please configure database access! Attention: Database MUST BE existing.
	@hymn database-config


##  COMPOSER
#------------------------
composer-install:
	@test vendor && echo && echo Updating libraries: && composer update || true
	@test ! vendor && echo && echo Loading libraries: && composer install || true

composer-install-force:
	@test vendor && rm -Rf vendor
	@composer install
	@find . -type f -path "./vendor/ceus-media/hydrogen-modules-nonfree/*" -print0 | xargs -0 xargs chmod 775 >/dev/null 2>&1 || true

composer-update:
	@composer update
	@find . -type f -path "./vendor/ceus-media/hydrogen-modules-nonfree/*" -print0 | xargs -0 xargs chmod 775 >/dev/null 2>&1 || true


##  HYMN: APP
#------------------------
app-database-dump:
	@hymn database-dump

app-database-load:
	@hymn database-load


##  JOBS
#------------------------
count-mails:
	@php job.php countQueuedMails

send-mails:
	@php job.php sendQueuedMails

count-newsletters:
	@php job.php Newsletter.count

send-newsletters:
	@php job.php Newsletter.send

##  JOBS: New Style
#------------------------
job-resource-mail-queue-count:
	@php job.php Resource.Mail.Queue.count

job-resource-mail-queue-send:
	@php job.php Resource.Mail.Queue.send

job-resource-newsletter-count:
	@php job.php Resource.Newsletter.count

job-resource-newsletter-send:
	@php job.php Resource.Newsletter.send
