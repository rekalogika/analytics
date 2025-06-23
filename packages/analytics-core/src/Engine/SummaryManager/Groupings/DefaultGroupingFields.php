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

namespace Rekalogika\Analytics\Engine\SummaryManager\Groupings;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\GroupingFields;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;

final class DefaultGroupingFields implements GroupingFields
{
    /**
     * @var list<string>
     */
    private array $selected = [];

    /**
     * @var list<string>
     */
    private readonly array $availableFields;

    public function __construct(
        private readonly DimensionMetadata $dimensionMetadata,
    ) {
        $parent = $dimensionMetadata->getParent();

        if (null === $parent) {
            $parent = $dimensionMetadata->getSummaryMetadata();
        }

        $this->availableFields = array_keys($parent->getGroupingFields());
    }

    #[\Override]
    public function getAvailableFields(): array
    {
        return $this->availableFields;
    }

    #[\Override]
    public function selectField(string $identifier): void
    {
        if (!\in_array($identifier, $this->availableFields, true)) {
            throw new InvalidArgumentException(\sprintf(
                'Field "%s" is not available. Available fields are: %s.',
                $identifier,
                implode(', ', $this->availableFields),
            ));
        }

        $this->selected[] = $identifier;
    }

    /**
     * @return list<string>
     */
    public function getSelectedFields(): array
    {
        return $this->selected;
    }
}
