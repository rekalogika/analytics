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

namespace Rekalogika\Analytics\Bundle\Command;

use Rekalogika\Analytics\Uuid\Util\UuidV7Util;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rekalogika:analytics:uuid:summary-to-source',
    description: 'Convert a resulting summary integer key to base UUIDv7 used in the source entity. Used for debugging and troubleshooting purposes.',
)]
final class UuidConvertSummaryToSourceCommand extends Command
{
    private ?SymfonyStyle $io = null;

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument(
            name: 'integerkey',
            mode: InputArgument::REQUIRED,
            description: 'Integer key from the summary table',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        /** @var string */
        $argument = $input->getArgument('integerkey');

        // the key is the first 48 bits of the UUID, so it must be an integer
        // between 0 and 2^48 - 1. 2^48 has 15 decimal digits, so the cast
        // cannot overflow.
        $integerKey = preg_match('/^0*(\d{1,15})$/', $argument, $matches) === 1
            ? (int) $matches[1]
            : null;

        if ($integerKey === null || $integerKey > 0xFFFFFFFFFFFF) {
            $this->io->error(\sprintf(
                'The integer key must be an integer between 0 and %d.',
                0xFFFFFFFFFFFF,
            ));

            return Command::FAILURE;
        }

        $output->writeln(UuidV7Util::getNilOfInteger($integerKey << 16));

        return Command::SUCCESS;
    }
}
