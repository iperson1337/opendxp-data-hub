<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType;

use Carbon\CarbonPeriod;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Value of the core `dateRange` data type ({@see CarbonPeriod}): start and end dates, formatted
 * like a `date` field, which serves the date object cast to string.
 */
class DateRangeType extends ObjectType
{
    protected static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new static([
                'name' => 'DateRange',
                'fields' => [
                    'start' => [
                        'type' => Type::string(),
                        'resolve' => static fn ($value) => $value instanceof CarbonPeriod ? self::format($value->getStartDate()) : null,
                    ],
                    'end' => [
                        'type' => Type::string(),
                        'resolve' => static fn ($value) => $value instanceof CarbonPeriod ? self::format($value->getEndDate()) : null,
                    ],
                ],
            ]);
        }

        return self::$instance;
    }

    private static function format(?\Carbon\CarbonInterface $date): ?string
    {
        return $date === null ? null : (string) $date;
    }
}
