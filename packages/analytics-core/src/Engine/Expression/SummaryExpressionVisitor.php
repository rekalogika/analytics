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

namespace Rekalogika\Analytics\Engine\Expression;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;

final class SummaryExpressionVisitor extends BaseExpressionVisitor
{
    #[\Override]
    public function visitField(Field $field): FieldExpression
    {
        $fieldName = $field->getField();

        if (!\in_array($fieldName, $this->validFields, true)) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid field "%s", valid fields are: %s',
                $fieldName,
                implode(', ', $this->validFields),
            ));
        }

        $this->involvedDimensions[$fieldName] = true;
        $type = $this->getFieldType($fieldName);
        $fieldWithAlias = $this->rootAlias . '.' . $fieldName;

        return new FieldExpression($fieldWithAlias, $type);
    }

    private function getFieldType(string $field): ?string
    {
        if (!\in_array($field, $this->validFields, true)) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid field "%s", valid fields are: %s',
                $field,
                implode(', ', $this->validFields),
            ));
        }

        if ($this->classMetadata->hasAssociation($field)) {
            return null;
        } elseif ($this->classMetadata->hasField($field)) {
            $fieldMetadata = $this->classMetadata->getFieldMapping($field);

            $result = $fieldMetadata['type'] ?? null;

            if ($result === null) {
                throw new InvalidArgumentException(\sprintf(
                    'Field "%s" does not have a type defined in the class metadata',
                    $field,
                ));
            }

            if (!\is_string($result)) {
                throw new InvalidArgumentException(\sprintf(
                    'Field "%s" type must be a string, got "%s"',
                    $field,
                    get_debug_type($result),
                ));
            }

            return $result;
        } else {
            return null;
        }
    }
}
