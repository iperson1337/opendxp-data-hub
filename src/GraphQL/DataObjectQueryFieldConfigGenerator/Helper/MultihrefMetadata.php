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
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Data\ElementMetadata;

class MultihrefMetadata
{
    use ServiceTrait;

    /**
     * @param string $attribute
     * @param Data $fieldDefinition
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
        $relations = \OpenDxp\Bundle\DataHubBundle\GraphQL\Service::resolveValue($value, $this->fieldDefinition, $this->attribute, $args);
        if ($relations) {
            $result = [];
            /** @var ElementMetadata $relation */
            foreach ($relations as $relation) {
                $element = $relation->getElement();
                if (!WorkspaceHelper::checkPermission($element, 'read')) {
                    continue;
                }

                $data = [];

                $element = $relation->getElement();
                $elementData = new ElementDescriptor($element);

                $elementData['__relation'] = $relation;
                $elementData['__destId'] = $relation->getElementId();
                $data['element'] = $elementData;

                $result[] = $data;
            }

            return $result;
        }

        return null;
    }
}
