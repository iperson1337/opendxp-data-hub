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
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\ClassDefinition\Data;

class QuantityValueType extends ObjectType
{
    use ServiceTrait;

    /** @var Data */
    protected $fieldDefinition;

    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, ?Data $fieldDefinition = null, $config = [], $context = [])
    {
        $this->fieldDefinition = $fieldDefinition;
        $this->setGraphQLService($graphQlService);
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $valueType = Type::float();
        if (isset($config['fields']['value']['type'])) {
            $valueType = $config['fields']['value']['type'];
        }

        $config['fields'] = self::getFieldConfig($this->getGraphQlService(), $valueType);
    }

    /**
     * @param string $valueType
     *
     * @return array[]
     */
    public static function getFieldConfig(Service $graphQlService, $valueType)
    {
        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\QuantityValue();
        $resolver->setGraphQLService($graphQlService);
        $fields = [
            'unit' => [
                'type' => QuantityValueUnitType::getInstance(),
                'resolve' => $resolver->resolveUnit(...),
            ],
            'value' => [
                'type' => $valueType,
                'resolve' => $resolver->resolveValue(...),
            ],
            'toString' => [
                'type' => Type::string(),
                'resolve' => $resolver->resolveToString(...),
                'args' => ['language' => ['type' => Type::string()]],
            ],
        ];

        return $fields;
    }
}
