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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\FieldHelper;

use Exception;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\File;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use OpenDxp\Model\DataObject\Localizedfield;
use OpenDxp\Model\DataObject\Objectbrick\Definition;
use Override;

class DataObjectFieldHelper extends AbstractFieldHelper
{
    /**
     * @param array $nodeDef
     * @param ClassDefinition|\OpenDxp\Model\DataObject\Fieldcollection\Definition $class
     * @param object|null $container
     *
     * @return array|bool|null
     */
    public function getQueryFieldConfigFromConfig($nodeDef, $class, $container = null)
    {
        $result = false;

        $attributes = $nodeDef['attributes'];

        if ($nodeDef['isOperator']) {
            $key = $attributes['label'] ?? '';

            $key = File::getValidFilename($key);

            $result = [
                'key' => $key,
                'config' => $this->getGraphQlOperatorConfig(
                    'query',
                    $nodeDef,
                    $class,
                    null,
                    []
                )];
        } else {
            $key = $attributes['attribute'];

            // system columns which are not part of the common set (see OpenDxpObjectType)
            if ($attributes['dataType'] == 'system') {
                return match ($key) {
                    'creationDate', 'modificationDate' => [
                        'key' => $key,
                        'config' => [
                            'name' => $key,
                            'type' => Type::int(),
                        ],
                    ],
                    'filename', 'fullpath', 'key' => [
                        'key' => $key,
                        'config' => [
                            'name' => $key,
                            'type' => Type::string(),
                        ],
                    ],
                    'published' => [
                        'key' => $key,
                        'config' => [
                            'name' => $key,
                            'type' => Type::boolean(),
                        ],
                    ],
                    default => null,
                };
            } else {
                $fieldDefinition = $this->getFieldDefinitionFromKey($class, $key, $container);

                if (!$fieldDefinition) {
                    Logger::error('could not resolve field "' . $key . '" in class ' . $class->getName());

                    return false;
                }

                if ($this->supportsGraphQL($fieldDefinition, 'query')) {
                    $fieldName = $fieldDefinition->getName();

                    $result = ['key' => $fieldName,
                        'config' => $this->getGraphQlQueryFieldConfig(
                            $key,
                            $fieldDefinition,
                            $class,
                            $container
                        )];
                }
            }
        }

        return $result;
    }

    /**
     * @param string $mode
     * @param array $nodeDef
     * @param ClassDefinition $class
     * @param object|null $container
     * @param array $params
     *
     * @return mixed
     */
    public function getGraphQlOperatorConfig($mode, $nodeDef, $class, $container, $params = [])
    {
        $attributes = $nodeDef['attributes'];
        $operatorTypeName = $attributes['class'];

        $builder = 'buildDataObject' . ucfirst($mode) . 'OperatorConfig';
        $typeDef = $this->getGraphQlService()->$builder($operatorTypeName, $nodeDef, $class, $container, $params);

        return $typeDef;
    }

    /**
     * @param ClassDefinition|\OpenDxp\Model\DataObject\Fieldcollection\Definition $class
     * @param string $key
     * @param object|null $container
     *
     * @return Data|null
     */
    public function getFieldDefinitionFromKey($class, $key, &$container = null)
    {
        $fieldDefinition = null;
        $parts = explode('~', $key);

        if (str_starts_with($key, '~')) {
            // classification store ...
        } elseif (count($parts) > 1) {
            $brickType = $parts[0];
            $brickDescriptor = null;

            if (str_contains($brickType, '?')) {
                $brickDescriptor = substr($brickType, 1);
                $brickDescriptor = json_decode($brickDescriptor, true);
                $brickType = $brickDescriptor['containerKey'];
            }

            $brickKey = $parts[1];

            $brickDefinition = Definition::getByKey($brickType);

            if ($brickDescriptor) {
                /** @var Data\Localizedfields|null $fieldDefinitionLocalizedFields */
                $fieldDefinitionLocalizedFields = $brickDefinition->getFieldDefinition('localizedfields');
                if ($fieldDefinition = $fieldDefinitionLocalizedFields?->getFieldDefinition($brickKey)) {
                    $container = $fieldDefinitionLocalizedFields;
                }
            }

            if (!$fieldDefinition) {
                $fieldDefinition = $brickDefinition->getFieldDefinition($brickKey);
            }
        } else {
            /** @var Data\Localizedfields|null $fieldDefinitionLocalizedFields */
            $fieldDefinitionLocalizedFields = $class->getFieldDefinition('localizedfields');
            if ($fieldDefinition = $fieldDefinitionLocalizedFields?->getFieldDefinition($key)) {
                $container = $fieldDefinitionLocalizedFields;
            }
            if (!$fieldDefinition) {
                $fieldDefinition = $class->getFieldDefinition($key);
            }
        }

        return $fieldDefinition;
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    public function supportsGraphQL(Data $fieldDefinition, string $operationType)
    {
        $typeName = $fieldDefinition->getFieldtype();

        return match ($operationType) {
            'query' => $this->getGraphQlService()->supportsDataObjectQueryDataType($typeName),
            'mutation' => $this->getGraphQlService()->supportsDataObjectMutationDataType($typeName),
            default => throw new ClientSafeException('unknown operation type ' . $typeName),
        };
    }

    /**
     * @param string $attribute
     * @param Data $fieldDefinition
     * @param ClassDefinition $class
     * @param object $container
     *
     * @return mixed
     */
    public function getGraphQlQueryFieldConfig($attribute, $fieldDefinition, $class, $container)
    {
        $typeName = $fieldDefinition->getFieldtype();
        $typeDef = $this->getGraphQlService()->buildDataObjectQueryDataConfig($attribute, $typeName, $fieldDefinition, $class, $container);

        return $typeDef;
    }

    /**
     * @param array $nodeDef
     * @param ClassDefinition|\OpenDxp\Model\DataObject\Fieldcollection\Definition $class
     *
     * @return array|false|null
     */
    public function getMutationFieldConfigFromConfig($nodeDef, $class)
    {
        $container = null;
        $result = false;

        $attributes = $nodeDef['attributes'];

        if ($nodeDef['isOperator'] ?? false) {
            $key = $attributes['label'] ?? '';
            $key = preg_replace('/[^A-Za-z0-9\-\.~_]+/', '_', $key);

            $result = $this->getGraphQlOperatorConfig(
                'mutation',
                $nodeDef,
                $class,
                null,
                []
            );

            $result['key'] = $key;
        } else {
            $key = $attributes['attribute'];

            // system columns which are not part of the common set (see OpenDxpObjectType)
            if ($attributes['dataType'] === 'system') {
                return match ($key) {
                    'key' => [
                        'key' => $key,
                        'arg' => ['type' => Type::string()],
                        'processor' => function ($object, $newValue, $args) {
                            $object->setKey($newValue);
                        },

                    ],
                    'published' => [
                        'key' => $key,
                        'arg' => ['type' => Type::boolean()],
                        'processor' => function ($object, $newValue, $args) {
                            $object->setPublished($newValue);
                        },
                    ],
                    default => null,
                };
            } else {
                $fieldDefinition = $this->getFieldDefinitionFromKey($class, $key, $container);

                if (!$fieldDefinition) {
                    Logger::error('could not resolve field ' . $key);

                    return false;
                }

                if ($this->supportsGraphQL($fieldDefinition, 'mutation')) {
                    $fieldName = $fieldDefinition->getName();

                    $result = $this->getGraphQlMutationFieldConfig(
                        $nodeDef,
                        $class,
                        $container
                    );
                    $result['key'] = $fieldName;
                }
            }
        }

        return $result;
    }

    /**
     * @param array $nodeDef
     * @param ClassDefinition|\OpenDxp\Model\DataObject\Fieldcollection\Definition $class
     * @param object $container
     *
     * @return array
     */
    public function getGraphQlMutationFieldConfig($nodeDef, $class, $container)
    {
        $typeDef = $this->getGraphQlService()->buildDataObjectMutationDataConfig($nodeDef, $class, $container);

        return $typeDef;
    }

    /**
     * @param array $nodeConf
     * @param ClassDefinition $class
     * @param object|null $container
     *
     * @return mixed
     */
    public function getGraphQlTypeFromNodeConf($nodeConf, $class, $container = null)
    {
        $attributes = $nodeConf['attributes'];

        if ($nodeConf['isOperator']) {
            $operatorTypeName = $attributes['class'];
            $type = $this->getGraphQlService()->buildDataObjectOperatorQueryType('mutation', $operatorTypeName, $nodeConf, $class, $container);
        } else {
            $key = $attributes['attribute'];
            $fieldDefinition = $this->getFieldDefinitionFromKey($class, $key);
            $type = $this->getGraphQlService()->buildDataObjectDataQueryType($fieldDefinition, $class, $container);
        }

        return $type;
    }

    /**
     * @param array $data
     * @param object $container
     * @param array $args
     * @param array $context
     * @param ResolveInfo $resolveInfo
     */
    #[Override]
    public function doExtractData(FieldNode $ast, &$data, $container, $args, $context, $resolveInfo = null)
    {
        $astName = $ast->name->value;

        // sometimes we just want to expand relations just to throw them away afterwards because not requested
        if ($this->skipField($container, $astName)) {
            return;
        }

        // example for http://webonyx.github.io/graphql-php/error-handling/
        //         throw new MySafeException("fieldhelper", "TBD customized error message");

        $getter = 'get' . ucfirst((string) $astName);

        $isLocalizedField = false;
        $containerDefinition = null;

        if ($container instanceof Concrete) {
            $containerDefinition = $container->getClass();
        } elseif ($container instanceof AbstractData || $container instanceof \OpenDxp\Model\DataObject\Objectbrick\Data\AbstractData) {
            $containerDefinition = $container->getDefinition();
        }

        if ($containerDefinition) {
            /** @var Data\Localizedfields|null $lfDefs */
            $lfDefs = $containerDefinition->getFieldDefinition('localizedfields');
            if ($lfDefs && $lfDefs->getFieldDefinition($astName)) {
                $isLocalizedField = true;
            }
        }

        if (method_exists($container, $getter)) {
            if ($isLocalizedField) {
                // defer it
                $data[$astName] = function ($source, $args, $context, ResolveInfo $info) use (
                    $container,
                    $getter
                ) {
                    $orgUseFallbackValues = Localizedfield::getGetFallbackValues();
                    Localizedfield::setGetFallbackValues(
                        $args['getFallbackLanguageValue'] ?? $orgUseFallbackValues
                    );
                    $localizedValue = $container->$getter($args['language'] ?? null);
                    Localizedfield::setGetFallbackValues($orgUseFallbackValues);

                    return $localizedValue;
                };
            } else {
                $data[$astName] = $container->$getter();
            }
        }
    }

    /**
     * @param object $container
     * @param string $astName
     *
     * @return bool
     */
    #[Override]
    public function skipField($container, $astName)
    {
        if ($container instanceof Concrete || $container instanceof Localizedfield) {
            $fieldDefinition = $container->getClass()->getFieldDefinition($astName);

            if ($fieldDefinition instanceof Data\Relations\AbstractRelations) {
                // do not autoexpand relations
                return true;
            }
        }

        return false;
    }
}
