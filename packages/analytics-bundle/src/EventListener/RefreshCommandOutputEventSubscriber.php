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

namespace Rekalogika\Analytics\Bundle\EventListener;

use Rekalogika\Analytics\Engine\SummaryManager\Event\AbstractEndEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\AbstractStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\DeleteRangeEndEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\DeleteRangeStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RefreshEndEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RefreshRangeEndEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RefreshRangeStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RefreshStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RollUpRangeEndEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RollUpRangeStartEvent;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ResetInterface;

final class RefreshCommandOutputEventSubscriber implements EventSubscriberInterface, ResetInterface
{
    private ?SymfonyStyle $io = null;

    private ?ProgressIndicator $progressIndicator = null;

    private ?string $currentResumeId = null;

    #[\Override]
    public function reset(): void
    {
        $this->io = null;
        $this->progressIndicator = null;
        $this->currentResumeId = null;
    }

    public function initialize(SymfonyStyle $io): void
    {
        $this->io = $io;
        $this->progressIndicator = new ProgressIndicator($io, 'very_verbose');
    }

    public function getCurrentResumeId(): ?string
    {
        return $this->currentResumeId;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            RefreshStartEvent::class => 'onRefreshStartEvent',
            RefreshEndEvent::class => 'onRefreshEndEvent',
            RefreshRangeStartEvent::class => 'onRefreshRangeStartEvent',
            RefreshRangeEndEvent::class => 'onRefreshRangeEndEvent',
            DeleteRangeStartEvent::class => 'onStartEvent',
            DeleteRangeEndEvent::class => 'onEndEvent',
            RollUpRangeStartEvent::class => 'onStartEvent',
            RollUpRangeEndEvent::class => 'onEndEvent',
        ];
    }

    public function onRefreshStartEvent(RefreshStartEvent $event): void
    {
        $this->io?->info(\sprintf(
            'Refreshing %s',
            $event->getClass(),
        ));

        $this->io?->definitionList(
            [
                'Input range' => \sprintf(
                    '%s - %s',
                    Printer::print($event->getInputStartValue()),
                    Printer::print($event->getInputEndValue()),
                ),
            ],
            [
                'Actual range' => \sprintf(
                    '%s - %s',
                    Printer::print($event->getActualStartValue()),
                    Printer::print($event->getActualEndValue()),
                ),
            ],
        );
    }

    public function onRefreshEndEvent(RefreshEndEvent $event): void
    {
        $this->io?->success(\sprintf(
            'Refreshed %s in %s',
            $event->getClass(),
            Printer::print($event->getDuration()),
        ));
    }

    public function onRefreshRangeStartEvent(RefreshRangeStartEvent $event): void
    {
        $this->currentResumeId = $event->getRange()->getSignature();
        $this->progressIndicator?->start(Printer::print($event->getRange()));
    }

    public function onRefreshRangeEndEvent(RefreshRangeEndEvent $event): void
    {
        $this->progressIndicator?->finish(Printer::print($event->getRange()));
    }

    public function onStartEvent(AbstractStartEvent $event): void
    {
        $this->progressIndicator?->advance();
    }

    public function onEndEvent(AbstractEndEvent $event): void
    {
        $this->progressIndicator?->advance();
    }
}
