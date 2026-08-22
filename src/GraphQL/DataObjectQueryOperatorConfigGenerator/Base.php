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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryOperatorConfigGenerator;

use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\OperatorTypeDefinitionInterface;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;

abstract class Base implements OperatorTypeDefinitionInterface
{
    /**
     * @var Service
     */
    protected $graphQlService;

    public function __construct(Service $graphQlService)
    {
        $this->graphQlService = $graphQlService;
    }

    /**
     * @param string $typeName
     * @param array $nodeDef
     * @param ClassDefinition|null $class
     * @param object|null $container
     * @param array $params
     *
     * @return Type
     */
    public function getGraphQlType($typeName, $nodeDef, $class = null, $container = null, $params = [])
    {
        return Type::string();
    }

    /**
     * @param string $typeName
     * @param array $nodeDef
     * @param ClassDefinition|null $class
     * @param object|null $container
     * @param array $params
     *
     * @return mixed
     */
    public function getGraphQlQueryOperatorConfig($typeName, $nodeDef, $class = null, $container = null, $params = [])
    {
        $attributes = $nodeDef['attributes'];
        $fieldname = $this->getFieldname($attributes);
        $type = $this->getGraphQlType($typeName, $nodeDef, $class, $container, $params);

        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\Base($typeName, $attributes, $class, $container);
        $resolver->setGraphQlService($this->graphQlService);

        return $this->enrichConfig([
            'name' => $fieldname,
            'type' => $type,
            'resolve' => $resolver->resolve(...),

        ], $container);
    }

    /**
     * @param array $config
     * @param object|null $container
     *
     * @return mixed
     */
    public function enrichConfig($config, $container = null)
    {
        if ($container instanceof Data\Localizedfields) {
            $config['args'] = $config['args'] ?: [];
            $config['args'] = array_merge($config['args'],
                ['language' => ['type' => Type::string()],
            ]);
        }

        return $config;
    }

    /**
     * @param array $attributes
     * @param ClassDefinition|null $class
     * @param object|null $container
     *
     * @return \GraphQL\Type\Definition\ScalarType
     */
    public function getFieldType($attributes, $class = null, $container = null)
    {
        return Type::string();
    }

    /**
     * @param array{label?: string} $attributes
     *
     * @return string|null
     */
    protected function getFieldname($attributes)
    {
        $label = $attributes['label'] ?? '#' . uniqid();

        return preg_replace('/[^A-Za-z0-9\-\.~_]+/', '_', $label);
    }
}
