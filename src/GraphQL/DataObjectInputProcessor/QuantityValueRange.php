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

use OpenDxp\Model\DataObject\Data\QuantityValueRange as QuantityValueRangeValue;
use OpenDxp\Model\DataObject\QuantityValue\Unit;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use Override;

class QuantityValueRange extends Base
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
            if (!$newValue) {
                return $container->$setter(null);
            }

            $unit = null;
            if (isset($newValue['unitId'])) {
                $unit = Unit::getById($newValue['unitId']);
            } elseif (isset($newValue['unit'])) {
                $unit = Unit::getByAbbreviation($newValue['unit']);
            }

            return $container->$setter(new QuantityValueRangeValue($newValue['minimum'] ?? null, $newValue['maximum'] ?? null, $unit));
        });
    }
}
