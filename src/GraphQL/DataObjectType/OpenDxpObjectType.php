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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType;

use Exception;
use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Bundle\DataHubBundle\GraphQL\FieldcollectionDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\FieldHelper\DataObjectFieldHelper;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\TypeInterface\Element;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use OpenDxp\Model\DataObject\Fieldcollection\Definition;
use OpenDxp\Model\DataObject\Objectbrick;
use OpenDxp\Model\DataObject\Objectbrick\Data\AbstractData as ObjectbrickAbstractData;
use OpenDxp\Model\DataObject\Objectbrick\Definition as ObjectbrickDefinition;

class OpenDxpObjectType extends ObjectType
{
    use ServiceTrait;

    /**
     * @var string
     */
    protected $className;

    protected static $skipOperators;

    protected $fields;

    /**
     * @param string $classId
     * @param array $config
     * @param array $context
     */
    public function __construct(
        Service $graphQlService,
        string $className,
        protected $classId,
        $config = [],
        $context = []
    ) {
        $this->className = $className;
        $this->name = $config['name'] = 'object_' . $className;
        $this->setGraphQLService($graphQlService);
        $config['interfaces'] = [Element::getInstance()];
        parent::__construct($config);
    }

    /**
     * @param array $context
     */
    public function build($context = [])
    {
        $propertyType = $this->getGraphQlService()->buildGeneralType('element_property');
        $objectTreeType = $this->getGraphQlService()->buildGeneralType('object_tree');
        $elementTagType = $this->getGraphQlService()->buildGeneralType('element_tag');

        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\DataObject($this->getGraphQLService());

        // these are the system fields that are always available, maybe move some of them to FieldHelper so that they
        // are only visible if explicitly configured by the user
        $fields = [
            'id' => Type::id(),
            'creationDate' => Type::int(),
            'modificationDate' => Type::int(),
            'version' => [
                'type' => Type::int(),
                'resolve' => function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                    $object = \OpenDxp\Model\DataObject::getById($value['id']);
                    if ($object) {
                        foreach (array_reverse($object->getVersions()) as $version) {
                            if ($object->getModificationDate() === $version->getDate()) {
                                return $version->getId();
                            }
                        }
                    }

                    return null;
                },
            ],
            'objectType' => [
                'type' => Type::string(),
                'resolve' => function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                    $object = \OpenDxp\Model\DataObject::getById($value['id']);

                    if ($object) {
                        $result = $object->getType();

                        if ($result) {
                            return $result;
                        }
                    }

                    return null;
                },
            ],
            'index' => [
                'type' => Type::int(),
                'resolve' => $resolver->resolveIndex(...),
            ],
            'childrenSortBy' => [
                'type' => Type::string(),
                'resolve' => $resolver->resolveChildrenSortBy(...),
            ],
            'classname' => [
                'type' => Type::string(),
            ],
            'tags' => [
                'type' => Type::listOf($elementTagType),
                'args' => [
                    'name' => ['type' => Type::string()],
                ],
                'resolve' => $resolver->resolveTag(...),
            ],
            'properties' => [
                'type' => Type::listOf($propertyType),
                'args' => [
                    'keys' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => 'List of property key names to include '
                            . '(if omitted, all properties are returned).',
                    ],
                ],
                'resolve' => $resolver->resolveProperties(...),
            ],
            'parent' => [
                'type' => $objectTreeType,
                'resolve' => $resolver->resolveParent(...),
            ],
            'children' => [
                'type' => Type::listOf($objectTreeType),
                'args' => [
                    'objectTypes' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => 'list of object types (object, variant, folder)',
                    ],
                ],
                'resolve' => $resolver->resolveChildren(...),
            ],
            '_siblings' => [
                'type' => Type::listOf($objectTreeType),
                'args' => [
                    'objectTypes' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => 'list of object types (object, variant, folder)',
                    ],
                ],
                'resolve' => $resolver->resolveSiblings(...),
            ],
        ];

        if ($context['clientname']) {

            /** @var Configuration $configurationItem */
            $configurationItem = $context['configuration'];

            $queryColumnConfig = $configurationItem->getQueryColumnConfig($this->className);
            $columns = $queryColumnConfig['columns'] ?? [];

            if ($columns) {
                $class = ClassDefinition::getById($this->classId);
                foreach ($columns as $column) {
                    if ($column['isOperator'] && self::$skipOperators) {
                        continue;
                    }

                    if (!$column['isOperator'] && is_array($column['attributes']) && $column['attributes']['dataType'] == 'fieldcollections') {
                        $this->addFieldCollectionDefs($column, $class, $fields);
                    } elseif (!$column['isOperator'] && is_array($column['attributes']) && $column['attributes']['dataType'] == 'objectbricks') {
                        $this->addObjectBrickDefs($column, $class, $fields);
                    } else {
                        $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();
                        $result = $fieldHelper->getQueryFieldConfigFromConfig($column, $class);
                        if (is_array($result)) {
                            $fields[$result['key']] = $result['config'];
                        }
                    }
                }
            }
        }

        $this->fields = null;
        ksort($fields);
        $this->config['fields'] = $fields;
    }

    /**
     * @param array $column
     * @param array $fields
     *
     * @return void
     *
     * @throws Exception
     */
    public function addFieldCollectionDefs($column, ClassDefinition $class, &$fields)
    {
        $fieldname = $column['attributes']['attribute'];
        /** @var ClassDefinition\Data\Fieldcollections $fieldDef */
        $fieldDef = $class->getFieldDefinition(($fieldname));
        $allowedFcs = $fieldDef->getAllowedTypes();
        $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();

        $unionTypes = [];

        foreach ($allowedFcs as $allowedFcName) {
            $fcKey = 'graphql_fieldcollection_' . $allowedFcName;
            if (RuntimeCache::isRegistered($fcKey)) {
                $itemFcType = RuntimeCache::get($fcKey);
            } else {
                $fcDef = Definition::getByKey($allowedFcName);
                $fcFields = [];
                if ($fcDef != null) {
                    $fcFieldDefs = $fcDef->getFieldDefinitions();

                    foreach ($fcFieldDefs as $key => $fieldDef) {
                        $attrName = $fieldDef->getName();
                        $columnDesc = [
                            'isOperator' => false,
                            'attributes' => [
                                'attribute' => $attrName,
                                'label' => $fieldDef->getName(),
                                'dataType' => $fieldDef->getFieldtype(),
                            ],
                        ];
                        $fcResult = $fieldHelper->getQueryFieldConfigFromConfig($columnDesc, $fcDef);
                        if ($fcResult) {
                            $fcFields[$fcResult['key']] = $fcResult['config'];
                        }
                    }
                }

                $fcLocalizedFields = $fcDef->getFieldDefinition('localizedfields');
                if ($fcLocalizedFields instanceof ClassDefinition\Data\Localizedfields) {
                    $fcLocalizedFieldDefs = $fcLocalizedFields->getFieldDefinitions();

                    foreach ($fcLocalizedFieldDefs as $key => $fieldDef) {
                        $attrName = $fieldDef->getName();

                        $columnDesc = [
                            'isOperator' => false,
                            'attributes' => [
                                'attribute' => $attrName,
                                'label' => $fieldDef->getName(),
                                'dataType' => $fieldDef->getFieldtype(),
                            ],
                        ];
                        $fcResult = $fieldHelper->getQueryFieldConfigFromConfig($columnDesc, $fcDef, $fcLocalizedFields);
                        if ($fcResult) {
                            $fcFields[$fcResult['key']] = $fcResult['config'];
                        }
                    }
                }

                $typename = 'fieldcollection_' . $allowedFcName;

                $itemFcType = new ObjectType([
                    'name' => $typename,
                    'fields' => $fcFields,
                ]);

                RuntimeCache::save($itemFcType, $fcKey);
            }

            $unionTypes[] = $itemFcType;
        }

        $unionname = 'object_' . $this->className . '_' . $fieldname;

        $unionTypesConfig = [
            'name' => $unionname,
            'types' => $unionTypes,
        ];

        $union = new FieldcollectionType($this->getGraphQlService(), $unionTypesConfig);

        $fields[$fieldname] =
            [
                'name' => $fieldname,
                'type' => Type::listOf($union),
                'resolve' => function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) use ($fieldname) {
                    if ($value[$fieldname] instanceof Fieldcollection) {
                        $lofItems = [];
                        $fcData = $value[$fieldname];

                        $items = $fcData->getItems();
                        if ($items) {
                            $idx = -1;

                            /** @var AbstractData $item */
                            foreach ($items as $item) {
                                $idx++;
                                $data = new FieldcollectionDescriptor();
                                $data['__fcType'] = $item->getType();
                                $data['__fcFieldname'] = $fieldname;
                                $data['__itemIdx'] = $idx;

                                $data['id'] = $value['id'];
                                $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();
                                $fieldHelper->extractData($data, $item, $args, $context, $resolveInfo);
                                $lofItems[] = $data;
                            }
                        }

                        return $lofItems;
                    }

                    return null;
                },

            ];
    }

    /**
     * @param array $column
     * @param ClassDefinition $class
     * @param array $fields
     *
     * @return void
     *
     * @throws \Exception
     */
    public function addObjectBrickDefs(array $column, ClassDefinition $class, array &$fields): void
    {
        $fieldname = $column['attributes']['attribute'];
        /** @var ClassDefinition\Data\Objectbricks $fieldDef */
        $fieldDef = $class->getFieldDefinition($fieldname);
        if (!$fieldDef) {
            return;
        }

        $allowedBricks = $fieldDef->getAllowedTypes();
        if (empty($allowedBricks)) {
            return;
        }

        $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();
        $brickFields = [];

        foreach ($allowedBricks as $allowedBrickName) {
            $brickKey = 'graphql_objectbrick_' . $allowedBrickName;
            if (RuntimeCache::isRegistered($brickKey)) {
                $brickType = RuntimeCache::get($brickKey);
            } else {
                $brickDef = ObjectbrickDefinition::getByKey($allowedBrickName);
                if (!$brickDef) {
                    continue;
                }

                $brickTypeFields = $this->buildBrickTypeFields($brickDef, $fieldHelper);
                $typename = 'objectbrick_' . $allowedBrickName;

                $brickType = new ObjectType([
                    'name' => $typename,
                    'fields' => $brickTypeFields,
                ]);

                RuntimeCache::save($brickType, $brickKey);
            }

            $brickFields[$allowedBrickName] = [
                'type' => $brickType,
            ];
        }

        if (empty($brickFields)) {
            return;
        }

        $fields[$fieldname] = [
            'name' => $fieldname,
            'type' => new ObjectType([
                'name' => 'object_' . $this->className . '_' . $fieldname,
                'fields' => $brickFields,
            ]),
            'resolve' => function ($value = null, $args = [], $context = [], ResolveInfo $resolveInfo = null) use ($fieldname, $fieldHelper) {
                if (!isset($value[$fieldname]) || !($value[$fieldname] instanceof Objectbrick)) {
                    return null;
                }

                $objectbrickData = $value[$fieldname];
                $items = $objectbrickData->getItems();
                if (empty($items)) {
                    return [];
                }

                $result = [];
                foreach ($items as $brickType => $brickData) {
                    if ($brickData instanceof ObjectbrickAbstractData) {
                        $data = ['id' => $value['id']];
                        $fieldHelper->extractData($data, $brickData, $args, $context, $resolveInfo);
                        $result[$brickType] = $data;
                    }
                }

                return $result;
            },
        ];
    }

    /**
     * Build fields for a brick type (regular and localized fields)
     *
     * @param ObjectbrickDefinition $brickDef
     * @param DataObjectFieldHelper $fieldHelper
     *
     * @return array
     */
    private function buildBrickTypeFields(ObjectbrickDefinition $brickDef, $fieldHelper): array
    {
        $brickTypeFields = [];

        $brickFieldDefs = $brickDef->getFieldDefinitions();
        foreach ($brickFieldDefs as $fieldDef) {
            $columnDesc = [
                'isOperator' => false,
                'attributes' => [
                    'attribute' => $fieldDef->getName(),
                    'label' => $fieldDef->getName(),
                    'dataType' => $fieldDef->getFieldtype(),
                ],
            ];
            $brickResult = $fieldHelper->getQueryFieldConfigFromConfig($columnDesc, $brickDef);
            if ($brickResult) {
                $brickTypeFields[$brickResult['key']] = $brickResult['config'];
            }
        }

        $brickLocalizedFields = $brickDef->getFieldDefinition('localizedfields');
        if ($brickLocalizedFields instanceof ClassDefinition\Data\Localizedfields) {
            $brickLocalizedFieldDefs = $brickLocalizedFields->getFieldDefinitions();
            foreach ($brickLocalizedFieldDefs as $fieldDef) {
                $columnDesc = [
                    'isOperator' => false,
                    'attributes' => [
                        'attribute' => $fieldDef->getName(),
                        'label' => $fieldDef->getName(),
                        'dataType' => $fieldDef->getFieldtype(),
                    ],
                ];
                $brickResult = $fieldHelper->getQueryFieldConfigFromConfig($columnDesc, $brickDef, $brickLocalizedFields);
                if ($brickResult) {
                    $brickTypeFields[$brickResult['key']] = $brickResult['config'];
                }
            }
        }

        return $brickTypeFields;
    }

    /**
     * @return FieldDefinition[]
     *
     * @throws InvariantViolation
     */
    public function getFields(): array
    {
        if (null === $this->fields) {
            $fields = $this->config['fields'] ?? [];
            $this->fields = FieldDefinition::defineFieldMap($this, $fields);
        }

        return $this->fields;
    }
}
