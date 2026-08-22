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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\SharedType\HotspotCropType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

/**
 * Class HotspotType
 *
 * @package OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType
 */
class HotspotType extends ObjectType
{
    use ServiceTrait;

    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, $config = ['name' => 'hotspotimage'], $context = [])
    {
        $this->setGraphQLService($graphQlService);
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\HotspotType();
        $resolver->setGraphQLService($this->getGraphQlService());
        $service = $this->getGraphQlService();
        $assetType = $service->buildAssetType('asset');
        $hotspotMarkerType = $service->buildGeneralType('hotspotmarker');
        $hotspotHotspotType = $service->buildGeneralType('hotspothotspot');

        $config['fields'] = [
            'image' => [
                'type' => $assetType,
                'resolve' => $resolver->resolveImage(...),
            ],
            'crop' => [
                'type' => HotspotCropType::getInstance(),
                'resolve' => $resolver->resolveCrop(...),
            ],
            'hotspots' => [
                'type' => Type::listOf($hotspotHotspotType),
                'resolve' => $resolver->resolveHotspots(...),
            ],
            'marker' => [
                'type' => Type::listOf($hotspotMarkerType),
                'resolve' => $resolver->resolveMarker(...),
            ],
        ];
    }
}
