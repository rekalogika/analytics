<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/analytics package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Analytics\EventListener;

use Rekalogika\Analytics\SummaryManager\NewEntitySignalConverter;

final class NewEntitySignalListener
{
    /**
     * @var list<class-string>
     */
    private array $classes = [];

    public function __construct(
        private readonly NewEntitySignalConverter $newEntitySignalConverter,
    ) {}

    /**
     * @param class-string $class
     */
    public function collectClassToProcess(string $class): void
    {
        $this->classes[] = $class;
    }

    public function process(): void
    {
        foreach ($this->classes as $class) {
            $signals = $this->newEntitySignalConverter
                ->convertNewRecordsSignalsToDirtyPartitionSignals($class);

            foreach ($signals as $signal) {
                // @todo
            }
        }
    }
}
