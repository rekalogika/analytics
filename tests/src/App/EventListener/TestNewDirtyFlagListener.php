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

namespace Rekalogika\Analytics\Tests\App\EventListener;

use Rekalogika\Analytics\SummaryManager\Event\NewDirtyFlagEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class TestNewDirtyFlagListener
{
    /**
     * @var list<NewDirtyFlagEvent>
     */
    private array $events = [];

    #[AsEventListener()]
    public function onNewDirtyFlag(NewDirtyFlagEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<NewDirtyFlagEvent>
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}
