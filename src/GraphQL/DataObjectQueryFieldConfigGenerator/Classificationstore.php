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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryFieldConfigGenerator;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Classificationstore\GroupConfig;
use Override;

class Classificationstore extends Base
{
    /**
     * @param string $attribute
     * @param ClassDefinition|null $class
     * @param object|null $container
     *
     * @return array
     */
    #[Override]
    public function getGraphQlFieldConfig($attribute, Data $fieldDefinition, $class = null, $container = null)
    {
        return $this->enrichConfig($fieldDefinition, $class, $attribute, [
            'name' => $fieldDefinition->getName(),
            'type' => $this->getFieldType($fieldDefinition, $class, $container),
            'args' => ['language' => ['type' => Type::string()]],
            'description' => 'returns a list of group containers',
            'resolve' => function ($value, $args, $context = [], ?ResolveInfo $resolveInfo = null) {
                $fieldName = $resolveInfo->fieldName;
                $language = $args['language'] ?? null;
                /** @var \OpenDxp\Model\DataObject\Classificationstore $csField */
                $csField = $value[$fieldName];

                $fd = new Data\Classificationstore();
                $fd->setName($fieldName);
                $activeGroups = [];
                $activeGroups = $fd->recursiveGetActiveGroupsIds($csField->getObject(), $activeGroups);

                $result = [];
                foreach ($activeGroups as $groupId => $enabled) {
                    // in case group name and description is not needed this can be optimized
                    // analyze the resolveInfo
                    $groupConfig = GroupConfig::getById($groupId);

                    if ($groupConfig) {
                        $result[] = [
                                'id' => $groupId,
                                'name' => $groupConfig->getName(),
                                'description' => $groupConfig->getDescription(),
                                '_csValue' => $csField,
                                '_language' => $language,
                            ];
                    }
                }

                return $result;
            },
        ], $container);
    }

    /**
     * @param ClassDefinition|null $class
     * @param object|null $container
     *
     * @return ListOfType
     */
    #[Override]
    public function getFieldType(Data $fieldDefinition, $class = null, $container = null)
    {
        $service = $this->getGraphQlService();
        $groupType = $service->getClassificationStoreTypeDefinition('cs_group');

        return Type::listOf($groupType);
    }
}
