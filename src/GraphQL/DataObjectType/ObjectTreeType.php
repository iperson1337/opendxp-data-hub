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

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ClassTypeDefinitions;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\OpenDxpDataHubBundle;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\DataObject;

class ObjectTreeType extends UnionType
{
    use ServiceTrait;

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = ['name' => 'object_tree'])
    {
        $this->setGraphQLService($graphQlService);
        parent::__construct($config);
    }

    /**
     * @throws Exception
     */
    public function getTypes(): array
    {
        $context = RuntimeCache::get(OpenDxpDataHubBundle::RUNTIME_CONTEXT_KEY);
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];

        $types = array_values(ClassTypeDefinitions::getAll(true));
        if ($configuration->getSpecialEntities()['object_folder']['read'] ?? false) {
            $types[] = $this->getGraphQlService()->getDataObjectTypeDefinition('_object_folder');
        }

        return $types;
    }

    public function resolveType($element, $context, ResolveInfo $info)
    {
        if (!$element) {
            return null;
        }
        $object = DataObject\AbstractObject::getById($element['id']);

        if ($object instanceof DataObject\Folder) {
            return $this->getGraphQlService()->getDataObjectTypeDefinition('_object_folder');
        }

        return ClassTypeDefinitions::get($object->getClass());
    }
}
