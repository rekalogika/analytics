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

namespace Rekalogika\Analytics\Tests\UnitTests;

use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Contracts\Dto\ComparisonDto;
use Rekalogika\Analytics\Contracts\Dto\CompositeExpressionDto;
use Rekalogika\Analytics\Contracts\Dto\CoordinatesDto;
use Rekalogika\Analytics\Contracts\Dto\ExpressionDto;
use Rekalogika\Analytics\Contracts\Dto\ValueDto;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;

final class JsonSerializationTest extends TestCase
{
    public function testValueDtoJsonSerialization(): void
    {
        $valueDto = new ValueDto('test-string');

        $json = json_encode($valueDto);
        $this->assertNotFalse($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals([
            'class' => 'value',
            'value' => 'test-string',
        ], $decoded);

        // Test round-trip
        /** @var array<string,mixed> $decoded */
        $reconstructed = ValueDto::fromArray($decoded);
        $this->assertEquals($valueDto, $reconstructed);
    }

    public function testValueDtoJsonSerializationWithArray(): void
    {
        $arrayValue = ['key1' => 'value1', 'key2' => ['nested' => 'value']];
        $valueDto = new ValueDto($arrayValue);

        $json = json_encode($valueDto);
        $this->assertNotFalse($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals([
            'class' => 'value',
            'value' => $arrayValue,
        ], $decoded);

        // Test round-trip
        /** @var array<string,mixed> $decoded */
        $reconstructed = ValueDto::fromArray($decoded);
        $this->assertEquals($valueDto, $reconstructed);
    }

    public function testValueDtoJsonSerializationWithNull(): void
    {
        $valueDto = new ValueDto(null);

        $json = json_encode($valueDto);
        $this->assertNotFalse($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals([
            'class' => 'value',
            'value' => null,
        ], $decoded);

        // Test round-trip
        /** @var array<string,mixed> $decoded */
        $reconstructed = ValueDto::fromArray($decoded);
        $this->assertEquals($valueDto, $reconstructed);
    }

    public function testComparisonDtoJsonSerialization(): void
    {
        $valueDto = new ValueDto('comparison-value');
        $comparisonDto = new ComparisonDto('field_name', '=', $valueDto);

        $json = json_encode($comparisonDto);
        $this->assertNotFalse($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals([
            'class' => 'comparison',
            'field' => 'field_name',
            'op' => '=',
            'value' => [
                'class' => 'value',
                'value' => 'comparison-value',
            ],
        ], $decoded);

        // Test round-trip
        /** @var array<string,mixed> $decoded */
        $reconstructed = ComparisonDto::fromArray($decoded);
        $this->assertEquals($comparisonDto, $reconstructed);
    }

    public function testCompositeExpressionDtoJsonSerialization(): void
    {
        $value1 = new ValueDto('value1');
        $value2 = new ValueDto('value2');
        $comparison1 = new ComparisonDto('field1', '=', $value1);
        $comparison2 = new ComparisonDto('field2', '!=', $value2);

        $compositeDto = new CompositeExpressionDto('AND', [$comparison1, $comparison2]);

        $json = json_encode($compositeDto);
        $this->assertNotFalse($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals([
            'class' => 'compositeExpression',
            'type' => 'AND',
            'expressions' => [
                [
                    'class' => 'comparison',
                    'field' => 'field1',
                    'op' => '=',
                    'value' => [
                        'class' => 'value',
                        'value' => 'value1',
                    ],
                ],
                [
                    'class' => 'comparison',
                    'field' => 'field2',
                    'op' => '!=',
                    'value' => [
                        'class' => 'value',
                        'value' => 'value2',
                    ],
                ],
            ],
        ], $decoded);

        // Test round-trip
        /** @var array<string,mixed> $decoded */
        $reconstructed = CompositeExpressionDto::fromArray($decoded);
        $this->assertEquals($compositeDto, $reconstructed);
    }

    public function testNestedCompositeExpressionDtoJsonSerialization(): void
    {
        $value1 = new ValueDto('value1');
        $value2 = new ValueDto('value2');
        $value3 = new ValueDto('value3');

        $comparison1 = new ComparisonDto('field1', '=', $value1);
        $comparison2 = new ComparisonDto('field2', '>', $value2);
        $comparison3 = new ComparisonDto('field3', '<', $value3);

        $innerComposite = new CompositeExpressionDto('OR', [$comparison2, $comparison3]);
        $outerComposite = new CompositeExpressionDto('AND', [$comparison1, $innerComposite]);

        $json = json_encode($outerComposite);
        $this->assertNotFalse($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('class', $decoded);
        $this->assertEquals('compositeExpression', $decoded['class']);
        $this->assertEquals('AND', $decoded['type']);
        $this->assertIsArray($decoded['expressions']);
        $this->assertCount(2, $decoded['expressions']);

        // Test the nested structure
        $this->assertIsArray($decoded['expressions'][0]);
        $this->assertIsArray($decoded['expressions'][1]);
        $this->assertEquals('comparison', $decoded['expressions'][0]['class']);
        $this->assertEquals('compositeExpression', $decoded['expressions'][1]['class']);
        $this->assertEquals('OR', $decoded['expressions'][1]['type']);

        // Test round-trip
        /** @var array<string,mixed> $decoded */
        $reconstructed = CompositeExpressionDto::fromArray($decoded);
        $this->assertEquals($outerComposite, $reconstructed);
    }

    public function testCoordinatesDtoJsonSerializationWithoutPredicate(): void
    {
        $members = [
            'dimension1' => 'value1',
            'dimension2' => 'value2',
            'dimension3' => null,
        ];

        $coordinatesDto = new CoordinatesDto($members, null);

        $json = json_encode($coordinatesDto);
        $this->assertNotFalse($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals([
            'class' => 'coordinates',
            'members' => $members,
            'predicate' => null,
        ], $decoded);

        // Test round-trip
        /** @var array<string,mixed> $decoded */
        $reconstructed = CoordinatesDto::fromArray($decoded);
        $this->assertEquals($coordinatesDto, $reconstructed);
    }

    public function testCoordinatesDtoJsonSerializationWithPredicate(): void
    {
        $members = [
            'dimension1' => 'value1',
            'dimension2' => 'value2',
        ];

        $predicate = new ComparisonDto('field1', '=', new ValueDto('predicate-value'));
        $coordinatesDto = new CoordinatesDto($members, $predicate);

        $json = json_encode($coordinatesDto);
        $this->assertNotFalse($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals([
            'class' => 'coordinates',
            'members' => $members,
            'predicate' => [
                'class' => 'comparison',
                'field' => 'field1',
                'op' => '=',
                'value' => [
                    'class' => 'value',
                    'value' => 'predicate-value',
                ],
            ],
        ], $decoded);

        // Test round-trip
        /** @var array<string,mixed> $decoded */
        $reconstructed = CoordinatesDto::fromArray($decoded);
        $this->assertEquals($coordinatesDto, $reconstructed);
    }

    public function testExpressionDtoPolymorphicDeserialization(): void
    {
        // Test that ExpressionDto::fromArray correctly handles different subclasses

        // ValueDto
        $valueArray = ['class' => 'value', 'value' => 'test'];
        $valueDto = ExpressionDto::fromArray($valueArray);
        $this->assertInstanceOf(ValueDto::class, $valueDto);

        // Verify by serializing back
        $serialized = $valueDto->toArray();
        $this->assertEquals('test', $serialized['value']);

        // ComparisonDto
        $comparisonArray = [
            'class' => 'comparison',
            'field' => 'test_field',
            'op' => '=',
            'value' => ['class' => 'value', 'value' => 'test_value'],
        ];
        $comparisonDto = ExpressionDto::fromArray($comparisonArray);
        $this->assertInstanceOf(ComparisonDto::class, $comparisonDto);

        // Verify by serializing back
        $serialized = $comparisonDto->toArray();
        $this->assertEquals('test_field', $serialized['field']);
        $this->assertEquals('=', $serialized['op']);

        // CompositeExpressionDto
        $compositeArray = [
            'class' => 'compositeExpression',
            'type' => 'AND',
            'expressions' => [
                ['class' => 'value', 'value' => 'test1'],
                ['class' => 'value', 'value' => 'test2'],
            ],
        ];
        $compositeDto = ExpressionDto::fromArray($compositeArray);
        $this->assertInstanceOf(CompositeExpressionDto::class, $compositeDto);

        // Verify by serializing back
        $serialized = $compositeDto->toArray();
        $this->assertEquals('AND', $serialized['type']);
        $this->assertIsArray($serialized['expressions']);
        $this->assertCount(2, $serialized['expressions']);
    }

    public function testJsonSerializationRoundTripIntegrity(): void
    {
        // Create a complex nested structure
        $value1 = new ValueDto(['array' => ['nested' => 'value']]);
        $value2 = new ValueDto(null);
        $value3 = new ValueDto('string-value');

        $comparison1 = new ComparisonDto('field1', 'IN', $value1);
        $comparison2 = new ComparisonDto('field2', 'IS NULL', $value2);
        $comparison3 = new ComparisonDto('field3', 'LIKE', $value3);

        $innerComposite = new CompositeExpressionDto('OR', [$comparison2, $comparison3]);
        $outerComposite = new CompositeExpressionDto('AND', [$comparison1, $innerComposite]);

        $members = [
            'dim1' => 'member1',
            'dim2' => null,
            'dim3' => 'member3',
        ];
        $coordinates = new CoordinatesDto($members, $outerComposite);

        // Serialize to JSON and back
        $json = json_encode($coordinates);
        $this->assertNotFalse($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);

        /** @var array<string,mixed> $decoded */
        $reconstructed = CoordinatesDto::fromArray($decoded);

        // Verify the reconstructed object matches the original
        $this->assertEquals($coordinates->getMembers(), $reconstructed->getMembers());
        $this->assertEquals($coordinates->getPredicate(), $reconstructed->getPredicate());
        $this->assertEquals($coordinates, $reconstructed);
    }

    public function testInvalidArrayThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ExpressionDto::fromArray(['invalid' => 'array']);
    }

    public function testUnsupportedClassThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported expression class: unsupported');

        ExpressionDto::fromArray(['class' => 'unsupported']);
    }

    public function testInvalidValueDtoArrayThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValueDto::fromArray(['class' => 'value']); // Missing 'value' key
    }

    public function testInvalidComparisonDtoArrayThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ComparisonDto::fromArray([
            'class' => 'comparison',
            'field' => 'test',
            // Missing 'op' and 'value'
        ]);
    }

    public function testInvalidCompositeExpressionDtoArrayThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CompositeExpressionDto::fromArray([
            'class' => 'compositeExpression',
            // Missing 'type' and 'expressions'
        ]);
    }

    public function testInvalidCoordinatesDtoArrayThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CoordinatesDto::fromArray([
            'class' => 'coordinates',
            // Missing 'members'
        ]);
    }

    public function testInvalidCoordinatesMemberKeyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Coordinates key must be a string');

        CoordinatesDto::fromArray([
            'class' => 'coordinates',
            'members' => [123 => 'invalid-key'], // Non-string key
        ]);
    }

    public function testInvalidCoordinatesMemberValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Coordinates member value must be a string, an integer, or null.');

        CoordinatesDto::fromArray([
            'class' => 'coordinates',
            'members' => ['valid-key' => 123.3], // Invalid value type
        ]);
    }
}
