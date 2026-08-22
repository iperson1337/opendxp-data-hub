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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\Data\AbstractQuantityValue;

class QuantityValue
{
    use ServiceTrait;

    /**
     * @param AbstractQuantityValue|null $value
     * @param array $args
     * @param array $context
     *
     * @return array
     *
     * @throws Exception
     */
    public function resolveUnit($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value instanceof AbstractQuantityValue && $unit = $value->getUnit()) {
            return $unit->getObjectVars();
        }

        return [];
    }

    /**
     * @param AbstractQuantityValue|null $value
     * @param array $args
     * @param array $context
     *
     * @return float|string|null
     *
     * @throws Exception
     */
    public function resolveValue($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value instanceof AbstractQuantityValue) {
            return $value->getValue();
        }

        return null;
    }

    /**
     * @param AbstractQuantityValue|null $value
     * @param array $args
     * @param array $context
     *
     * @return string|null
     *
     * @throws Exception
     */
    public function resolveToString($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $returnValue = null;

        if ($value instanceof AbstractQuantityValue) {
            $currentLocale = null;
            $localService = null;
            if (isset($args['language'])) {
                $localService = $this->getGraphQlService()->getLocaleService();
                $currentLocale = $localService->getLocale();

                $localService->setLocale($args['language']);
            }

            $returnValue = $value->__toString();

            if (isset($args['language'])) {
                $localService->setLocale($currentLocale);
            }
        }

        return $returnValue;
    }
}
