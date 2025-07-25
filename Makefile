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
TRANSLATION_MISSING_TARGETS=$(addprefix translation-missing-, $(LANG))
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
	PHP_CS_FIXER_IGNORE_ENV=1 $(PHP) $< fix --config=.php-cs-fixer.dist.php --verbose --allow-risky=yes

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
	rm -rf tests/var/cache/* tests/var/log/*
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
	$(PHP) tests/bin/console doctrine:fixtures:load --no-interaction -vv

#
# js
#

.PHONY: js-compile
js-compile:
	cd packages/analytics-ux-panel/assets && npm run build

.PHONY: asset-map
asset-map:
	$(PHP) tests/bin/console asset-map:compile

.PHONY: js-symlink
js-symlink:
	mkdir -p tests/assets/controllers/rekalogika/analytics-ux-panel && cd tests/assets/controllers/rekalogika/analytics-ux-panel && rm -f * ; for A in ../../../../../packages/analytics-ux-panel/assets/dist/*; do ln -sf $$A ; done

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
	$(PHP) tests/bin/console translation:extract --domain=$(TRANSLATION_DOMAIN) --force --no-interaction --format=xlf20 $*

.PHONY: translation-unused-%
translation-unused-%:
	$(PHP) tests/bin/console debug:translation $* --domain=$(TRANSLATION_DOMAIN) --only-unused

.PHONY: translation-missing-%
translation-missing-%:
	$(PHP) tests/bin/console debug:translation $* --domain=$(TRANSLATION_DOMAIN) --only-missing

.PHONY: translation-lint
translation-lint: $(TRANSLATION_UNUSED_TARGETS) $(TRANSLATION_MISSING_TARGETS)

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
	$(PHP) tests/bin/console doctrine:migrations:migrate --no-interaction
	make fixtures-load

#
# refresh range
#

.PHONY: summary-refreshrange
summary-refreshrange: summary-refreshrange-order summary-refreshrange-customer summary-refreshrange-occupancy-history

.PHONY: summary-refreshrange-order
summary-refreshrange-order:
	tests/bin/console rekalogika:analytics:refresh:range 'Rekalogika\Analytics\Tests\App\Entity\OrderSummary'

.PHONY: summary-refreshrange-customer
summary-refreshrange-customer:
	tests/bin/console rekalogika:analytics:refresh:range 'Rekalogika\Analytics\Tests\App\Entity\CustomerSummary'

.PHONY: summary-refreshrange-occupancy-history
summary-refreshrange-occupancy-history:
	tests/bin/console rekalogika:analytics:refresh:range 'Rekalogika\Analytics\Tests\App\Entity\OccupancyHistorySummary'

#
# refresh
#

.PHONY: summary-refresh
summary-refresh: summary-refresh-order summary-refresh-customer summary-refresh-occupancy-history

.PHONY: summary-refresh-order
summary-refresh-order:
	tests/bin/console rekalogika:analytics:refresh -vv 'Rekalogika\Analytics\Tests\App\Entity\OrderSummary'

.PHONY: summary-refresh-customer
summary-refresh-customer:
	tests/bin/console rekalogika:analytics:refresh -vv 'Rekalogika\Analytics\Tests\App\Entity\CustomerSummary'

.PHONY: summary-refresh-occupancy-history
summary-refresh-occupancy-history:
	tests/bin/console rekalogika:analytics:refresh -vv 'Rekalogika\Analytics\Tests\App\Entity\OccupancyHistorySummary'
