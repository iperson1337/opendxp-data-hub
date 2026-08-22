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

use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\ClassDefinition;
use stdClass;

class Base
{
    use ServiceTrait;

    /**
     * @param string $typeName
     * @param array $attributes
     * @param ClassDefinition $class
     * @param object $container
     */
    public function __construct(
        protected $typeName,
        protected $attributes,
        protected $class,
        protected $container
    ) {
    }

    /**
     * @param array $value
     * @param array $args
     * @param array $context
     *
     * @return stdClass
     */
    public function resolve($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        /** @var \OpenDxp\Bundle\DataHubBundle\GraphQL\Query\Operator\AbstractOperator $operatorImpl */
        $operatorImpl = $this->getGraphQlService()->buildQueryOperator($this->typeName, $this->attributes);

        $element = AbstractObject::getById($value['id']);
        $valueFromOperator = $operatorImpl->getLabeledValue($element, $resolveInfo);

        $value = $valueFromOperator->value ?? null;

        return $value;
    }

    /**
     * Helper method that allows dynamic inspection into the resolver attributes.
     */
    public function getResolverAttribute(string $type): ?string
    {
        if (isset($this->attributes['children'][0]) && !empty($this->attributes['children'][0])) {
            return $this->attributes['children'][0]['attributes'][$type];
        } else {
            return null;
        }
    }
}
