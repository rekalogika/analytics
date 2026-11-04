# Changelog

## Unreleased

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

### Fixed

* A property read through a root cast, e.g. `(IndividualCustomer).gender`,
  or a field of an embeddable, is now tracked (#730).
