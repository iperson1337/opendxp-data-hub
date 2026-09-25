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
use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\ListingEvents;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\ListingEvent;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Limits;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Helper;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\WorkspaceConditionBuilder;
use OpenDxp\Bundle\DataHubBundle\WorkspaceHelper;
use OpenDxp\Db;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function json_decode;

class AssetListing
{
    use ServiceTrait;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct(Service $graphQlService, EventDispatcherInterface $eventDispatcher)
    {
        $this->setGraphQLService($graphQlService);

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ElementDescriptor $value
     * @param array $args
     * @param array $context
     *
     * @return mixed
     */
    public function resolveEdges($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return is_callable($value['edges']) ? ($value['edges'])() : $value['edges'];
    }

    /**
     * @param ElementDescriptor $value
     * @param array $args
     * @param array $context
     *
     * @return ElementDescriptor|null
     */
    public function resolveEdge($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $element = $value['node'];

        if (null !== $element) {
            return $this->extractSingleElement($element, $args, $context, $resolveInfo);
        }

        return null;
    }

    /**
     * @param mixed $value
     * @param array $args
     * @param array $context
     *
     * @return array
     *
     * @throws Exception
     */
    public function resolveListing($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($args && isset($args['defaultLanguage'])) {
            $this->getGraphQlService()->getLocaleService()->setLocale($args['defaultLanguage']);
        }

        $db = Db::get();
        $modelFactory = $this->getGraphQlService()->getModelFactory();
        $listClass = Asset\Listing::class;

        /** @var Asset\Listing $objectList */
        $objectList = $modelFactory->build($listClass);
        $conditionParts = [];
        if (isset($args['ids'])) {
            // Раньше строка вставлялась в SQL как есть — прямая инъекция через `ids`.
            $ids = is_array($args['ids']) ? $args['ids'] : explode(',', (string) $args['ids']);
            $ids = array_values(array_filter(array_map('trim', $ids), static fn ($id) => $id !== ''));
            $conditionParts[] = $ids === []
                ? '(0 = 1)'
                : '(id IN (' . implode(', ', array_map($db->quote(...), $ids)) . '))';
        }

        if (isset($args['fullpaths'])) {
            $quotedFullpaths = array_map(
                static function ($fullpath) use ($db) {
                    $fullpath = trim($fullpath, " '");
                    $fullpath = \OpenDxp\Model\Element\Service::correctPath($fullpath);

                    return $db->quote($fullpath);
                },
                str_getcsv($args['fullpaths'], ',', "'")
            );
            $conditionParts[] = '(concat(path, filename) IN (' . implode(',', $quotedFullpaths) . '))';
        }

        // paging
        $objectList->setLimit(Limits::first($args['first'] ?? null));

        if (isset($args['after'])) {
            $objectList->setOffset($args['after']);
        }

        // sorting
        if (!empty($args['sortBy'])) {
            $objectList->setOrderKey(Limits::assertSortKeys($args['sortBy']));
            if (!empty($args['sortOrder'])) {
                $objectList->setOrder($args['sortOrder']);
            }
        }

        /** @var Configuration $configuration */
        $configuration = $context['configuration'];

        // check permissions
        $tableName = 'assets';

        if (!$configuration->skipPermisssionCheck()) {
            $workspaces = $db->fetchAllAssociative(
                'SELECT cpath, `read` FROM plugin_datahub_workspaces_asset WHERE configuration = ?',
                [$configuration->getName()]
            );

            $permissionCondition = WorkspaceConditionBuilder::forConnection($db)
                ->build($tableName, 'filename', $workspaces);

            if ($permissionCondition !== null) {
                $conditionParts[] = $permissionCondition;
            }
        }

        if (isset($args['filter'])) {
            $filter = json_decode($args['filter'], false);
            if (json_last_error() !== JSON_ERROR_NONE || (!is_array($filter) && !$filter instanceof \stdClass)) {
                throw new ClientSafeException('unable to decode filter: ' . json_last_error_msg());
            }
            $filterCondition = Helper::buildSqlCondition($tableName, $filter);
            $conditionParts[] = $filterCondition;
        }

        $condition = implode(' AND ', $conditionParts);
        $objectList->setCondition($condition);

        $event = new ListingEvent(
            $objectList,
            $args,
            $context,
            $resolveInfo
        );
        $this->eventDispatcher->dispatch($event, ListingEvents::PRE_LOAD);
        /** @var Asset\Listing $objectList */
        $objectList = $event->getListing();

        // Ленивые edges/totalCount, как у объектов: COUNT(*) выполняется только если поле запрошено.
        $connection = [];
        $connection['edges'] = static function () use ($objectList): array {
            $nodes = [];
            foreach ($objectList->load() as $element) {
                if (!WorkspaceHelper::checkPermission($element, 'read')) {
                    continue;
                }

                $nodes[] = [
                    'cursor' => 'asset-' . $element->getId(),
                    'node' => $element,
                ];
            }

            return $nodes;
        };
        $connection['totalCount'] = [$objectList, 'getTotalCount'];

        return $connection;
    }

    /**
     * @param ElementDescriptor $value
     * @param array $args
     * @param array $context
     *
     * @return mixed
     */
    public function resolveListingTotalCount($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return is_callable($value['totalCount']) ? ($value['totalCount'])() : $value['totalCount'];
    }

    /**
     * @param array $elements
     * @param array $args
     * @param array $context
     * @param ResolveInfo|null $resolveInfo
     *
     * @return array
     *
     * @throws Exception
     */
    protected function extractMultipleElements($elements, $args, $context, $resolveInfo)
    {
        $result = [];
        if ($elements) {
            foreach ($elements as $element) {
                $result[] = $this->extractSingleElement($element, $args, $context, $resolveInfo);
            }
        }

        return array_filter($result);
    }

    /**
     * @param ElementInterface $element
     * @param array $args
     * @param array $context
     * @param ResolveInfo|null $resolveInfo
     *
     * @return ElementDescriptor|null
     *
     * @throws Exception
     */
    protected function extractSingleElement($element, $args, $context, $resolveInfo)
    {
        // Check Workspace permissions
        if (!WorkspaceHelper::checkPermission($element, 'read')) {
            return null;
        }

        $data = new ElementDescriptor($element);
        $data['id'] = $element->getId();

        // Check element type
        $treeType = $this->getGraphQlService()->buildGeneralType('asset_tree');
        $elementType = $treeType->resolveType($data, $context, $resolveInfo);
        if (in_array($elementType, $treeType->getTypes(), true)) {
            $this->getGraphQLService()->getAssetFieldHelper()->extractData($data, $element, $args, $context, $resolveInfo);

            return $data;
        }

        return null;
    }
}
