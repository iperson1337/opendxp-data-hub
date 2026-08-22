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

class Link
{
    use ServiceTrait;

    /**
     * @param mixed $value
     * @param array $args
     * @param array $context
     *
     * @return string|null
     *
     * @throws Exception
     */
    public function resolveText($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveLinkValue($value, 'text');
    }

    /**
     * @param mixed $value
     * @param array $args
     * @param array $context
     *
     * @return string|null
     *
     * @throws Exception
     */
    public function resolvePath($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveLinkValue($value, 'path');
    }

    /**
     * @return null
     */
    public function resolveTarget($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveLinkValue($value, 'target');
    }

    /**
     * @return null
     */
    public function resolveAnchor($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveLinkValue($value, 'anchor');
    }

    /**
     * @return null
     */
    public function resolveTitle($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveLinkValue($value, 'title');
    }

    /**
     * @return null
     */
    public function resolveAccesskey($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveLinkValue($value, 'accesskey');
    }

    /**
     * @return null
     */
    public function resolveRel($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveLinkValue($value, 'rel');
    }

    /**
     * @return null
     */
    public function resolveClass($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveLinkValue($value, 'class');
    }

    /**
     * @return null
     */
    public function resolveAttributes($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveLinkValue($value, 'attributes');
    }

    /**
     * @return null
     */
    public function resolveTabindex($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveLinkValue($value, 'tabindex');
    }

    /**
     * @return null
     */
    public function resolveParameters($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveLinkValue($value, 'parameters');
    }

    /**
     * @return null
     */
    protected function resolveLinkValue(?\OpenDxp\Model\DataObject\Data\Link $value, string $property)
    {
        if ($value instanceof \OpenDxp\Model\DataObject\Data\Link) {
            $getter = 'get' . ucfirst($property);

            return $value->{$getter}();
        }

        return null;
    }
}
