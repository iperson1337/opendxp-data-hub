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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\ClassificationstoreType;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\Bundle\DataHubBundle\GraphQL\FeatureDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

class Feature extends UnionType
{
    use ServiceTrait;

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = ['name' => 'csFeature'])
    {
        $this->setGraphQLService($graphQlService);

        parent::__construct($config);
    }

    /**
     * @throws Exception
     */
    public function getTypes(): array
    {
        $service = $this->getGraphQlService();
        $supportedFeatureTypeNames = $service->getSupportedCsFeatureQueryDataTypes();

        $types = [];
        foreach ($supportedFeatureTypeNames as $featureTypeName) {
            $featureType = $service->buildCsFeatureDataQueryType($featureTypeName);
            $types[] = $featureType;
        }

        return $types;
    }

    public function resolveType($element, $context, ResolveInfo $info)
    {
        if (!$element instanceof FeatureDescriptor) {
            throw new ClientSafeException('expected feature descriptor');
        }

        $type = $element->getType();

        $service = $this->getGraphQlService();
        $resolvedType = $service->buildCsFeatureDataQueryType($type);

        return $resolvedType;
    }
}
