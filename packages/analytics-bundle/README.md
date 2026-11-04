# rekalogika/analytics-bundle

Symfony bundle for [Rekalogika Analytics](https://github.com/rekalogika/analytics).
Integrates the framework into a Symfony application, adding OLAP functionality
to your Doctrine entities.

> [!WARNING]
> This package is still in development and not yet ready for production use.
> The API is subject to change.

## What's Inside

* Service wiring for the metadata, refresh engine, and query engine from
  [`rekalogika/analytics-core`](https://github.com/rekalogika/analytics-core),
  including the `SummaryManager` service.
* A refresh agent that refreshes the summary tables in the background, using
  Symfony Messenger, whenever the source entities change.
* Console commands for maintaining the summary tables.
* Service wiring for
  [`rekalogika/analytics-frontend`](https://github.com/rekalogika/analytics-frontend),
  if it is installed.
* Translations.

## Requirements

* PHP 8.2 or later
* Symfony 6.4, 7.x, or 8.x
* Doctrine ORM 2.20+ or 3.x, with DoctrineBundle and DoctrineMigrationsBundle
* PostgreSQL
* Symfony Messenger and Symfony Lock

## Installation

```bash
composer require rekalogika/analytics-bundle
```

If you don't use Symfony Flex, register the bundle manually:

```php
// config/bundles.php
return [
    // ...
    Rekalogika\Analytics\Bundle\RekalogikaAnalyticsBundle::class => ['all' => true],
];
```

Route the refresh messages to an asynchronous Messenger transport, and make
sure a worker is consuming it:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            'Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStartCommand': async
```

## Configuration

The default configuration:

```yaml
# config/packages/rekalogika_analytics.yaml
rekalogika_analytics:
    # The maximum number of results returned from a query. A query exceeding
    # this limit throws an OverflowException.
    query_result_limit: 5000

    # The maximum number of nodes created to fill gaps between values. Going
    # over this limit throws an OverflowException.
    filling_nodes_limit: 10000

    # The Twig theme used to render pivot tables. It should extend
    # @RekalogikaAnalyticsFrontend/renderer.html.twig. A Bootstrap 5 theme is
    # available as @RekalogikaAnalyticsFrontend/bootstrap_5_renderer.html.twig.
    table_theme: '@RekalogikaAnalyticsFrontend/renderer.html.twig'

    # Determines when the refresh agent refreshes the dirty partitions. All
    # values are in seconds; null disables the setting.
    refresh:
        # A dirty partition is not refreshed until its oldest change is at
        # least this old.
        minimum_age: 600

        # A dirty partition is not refreshed until there have been no new
        # changes for this long.
        minimum_idle_delay: 300

        # A dirty partition is refreshed once its oldest change is this old,
        # ignoring minimum_idle_delay.
        maximum_age: 1800

        # An upper-level partition is refreshed immediately after the
        # partitions below it, unless it has been refreshed within this period.
        upper_partition_cooldown: 300

        # Used by the sweeper. A scheduled run of the refresh agent that is
        # overdue by more than this is considered lost, and is dispatched again.
        sweep_grace: 300
```

## Sweeper

The refresh agent is dispatched after a flush that creates dirty flags. If
the dispatched message is lost or ends up in the failure transport, or if the
flags are created without a flush (e.g. by a database trigger), the dirty
partitions wait until another flush dirties the same summary. The sweeper
dispatches the refresh agent of every summary that has dirty flags but no
pending run. Run it periodically, either with the
`rekalogika:analytics:sweep` command, or with Symfony Scheduler:

```php
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentSweepCommand;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('analytics')]
final class AnalyticsSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(
            RecurringMessage::every('1 minute', new RefreshAgentSweepCommand()),
        );
    }
}
```

## Database Triggers

Changes made through the ORM are detected automatically. Changes made
otherwise, e.g. DQL `UPDATE` and `DELETE`, native SQL, migrations, or database
cascades, are not. On PostgreSQL, the bundle can generate triggers that create
the dirty flags for every change to the source tables, and to the tables of
the entities the summaries read through an association:

```bash
# print the SQL, to put in a migration
php bin/console rekalogika:analytics:trigger:dump

# or, in development, install them directly, and drop them
php bin/console rekalogika:analytics:trigger:install
php bin/console rekalogika:analytics:trigger:drop
```

The triggers are statement-level, with transition tables, so a statement
costs one `INSERT … SELECT` per summary, whatever the number of rows. An
update only flags the rows whose columns read by a summary have changed.
Regenerate them after changing a summary.

The triggers only create the dirty flags. Run the [sweeper](#sweeper)
periodically to dispatch the refresh agent. The flags are created in the time
zone of PHP when the SQL is generated, which must be the time zone the
application runs with.

Not supported: class table inheritance, composite identifiers, `TRUNCATE`,
and updates of primary keys.

## Verification

`rekalogika:analytics:verify` compares a summary table with a fresh roll-up of
its source, and reports the partitions that differ, e.g. because of a change
that was not detected. It verifies the whole summarized range, a range of
source keys, or a random sample of partitions:

```bash
php bin/console rekalogika:analytics:verify 'App\Entity\OrderSummary'
php bin/console rekalogika:analytics:verify 'App\Entity\OrderSummary' 1000 2000
php bin/console rekalogika:analytics:verify 'App\Entity\OrderSummary' --sample=20
```

With `--repair`, the partitions that differ are marked dirty, and the refresh
agent refreshes them. The command fails if any partition differs, so it can
run periodically as a check. Partitions with a pending dirty flag, and the
ones with source entities not summarized yet, are skipped. Floating point
measures are compared in single precision, HyperLogLog measures by their
cardinality.

## Console Commands

| Command | Description |
| --- | --- |
| `rekalogika:analytics:refresh` | Immediately refreshes all the dirty partitions of a summary table. |
| `rekalogika:analytics:refresh:range` | Refreshes a range of source records, regardless of whether they are dirty. Usually run once after creating a summary table. Supports `--resume` and `--batchsize`. |
| `rekalogika:analytics:truncate` | Truncates a summary table. |
| `rekalogika:analytics:sweep` | Dispatches the refresh agent of the summaries that have dirty partitions but no pending run. See [Sweeper](#sweeper). |
| `rekalogika:analytics:trigger:dump` | Prints the SQL creating the PostgreSQL triggers, or dropping them with `--drop`. See [Database Triggers](#database-triggers). |
| `rekalogika:analytics:trigger:install` | Installs the PostgreSQL triggers directly, replacing the existing ones. For development. |
| `rekalogika:analytics:trigger:drop` | Drops the PostgreSQL triggers. |
| `rekalogika:analytics:verify` | Compares a summary table with its source, and reports the partitions that differ. Supports `--sample` and `--repair`. See [Verification](#verification). |
| `rekalogika:analytics:status` | Shows the refresh status of the summary tables: pending dirty flags, the next and the scheduled refresh, and the latest summarized key against the latest source key. |
| `rekalogika:analytics:debug:summary` | Shows information about a summary table. |
| `rekalogika:analytics:debug:source` | Shows which changes to a source class, or to a class read through an association, refresh which summaries. |
| `rekalogika:analytics:uuid:summary-to-source` | Converts a UUIDv7 partition key in the summary table to the corresponding source value. |

## Documentation

[rekalogika.dev/analytics](https://rekalogika.dev/analytics)

## License

MIT

## Contributing

This framework consists of multiple repositories split from a monorepo. Be
sure to submit issues and pull requests to the
[`rekalogika/analytics`](https://github.com/rekalogika/analytics) monorepo.
