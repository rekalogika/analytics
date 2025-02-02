# TODO

## Bugs

* Max and min values do not consider `HasQueryBuilderModifier`
* Canonicalize grouping column ordering (use alphabetical order?), avoid relying
  on undefined reflection ordering.

## Missing Features

* Metadata is not cached yet
* Disable dirty flag generation and event dispatching in manual refresh
* Support for MS SQL Server
* Configurable event timing (`RefreshClassPropertiesResolver`)
* Allow customized generation of dirty flag (see `DirtyFlagGenerator`)
* It should be possible for `DirtyFlagGenerator` to generate dirty partition
  signals for non-`IDENTITY` entities, bypassing 'new entity' phase.
* Hyperloglog

## Limitations

* Postgres `GROUP BY` maximum grouping set limit of 4096, can be circumvented by
  using multiple queries.

## Reorganization

* Spin off pivot table to its own package.