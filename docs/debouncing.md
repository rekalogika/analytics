# Debouncing Algorithm

Objectives:

* Runs immediately if there is no contention
* Runs only once within a given interval
* Allows ad-hoc refreshes to take place
* Anticipates long-running refreshes

Manager:

```
$s = start delay
$d = debounce interval
$m = expected max runtime
$lock = lock
$flag = pending flag

if acquire $lock that expires at now() + $s + $m + 2 * $d:
    schedule primary worker at now() + $s
    do not release $lock
else:
    raise $flag
```

Primary Worker:

```
refresh $lock to expire at now() + $m + 2 * $d
remove $flag
do the work
refresh $lock to expire at now() + 2 * $d
schedule secondary worker at now() + $d
```

Secondary Worker:

```
if $flag is raised:
    execute primary worker
else:
    release $lock
```
