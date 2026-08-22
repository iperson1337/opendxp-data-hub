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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\AssetType;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Asset;

class AssetTreeType extends UnionType
{
    use ServiceTrait;

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = ['name' => 'asset_tree'])
    {
        $this->setGraphQLService($graphQlService);
        parent::__construct($config);
    }

    /**
     * @throws Exception
     */
    public function getTypes(): array
    {
        $types = [];
        $types[] = $this->getGraphQlService()->buildAssetType('asset');
        $types[] = $this->getGraphQlService()->getAssetTypeDefinition('_asset_folder');

        return $types;
    }

    public function resolveType($element, $context, ResolveInfo $info)
    {
        if (!$element) {
            return null;
        }
        $asset = Asset::getById($element['id']);

        if ($asset instanceof Asset\Folder) {
            return $this->getGraphQlService()->getAssetTypeDefinition('_asset_folder');
        }

        return $this->getGraphQlService()->buildAssetType('asset');
    }
}
