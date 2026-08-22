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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryFieldConfigGenerator\Helper;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\BaseDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\WorkspaceHelper;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Data\ObjectMetadata;

class ObjectsMetadata
{
    use ServiceTrait;

    /**
     * @param string $attribute
     * @param ClassDefinition\Data $fieldDefinition
     * @param ClassDefinition $class
     */
    public function __construct(
        Service $graphQlService,
        public $attribute,
        public $fieldDefinition,
        public $class
    ) {
        $this->setGraphQLService($graphQlService);
    }

    /**
     * @param BaseDescriptor|null $value
     * @param array $args
     * @param array $context
     *
     * @return array|null
     *
     * @throws Exception
     */
    public function resolve($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $result = [];
        $relations = \OpenDxp\Bundle\DataHubBundle\GraphQL\Service::resolveValue($value, $this->fieldDefinition, $this->attribute, $args);
        if ($relations) {
            /** @var ObjectMetadata $relation */
            foreach ($relations as $relation) {
                $element = $relation->getElement();
                if (!WorkspaceHelper::checkPermission($element, 'read')) {
                    continue;
                }

                $data = [];
                $elementData = new ElementDescriptor($element);
                $this->getGraphQlService()->extractData($elementData, $element, $args, $context, $resolveInfo);

                $elementData['__relation'] = $relation;
                $elementData['__destId'] = $relation->getElement()?->getId();
                $data['element'] = $elementData;
                $data['metadata'] = microtime();

                $result[] = $data;
            }

            return $result;
        }

        return null;
    }
}
