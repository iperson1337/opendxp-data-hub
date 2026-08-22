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
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\WorkspaceHelper;
use OpenDxp\Model\Asset\Image;

class Video
{
    use ServiceTrait;

    /**
     * @param mixed $value
     * @param array $args
     * @param array $context
     *
     * @return string|null
     */
    public function resolveType($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value instanceof \OpenDxp\Model\DataObject\Data\Video) {
            return $value->getType();
        }

        return null;
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
    public function resolveTitle($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value instanceof \OpenDxp\Model\DataObject\Data\Video) {
            return $value->getTitle();
        }

        return null;
    }

    /**
     * @param mixed $value
     * @param array $args
     * @param array $context
     *
     * @return string|null
     */
    public function resolveDescription($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value instanceof \OpenDxp\Model\DataObject\Data\Video) {
            return $value->getDescription();
        }

        return null;
    }

    /**
     * @param mixed $value
     * @param array $args
     * @param array $context
     *
     * @return ElementDescriptor|null
     */
    public function resolvePoster($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value instanceof \OpenDxp\Model\DataObject\Data\Video) {
            $asset = $value->getPoster();
            if ($asset instanceof Image) {
                if (!WorkspaceHelper::checkPermission($asset, 'read')) {
                    return null;
                }

                $data = new ElementDescriptor();
                $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();
                $fieldHelper->extractData($data, $asset, $args, $context, $resolveInfo);

                $data['data'] = isset($data['data']) ? base64_encode((string) $data['data']) : null;
                $data['__elementSubtype'] = $asset->getType();

                return $data;
            }
        }

        return null;
    }

    /**
     * @param mixed $value
     * @param array $args
     * @param array $context
     *
     * @return ElementDescriptor|array|null
     */
    public function resolveData($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value instanceof \OpenDxp\Model\DataObject\Data\Video) {
            if ($value->getType() == 'asset' && $value->getData() instanceof \OpenDxp\Model\Asset\Video) {
                if (!WorkspaceHelper::checkPermission($value->getData(), 'read')) {
                    return null;
                }

                $data = new ElementDescriptor();
                $asset = $value->getData();
                $fieldHelper = $this->getGraphQlService()->getAssetFieldHelper();
                $fieldHelper->extractData($data, $asset, $args, $context, $resolveInfo);
                $data['data'] = !empty($data['data']) ? base64_encode((string) $data['data']) : null;
                $data['__elementSubtype'] = $asset->getType();

                return $data;
            } else {
                if ($value->getData()) {
                    $data = ['id' => $value->getData()];

                    return $data;
                }
            }
        }

        return null;
    }
}
