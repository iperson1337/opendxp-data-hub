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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentType;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

class DocumentElementType extends UnionType
{
    use ServiceTrait;

    protected $container;

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = [])
    {
        $this->setGraphQLService($graphQlService);
        parent::__construct($config);
    }

    public function getTypes(): array
    {
        $service = $this->getGraphQlService();
        $supportedTypeNames = $service->getSupportedDocumentElementQueryDataTypes();
        $supportedTypes = [];
        foreach ($supportedTypeNames as $typeName) {
            $type = $service->buildDocumentElementDataQueryType($typeName);
            $supportedTypes[] = $type;
        }

        return $supportedTypes;
    }

    public function resolveType($element, $context, ResolveInfo $info)
    {
        $type = $element->getType();
        $service = $this->getGraphQlService();
        $supportedTypes = $service->getSupportedDocumentElementQueryDataTypes();
        if (in_array($type, $supportedTypes)) {
            $queryType = $service->buildDocumentElementDataQueryType($type);

            return $queryType;
        }

        return null;
    }
}
