# Changelog

## v0.39.0

Change detection now covers more of the ways a source entity can change, and
there are tools to recover from the changes it still misses.

### Breaking changes

* `HasQueryBuilderModifier` is removed (#737). A summary that still
  implements it fails with "interface not found". Use the new `filter`
  argument of `#[Summary]` instead:

  ```php
  // before
  #[Analytics\Summary(sourceClass: Order::class)]
  class OrderSummary extends BaseSummary implements HasQueryBuilderModifier
  {
      public static function modifyQueryBuilder(QueryBuilder $queryBuilder): void
      {
          $queryBuilder->andWhere('root.id > :minId')->setParameter('minId', 10);
      }
  }

  // after
  use Doctrine\Common\Collections\Expr\Comparison;
  // for conditions that are not comparisons:
  // use Rekalogika\Analytics\Core\Filter\SourceCondition;

  #[Analytics\Summary(
      sourceClass: Order::class,
      filter: new Comparison('id', '>', 10),
  )]
  class OrderSummary extends BaseSummary
  ```

  A condition that is not a comparison, e.g. `INSTANCE OF` or a comparison
  between two fields, can be written in DQL with `SourceCondition`, with the
  property paths in brackets:
  `new SourceCondition('[paidAt] > [createdAt]')`. The filter can only use
  constant values. Unlike `modifyQueryBuilder()`, the properties it reads
  are tracked, so a change to one of them refreshes the summary. A path
  through a to-many association, e.g. `orders.time` on a `Customer`
  summary, is rejected (#740): the join would summarize a source entity more
  than once. Use a `SourceCondition` with an `EXISTS` subquery instead.

* `SummaryManager` has two new methods, `markDirty()` and `markDirtyRange()`
  (#731). A custom implementation of the interface must implement them.

* Changes to related entities now refresh the summary (#735). A summary
  that reads a property through an association, e.g. `customer.country`, is
  refreshed when that property changes, or when the related entity is
  deleted. This can create more dirty flags than before. To keep the
  previous behavior for a summary, use
  `#[Summary(trackAssociations: false)]`.

### Added

* `#[Summary(filter: ...)]`: a declarative filter of the source entities, see
  above (#737).
* `SummaryManager::markDirty()` and `markDirtyRange()`: report changes the
  framework cannot detect, e.g. a DQL `UPDATE`, native SQL, or a database
  cascade (#731).
* `rekalogika:analytics:sweep` and the `RefreshAgentSweepCommand` message:
  dispatch the refresh agent for summaries with dirty flags but no pending
  run, e.g. after a lost message (#733). New configuration option
  `refresh.sweep_grace`, 300 seconds by default.
* `rekalogika:analytics:status`: the pending dirty flags, the scheduled
  refresh, and the latest summarized key against the latest source key
  (#733).
* `rekalogika:analytics:debug:source`: the properties tracked for a source
  class, and the summaries that depend on them (#732). It warns about the
  paths of dimensions and measures read through a to-many association, whose
  changes are not detected (#740).
* `rekalogika:analytics:trigger:dump`, `trigger:install` and `trigger:drop`:
  PostgreSQL triggers that create the dirty flags for changes made outside
  the ORM (#736).
* `rekalogika:analytics:verify`: compare a summary with a fresh roll-up of its
  source, and optionally mark the differing partitions dirty (#738).

### Changed

* The source class and the summary class must be managed by the same entity
  manager. A `MetadataException` is thrown otherwise (#722).
* The refresh agent is dispatched on the default Messenger bus,
  `messenger.default_bus`, instead of `messenger.bus.default`, which only
  exists if the default bus has that name (#726).
* `--batchsize` of `rekalogika:analytics:refresh:range`, and the batch size
  of `SummaryManager::refresh()`, must be at least 1 (#720).
* A `NOT` expression with other than one child throws (#724).
* The pivot spreadsheet export no longer sets an autofilter. The flat export
  keeps it (#724).
* A non-zero number below 1 is formatted with 3 significant digits instead
  of 3 fraction digits, so `0.000123` no longer renders as `0` (#721).
* An object the default stringifier cannot handle renders as `object`,
  instead of its class name and identifier (#721).

### Fixed

Change detection and refresh:

* A property read through a root cast, e.g. `(IndividualCustomer).gender`,
  or a field of an embeddable, is now tracked (#730).
* Changing the partitioning key of a source entity refreshes the old
  partition as well as the new one (#720).
* Upper-level aggregates went stale after a correction in an earlier child of
  a partially covered upper partition (#715).
* A failed run of the refresh agent is retried 5 minutes later (#720).
* `rekalogika:analytics:truncate` takes the refresh lock, and truncates the
  table, the summary properties and the dirty flags in one transaction
  (#720).
* UUID partitions gave wrong bounds for values with the highest bit set
  (#718).

Queries:

* `NOT IN` with an empty list matched nothing, instead of everything (#714).
* Time bins had wrong boundaries where a DST transition falls at midnight.
  Database values with extra digits, e.g. the quarter `202613`, are rejected
  (#719).
* The SQL shown for debugging escapes literals correctly for PostgreSQL, and
  only replaces positional parameters outside literals and identifiers
  (#727).
* `NEXTVAL()` is generated with a quoted sequence name (#727).
* A property path with an empty segment, e.g. `customer..name`, is rejected
  (#727).

Bundle:

* Value serializers and distinct values resolvers registered for a specific
  class did not work (#716).
* `rekalogika:analytics:refresh` and `truncate` print an error instead of
  crashing when run with `--no-interaction` and no class (#726).

Frontend and UX panel:

* Charts no longer fail on non-finite values (`INF`, `NaN`), which now show
  as gaps (#721).
* Several fixes to the pivot table and spreadsheet renderers with no
  measures or an empty result, to the escaping of time bins in HTML, and to
  missing values in pie charts (#724).
* The date range filter: change events, one-sided ranges, and locale loading
  under both AssetMapper and Webpack Encore (#717, #723).
* Several panels on the same page no longer share element ids. The filter
  labels point to their inputs (#723).
