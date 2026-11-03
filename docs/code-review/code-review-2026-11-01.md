# Code review findings: rekalogika/analytics

Date: 2026-11-01
Baseline: `main` at `fc20c5a4` (refactor: remove Dimension::$mandatory, #682)
Last updated: 2026-11-03 against `main` at `f139750b` (#710)

## Status

Findings are marked **FIXED** (with the PR) or **OPEN**. Line numbers in the
text refer to the baseline commit and have shifted in many files since.

| Severity | Fixed | Open |
|----------|-------|------|
| P1       | 9 (items 1-9) | 0 |
| P2       | 25 | 0 |
| P3       | 23 | 0 |

All findings are fixed. What remains open is listed under
[Still open](#still-open): the bin validation left over from the time bin
fixes, and a note recorded with P1-6. All suggestions, including the tooling
drift, are done.

Fixed, by PR (all merged to `main` on 2026-11-02 or 2026-11-03; the PRs were
squash-merged, so commit hashes from the PR branches are not on `main`):

* #687 (`3ca03a0f`): P1-1, UUID-partitioned summaries and upper dirty flags.
* #688 (`81850efc`): P1-2, batched summary-to-summary roll-ups.
* #689 (`b5d65b4c`): P1-3, single-key manual refresh ranges.
* #690 (`5a732676`): P1-4, hour bin arithmetic across DST.
* #691 (`9f5caa6d`): P1-5, choice filter XSS (`analytics-ux-panel`).
* #692 (`ee8a6a0a`): P1-6, transaction rollback and tolerant lock release.
* #693 (`9bb241b4`): P1-7, `Query::dice(null)` result cache.
* #694 (`b703722a`): P1-8, drill-down order after `orderBy(measure)`.
* #695 (`8ebfdc04`): P1-9, inserts committed out of key order.
* #696 (`06f05f40`): P2, non-matching `--resume` id.
* #697 (`16061789`): P2, memoized post-refresh latest-key check.
* #698 (`7d3c8c9e`): P2, `PartitionMetadata` attribute collection.
* #699 (`23607e47`): P2, `DoctrineValueSerializer` breaking the serializer
  chain.
* #700 (`fa6e4db6`): P2, coordinates DTO round trip through strings and the
  dead `NullCell` path.
* #701 (`914c1e6d`): P2, the three query engine findings, plus the follow-up
  to the coordinates finding (gap-filled cells could not be serialized or
  sliced, an intermittent CI failure).
* #702 (`f892b36b`): P2, all eight time bin findings, plus localized dates in
  the week titles.
* #703 (`87663ff3`): P2, all nine frontend and UX panel findings.
* #704 (`97bb3615`): P3, 22 of the 23 findings (the 23rd, the empty
  `packages/cube-contracts/` directory, was untracked and is gone).
* #705 (`96cb51b6`): robustness suggestions.
* #706 (`4569dfb7`): performance suggestions.
* #707 (`d1ed2288`): API and UX footgun suggestions.
* #708 (`c44a754a`): static analysis with Symfony 8.1 (tooling drift).
* #709 (`ea7d311d`): float deprecations in the test app and the chart color
  dispenser (tooling drift).
* #710 (`f139750b`): the remaining suggested tests.

At `f139750b`, PHPStan and Psalm report no errors and the full PHPUnit suite
passes without deprecation notices (414 tests, 44 308 assertions) with
Symfony 8.1.10, brick/math 0.14.8 and PHP 8.5.12.

### Still open

Checked against `f139750b` on 2026-11-03:

* **Some monotonic bins accept invalid database values.** `Date`, `Hour`,
  `Quarter` and `IsoWeekWeek` throw `InvalidArgumentException` since #702 and
  #704; `Month` (202413 starts 2025-01), `IsoWeekDate`, `MonthBasedWeekWeek`
  and `MonthBasedWeekDate` still overflow silently.
* ~~Notes recorded with P1-6, design caveats rather than bugs:
  `RefreshAgentLock` has no logger, so a failed release that falls back to
  closing the connection is not logged; with persistent connections that
  fallback does not free the advisory lock.~~ Fixed in the working tree (not
  yet in a PR). The fallback also had a bug: symfony/lock's
  `DoctrineDbalPostgreSqlStore` keeps held keys in a static in-memory store
  keyed by the `Connection` object, which survives `close()`, so afterwards
  the same process could never acquire that lock again. `RefreshAgentLock` is
  now an interface. The default `PostgreSqlRefreshAgentLock` issues the
  advisory lock SQL itself (same lock key), retries the unlock after
  reconnecting, and logs a warning, or an error if the lock might still be
  held. `SymfonyRefreshAgentLock` uses any symfony/lock `LockFactory`, chosen
  with `rekalogika_analytics.refresh_lock.factory`; its TTL
  (`refresh_lock.ttl`, default 300 seconds) is extended before each refresh
  step.

## Scope and method

* All five packages were read in full (about 40k lines of PHP plus templates
  and Stimulus controllers), split across five parallel reviewers by area.
* PHPStan (level max) and Psalm pass on the packages. The full PHPUnit suite
  passes (245 tests, 12 312 assertions) against the local PostgreSQL.
* Each finding was traced to its callers. The highest-impact items were
  reproduced with throwaway scripts or tests against the test database; those
  are marked "reproduced". Nothing in the repository was modified.
* Confidence: **high** means the mechanism is certain and usually reproduced,
  **medium** means code-reasoned but dependent on data or deployment.

Severity key:

* **P1**: wrong aggregates, data loss, security, or a hang.
* **P2**: wrong results in a specific but realistic situation, or a crash on
  legitimate input.
* **P3**: incorrect but low impact, latent, or cosmetic.

---

## P1: data correctness, security, hangs

### 1. UUID-partitioned summaries never mark upper partition levels dirty — FIXED (#687)

* `packages/analytics-core/src/Engine/SummaryRefresher/SummaryRefresher.php:443-468`
  (`isNecessaryToMarkUpperPartitionAsDirty`), callers at lines 397 and 413-417.
* For summaries partitioned with `StringUuidToTruncatedInteger` +
  `UuidV7IntegerPartition` (e.g. `tests/src/App/Entity/CustomerSummary.php`),
  `SummaryHandler::getLatestKey()` returns the raw source value, a UUID
  string. The method compares it with `Partition::getUpperBound()`, an
  integer in the truncated 48-bit domain. PHP 8 compares int vs non-numeric
  string as strings, so `$partition->getUpperBound() > $summaryLatestKey` is
  always true and `$upperPartition->getUpperBound() <= $summaryLatestKey` is
  always false.
* Scenario: CustomerSummary is fully summarized. A Customer in an old
  partition P that is not the last child of its containing partition U is
  modified or deleted. P is refreshed, `$isNew` is wrongly true, U is never
  flagged, and every level above U is stale. Queries prefer the highest
  available level, so they return pre-modification aggregates forever.
* Confidence: high. Only integer-keyed summaries are covered by
  `SourceChangeTest`.
* Fix: convert the latest key into the partition domain before comparing,
  e.g. build the lowest partition from the source value and compare upper
  bounds, or route through `PartitionValueResolver::transformSourceValueToSummaryValue()`.
* Resolution (#687, `3ca03a0f`): `refreshRange()` now converts the latest
  key once via the new `PartitionHandler::transformSourceValueToSummaryValue()`
  and passes the resulting `?int` to `isNecessaryToMarkUpperPartitionAsDirty()`,
  whose parameter is now typed `?int`. Covered by
  `SourceChangeTest::testUuidPartitionRefreshMarksUpperPartitionDirty`,
  which modifies a customer in an old partition and checks that the
  containing partition is flagged.

### 2. Batched summary-to-summary roll-ups merge several partitions into one — FIXED (#688)

* `packages/analytics-core/src/Engine/SummaryRefresher/Query/RollUpSummaryToSummaryGroupAllStrategyQuery.php:91-104`
  selects `MIN(key) AS p_key` and groups only by `p_level`, dimensions and
  groupings. `SummaryRefresher.php:266-276` applies `batch($batchSize)` to
  every level yielded by `getRangesForManualRefresh`.
* The group-all query is only correct for a single upper partition. With
  `--batchsize N > 1` the upper-level ranges are also batched N at a time,
  so N partitions are aggregated into one row set keyed by the MIN lower key
  inside the first partition. The other N-1 partitions have no rows at that
  level.
* Scenario: `refresh:range OrderSummary --batchsize 10`. Later the agent
  refreshes one of those upper partitions P2 via a dirty flag:
  `DeleteExistingSummaryQuery` deletes nothing (P2's rows live under P1's
  key), the re-insert adds P2 again, and P2 is double counted at that level
  and every level rolled up from it.
* Confidence: high. `SummaryManager::refresh()` documents batchSize as
  counting lowest-level partitions.
* Fix: batch only the lowest-level range and process upper ranges one
  partition at a time, or group by the truncated key
  (`REKALOGIKA_TRUNCATE_BIGINT(root.partition.key, :currentLevel)`) instead
  of `MIN(key)`.
* Resolution (#688, `81850efc`): the second option. The group-all query is
  now built per level (`SqlFactory::getRollUpSummaryToSummaryQuery(int $level)`,
  cached per level) and selects
  `Partition::getClassifierExpression(new IntegerValue(key), $level)` as
  `p_key`, grouping by it, so a batched range is split into its partitions.
  `withBoundary()` throws if the range level differs from the query level.
  Covered by `ManualRefreshTest::testBatchedRefreshKeepsUpperPartitionsSeparate`.
* Notes:
  * The `Partition::getClassifierExpression()` contract was widened: the
    input may now be the key of a lower-level partition rather than a source
    value. A custom `Partition` implementation that assumes the input is a
    source value (e.g. applies the source-to-summary transformation inside
    the expression) needs to be reviewed.
  * `debug:summary` now prints the summary-to-summary SQL for the level just
    above the lowest, and prints nothing for single-level partitions.
  * `batchSize` is now applied uniformly at every level and is correct there;
    the `SummaryManager::refresh()` doc, which described it as counting
    lowest-level partitions, was updated in #705.

### 3. Manual refresh silently skips single-key ranges — FIXED (#689)

* `packages/analytics-core/src/Engine/SummaryRefresher/SummaryRefresher.php:235-256`.
* "No new entities" (start = latest+1 > end = latest) is handled by clamping
  `$end = $start`, then returning on `$start === $end && $summaryLatestKey !== null`.
  That condition is indistinguishable from legitimate single-key work.
* Scenarios: (a) latest key is 100, the source has exactly one new row 101;
  `refresh(class, null, null)` computes start = end = 101 and returns, so the
  row is not summarized and the latest key does not advance until a second
  row arrives. (b) `refresh:range OrderSummary 500 500` on a non-empty
  summary is a no-op although `Contracts/SummaryManager.php` documents the
  bounds as inclusive.
* Confidence: high.
* Fix: decide "nothing to do" before mutating `$end` (`if ($start > $end)`
  dispatch the end event and return) and remove the `$start === $end` return.
* Resolution (#689, `b5d65b4c`): `manualRefresh()` computes a single
  `$isNothingToDo` flag before touching `$end`: true when `$end` is null,
  when `$start > $end`, or when `$start === $end` and the start is the
  already-summarized latest key. The last case only arises for non-integer
  keys (UUID strings), where the stored latest key cannot be incremented and
  is used as the start itself. Both scenarios are covered by
  `SourceChangeTest::testManualRefreshOfSingleNewRecord` and
  `testManualRefreshOfSingleKeyRange`.
* Note: for string-keyed summaries, an explicit `refresh:range X K K` where
  K equals the stored latest key is treated as a no-op by design; only the
  `start === null` path sets `$isStartAlreadySummarized`, so explicit ranges
  are always processed.

### 4. Descending hour sequence loops forever across a DST spring-forward — FIXED (#690)

* `packages/analytics-core/src/Time/Bin/Gregorian/Hour.php:135-144`
  (`getPrevious()` does `$this->start->modify('-1 hour')` then `format('YmdH')`).
* In a DST zone (America/New_York, Europe/Berlin) bin 2025030903 starts at
  03:00 EDT. `modify('-1 hour')` lands on the non-existent 02:00 wall clock,
  which PHP normalises forward to 03:00, so `getPrevious()` returns the same
  cached instance. Reproduced: `prev of 2025030903 = 2025030903, same object`.
* `DefaultSequence::getDescendingIterator()`
  (`Engine/Sequence/DefaultSequence.php:113-126`) only stops when it reaches
  `$last`, so a query ordered descending on an hour dimension whose range
  crosses the spring-forward makes `GapFiller` push the same bin until memory
  is exhausted. Ascending works because `+1 hour` is elapsed time in PHP.
* Related: `createFromDatabaseValue(2025030902)` (reachable via the number
  range filter) yields a bin 03:00-04:00 that overlaps bin ...03.
* Confidence: high (reproduced).
* Fix: do hour arithmetic on instants (convert to UTC, add or subtract the
  hour, convert back), in both `getNext()` and `getPrevious()`.
* Resolution (#690): `Hour` now does all arithmetic on
  Unix timestamps. `$end` is the first instant whose `YmdH` label differs
  from the start's (`getNextHourBoundary()`), so a fall-back bin such as
  2025110201 correctly spans two hours and a spring-forward skips the
  missing label. `getNext()` is the bin at `$end`; `getPrevious()` is the bin
  at `start - 3600 s`. A database value in a spring-forward gap
  (2025030902 in New York) is now an empty bin (`end === start`) whose
  neighbours are ...01 and ...03, instead of overlapping bin ...03.
  Covered by `TimeBinTest::testHourSequenceAcrossSpringForward`,
  `testHourInSpringForwardGapIsEmpty`, `testHourSequenceAcrossFallBack`
  and `testHourDescendingWalkAcrossSpringForwardTerminates`.

### 5. Stored XSS in the choice filter dropdown — FIXED (#691)

* `packages/analytics-ux-panel/templates/filter/choice_filter.html.twig:15`
  emits `data-html="{{ choice.label }}"` (plain-text label, not
  `choice.htmlLabel`).
* `packages/analytics-ux-panel/assets/src/choice-filter-controller.js:21-28`
  renders options and items with `` `<div>${data.html}</div>` `` without the
  `escape` callback. Tom Select builds option data from `option.dataset`, so
  the browser-decoded attribute value is injected into innerHTML verbatim.
* Scenario: an entity dimension whose `__toString()` returns a user-supplied
  name such as `<img src=x onerror=alert(1)>` is added to Filters; the script
  runs for every viewer.
* Confidence: high.
* Fix: emit `data-html="{{ choice.htmlLabel }}"` (already escaped by the
  Htmlifier), or drop the custom render callbacks so Tom Select escapes `text`.
* Resolution (#691): the second option. The render
  callbacks now return `` `<div>${escape(data.text)}</div>` `` (the option's
  text content, escaped by Tom Select), and the template no longer emits
  `data-html`. The HTML label stays disabled as in #415; re-enabling it would
  mean passing `choice.htmlLabel` and trusting the Htmlifier's escaping.
  `assets/dist/choice-filter-controller.js` was rebuilt.

### 6. Transactions are never rolled back; the lock release masks the real error — FIXED (#692)

* `SummaryRefresher.php:399-437` (`refreshRange`), `:537-592`
  (`convertNewRecordsToDirtyFlags`), `:286-297` (`manualRefresh`);
  `Engine/RefreshAgent/RefreshAgent.php:40-57`;
  `packages/analytics-bundle/src/Command/RefreshCommand.php:97-103`;
  `Engine/SummaryManager/DefaultSummaryManager.php:63-74`.
* `beginTransaction()` has no try/catch with `rollBack()`. If a roll-up
  statement fails, the DBAL nesting level stays at 1 and PostgreSQL is in an
  aborted transaction. The `finally` block calls `RefreshAgentLock::release()`,
  which runs `SELECT count(*) FROM pg_locks` through the same connection,
  throws, and replaces the original exception. In a messenger worker without
  the opt-in ping middleware every later message on that connection fails
  with "current transaction is aborted" until restart, and the advisory lock
  stays held.
* Confidence: high for the mechanics; impact depends on deployment.
* Fix: wrap each transaction (or use `Connection::transactional()`), and make
  `release()` tolerant (catch, or check `isTransactionActive()` first).
* Resolution (#692): both parts of the fix.
  * `SummaryRefresher`: `refreshRange()` and `convertNewRecordsToDirtyFlags()`
    now run their bodies (moved to `doRefreshRange()` and
    `doConvertNewRecordsToDirtyFlags()`) through `Connection::transactional()`.
    The conditional commit/rollback of the new-flag removal in
    `manualRefresh()` is wrapped in a try/catch that rolls back if the
    transaction is still active.
  * `RefreshAgentLock::release()` catches DBAL exceptions from the store.
    On failure it closes the connection, which ends the PostgreSQL session
    and so releases the advisory lock, and discards the cached
    `DoctrineDbalPostgreSqlStore` for that connection (its in-memory store
    still remembers the key and would otherwise report a conflict on the
    next acquire). DBAL reconnects on next use. The callers
    (`RefreshAgent`, `DefaultSummaryManager`, `RefreshCommand`) are
    unchanged.
  * Covered by `ManualRefreshTest::testFailedRefreshRollsBackTransaction`:
    a `DeleteRangeStartEvent` listener runs `SELECT 1/0` inside the
    transaction. Without the fix the caller sees SQLSTATE 25P02 from the
    lock release; with it, 22012, and the connection is usable afterwards.
* Notes:
  * With persistent connections (`PDO::ATTR_PERSISTENT`) closing does not
    end the session, so the lock-release fallback does not free the lock.
    It is only reached when the connection is already broken.
  * The fallback swallows the release failure on the success path too. That
    is acceptable because closing the connection frees the lock, but the
    failure is not logged (`RefreshAgentLock` has no logger).

### 7. `Query::dice(null)` returns the previously diced result — FIXED (#693)

* `packages/analytics-core/src/Engine/SummaryQuery/DefaultQuery.php:188-197`.
* `dice()` clears `$this->dice` but only resets `$this->result` indirectly
  through `andDice()`, which is skipped when the predicate is null.
* Reproduced: `dice(eq('time.civil.year', 2024))->getResult()` gives count
  104; `dice(null)->getResult()` on the same query still gives 104 while a
  fresh query gives 190. `Contracts/Query.php:179` promises the cache is
  invalidated when any parameter changes.
* Fix: `$this->result = null;` at the top of `dice()`.
* Resolution (#693): as suggested. Covered by
  `QueryTest::testDiceNullResetsResult`, which fails with 104 vs 190
  without the fix.

### 8. Measure `orderBy()` is discarded when drilling down — FIXED (#694)

* `packages/analytics-core/src/Engine/SummaryQuery/DimensionFactory/MetadataOrderByResolver.php:29-52`
  returns the dimension's metadata order (default `Order::Ascending`) whenever
  the query's `orderBy` does not name that dimension.
  `DimensionFieldCollection::getSorted()` then re-sorts members in PHP by
  member value and discards the DB order that
  `SummaryQuery::addUserSuppliedOrderBy()` (`Query/SummaryQuery.php:429-469`)
  produced.
* Reproduced: `withDimensions('customerType')->orderBy('count', Desc)`
  then `drillDown('customerType')` yields counts `[73, 117]`;
  `customerGender` yields `[31, 48, 38, 73]`; months come out in calendar
  order. Only `customerCountry` (array `orderBy` in metadata, resolver returns
  null) keeps DB order. `Contracts/Result/CubeCells.php` promises the order
  determined by `orderBy()`. `tests/src/IntegrationTests/QueryTest.php:355`
  asserts only the count.
* Fix: return `null` (keep DB order) whenever the query has an explicit
  `orderBy` that is not for this dimension, at minimum whenever a measure is
  in the `orderBy`.
* Resolution (#694): `MetadataOrderByResolver` walks the
  query's `orderBy` in sequence. If it reaches the dimension first, it
  returns that order as before; if it reaches a measure first, it returns
  `null` and the DB order is kept; otherwise it falls back to the metadata
  order. So `orderBy(count)` keeps the count order for every dimension, while
  `orderBy(customerType)->addOrderBy(count)` still sorts `customerType` by
  member. A dimension-only `orderBy` that does not name the dimension still
  uses the metadata order, because the DB does not order that dimension.
  `DimensionFieldCollection::tryFillGaps()` now skips gap filling when the
  order is `null`: `GapFiller` builds the sequence from the first to the last
  member of the list, so on a count-ordered month list it would have dropped
  and reordered members. The only dimensions that had a `null` order before
  are entity dimensions with an array `orderBy`, which are not sequences.
  Covered by `QueryTest::testOrderByMeasureDrillDownOrder` (customerType,
  customerGender, customerCountry and months, ascending and descending; six
  of the eight cases fail without the fix) and
  `testOrderByDimensionThenMeasure`.
* Note: a sequence dimension ordered by a measure is no longer gap filled,
  so months with no rows are absent from the drill-down instead of appearing
  as interpolated cells. Interpolated cells have no meaningful place in a
  list sorted by value.

### 9. Inserts committed out of id order are never summarized — FIXED (#695)

* `SummaryRefresher.php:539-550` and `:583-587`;
  `Engine/Handler/SourceHandler.php:37-48` (creation flags carry no key);
  `SummaryHandler::getNewEntitiesRange` lines 153-157.
* New rows are found purely by `summaryLatestKey < sourceMaxKey`. If a
  transaction holding lower ids commits after a refresh has advanced the
  latest key past them, the next run sees no new range, returns null, and
  `removeNewFlags()` deletes their flags. Their partition was refreshed
  before they were visible.
* Scenario: a batch import reserves ids 1000-2000 and stays open; web
  requests insert 2001-2050 and the agent runs (latest key becomes 2050);
  the import commits; the next run finds nothing and drops the flags. Rows
  1000-2000 are missing until an unrelated change dirties those partitions.
  UUID v7 keys have the same issue when clock and commit order diverge.
* Confidence: medium (design-level race, reachable with ordinary sequences
  and long transactions).
* Fix: for inserts, record the entity's lowest partition in the flag when the
  id is already known at persist time, or convert new flags to partition
  flags in `postFlush`; alternatively never delete new flags whose `created`
  is newer than the last `updateLatestKey` time.
* Resolution (#695): an insert now also creates the dirty
  flag of the lowest partition containing the new entity, so it no longer
  depends on the latest-key comparison. The flag is committed in the same
  transaction as the entity: if a refresh runs before the commit, the flag is
  invisible and survives `removeDirtyFlags()`, and the next run refreshes the
  partition. The partition is then below the latest key, so the containing
  partitions are marked dirty as for a modification.
  * `SourceEntityListener` creates these flags in a new `postPersist`
    handler (registered in `analytics-bundle/config/engine.php`), not in
    `onFlush`. `postPersist` runs after the insert, so a key generated by the
    database is known, and before the commit. The ORM has already executed
    its inserts by then, so the flags are written with DBAL
    (`insertDirtyFlag()`), using the mapping of `DirtyFlag` and its ID
    generator unless it is an identity column.
  * The new entity flag is still created, so the latest-key advance and the
    refresh timing are unchanged.
  * New `SourceHandler::generatePartitionDirtyFlagsForEntityCreation()` and
    `PartitionHandler::tryGetLowestPartitionFromEntity()`. An entity whose
    partitioning key is still null after the insert gets no partition flag.
  * Covered by `SourceChangeTest::testRecordCommittedAfterLatestKeyIsSummarized`:
    it inserts an order, moves the latest key to the order's id as if a
    refresh had run before the commit, then runs the refresher. Without the
    fix the count is 190 instead of 191. `testSourceCreation` now expects
    both flags.
* Notes:
  * Requiring sequence (or other pre-insert) ID generation instead was
    considered and rejected. Doctrine recommends `IDENTITY` on PostgreSQL
    with DBAL 4, which is also what `AUTO` resolves to, and `Order` in the
    test app uses `IDENTITY` explicitly. Requiring it would also not cover
    partitioning keys that are not the ID.
  * Rows written outside the ORM (DBAL, raw SQL) produce no flags, as before,
    and are still found only by the latest-key comparison.
  * Each insert flush now writes one extra flag row per involved summary and
    distinct lowest partition.

---

## P2: wrong results or crashes in realistic situations

### Refresh pipeline

* **A non-matching `--resume` id still advances the latest key and deletes new flags.**
  `SummaryRefresher.php:260-297`. With `$resumeId` set, `$doWork` only becomes
  true when a batch signature matches. Signatures include the range bounds,
  so they change with `--batchsize`. Mistype the id or change the batch size
  and the run reports success, sets the latest key to the source max, and the
  skipped rows are never summarized by the agent. Fix: throw if the loop ends
  with `$doWork === false`; only update the latest key and remove new flags
  when at least one range was processed.
  **FIXED (#696)**: `manualRefresh()` throws the
  contracts `InvalidArgumentException` after the loop when `$doWork` is still
  false, before the latest key is updated and the new flags are removed. A
  resume id that matches marks every batch from it onward, so a run that
  processed at least one range always passes. The message says that resuming
  needs the same arguments and batch size. Covered by
  `ManualRefreshTest::testNonMatchingResumeIdFails`, which sets the latest
  key to 100, adds a new flag, and checks both survive a run with an unknown
  id. Note: with `start` and `end` omitted, `end` is the current source max,
  so if new rows arrive between the interrupted run and the resume, the
  last batches of each level can get different signatures;
  a resume id from those batches now fails instead of silently skipping, and
  the run has to be restarted without `--resume`.
* **Post-refresh "did new rows arrive?" check reads a memoized value.**
  `SummaryRefresher.php:289-297`, cache in
  `Engine/Handler/SourceOfSummaryHandler.php:62-66`. The re-read returns the
  same value as the first read, so the rollback branch is dead; new flags
  created during a long manual refresh are deleted and their creation times
  (used for strategy timing) are lost. Rows themselves are still picked up by
  key range later. Fix: reset the handler or add an uncached query.
  **FIXED (#697)**: the second option. New
  `SourceOfSummaryHandler::refreshLatestKey()` always queries the source and
  updates the cached value; `manualRefresh()` uses it for the check after
  `removeNewFlags()`, so if rows arrived during the refresh the removal is
  rolled back and their new flags survive for the agent. Covered by
  `ManualRefreshTest::testRecordArrivingDuringRefreshKeepsNewFlag`, which
  inserts an order from a `DeleteRangeStartEvent` listener and checks that
  the new flag survives and the latest key stays at the end of the
  refreshed range; without the fix the flag is gone. Note:
  `doConvertNewRecordsToDirtyFlags()` has the same shape (it reads the range,
  then removes all new flags), so a row arriving in between loses its new
  flag there too. That is left as is: since P1-9 the insert also creates a
  partition flag carrying its creation time, and the row is still found by
  key range because the latest key is set from the earlier read.

### Metadata and serialization

* **`PartitionMetadata` receives the wrong attribute collection.**
  `Metadata/Implementation/DefaultSummaryMetadataFactory.php:246-286`. The
  `$attributes` parameter (attributes of the summary's `#[Partition]`
  property) is overwritten inside the `foreach` over the partition class's
  properties, so `new PartitionMetadata(attributes: $attributes)` gets the
  attributes of whichever property was iterated last. Fix: rename the loop
  variable.
  **FIXED (#698)**: as suggested; the loop variable is now
  `$propertyAttributes`. Covered by
  `MetadataTest::testPartitionMetadataAttributes`, which checks that the
  `OrderSummary` partition metadata carries `#[Partition]` and not
  `#[PartitionKey]` or `#[PartitionLevel]`; without the fix it fails.
* **`DoctrineValueSerializer` breaks the serializer chain.**
  `Engine/Serialization/DoctrineValueSerializer.php:59-67` with
  `packages/analytics-bundle/src/Serialization/ChainValueSerializer.php:73-78, 102-107`.
  The chain only continues on `UnsupportedValue`, but for a non-entity object
  (a raw `DateTimeImmutable` member, a value object)
  `new ClassMetadataWrapper(...)` throws `UnexpectedValueException`, and for
  an entity with an object identifier (Symfony `Uuid`, `Ulid`)
  `getStringIdentifierFromObject()` throws `MetadataException`
  (`Metadata/Doctrine/ClassMetadataWrapper.php:195-203`). A user serializer
  registered for those cases is never reached. Booleans fall through to
  `UnsupportedValue` and surface as "No serializer found", so boolean
  dimensions are unserializable out of the box. `ValueSerializerPass` and
  `DistinctValuesResolverPass` use `findTaggedServiceIds()` and ignore tag
  `priority`, so users cannot order their serializer first. Fix: throw
  `UnsupportedValue` when no manager or non-scalar id; handle `bool` and
  `Stringable` ids; sort tagged services by priority.
  **FIXED (#699)**: all four parts.
  * `DoctrineValueSerializer::serialize()` throws `UnsupportedValue` for an
    object whose class has no ORM manager, that is not an entity (embeddable
    or mapped superclass), or whose identifier cannot be converted to a
    string (composite, or an object that is not `Stringable`). Booleans
    serialize to `'true'`/`'false'`; `deserialize()` turns them back into a
    `bool` for `boolean` columns (via `FILTER_VALIDATE_BOOL`) and throws
    `InvalidArgumentException` for anything else. It also throws
    `UnsupportedValue` for date and time columns instead of returning the
    raw identifier, so a user serializer for a `DateTimeImmutable` member is
    reached in both directions.
  * `ClassMetadataWrapper::getStringIdentifierFromObject()` accepts
    `Stringable` identifiers (Symfony `Uuid`, `Ulid`); new `isEntity()`.
  * `ValueSerializerPass` and `DistinctValuesResolverPass` use
    `PriorityTaggedServiceTrait`, so non-specific services are tried in tag
    priority order, highest first. The Doctrine serializer and resolver are
    now tagged with priority -1000, like the built-in stringifiers, so user
    services at the default priority run before them. The `ValueSerializer`
    and `DistinctValuesResolver` docblocks describe the order.
  * Covered by `ValueSerializerTest` (boolean round trip and invalid
    boolean, `DateTimeImmutable` and embeddable rejected with
    `UnsupportedValue`, a chain reaching a date serializer placed after the
    Doctrine one) and `ChainServicePassTest` (priority order for both
    passes). All eight fail without the fix. The test app's
    `EmptyEntitySummary` gained a nullable `active` boolean dimension for
    this; existing test databases need `doctrine:schema:update` or
    `make doctrine-schema-create`.
  * Notes:
    * The new default order is a behaviour change: a non-specific user
      serializer or resolver at the default priority used to run after the
      Doctrine one and now runs before it, so it must throw
      `UnsupportedValue` (or return null) for values it does not handle, as
      the contract already requires.
    * No test entity has a `Uuid` or `Ulid` identifier, so the `Stringable`
      branch is not covered by a test.
    * Scalars were still returned uncast by `deserialize()`; that is fixed
      with the next finding.
* **Coordinates DTO round-trip through strings fails; the `NullCell` path is dead.**
  `Serialization/Mapper/DefaultCoordinatesMapper.php:142-155`. The code
  checks `$cube === null` to build a `NullCell`, but `DefaultCell::slice()`
  (`Engine/SummaryQuery/Output/DefaultCell.php:163`) is non-nullable and
  `CellRepository::getCellByBaseAndDimension()` throws when no member
  matches. Members match by `hash(serialize($member))`, and
  `DoctrineValueSerializer::deserialize()` (`:132-134`) returns scalars
  unchanged, so `"202301"` from a query string never matches stored `202301`,
  floats never round-trip (`1.5` becomes `"1.5"`), and a member missing from
  the result throws instead of yielding `NullCell`.
  `ValueSerializerTest::testIntegerDeserialization` passes only because
  `assertEquals` is loose. Fix: cast scalars according to `getScalarType()`
  in `deserialize()`, and make `slice()` honour its `?self` contract or catch
  the "member not found" case in `fromDto()`.
  **FIXED (#700)**: both parts, `slice()` by the first
  option.
  * `DoctrineValueSerializer::deserialize()` returns an `int` for `smallint`,
    `integer` and `bigint` fields (`FILTER_VALIDATE_INT`) and a `float` for
    `float` and `smallfloat` fields, and throws `InvalidArgumentException`
    for an identifier that is not a valid number. Other scalar types
    (`string`, `decimal`, ...) are returned unchanged as before; their
    members are strings already.
  * `serialize()` formats floats with `var_export()`, which uses
    `serialize_precision`, instead of a string cast, which uses `precision`
    and turned `0.1 + 0.2` into `"0.3"`.
  * `DefaultCell::slice()` returns `null` when the member does not occur in
    the result for the dimension, as `CubeCell::slice(): ?self` allows; the
    contract docblock now says so. `CellRepository::getCellByBaseAndDimension()`
    returns null in that case instead of throwing, so `fromDto()` reaches the
    `NullCell` branch. A member that occurs in the result but has no row at
    the resulting coordinates still gives a null `DefaultCell`, as before.
  * `getCellByBaseAndDimension()` now throws `InvalidArgumentException` if
    the dimension is not among the query's dimensions. Before, a typo was
    reported as the member not being found; with the null return it would
    otherwise have passed silently.
  * Covered by `CoordinatesMapperTest::testRoundTripThroughStrings` (month
    and customer type members stringified, as from a URL),
    `testMemberMissingFromResultYieldsNullCell`,
    `ValueSerializerTest::testIntegerDeserialization` (now `assertSame`),
    `testInvalidIntegerDeserialization`, `testFloatRoundTrip`, and
    `QueryTest::testSliceMemberMissingFromResult` and
    `testSliceDimensionNotInQuery`. The first five fail without the fix.
  * Notes:
    * `slice()` returning null is a behaviour change for callers that relied
      on the exception. `CubeCellAdapter::slice()` (pivot table) already
      turned a null into a `LogicException`, so it still throws there.
    * The float round trip is tested on a float measure field
      (`OrderSummary::$priceSumSquare`), since no test summary has a float
      dimension; `deserialize()` does not distinguish them.
    * `INF` and `NAN` serialize to `"INF"`/`"NAN"`, which `deserialize()`
      rejects as invalid floats.
    * Follow-up (#701): `testRoundTripThroughStrings` failed
      intermittently in CI (run 41451918810, one job) with "No serializer
      found ... value Month". Fixture dates are random, and when a month had
      no orders, the gap filler's interpolated cells carried the `Month`
      object as their raw member instead of the stored integer, so `toDto()`
      could not serialize them. `slice()` also could not find interpolated
      members until something had triggered gap filling, so `fromDto()`
      returned a `NullCell` for them. Fixed together: `GapFiller` uses
      `getDatabaseValue()` as the raw member of a `DatabaseValueAware`
      member, and `DimensionFieldCollection::getDimensionByMember()` fills
      the gaps on a miss. Covered deterministically by
      `CoordinatesMapperTest::testRoundTripOfInterpolatedMembers`, which uses
      a dice to leave a two-month gap.

### Query engine

* **`CubeCell::find()` never calls a callable for `MeasureMember` or `Stringable` members.**
  `Engine/SummaryQuery/Output/DefaultCell.php:183-194`. The `is_callable`
  branch is the last `elseif`, unreachable for `@values` members and all time
  bins. Reproduced: `find('@values', fn($m) => ...)` returns null, contrary
  to `Contracts/Result/CubeCell.php:140-147`. Also, for scalar members a
  string like `'phpinfo'` is treated as a callable. Fix: test
  `$argument instanceof \Closure` first.
  **FIXED (#701)**: as suggested. A `\Closure` argument is
  tested first and is the only form that is called; any other argument,
  including a callable string or array, is matched as a value. The
  `CubeCell::find()` docblock now says so. `\Closure` was added to the
  dependencies allowed for the engine in `ArchitectureTest`. Covered by
  `QueryTest::testFindWithClosure` (`@values` and month members) and
  `testFindDoesNotCallCallableString` (`find('customerType', 'is_object')`
  matched the first enum member before); both fail without the fix. Note:
  passing a non-`Closure` callable is a behaviour change for scalar and enum
  members, which used to call it; wrap it with `$callable(...)` instead.
* **Null member of a sequence dimension is dropped by the gap filler.**
  `Engine/SummaryQuery/DimensionFactory/GapFiller.php` (constructor skips
  null, marked `@todo`); `DefaultCells::getResult()` always uses the
  gap-filled output. `DimensionFieldCollection::tryFillGaps()` checks
  `$dimensions[0]` after sorting, so with `Order::Descending` and a null
  present (null sorts first) gap filling is silently skipped. Scenario: a
  nullable time-bin dimension such as "shipped date": `drillDown()` cells do
  not add up to the apex total. Confidence: medium (test data has no null
  sequence members). Fix: append or prepend the null dimension in
  `GapFiller`; look for the first non-null member in `tryFillGaps`.
  **FIXED (#701)**: as suggested. `GapFiller` keeps the
  null-member dimension aside and puts it back at the end where it was in
  the sorted input (last in ascending, first in descending order).
  `tryFillGaps()` decides on the first non-null member, and leaves a list
  with only a null member untouched. Covered by
  `DimensionFactoryTest::testNullMemberOfSequenceDimension` (unit, month
  members, both orders) and `SourceChangeTest::testNullMemberOfSequenceDimension`
  (inserts an unshipped order, refreshes, and checks for `shippedDate` in both
  orders that the drill-down adds up to the apex total, that the null member
  is at the right end, and that the other members are a gapless sequence).
  All four fail without the fix: ascending sums to 190 instead of 191,
  descending is not gap filled. For this the test app's `Order::$shipped` is
  now nullable; existing test databases need `doctrine:schema:update` or
  `make doctrine-schema-create`.
* **Class-cast join uses the parent entity's identifier field.**
  `SimpleQueryBuilder/Path/BaseEntity.php` (`createBaseEntityWithCast()`,
  lines ~180-205). `getIdOfClass()` resolves the id of `$this->baseClass`
  (e.g. `Order`), but the `WITH castedAlias.id = relatedAlias.id` condition is
  on the related class. `resolve('customer(IndividualCustomer).gender')` with
  `Order::$orderId` and `Customer::$id` produces `WITH _a1.orderId = _a0.orderId`.
  Masked because every test entity uses `id`. Fix: use
  `$baseEntityWithoutCast->getIdOfClass()`.
  **FIXED (#701)**: as suggested. For a cast at the root
  (`(IndividualCustomer).gender`) the entity without the cast is the root
  itself, so that path is unchanged. Covered by
  `PathResolverTest::testManyToOneEntityCastWithDifferentIdentifierName`,
  using a new test entity `ClassCast\Shipment` (identifier `shipmentId`,
  many-to-one to `Customer`); without the fix the DQL has
  `WITH _a1.shipmentId = _a0.shipmentId`. The entity adds a `shipment` table,
  so existing test databases need `doctrine:schema:update` or
  `make doctrine-schema-create`.

### Time bins

* **Month labels use ICU week-year `YYYY`.**
  `Time/Bin/Gregorian/Month.php:109` (`'MMMM YYYY'`), also
  `Bin/MonthBasedWeek/MonthBasedWeekWeek.php:160`. Reproduced:
  `Month::createFromDatabaseValue(202101)->trans()` in de_DE, fr_FR or en_GB
  gives "Januar 2020" / "janvier 2020" / "January 2020". Any year whose
  Jan 1 falls Fri-Sun under ISO rules is affected. Fix: `'MMMM y'`.
  **FIXED (#702)**: as suggested, in both places. Covered
  by `TimeBinTest::testMonthLabelUsesCalendarYear` (202101 in de_DE, fr_FR
  and en_GB, and month-based week 2021011).
* **Month-based-week `createFromDateTime` assigns Fri-Sun to the following week.**
  `Bin/MonthBasedWeek/MonthBasedWeekWeek.php:112-113` and
  `Bin/MonthBasedWeek/MonthBasedWeekDate.php:82-83` use
  `->modify('this thursday')`, which means "next Thursday on or after today".
  Reproduced: Fri 2025-01-31 gives 2025021 (Feb week 1) while the SQL
  function `REKALOGIKA_TIME_BIN_MBW_WEEK` (`Version20250703`, which correctly
  uses `input + (4 - ISODOW)`) gives 2025015. Reachable from
  `TimeBinDateRangeFilterOptions::transformDateToObject`. Fix: compute the
  Thursday of the ISO week explicitly
  (`modify(sprintf('%+d days', 4 - (int) $dt->format('N')))`).
  **FIXED (#702)**: as suggested, in both classes. Covered
  by new `TimeBinTest::testDateTimeToObject` cases: Fri 2025-01-31 and Sun
  2025-02-02 give 2025015 (20250155, 20250157 for the date), Mon 2025-02-03
  gives 2025021.
* **`MonthBasedWeekWeek::trans()` shows the previous month when zones differ.**
  `MonthBasedWeekWeek.php:150-153` builds the date in PHP's default zone and
  only then converts to the bin zone. Reproduced: default UTC, summary zone
  America/New_York, bin 2025021 gives "Week 1, January 2025". Fix: construct
  the date directly in the bin's zone.
  **FIXED (#702)**: as suggested. Covered by
  `TimeBinTest::testMonthBasedWeekLabelInAnotherTimeZone`.
* **Week titles print day-of-year and the exclusive end.**
  `Bin/IsoWeek/IsoWeekWeek.php:65,72` and `MonthBasedWeekWeek.php:82,89` use
  pattern `'D MMMM YYYY'` (`D` is day of year) and format `$this->end`.
  Reproduced: IsoWeekWeek 202501 gives "Week starting on 365 December 2025,
  ending on 6 January 2025". Shown in the `<span title>` by
  `Formatter/TimeBinHtmlifier.php`. Fix: `'d MMMM y'` and `end - 1 day`.
  **FIXED (#702)**: as suggested. Covered by
  `TimeBinTest::testWeekTitles` ("Week starting on 30 December 2024, ending
  on 5 January 2025").
  Follow-up (#702): the dates were still formatted in
  English (`locale: 'en'`) when the title was built. They are now
  `Time\Util\TranslatableDate` parameters of the message (internal),
  formatted with the locale's long date format when the message is
  translated: "December 30, 2024" in en, "30 Desember 2024" in id, "30.
  Dezember 2024" in de. `testWeekTitles` covers en_GB and de_DE.
* **`MonthBasedWeekDate::getNext()`/`getPrevious()`/`__toString()`/`trans()` are copy-pasted ISO-week code.**
  `MonthBasedWeekDate.php:161-178, 108, 112-128`. They format `'oW'` and feed
  it into the YYYYMMWD decoder. Reproduced: `createFromDatabaseValue(20250712)`
  then `getNext()` returns database value 202527 whose start is 0022-01-16.
  Reach is limited because `getDQLExpression()` throws "Not implemented yet",
  but the class is public and unit tested for start/end only. Fix: derive
  next/previous from `start ± 1 day` and print the decoded y/m/w/d.
  **FIXED (#702)**: as suggested. `getNext()` and
  `getPrevious()` go through `createFromDateTime(start ± 1 day)`, like
  `IsoWeekDate`; `__toString()` and `trans()` print `2025-07-W1-2`, also like
  `IsoWeekDate`, which prints `2025-W27-2` in `trans()` too. The commented-out
  ISO week code was removed. Covered by
  `TimeBinTest::testMonthBasedWeekDateSequence` (labels, neighbours, the
  month boundary, and a 60-day walk checking start + 1 day and
  `getNext()->getPrevious()` identity).
* **Inconsistent locale handling in `trans()`.**
  `IsoWeekWeek.php:105`, `MonthBasedWeekDate.php:117`,
  `MonthBasedWeekWeek.php:156` pass the raw `$locale` (often null, since
  Symfony's translator forwards null) to `IntlDateFormatter`, which falls back
  to `intl.default_locale`. `Month.php:102`, `Gregorian/DayOfWeek.php:50`,
  `Gregorian/MonthOfYear.php:57` ignore `$locale` entirely. Only
  `Gregorian/Date.php:76` does `$locale ??= $translator->getLocale()`. Apply
  that everywhere.
  **FIXED (#702)**: as suggested, in `Month`, `DayOfWeek`,
  `MonthOfYear`, `IsoWeekWeek` and `MonthBasedWeekWeek` (`MonthBasedWeekDate`
  no longer formats dates). Covered by `TimeBinTest::testTransLocale`, which
  checks the translator's locale is used when none is given and the given
  one otherwise. The expected strings are ICU output (e.g. "3 mars 2025"),
  so a very different ICU version could change them.
* **`DateToInteger` diffs instants in different zones.**
  `Time/ValueResolver/DateToInteger.php:53,57`.
  `$value->diff(new \DateTimeImmutable('1970-01-01'))` compares `$value` in
  its own zone with midnight in the default zone. Reproduced:
  `2024-01-01 00:00 Asia/Jakarta` gives 19722 while the SQL side
  (`DATE(x) - DATE('1970-01-01')`) gives 19723; `1970-01-01 00:00 Asia/Jakarta`
  throws "Date cannot be before epoch". Triggers with `datetimetz` columns
  when PHP's default zone differs from the data's zone, producing partition
  keys that disagree between PHP and SQL. Confidence: medium. Fix: work on
  calendar dates only, in UTC.
  **FIXED (#702)**: `transformSourceValueToSummaryValue()`
  takes the calendar date of the value in its own zone, as midnight UTC, and
  divides its timestamp by 86 400; the epoch check is on the resulting day
  count. Covered by the new `DateToIntegerTest` (Asia/Jakarta and
  America/New_York values give 19723 for 2024-01-01, 1970-01-01 in Jakarta
  gives 0). Note: `transformSummaryValueToSourceValue()` is unchanged and
  still returns midnight in the default zone, which matches `DATE(x)` when
  the PostgreSQL session zone is PHP's default zone.
* **Invalid filter input escapes as PHP `\ValueError` / `DateMalformedStringException`.**
  `Time/Filter/TimeBinNumberRangeFilterOptions.php:47` via
  `Bin/Trait/RecurringTimeBinTrait.php:22-25` (`self::from()`). Typing "8"
  into a day-of-week range filter throws `\ValueError`; monotonic bins throw
  from `new \DateTimeImmutable('2024-13-99 ...')`. Nothing in the panel
  catches these. Fix: `tryFrom()` and the library's
  `InvalidArgumentException`, or validate in the filter options.
  **FIXED (#702)**: both.
  * `RecurringTimeBinTrait::createFromDatabaseValue()` uses `tryFrom()` and
    throws the contracts `InvalidArgumentException`. `Date` and `Hour` check
    the value with `checkdate()` (and hour <= 23) and throw the same, which
    also rejects values that used to overflow silently, such as 20240230.
    `Quarter` already threw it. Other monotonic bins still overflow silently
    (`Month` 202413 starts 2025-01). `IsoWeekWeek` validates since #704
    (see P3); the others are still open, see [Still open](#still-open).
  * `TimeBinNumberRangeFilterOptions` returns the number itself when it is
    not a valid bin. The number is compared with the database values, so
    "8" matches nothing and "5-8" means Friday to Sunday.
  * Covered by `TimeBinTest::testInvalidDatabaseValue` and
    `OccupancyHistorySummaryTest::testNumberRangeFilterWithInvalidTimeBin`,
    which runs a panel query with `dayOfWeek` filter "2,5-8,8" and compares
    the count with a dice on Tuesday and Friday to Sunday; it failed with
    `\ValueError` before.

### Frontend and UX panel

* **Unknown choice id silently becomes an "IS NULL" filter.**
  `packages/analytics-ux-panel/src/Filter/Choice/ChoiceFilter.php:96` calls
  `getValueFromId($v)`; `DefaultChoiceFilterOptions.php:42` returns `null` for
  unknown ids; `createExpression()` (lines 103-113) then emits `IN (..., null)`
  and `getChoices()` (lines 153-157) marks "(None)" as selected. Realistic
  triggers: entity choices are capped at 100 (`DoctrineFilterResolver`
  `getDistinctValues(..., 100)`), so a selected entity that drops out of the
  first 100, or a deleted entity, flips the filter to "rows with no value"
  with no error. Fix: skip or reject ids that do not resolve; only append
  `null` when `Choice::NULL` was explicitly selected.
  **FIXED (#703)**: `ChoiceFilter` skips ids that do not resolve; null is
  only selected with `Choice::NULL`.
* **Auto chart type can never pick a line chart.**
  `packages/analytics-frontend/src/Chart/Implementation/DefaultChartGenerator.php:155-164`
  (`isDimensionSequential`) looks up the dimension in `$cube->getCoordinates()`,
  but callers pass the apex cell whose coordinates are empty, so
  `createAutoChart` (lines 127-140) always falls to a bar chart. Fix: inspect
  the first child of `$cube->drillDown($dimension)` or the dimension metadata.
  **FIXED (#703)**: `isDimensionSequential()` looks at the members of the
  children of the cell instead of its coordinates. Covered by
  `ChartGeneratorTest`.
* **Grouped/stacked/multi-line y-axis title shows a member instead of the measure.**
  `DefaultChartGenerator.php:437-449` builds `$yTitle` from
  `$secondDimensionObject->getDisplayMember()`. Rows = country, columns =
  year, values = revenue gives a y-axis labelled "2023 - USD". Fix: use the
  measure label as `createBarOrLineChart` does.
  **FIXED (#703)**: the y-axis title is the measure label, as in the
  one-dimensional charts.
* **Spreadsheet export writes every plain number as a text cell.**
  `packages/analytics-frontend/src/Formatter/Chain/ChainCellifier.php:62-69`
  falls back to the Stringifier and hard-codes `DataType::TYPE_STRING`. The
  only registered cellifiers are `DefaultCellifier` (null/bool) and
  `PropertyCellifier` (`packages/analytics-bundle/config/frontend.php:264-272`),
  so a count of 1234.5 lands in Excel as the locale string "1,234.5" with no
  sums or sorting. Fix: add a default numeric cellifier (int/float to
  `TYPE_NUMERIC`, raw `(string) $value`).
  **FIXED (#703)**: `DefaultCellifier` writes finite integers and floats as
  numeric cells. Covered by `SpreadsheetRendererTest`.
* **Downloading or rendering an empty result throws an uncaught vendor exception.**
  `packages/analytics-frontend/src/Spreadsheet/SpreadsheetRenderer.php:47-55`
  and `packages/analytics-core/src/PivotTable/Adapter/Table/TableAdapter.php:50-58`.
  With no rows and `$dimensions !== []`, `getRows()` is empty and
  `TableToHtmlTableTransformer` throws "Table must have at least one row".
  The demo `/summary/download` route is a 500 whenever a filter excludes
  everything; `TableRenderer::renderTable()` shows the generic error box
  instead of the `no_data` block. Fix: short-circuit on an empty cell set.
  **FIXED (#703)**: `SpreadsheetRenderer::render()` and
  `TableRenderer::renderTable()` short-circuit on an empty table (an empty
  spreadsheet, and the `no_data` block); `TableAdapter` gets `isEmpty()`.
  Covered by `SpreadsheetRendererTest` and `TableRendererTest`.
* **`DateRangeFilter` builds `DateTimeImmutable` from raw request input.**
  `packages/analytics-ux-panel/src/Filter/DateRange/DateRangeFilter.php:135-136,154-155`;
  `createExpression()` runs inside the `PivotAwareQuery` constructor
  (`PivotAwareQuery.php:125-131`), so `filterExpressions[time.date][start]=foo`
  (the input allows free text) is a 500. Fix: validate with
  `createFromFormat('Y-m-d', ...)` and treat invalid input as empty, like
  `NumberRangesFilter` does.
  **FIXED (#703)**: dates are parsed strictly as `Y-m-d` (flatpickr's
  format); anything else, including relative formats and overflowing dates
  such as 2024-02-30, is treated as empty. Covered by `DateRangeFilterTest`.
* **rows/columns/values from the URL are not validated against the panel's metadata.**
  `packages/analytics-ux-panel/src/PivotAwareQuery.php:64-78,207-210`. Unknown
  or hidden dimension names throw core `InvalidArgumentException` from the
  constructor; `values` is never checked, so an unknown measure fails late and
  inconsistently (`PivotTable/Model/Cube/MeasureLabel.php:40-46` throws,
  `TableAdapter` lines 66-73 throw, `MeasureValue` silently yields null, the
  chart throws `UnexpectedValueException`). The same dimension in both `rows`
  and `columns` is deduplicated for the query but still passed twice to the
  pivot transformer. Fix: filter through `getAllItems()`/`getMeasures()` and
  drop unknowns, or throw a translatable frontend exception.
  **FIXED (#703)**: `PivotAwareQuery` drops unknown and hidden items,
  duplicates, and column items already in the rows. Rows and columns accept
  leaf dimensions and `@values`, values accepts measures, filters accepts leaf
  dimensions. Covered by `PivotAwareQueryTest`.
* **Pivot-table Stimulus controller leaks its document listener.**
  `packages/analytics-ux-panel/assets/src/pivot-table-controller.js:72,82`.
  `connect()` adds `turbo:before-stream-render` with a fresh bound function;
  `disconnect()` removes `turbo:before-frame-render` with another fresh bound
  function. Neither the name nor the reference matches, so every Turbo Drive
  navigation stacks another wrapper around `event.detail.render`. Fix: store
  the bound handler and remove the same name and reference.
  **FIXED (#703)**: the bound handler is kept, and the same event and
  reference are removed.
* **Bootstrap theme breaks `renderTable()` output under `strict_variables`.**
  `packages/analytics-frontend/templates/bootstrap_5_renderer.html.twig:35-47`
  reads `element.context.subtotalDepth`, but cells from
  `TableToHtmlTableTransformer` have `context: null`. Confidence: medium.
  Fix: guard with `element.context is not null`.
  **FIXED (#703)**: a missing context is treated as subtotal depth 0. The
  test app's debug theme had the same problem with `generatingBlock`.

---

## P3: incorrect but low impact, latent, or cosmetic (all FIXED)

* `Contracts/Exception/SummaryNotFound.php:22-25` wraps its argument in
  `Summary "%s" not found.`, but the only caller
  (`DefaultSummaryMetadataFactory.php:123-125`) passes a pre-formatted
  message, producing `Summary "Class "App\Foo" is not a summary class" not found.`
  **FIXED (#704).**
* `Core/Doctrine/Migrations/Version20250705.php:35` vs `:48`: `up()` creates
  `REKALOGIKA_BUST(anyelement, anycompatible)` but `down()` drops
  `(anyelement, anyelement)`. PostgreSQL identifies functions by argument
  types, so with `IF EXISTS` the rollback is a silent no-op.
  **FIXED (#704).**
* `PostgreSQLExtra/Doctrine/Migrations/Version20250801.php:66`: `down()` is
  empty, so the migration cannot be rolled back.
  **FIXED (#704).**
* `Metadata/Summary/SummaryMetadata.php:107-108`: the partition stored in
  `$properties` is the original, unattached instance (summaryMetadata null),
  so `getProperty('partition')->getSummaryMetadata()` throws while
  `getPartition()->getSummaryMetadata()` works.
  **FIXED (#704).**
* `Metadata/Implementation/DefaultDimensionGroupMetadataFactory.php:72-82`:
  the guard admits classes having only `#[Summary]`, then `getAttribute()`
  throws a generic `RuntimeException`, so the intended
  `InvalidArgumentException` is unreachable.
  **FIXED (#704).**
* `Metadata/Util/AttributeUtil.php:178-196`: `ReflectionClass::getProperty()`
  returns inherited protected/public properties, so attributes on e.g.
  `BaseSummary::$__groupings` are yielded once per class in the hierarchy and
  `AttributeCollection::getAttributes()` contains duplicates. Only
  `tryGetAttribute` is used in-tree, so no runtime failure today.
  **FIXED (#704).**
* `Contracts/Exception/HierarchicalOrderingRequired.php` is a zero-byte file
  left over from #551. Composer's optimized classmap warns about it, and any
  future reference fatals with "class not found" after a successful include.
  **FIXED (#704).**
* `packages/cube-contracts/` is an empty, untracked directory.
  **Resolved**: the directory no longer exists.
* `Uuid/Doctrine/TruncateUuidToBigintFunction.php:64-68` declares return type
  `DATETIME_IMMUTABLE` for a bigint (copied from `UuidToDateTimeFunction`).
  Latent: only used nested inside `REKALOGIKA_TRUNCATE_BIGINT` today.
  **FIXED (#704).**
* `SimpleQueryBuilder/SimpleQueryBuilder.php::__clone` clones the
  `QueryBuilder` and `PathResolver`, but `PathResolver`/`BaseEntity`/
  `PathContext` keep the original `QueryBuilder`, so joins added through the
  clone go to the original. `Engine/Infrastructure/AbstractQuery::__clone`
  relies on this. No caller clones today.
  **FIXED (#704).**
* `Engine/Util/ComparableUtil.php:~27`: `$a::compare(...) !== $b::compare(...)`
  compares two fresh `Closure` objects by identity, so `compare()` always
  throws. Only `sort()` is used.
  **FIXED (#704).**
* `Engine/Groupings/Groupings.php` `registerExpression()` (~70-80): the two
  property names in the duplicate-expression message are swapped.
  **FIXED (#704).**
* `Engine/Util/DimensionUtil.php` (~120-145): comments say null sorts first;
  the code (and `DimensionFactoryTest::testNullLast`) put null last.
  **FIXED (#704).**
* `Engine/Util/ProxyUtil.php::normalizeClassName()`: for the `__PM__` branch
  `$pos` is computed before `ltrim($className, '\\')`, so a leading backslash
  shifts the `substr` offsets by one.
  **FIXED (#704).**
* `Core/Doctrine/Function/InFunction.php:50-55`: the first option is parsed
  with `ArithmeticPrimary()` and subsequent ones with `StringPrimary()`, so
  hand-written `REKALOGIKA_IN(e.x, 1, 2)` is a DQL syntax error on `2`.
  In-tree callers always pass named parameters.
  **FIXED (#704).**
* `packages/analytics-bundle/config/engine.php:220-229` defines an untagged
  `rekalogika.analytics.engine.refresh.agent`; `config/bundle.php:196-208`
  defines the same `RefreshAgent` as `rekalogika.analytics.refresh.agent`
  with the messenger tag. The engine one is unused.
  **FIXED (#704).**
* `Metadata/Implementation/CachingAttributeCollectionFactory.php:63`: cache
  key `class . '_' . property` is ambiguous for identifiers containing `_`.
  Also the whole `DefaultAttributeCollection` (including third-party attribute
  instances) is serialized into `cache.system`; an attribute holding a closure
  would break warm-up.
  **FIXED (#704)**: the key separator is `::`. Every fetched collection is
  memoized in memory, so a collection the cache cannot store is reflected once
  per process instead of on every call.
* `Core/Entity/ContextAwareSummaryTrait.php:30-33` raises PHP's "must not be
  accessed before initialization" `Error` where
  `ContextAwareDimensionGroupTrait` throws `LogicException('Context is not set.')`.
  **FIXED (#704).**
* `packages/analytics-frontend/templates/renderer.html.twig:114,124,134` test
  `element.content.node.measure.key is defined`, but `content` is a
  `PivotTable\Model\Property` with no `node` accessor, so the check is always
  false and every cell gets `analytics-dimension`; `analytics-measure` is
  never emitted.
  **FIXED (#704).**
* `DefaultChartGenerator.php:514-517,537-541` put legend `display`/`position`
  under `plugins.legend.labels`, where `position` is not valid; the legend
  position cannot be changed. Defaults coincide, so no visible effect today.
  **FIXED (#704).**
* `choice-filter-controller.js:6` and `number-ranges-filter-controller.js:5`
  use `!this.element instanceof X`, which parses as
  `(!this.element) instanceof X` and is always false.
  **FIXED (#704).**
* `packages/analytics-ux-panel/templates/filter/null_filter.html.twig:6`
  passes `domain=` twice.
  **FIXED (#704).**
* `Bin/IsoWeek/IsoWeekWeek.php:36-42`: `createFromDatabaseValue(202153)`
  silently becomes 2022-W01 (start 2022-01-03) while `getDatabaseValue()`
  stays 202153. Validating that `format('oW')` of the computed start equals
  the input would turn garbage filter input into a clear error.
  **FIXED (#704).**

---

## Suggestions (not bugs)

### Tests that would have caught the above

* `tests/src/UnitTests/TimeBinTest.php`: hour prev/next across a
  spring-forward in a DST zone (**done** with P1-4); Month and
  month-based-week labels for 202101 in de_DE (**done** in #702);
  month-based-week `createFromDateTime` on a Friday (**done** in #702); ISO
  week 1 starting in the previous year; and a round trip
  `createFromDatabaseValue(v)->getNext()->getDatabaseValue()` for every
  monotonic bin (**done** in #710: `testIsoWeekOneStartingInPreviousYear`
  and `testNextDatabaseValue`).
* `tests/src/IntegrationTests/SourceChangeTest.php`: add a UUID-partitioned
  summary (CustomerSummary) so the string-key refresh path is exercised,
  including a modification in an old partition followed by a query at the
  top level. **Partly done** in #687
  (`testUuidPartitionRefreshMarksUpperPartitionDirty` checks the dirty
  flag) and #689 (single-record and single-key manual refresh). The
  top-level query after the agent runs is **done** in #710
  (`testUuidPartitionModificationInOldPartitionReachesTopLevel`).
* `QueryTest`: assert drill-down order, not just count, after
  `orderBy(measure)` (**done** with P1-8); assert `dice(null)` resets the result
  (**done** with P1-7).
* A batched manual refresh (`batchSize > 1`) followed by a per-partition
  re-refresh with totals compared. **Done** in #688
  (`ManualRefreshTest::testBatchedRefreshKeepsUpperPartitionsSeparate`).

### Robustness

* Use `Connection::transactional()` in `SummaryRefresher`, `RefreshAgent`,
  `DefaultSummaryManager` and `RefreshCommand`, and make
  `RefreshAgentLock::release()` tolerant of a broken connection. **Done**
  with P1-6 (the transactions live in `SummaryRefresher`; the other three
  only hold the lock).
* `RefreshAgent.php:31-38`: on lock conflict the agent reschedules via
  `refresh(minimumDelay: 10)`, which with the default strategy resolves to
  now + 600 s, not the 10 s the comment implies; `scheduleAt(now + 10s)`
  matches the intent. **Done** in #705.
* `Entity/PartitionRange.php:25-36` accepts `start > end`;
  `getRangesForManualRefresh` (`SummaryRefresher.php:357-380`) yields such
  empty ranges, which still fire Refresh/Delete/RollUp events and appear in
  logs. Validate or skip them. **Done** in #705: the constructor
  rejects `start > end`, and the manual refresh stops climbing the levels
  once the range becomes empty. (With the batching from #688 the empty
  ranges yielded no batches, so no events fired; the inverted ranges were
  still created.)
* `Handler/DirtyFlagsHandler.php:240-245`: flags whose level is no longer in
  `getAllLevels()` are skipped but never deleted, so they accumulate after a
  partition class change. **Done** in #705
  (`DirtyFlagsHandler::removeStaleFlags()`, called at the start of
  `SummaryRefresher::refresh()`).
* `Contracts/SummaryManager.php:44-45`: document whether batchSize applies
  per level once finding P1-2 is fixed. After #688 the parameter is applied
  as "N partitions per unit of work at every level", while the doc said
  "number of lowest-level partitions". **Done** in #705 (doc and
  `--batchsize` option description updated).

### Performance

* `ValueResolver/Bust.php:46` uses `rand()`, so every roll-up SQL text is
  unique: this defeats the PostgreSQL plan cache and makes
  `debug:summary --query` output nondeterministic. A per-query counter gives
  the same "distinct expression" effect. **Done** in #706.
* `Bin/Trait/TimeBinTrait.php:71-78`: `__destruct` can never run while the
  instance sits in the static `$instances` cache, so the cache grows for the
  process lifetime. Drop the destructor or store `\WeakReference`s.
  **Done** in #706 (weak references; the destructor only removes its own
  entry).
* `ValueResolver/TimeBinValueResolver.php:117-118`:
  `createFromDatabaseValue($raw)->withTimeZone($tz)` materialises and caches a
  throwaway UTC instance per value; add an internal `create($value, $tz)` path.
  **Done** in #706 (`createFromDatabaseValueAndTimeZone()`).
* `DimensionFieldCollection::getUnprocessed()` runs the gap filler even though
  its only caller (`CellRepository::getCellsByBaseAndDimensionName`,
  `fillGaps=false`) skips every interpolated member. **Done** in #706 (the
  method is removed; the caller uses the sorted members).
* `CellRepository::getCellByCoordinates()`/`getCellByBaseAndDimension()` store
  synthetic null cells in `$signatureToCell` via `??=`, so they later show up
  in `getCellsByDimensionality()`/`getAllCubes()` as if they were query rows.
  **Done** in #706 (null cells are kept in a separate map).

### API and UX footguns

* `Engine/Util/DimensionUtil.php::compare()` falls back to PHP object
  comparison for non-`Comparable` objects (Doctrine proxies). An entity
  dimension with the default `Order::Ascending` drills down in an order that
  is neither by name nor by count. Compare by identifier or keep DB order for
  object members. **Done** in #707 (non-`Comparable` objects compare equal,
  so the stable sort keeps the DB order).
* `DefaultChartGenerator.php:294` maps missing measure values to `0`, which
  defeats the `spanGaps` option at line 368; emit `null` for gap-filled cells
  so line charts show gaps instead of dips to zero. **Done** in #707, also
  enabling `spanGaps` in the two-dimensional charts.
* `DefaultChartGenerator.php:623-657` (`selectMeasures`) silently drops
  measures whose unit differs from the first; `createChart()` lines 74-100
  index `$dimensions[1]`/`$measures[0]` unchecked and produce a `TypeError`
  rather than `UnsupportedData`. **Done** in #707: each unit gets its own
  y axis, and the input is validated up front.
* `NumberFormatStringifier` (priority -100,
  `packages/analytics-bundle/config/frontend.php:180-186`) also formats
  integer dimension members, so an integer dimension renders as "2,024".
  **Done** in #707: integer members are stringified directly where they are
  known to be members (pivot table `Member` properties, chart labels, choice
  filter labels, rendered filter expressions).
* `Metadata/Summary/DimensionMetadata.php:241,273`: `withSummaryMetadata()`
  and `withParent()` pass `label: $this->getLabel()` (a `HierarchyAwareLabel`
  wrapping the stale instance) rather than `getRawLabel()`; works only
  because `HierarchyAwareLabel::trans()` unwraps to the leaf. **Done** in
  #707.
* `SimpleQueryBuilder/QueryComponents.php::escapeFunction()`: `\Stringable`
  values are `addslashes`'d but not quoted, so the interpolated debug SQL is
  wrong for UUID objects. **Done** in #707.
* `PivotAwareQuery::getAvailableWithoutSubItems()` (lines 327-346) appends
  `@values` a second time after `getColumnsWithoutSubitems()` already did.
  **Done** in #707.

### Tooling drift

* With Symfony 8.1 installed locally, Psalm fails on the deprecated
  `BundleInterface` reachable from `RekalogikaAnalyticsBundle` and
  `RekalogikaPanelBundle`, so `make test` is red before PHPUnit runs.
  **Done** in #708: `psalm.xml` reports the deprecated `BundleInterface` as
  info (the bundles inherit it from `AbstractBundle`, and the replacement is
  not available before Symfony 8.1).
* PHPStan fails on the stale generated `tests/config/reference.php`
  (gitignored) which references config classes that no longer exist; it
  should be regenerated or excluded. **No longer reported** (2026-11-03).
  PHPStan then failed on the deprecated `BundleInterface` return type of
  `Kernel::registerBundles()` in the test app. **Done** in #708: the method
  is removed and the bundles are listed in `tests/config/bundles.php`, which
  `MicroKernelTrait` loads.
* The test app's `Money::ofMinor()` on float averages
  (`tests/src/App/Entity/OrderSummary.php:395-404`) triggers the brick/math
  "passing floats is deprecated, removed in 0.15" notice during the test run.
  **Done** in #709: the floats are cast to string. The same PR fixes a PHP
  deprecation in `ColorDispenser` (`Hsl::spin()` applies `%` to the float
  hue); it now keeps the hue itself with `fmod()`.

---

## Verified clean

Checked and found correct: `REKALOGIKA_TIME_BIN` AT TIME ZONE direction and
overloads (`Version20250625`); `REKALOGIKA_TIME_BIN_MBW_WEEK` SQL week math
(`Version20250703`); TO_CHAR patterns vs enum ranges; Quarter end via month
overflow; Hour fall-back (bin 2025110201 spans both 01:xx EDT and 01:xx EST);
Date/IsoWeek next/previous across DST (Hour is covered by P1-4); time bin
instance cache keyed by
class + value + zone; First/Last aggregates and HLL functions; grouped-chart
series alignment (drill-down is gap-filled and dimension objects are
interned); translation keys used by the frontend and panel all exist in the
en and id catalogs; no string interpolation of user data into DQL or SQL was
found.
