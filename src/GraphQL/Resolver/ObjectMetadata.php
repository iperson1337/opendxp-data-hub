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
use OpenDxp\Bundle\DataHubBundle\GraphQL\FieldHelper\DataObjectFieldHelper;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Data;

class ObjectMetadata
{
    use ServiceTrait;

    /**
     * @param ClassDefinition\Data|null $fieldDefinition
     * @param ClassDefinition|null $class
     * @param DataObjectFieldHelper|null $fieldHelper
     */
    public function __construct(
        protected $fieldDefinition = null,
        protected $class = null,
        protected $fieldHelper = null
    ) {
    }

    /**
     * @param mixed $value
     * @param array $args
     * @param array $context
     *
     * @return array|null
     *
     * @throws Exception
     */
    public function resolveElement($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $element = null;

        if (!$value['element']) {
            return null;
        }

        if ($value['element']['__elementType'] == 'object') {
            $element = AbstractObject::getById($value['element']['__destId']);
        } else {
            if ($value['element']['__elementType'] == 'asset') {
                $element = Asset::getById($value['element']['__destId']);
            }
        }

        if (!$element) {
            return null;
        }

        $data = $value['element'];
        $this->fieldHelper->extractData($data, $element, $args, $context, $resolveInfo);

        return $data;
    }

    /**
     * @param mixed $value
     * @param array $args
     * @param array $context
     *
     * @return array|null
     *
     * @throws Exception
     */
    public function resolveMetadata($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($value && $value['element']) {
            /** @var Data\ObjectMetadata $relation */
            $relation = $value['element']['__relation'];
            $meta = $relation->getData();
            $result = [];
            if ($meta) {
                foreach ($meta as $metaItemKey => $metaItemValue) {
                    $result[] = [
                        'name' => $metaItemKey,
                        'value' => $metaItemValue,
                    ];
                }
            }

            return $result;
        }

        return null;
    }
}
