<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataHubBundle\Tests\GraphQL;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor\DateRange as DateRangeProcessor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor\NumericRange as NumericRangeProcessor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor\QuantityValueRange as QuantityValueRangeProcessor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType\ConsentType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType\DateRangeType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType\NumericRangeType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\Model\DataObject\Data\Consent;
use OpenDxp\Model\DataObject\Data\NumericRange;
use OpenDxp\Model\DataObject\Data\QuantityValueRange;
use PHPUnit\Framework\TestCase;

/**
 * Core data types without a stock Data Hub generator: values are served in the shape of the core
 * value object and written back from the matching input type.
 */
class CoreDataTypesTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(NumericRange::class)) {
            self::markTestSkipped('OpenDXP core is not installed');
        }
    }

    public function testNumericRangeIsServedAsMinimumAndMaximum(): void
    {
        $range = new NumericRange(2, 6);

        self::assertSame(2, self::resolve(NumericRangeType::getInstance(), 'minimum', $range));
        self::assertSame(6, self::resolve(NumericRangeType::getInstance(), 'maximum', $range));
        self::assertNull(self::resolve(NumericRangeType::getInstance(), 'minimum', null));
    }

    public function testDateRangeIsServedAsStartAndEndLikeADateField(): void
    {
        $period = CarbonPeriod::create(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'));

        self::assertSame((string) Carbon::parse('2026-01-01'), self::resolve(DateRangeType::getInstance(), 'start', $period));
        self::assertSame((string) Carbon::parse('2026-01-31'), self::resolve(DateRangeType::getInstance(), 'end', $period));
    }

    public function testConsentIsServedAsFlagAndNoteId(): void
    {
        $consent = new Consent(true, 42);

        self::assertTrue(self::resolve(ConsentType::getInstance(), 'consent', $consent));
        self::assertSame(42, self::resolve(ConsentType::getInstance(), 'noteId', $consent));
    }

    public function testNumericRangeInputIsWrittenAsCoreValue(): void
    {
        $object = self::target();
        (new NumericRangeProcessor(self::node()))->process($object, ['minimum' => -18, 'maximum' => null], [], [], self::info());

        self::assertInstanceOf(NumericRange::class, $object->value);
        self::assertSame(-18, $object->value->getMinimum());
        self::assertNull($object->value->getMaximum());

        (new NumericRangeProcessor(self::node()))->process($object, null, [], [], self::info());
        self::assertNull($object->value);
    }

    public function testQuantityValueRangeInputWithoutUnit(): void
    {
        $object = self::target();
        (new QuantityValueRangeProcessor(self::node()))->process($object, ['minimum' => 1.5, 'maximum' => 3], [], [], self::info());

        self::assertInstanceOf(QuantityValueRange::class, $object->value);
        self::assertSame(1.5, $object->value->getMinimum());
        self::assertSame(3, $object->value->getMaximum());
    }

    public function testDateRangeInputParsesDatesLikeADateMutation(): void
    {
        $object = self::target();
        (new DateRangeProcessor(self::node()))->process($object, ['start' => '2026-01-01', 'end' => '2026-01-31'], [], [], self::info());

        self::assertInstanceOf(CarbonPeriod::class, $object->value);
        self::assertSame('2026-01-01', $object->value->getStartDate()->format('Y-m-d'));
        self::assertSame('2026-01-31', $object->value->getEndDate()?->format('Y-m-d'));

        (new DateRangeProcessor(self::node()))->process($object, ['start' => '', 'end' => null], [], [], self::info());
        self::assertNull($object->value);
    }

    public function testDateRangeInputRejectsGarbage(): void
    {
        $this->expectException(ClientSafeException::class);

        (new DateRangeProcessor(self::node()))->process(self::target(), ['start' => 'not a date'], [], [], self::info());
    }

    private static function resolve(\GraphQL\Type\Definition\ObjectType $type, string $field, mixed $value): mixed
    {
        return ($type->getField($field)->resolveFn)($value, [], [], self::info());
    }

    /** @return array{attributes: array{attribute: string}} */
    private static function node(): array
    {
        return ['attributes' => ['attribute' => 'value']];
    }

    private static function target(): object
    {
        return new class {
            public mixed $value = 'untouched';

            public function setValue(mixed $value): static
            {
                $this->value = $value;

                return $this;
            }
        };
    }

    private static function info(): ResolveInfo
    {
        return (new \ReflectionClass(ResolveInfo::class))->newInstanceWithoutConstructor();
    }
}
