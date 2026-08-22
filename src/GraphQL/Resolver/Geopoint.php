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
use OpenDxp\Model\DataObject\Data\GeoCoordinates;

class Geopoint
{
    use ServiceTrait;

    /**
     * @param mixed $value
     * @param array $args
     * @param array $context
     *
     * @return float|null
     *
     * @throws Exception
     */
    public function resolveLongitude($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value instanceof GeoCoordinates) {
            return $value->getLongitude();
        }

        return null;
    }

    /**
     * @param mixed $value
     * @param array $args
     * @param array $context
     *
     * @return float|null
     *
     * @throws Exception
     */
    public function resolveLatitude($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value instanceof GeoCoordinates) {
            return $value->getLatitude();
        }

        return null;
    }
}
