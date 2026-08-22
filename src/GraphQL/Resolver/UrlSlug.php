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

class UrlSlug
{
    use ServiceTrait;

    /**
     * @param \OpenDxp\Model\DataObject\Data\UrlSlug|null $value
     * @param array $args
     * @param array $context
     *
     * @return string|null
     *
     * @throws Exception
     */
    public function resolveSlug($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value instanceof \OpenDxp\Model\DataObject\Data\UrlSlug) {
            return $value->getSlug();
        }

        return null;
    }

    /**
     * @param \OpenDxp\Model\DataObject\Data\UrlSlug|null $value
     * @param array $args
     * @param array $context
     *
     * @return int|null
     *
     * @throws Exception
     */
    public function resolveSiteId($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value instanceof \OpenDxp\Model\DataObject\Data\UrlSlug) {
            return $value->getSiteId();
        }

        return null;
    }
}
