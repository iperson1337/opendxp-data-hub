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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use Override;

class DateRange extends Base
{
    /**
     * @param Concrete|AbstractData $object
     * @param array|null $newValue
     * @param array $args
     * @param array $context
     */
    #[Override]
    public function process($object, $newValue, $args, $context, ResolveInfo $info)
    {
        Service::setValue($object, $this->getAttribute(), static function ($container, $setter) use ($newValue) {
            if (!$newValue || (empty($newValue['start']) && empty($newValue['end']))) {
                return $container->$setter(null);
            }

            return $container->$setter(CarbonPeriod::create(self::toDate($newValue['start'] ?? null), self::toDate($newValue['end'] ?? null)));
        });
    }

    /** Same parsing as {@see Date}: a unix timestamp or a strtotime() string. */
    private static function toDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            $value = strtotime((string) $value);
            if ($value === false) {
                throw new ClientSafeException('invalid date value');
            }
        }

        return Carbon::createFromTimestamp($value);
    }
}
