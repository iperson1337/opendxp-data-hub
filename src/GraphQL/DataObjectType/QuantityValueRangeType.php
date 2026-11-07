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
use OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\QuantityValue as QuantityValueResolver;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\Data\QuantityValueRange;

/**
 * Value of the core `quantityValueRange` data type: {@see QuantityValueRange::toArray()} plus the
 * unit object and `toString`, the same way {@see QuantityValueType} serves a quantity value.
 */
class QuantityValueRangeType extends ObjectType
{
    use ServiceTrait;

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = ['name' => 'QuantityValueRange'])
    {
        $this->setGraphQLService($graphQlService);
        $resolver = new QuantityValueResolver();
        $resolver->setGraphQLService($graphQlService);

        $config['fields'] = [
            'minimum' => [
                'type' => Type::float(),
                'resolve' => static fn ($value) => $value instanceof QuantityValueRange ? $value->getMinimum() : null,
            ],
            'maximum' => [
                'type' => Type::float(),
                'resolve' => static fn ($value) => $value instanceof QuantityValueRange ? $value->getMaximum() : null,
            ],
            'unit' => [
                'type' => QuantityValueUnitType::getInstance(),
                'resolve' => $resolver->resolveUnit(...),
            ],
            'toString' => [
                'type' => Type::string(),
                'resolve' => $resolver->resolveToString(...),
                'args' => ['language' => ['type' => Type::string()]],
            ],
        ];
        parent::__construct($config);
    }
}
