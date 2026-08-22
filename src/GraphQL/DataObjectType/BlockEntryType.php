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

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\DependencyInjection\ContainerAwareInterface;
use OpenDxp\Bundle\DataHubBundle\DependencyInjection\ContainerAwareTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\BlockDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Helper;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Fieldcollection\Definition;

class BlockEntryType extends ObjectType implements ContainerAwareInterface
{
    /**
     * @var static|null
     */
    protected static $instance;

    use ServiceTrait;
    use ContainerAwareTrait;

    /** @var Data */
    protected $fieldDefinition;

    /**
     * @param ClassDefinition|null $class
     * @param array $config
     */
    public function __construct(
        Service $graphQlService,
        Data $fieldDefinition,
        protected $class = null,
        $config = []
    ) {
        $this->fieldDefinition = $fieldDefinition;
        $this->setGraphQLService($graphQlService);

        $this->build($config);

        parent::__construct($config);
    }

    /**
     * @param string $type
     * @param ClassDefinition|null $class
     *
     * @return static|null
     */
    public static function getInstance($type, Service $graphQlService, Data $fieldDefinition, $class)
    {
        if (!isset(self::$instance[$type])) {
            $config = [
                'name' => $type,
            ];
            self::$instance = new static($graphQlService, $fieldDefinition, $class, $config);
        }

        return self::$instance;
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        if ($this->class instanceof Definition) {
            $name = $this->class->getKey();
        } else {
            $name = $this->class->getName();
        }

        $config['name'] = 'block_'.$name.'_'.$this->fieldDefinition->getName() . '_entry';
        $fields = [];

        $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();

        Helper::extractDataDefinitions($this->fieldDefinition, $fieldDefinitions);

        foreach ($fieldDefinitions as $fieldDef) {
            if ($fieldDef instanceof ClassDefinition\Data\Localizedfields) {
                $fcLocalizedFieldDefs = $fieldDef->getFieldDefinitions();

                foreach ($fcLocalizedFieldDefs as $localizedFieldDef) {
                    if ($fieldHelper->supportsGraphQL($localizedFieldDef, 'query')) {
                        $fields[$localizedFieldDef->getName()] = $this->prepareField($localizedFieldDef, true);
                    }
                }
            } elseif ($fieldHelper->supportsGraphQL($fieldDef, 'query')) {
                $fields[$fieldDef->getName()] = $this->prepareField($fieldDef);
            }
        }

        $config['fields'] = $fields;
    }

    /**
     * @return mixed
     */
    protected function prepareField(Data $fieldDef, bool $localized = false)
    {
        $field = $this->getGraphQlService()->getObjectFieldHelper()->getGraphQlQueryFieldConfig(
            $fieldDef->getName(),
            $fieldDef,
            $this->class,
            $this->container
        );

        $hasResolve = isset($field['resolve']);
        /** @var callable $resolve */
        $resolve = $hasResolve ? $field['resolve'] : null;

        $field['resolve'] = function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) use ($hasResolve, $resolve) {
            if (!$resolveInfo) {
                return null;
            }

            if (!is_array($value)) {
                return null;
            }

            if (!array_key_exists($resolveInfo->fieldName, $value)) {
                return null;
            }

            $value = $value[$resolveInfo->fieldName];

            if (!$value instanceof BlockDescriptor) {
                return null;
            }

            if ($hasResolve) {
                return $resolve($value, $args, $context, $resolveInfo);
            }

            return $this->graphQlService::resolveValue($value, $this->fieldDefinition, $this->fieldDefinition->getName(), $args);
        };

        return $field;
    }
}
