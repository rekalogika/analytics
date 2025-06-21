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

namespace Rekalogika\Analytics\Tests\App\Command;

use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Time\Dimension\Set\MonthSet;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'rekalogika:debug:test',
    description: 'Debug & test',
)]
final class DebugTestCommand extends Command implements SignalableCommandInterface
{
    public function __construct(
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
    ) {
        parent::__construct();
    }

    #[\Override] protected function configure(): void {}

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // $classMetadata = $this->entityManager->getClassMetadata(MonthSet::class);
        // dump($classMetadata);

        $summaryMetadata = $this->summaryMetadataFactory
            ->getSummaryMetadata(OrderSummary::class);

        return Command::SUCCESS;
    }
}
