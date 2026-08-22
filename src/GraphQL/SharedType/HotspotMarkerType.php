<?php
declare(strict_types=1);

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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\SharedType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

/**
 * Class HotspotMarkerType
 *
 * @package OpenDxp\Bundle\DataHubBundle\GraphQL\SharedType
 */
class HotspotMarkerType extends ObjectType
{
    use ServiceTrait;

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = [])
    {
        $this->graphQlService = $graphQlService;
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $service = $this->getGraphQlService();
        $propertyType = $service->buildGeneralType('hotspot_metadata');
        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\HotspotType();

        $config['fields'] = [
            'top' => Type::float(),
            'left' => Type::float(),
            'data' => [
                'type' => Type::listOf($propertyType),
                'args' => [
                    'keys' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => 'List of metadata key names to include '
                            . '(if omitted, all entries are returned).',
                    ],
                ],
                'resolve' => $resolver->resolveMetadata(...),
            ],
            'name' => Type::string(),
        ];
    }
}
