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

namespace Rekalogika\Analytics\Metadata\Summary\Util;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\GroupingsConfiguration;

final class DefaultGroupingsConfiguration implements GroupingsConfiguration
{
    /**
     * @var array<string,string>
     */
    private array $groupingFields = [];

    /**
     * @param list<string> $availableFields
     */
    public function __construct(
        private readonly array $availableFields,
    ) {}

    #[\Override]
    public function addGroupingField(
        string $identifier,
        string $sourcePropertyname,
    ): void {
        // identifier must be alphanumeric and not empty
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $identifier)) {
            throw new InvalidArgumentException(\sprintf(
                'Identifier "%s" is not valid. It must be alphanumeric, begins with a letter and not empty.',
                $identifier,
            ));
        }

        if (!\in_array($sourcePropertyname, $this->availableFields, true)) {
            throw new InvalidArgumentException(\sprintf(
                'Field "%s" does not exist. Available fields: %s',
                $sourcePropertyname,
                implode(', ', $this->availableFields),
            ));
        }

        if (isset($this->groupingFields[$identifier])) {
            throw new InvalidArgumentException(\sprintf(
                'Grouping field "%s" already registered.',
                $identifier,
            ));
        }

        $this->groupingFields[$identifier] = $sourcePropertyname;
    }

    /**
     * @return array<string,string>
     */
    public function getGroupingFields(): array
    {
        return $this->groupingFields;
    }
}
