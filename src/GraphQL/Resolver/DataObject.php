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
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ElementTagTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\AbstractObject;
use Override;

class DataObject extends Element
{
    use ServiceTrait, ElementTagTrait;

    public function __construct(Service $graphQlService)
    {
        parent::__construct('object', $graphQlService);
    }

    /**
     * @param array $value
     * @param array $args
     * @param array $context
     *
     * @return array|null
     *
     * @throws Exception
     */
    #[Override]
    public function resolveTag($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $object = \OpenDxp\Model\DataObject::getById($value['id']);

        if ($object) {
            $result = $this->getTags('object', $object->getId());
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @param array|null $value
     * @param array $args
     * @param array $context
     *
     * @return int|null
     */
    public function resolveIndex($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if (null === $value) {
            return null;
        }

        $object = \OpenDxp\Model\DataObject::getById($value['id']);

        if (!$object instanceof AbstractObject) {
            return null;
        }

        return $object->getIndex();
    }

    /**
     * @param array|null $value
     * @param array $args
     * @param array $context
     *
     * @return string|null
     */
    public function resolveChildrenSortBy($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if (null === $value) {
            return null;
        }

        $object = \OpenDxp\Model\DataObject::getById($value['id']);

        if (!$object instanceof \OpenDxp\Model\DataObject) {
            return null;
        }

        return $object->getChildrenSortBy();
    }
}
