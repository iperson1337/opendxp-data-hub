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

class VideoType extends ObjectType
{
    use ServiceTrait;

    /**
     * @var VideoTypeDataType
     */
    protected $videoDataType;

    public function __construct(Service $graphQlService, VideoTypeDataType $videoDataType)
    {
        $this->setGraphQLService($graphQlService);
        $this->videoDataType = $videoDataType;
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\Video();
        $resolver->setGraphQLService($this->getGraphQlService());
        $service = $this->getGraphQlService();
        $assetType = $service->buildAssetType('asset');

        $config['fields'] =
            [
                'type' => [
                    'type' => Type::string(),
                    'resolve' => $resolver->resolveType(...),
                ],
                'data' => [
                    'type' => $this->videoDataType,
                    'resolve' => $resolver->resolveData(...),
                ],
                'poster' => [
                    'type' => $assetType,
                    'resolve' => $resolver->resolvePoster(...),
                ],
                'title' => [
                    'type' => Type::string(),
                    'resolve' => $resolver->resolveTitle(...),
                ],
                'description' => [
                    'type' => Type::string(),
                    'resolve' => $resolver->resolveDescription(...),
                ],

            ];
    }
}
