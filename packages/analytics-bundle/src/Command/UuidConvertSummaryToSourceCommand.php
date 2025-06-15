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

use Rekalogika\Analytics\Uuid\ValueResolver\StringUuidToTruncatedInteger;
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

    /**
     * @todo fix
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $integerKey = $input->getArgument('integerkey');

        if (!is_numeric($integerKey)) {
            $this->io->error('The integer key must be a number.');
            return Command::FAILURE;
        }

        $integerKey = (int) $integerKey;

        $resolver = new StringUuidToTruncatedInteger('foo');
        /** @var string */
        $uuid = $resolver->transformSummaryValueToSourceValue($integerKey);

        $output->writeln($uuid);

        return Command::SUCCESS;
    }
}
