PHP=php
COMPOSER=composer
SYMFONY=symfony
DOCKER=docker
PSQL=psql
PG_DUMP=pg_dump
ZSTD=zstd
LANG=en id

-include local.mk

TRANSLATION_EXTRACT_TARGETS=$(addprefix translation-extract-, $(LANG))
TRANSLATION_UNUSED_TARGETS=$(addprefix translation-unused-, $(LANG))
TRANSLATION_DOMAIN=rekalogika_analytics

.PHONY: test 
test: clean composer-dump lint monorepo-validate phpstan psalm doctrine-schema-create summary-refresh phpunit

.PHONY: composer-dump
composer-dump:
	$(COMPOSER) dump-autoload

.PHONY: phpstan
phpstan:
	$(PHP) vendor/bin/phpstan analyse

.PHONY: psalm
psalm:
	$(PHP) vendor/bin/psalm

.PHONY: phpunit
phpunit:
	$(eval c ?=)
	$(PHP) vendor/bin/phpunit $(c)

.PHONY: lint
lint:
	$(PHP) tests/bin/console lint:container
	$(PHP) tests/bin/console doctrine:schema:validate --skip-sync

.PHONY: rector
rector:
	$(PHP) vendor/bin/rector process > rector.log
	make php-cs-fixer

.PHONY: php-cs-fixer
php-cs-fixer: tools/php-cs-fixer
	$(PHP) $< fix --config=.php-cs-fixer.dist.php --verbose --allow-risky=yes

.PHONY: tools/php-cs-fixer
tools/php-cs-fixer:
	phive install php-cs-fixer

.PHONY: serve
serve:
	$(PHP) tests/bin/console cache:clear
	$(PHP) tests/bin/console asset:install tests/public/
	cd tests && sh -c "$(SYMFONY) server:start --document-root=public"

.PHONY: monorepo
monorepo: monorepo-validate monorepo-merge

.PHONY: monorepo-validate
monorepo-validate:
	vendor/bin/monorepo-builder validate

.PHONY: monorepo-merge
monorepo-merge:
	$(PHP) vendor/bin/monorepo-builder merge

.PHONY: monorepo-release-%
monorepo-release-%:
	git update-index --really-refresh > /dev/null; git diff-index --quiet HEAD || (echo "Working directory is not clean, aborting" && exit 1)
	[ $$(git branch --show-current) == main ] || (echo "Not on main branch, aborting" && exit 1)
	$(PHP) vendor/bin/monorepo-builder release $*
	git switch -c release/$*
	git add .
	git commit -m "release: $*"

.PHONY: clean
clean:
	rm -rf tests/var/cache/*
	$(PHP) vendor/bin/psalm --clear-cache

.PHONY: dump
dump:
	$(PHP) tests/bin/console server:dump

.PHONY: psql
psql:
	$(PSQL) postgresql://app:app@localhost:5432

.PHONY: compose-up
compose-up:
	DUID="$$(id -u)" DGID="$$(id -g)" $(DOCKER) compose up -d --wait

.PHONY: compose-down
compose-down:
	DUID="$$(id -u)" DGID="$$(id -g)" $(DOCKER) compose down

.PHONY: compose-restart
compose-restart: compose-down compose-up

.PHONY: fixtures-load
fixtures-load: compose-up
	$(PHP) tests/bin/console doctrine:fixtures:load --no-interaction

#
# js
#

.PHONY: js-compile
js-compile:
	cd packages/analytics-bundle/assets && npm run build

.PHONY: asset-map
asset-map:
	$(PHP) tests/bin/console asset-map:compile

.PHONY: js-symlink
js-symlink:
	cd tests/assets/controllers/rekalogika/analytics-bundle && rm -f * ; for A in ../../../../../packages/analytics-bundle/assets/dist/*; do ln -sf $$A ; done

.PHONY: importmap-install
importmap-install:
	$(PHP) tests/bin/console importmap:install

.PHONY: js
js: js-compile js-symlink importmap-install asset-map

#
# translation
#

.PHONY: translation-extract-%
translation-extract-%:
	$(PHP) tests/bin/console translation:extract --force --no-interaction --format=xlf20 $*

.PHONY: translation-unused-%
translation-unused-%:
	$(PHP) tests/bin/console debug:translation $* --only-unused --domain=$(TRANSLATION_DOMAIN)

.PHONY: translation-unused
translation-unused: $(TRANSLATION_UNUSED_TARGETS)

.PHONY: translation-clean
translation-clean:
	rm -f packages/analytics-bundle/translations/messages*.xlf

.PHONY: translation
translation: translation-clean $(TRANSLATION_EXTRACT_TARGETS)

#
# update schema
#

.PHONY: doctrine-schema-create
doctrine-schema-create: compose-restart
	$(PHP) tests/bin/console doctrine:schema:drop --no-interaction --force --full-database
	$(PHP) tests/bin/console doctrine:schema:create --no-interaction
	make fixtures-load

#
# Summarize
#

.PHONY: summary-refresh
summary-refresh: summary-refresh-order summary-refresh-customer

.PHONY: summary-refresh-order
summary-refresh-order:
	tests/bin/console rekalogika:analytics:refresh 'Rekalogika\Analytics\Tests\App\Entity\OrderSummary'

.PHONY: summary-refresh-customer
summary-refresh-customer:
	tests/bin/console rekalogika:analytics:refresh 'Rekalogika\Analytics\Tests\App\Entity\CustomerSummary'
