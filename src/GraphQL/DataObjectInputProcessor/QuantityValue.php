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

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use Override;

class QuantityValue extends Base
{
    /**
     * @param Concrete|AbstractData $object
     * @param array $newValue
     * @param array $args
     * @param array $context
     *
     * @throws Exception
     */
    #[Override]
    public function process($object, $newValue, $args, $context, ResolveInfo $info)
    {
        $attribute = $this->getAttribute();
        Service::setValue($object, $attribute, function ($container, $setter) use ($newValue) {
            if ($newValue) {
                $value = null;
                $unit = null;
                if (isset($newValue['value'])) {
                    $value = $newValue['value'];
                }
                if (isset($newValue['unitId'])) {
                    $unit = \OpenDxp\Model\DataObject\QuantityValue\Unit::getById($newValue['unitId']);
                } elseif (isset($newValue['unit'])) {
                    $unit = \OpenDxp\Model\DataObject\QuantityValue\Unit::getByAbbreviation($newValue['unit']);
                }
                $quantityValue = new \OpenDxp\Model\DataObject\Data\QuantityValue($value, $unit);

                return $container->$setter($quantityValue);
            }

            return null;
        });
    }
}
