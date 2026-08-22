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
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\ClassDefinition;

/**
 * @deprecated will be removed in Data Hub 2
 */
class Merge
{
    use ServiceTrait;

    /**
     * @param string|null $typeName
     * @param array|null $attributes
     * @param ClassDefinition|null $class
     * @param object|null $container
     */
    public function __construct(
        protected $typeName = null,
        protected $attributes = null,
        protected $class = null,
        protected $container = null
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
    public function resolve($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        /** @var \OpenDxp\Bundle\DataHubBundle\GraphQL\Query\Operator\AbstractOperator $operatorImpl */
        $operatorImpl = $this->getGraphQlService()->buildQueryOperator($this->typeName, $this->attributes);

        $element = AbstractObject::getById($value['id']);
        $valueFromOperator = $operatorImpl->getLabeledValue($element, $resolveInfo);

        return $valueFromOperator?->value;
    }
}
