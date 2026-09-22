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
use OpenDxp;
use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\ListingEvents;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\ListingEvent;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Helper;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ElementIdentificationTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\PermissionInfoTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\WorkspaceConditionBuilder;
use OpenDxp\Bundle\DataHubBundle\WorkspaceHelper;
use OpenDxp\Db;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Listing;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Model\Translation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QueryType
{
    use ServiceTrait;
    use PermissionInfoTrait;
    use ElementIdentificationTrait;

    /**
     * @param ClassDefinition|null $class
     * @param object $configuration
     * @param bool $omitPermissionCheck
     */
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        protected $class = null,
        protected $configuration = null,
        $omitPermissionCheck = false
    ) {
        $this->omitPermissionCheck = $omitPermissionCheck;
    }

    /**
     * @param ElementDescriptor|null $value
     * @param array $args
     * @param array $context
     * @param string|null $elementType
     *
     * @return array|null
     *
     * @throws ClientSafeException
     */
    public function resolveFolderGetter($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null, $elementType = null)
    {
        if ($args && isset($args['defaultLanguage'])) {
            $this->getGraphQlService()->getLocaleService()->setLocale($args['defaultLanguage']);
        }

        $element = $this->getElementByTypeAndIdOrPath($args, $elementType);

        if (!$element) {
            return null;
        }

        if (!$this->omitPermissionCheck && !WorkspaceHelper::checkPermission($element, 'read')) {
            return null;
        }

        $data = new ElementDescriptor();
        $getter = 'get' . ucfirst((string) $elementType) . 'FieldHelper';
        $fieldHelper = $this->getGraphQlService()->$getter();
        $fieldHelper->extractData($data, $element, $args, $context, $resolveInfo);
        $data = $data->getArrayCopy();

        return $data;
    }

    /**
     * @param ElementDescriptor|null $value
     * @param array $args
     * @param array $context
     *
     * @return array|null
     *
     * @throws ClientSafeException
     */
    public function resolveAssetFolderGetter($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveFolderGetter($value, $args, $context, $resolveInfo, 'asset');
    }

    /**
     * @param ElementDescriptor|null $value
     * @param array $args
     * @param array $context
     *
     * @return array|null
     *
     * @throws ClientSafeException
     */
    public function resolveDocumentFolderGetter($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveFolderGetter($value, $args, $context, $resolveInfo, 'document');
    }

    /**
     * @param ElementDescriptor|null $value
     * @param array $args
     * @param array $context
     *
     * @return array|null
     *
     * @throws ClientSafeException
     */
    public function resolveObjectFolderGetter($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $this->resolveFolderGetter($value, $args, $context, $resolveInfo, 'object');
    }

    /**
     * @param ElementDescriptor|null $value
     * @param array $args
     * @param array $context
     *
     * @return array|null
     *
     * @throws ClientSafeException
     *
     * @deprecated args['path'] will no longer be supported by Release 1.0. Use args['fullpath'] instead.
     */
    public function resolveDocumentGetter($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($args && isset($args['defaultLanguage'])) {
            $this->getGraphQlService()->getLocaleService()->setLocale($args['defaultLanguage']);
        }

        // TODO: remove this workaround for Release 1.0
        if ($args['path'] ?? false) {
            Logger::warn("Argument 'path' deprecated: will no longer be supported by Release 1.0. Use 'fullpath' instead.");
            $args['fullpath'] = $args['path'];
        }

        $documentElement = $this->getElementByTypeAndIdOrPath($args, 'document');

        if (!$documentElement) {
            return null;
        }

        if (!$this->omitPermissionCheck) {
            if (!WorkspaceHelper::checkPermission($documentElement, 'read')) {
                return null;
            }
        }

        $data = new ElementDescriptor($documentElement);
        $this->getGraphQlService()->extractData($data, $documentElement, $args, $context, $resolveInfo);
        $data = $data->getArrayCopy();

        return $data;
    }

    /**
     * @param ElementDescriptor|null $value
     * @param array $args
     * @param array $context
     *
     * @return ElementDescriptor|null
     *
     * @throws ClientSafeException
     */
    public function resolveAssetGetter($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        if ($args && isset($args['defaultLanguage'])) {
            $this->getGraphQlService()->getLocaleService()->setLocale($args['defaultLanguage']);
        }

        $assetElement = $this->getElementByTypeAndIdOrPath($args, 'asset');
        if (!$assetElement) {
            return null;
        }

        if (!$this->omitPermissionCheck) {
            if (!WorkspaceHelper::checkPermission($assetElement, 'read')) {
                return null;
            }
        }

        $data = new ElementDescriptor($assetElement);
        $this->getGraphQlService()->extractData($data, $assetElement, $args, $context, $resolveInfo);

        return $data;
    }

    /**
     * @throws Exception
     */
    public function resolveTranslationGetter(mixed $value = null, array $args = [], array $context = [], ?ResolveInfo $resolveInfo = null): array
    {
        if (empty($args['key'])) {
            throw new Exception('Argument key is mandatory');
        }

        $domain = 'messages';
        if (!empty($args['domain'])) {
            $domain = $args['domain'];
        }

        $languages = [];
        if (!empty($args['languages'])) {
            $languages = str_replace(' ', '', $args['languages']);
            $languages = explode(',', $languages);
        }

        $translation = Translation::getByKey($args['key'], $domain, false, false, $languages);
        if (!$translation) {
            return [];
        }

        $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();

        return $fieldHelper->extractData($data, $translation, $args, $context, $resolveInfo);
    }

    /**
     * @param ElementDescriptor|null $value
     * @param array $args
     * @param array $context
     *
     * @return ElementDescriptor
     *
     * @throws ClientSafeException
     */
    public function resolveObjectGetter($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $isIdSet = $args['id'] ?? false;
        $isFullpathSet = $args['fullpath'] ?? false;

        $entityConfig = null;
        $identifierFields = [];
        if ($this->configuration instanceof Configuration) {
            $entityConfig = $this->configuration->getQueryEntityConfig($this->class->getName());
        } elseif (isset($context['configuration']) && $context['configuration'] instanceof Configuration) {
            $entityConfig = $context['configuration']->getQueryEntityConfig($this->class->getName());
        }
        if (is_array($entityConfig) && isset($entityConfig['identifierFields']) && is_array($entityConfig['identifierFields'])) {
            $identifierFields = $entityConfig['identifierFields'];
        }
        // Universal UUID lookup support: allow querying by uuid even if identifierFields is not configured.
        if (!in_array('uuid', $identifierFields, true)
            && isset($args['uuid'])
            && method_exists($this->class, 'getFieldDefinition')
            && $this->class->getFieldDefinition('uuid')
        ) {
            $identifierFields[] = 'uuid';
        }
        $additionalProvided = [];
        foreach ($identifierFields as $idf) {
            if (in_array($idf, ['id', 'fullpath'], true)) {
                continue;
            }
            if (!empty($args[$idf])) {
                $additionalProvided[] = $idf;
            }
        }

        if (!$isIdSet && !$isFullpathSet && empty($additionalProvided)) {
            throw new ClientSafeException('object id or fullpath expected');
        }

        if ($args['defaultLanguage'] ?? false) {
            $this->getGraphQlService()->getLocaleService()->setLocale($args['defaultLanguage']);
        }

        if (!$isIdSet && !$isFullpathSet && !empty($additionalProvided)) {
            $classFqn = 'OpenDxp\\Model\\DataObject\\' . ucfirst($this->class->getName());
            $object = null;
            foreach ($additionalProvided as $field) {
                $method = 'getBy' . ucfirst($field);
                if (is_callable([$classFqn, $method])) {
                    $res = $classFqn::$method($args[$field], 1);
                    if (is_array($res) && !empty($res)) {
                        $object = $res[0];
                        break;
                    }
                }
            }

            if ($object) {
                return $this->extractObjectData($object, $args, $context, $resolveInfo);
            }
        }

        $db = Db::get();
        $modelFactory = $this->getGraphQlService()->getModelFactory();
        $listClass = 'OpenDxp\\Model\\DataObject\\' . ucfirst((string) $this->class->getName()) . '\\Listing';
        /** @var Listing $objectList */
        $objectList = $modelFactory->build($listClass);
        $conditionParts = [];

        if ($isIdSet) {
            $conditionParts[] = '(id =' . $args['id'] . ')';
        }

        if ($isFullpathSet) {
            $fullpath = Service::correctPath($args['fullpath']);
            $conditionParts[] = '(CONCAT(`path`,`key`) =' . $db->quote($fullpath) . ')';
        }

        foreach ($additionalProvided as $field) {
            if (method_exists($this->class, 'getFieldDefinition') && $this->class->getFieldDefinition($field)) {
                $conditionParts[] = sprintf('(%s = %s)', $db->quoteIdentifier($field), $db->quote($args[$field]));
            }
        }

        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $sqlGetCondition = $configuration->getSqlObjectCondition();
        $dataHubConfig = OpenDxp::getContainer()?->getParameter('opendxp_data_hub');
        if ($dataHubConfig && isset($dataHubConfig['graphql']['allow_sqlObjectCondition']) &&
            !$dataHubConfig['graphql']['allow_sqlObjectCondition']) {
            $sqlGetCondition = null;
        }

        if ($sqlGetCondition) {
            $conditionParts[] = '(' . $sqlGetCondition . ')';
        }

        $condition = implode(' AND ', $conditionParts);
        if ($condition === '' && !empty($additionalProvided)) {
            $errorMessage = $this->createArgumentErrorMessage($isFullpathSet, $isIdSet, $args, true);

            throw new ClientSafeException($errorMessage);
        }
        $objectList->setCondition($condition);

        $objectList->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT, AbstractObject::OBJECT_TYPE_FOLDER, AbstractObject::OBJECT_TYPE_VARIANT]);
        $objectList->setLimit(1);
        $objectList->setUnpublished(true);
        $objectList = $objectList->load();
        if (!$objectList) {
            if (!empty($additionalProvided)) {
                $errorMessage = $this->createArgumentErrorMessage($isFullpathSet, $isIdSet, $args, true);

                throw new ClientSafeException($errorMessage);
            }
            $errorMessage = $this->createArgumentErrorMessage($isFullpathSet, $isIdSet, $args);

            throw new ClientSafeException($errorMessage);
        }
        $object = $objectList[0];

        // Check if object is null to prevent getId() errors
        if (!$object) {
            $errorMessage = $this->createArgumentErrorMessage($isFullpathSet, $isIdSet, $args);
            throw new ClientSafeException($errorMessage);
        }

        return $this->extractObjectData($object, $args, $context, $resolveInfo);
    }

    /**
     * @param array|null $value
     * @param array $args
     * @param array $context
     *
     * @return array
     */
    public function resolveEdge($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $object = $value['node'];
        $nodeData = [];

        // Skip null objects to prevent errors
        if (!$object) {
            return $nodeData;
        }

        if ($this->omitPermissionCheck || WorkspaceHelper::checkPermission($object, 'read')) {
            $data = new ElementDescriptor();
            $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();
            $nodeData = $fieldHelper->extractData($data, $object, $args, $context, $resolveInfo);
        }

        return $nodeData;
    }

    /**
     * @param array|null $value
     * @param array $args
     * @param array $context
     *
     * @return array
     */
    public function resolveEdges($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $objectList = $value['edges']();
        $nodes = [];

        foreach ($objectList as $object) {
            // Skip null objects to prevent getId() errors
            if (!$object) {
                continue;
            }

            if (!$this->omitPermissionCheck && !WorkspaceHelper::checkPermission($object, 'read')) {
                continue;
            }

            $nodes[] = [
                'cursor' => 'object-' . $object->getId(),
                'node' => $object,
            ];
        }

        return $nodes;
    }

    /**
     * @param ElementDescriptor|null $value
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

        $modelFactory = $this->getGraphQlService()->getModelFactory();
        $listClass = 'OpenDxp\\Model\\DataObject\\' . ucfirst((string) $this->class->getName()) . '\\Listing';
        /** @var Listing\Concrete $objectList */
        $objectList = $modelFactory->build($listClass);
        $tableName = $objectList->getDao()->getTableName();

        $conditionParts = [];
        $db = Db::get();
        if (isset($args['ids'])) {
            // Explode it and then quote it
            if (!is_array($args['ids'])) {
                $args['ids'] = explode(',', (string) $args['ids']);
            }
            $ids = implode(', ', array_map($db->quote(...), $args['ids']));
            $conditionParts[] = '(id IN (' . $ids . '))';
        }
        if (isset($args['fullpaths'])) {
            $quotedFullpaths = array_map(
                static function ($fullpath) use ($db) {
                    $fullpath = trim($fullpath, " '");
                    $fullpath = Service::correctPath($fullpath);

                    return $db->quote($fullpath);
                },
                str_getcsv($args['fullpaths'], ',', "'")
            );
            $conditionParts[] = '(CONCAT(`path`,`key`) IN (' . implode(',', $quotedFullpaths) . '))';
        }

        if (isset($args['tags'])) {
            if (!is_array($args['tags'])) {
                $args['tags'] = explode(',', (string) $args['tags']);
            }
            $tags = strtolower(implode(', ', array_map(static function ($tag) use ($db) {
                $tag = trim($tag);

                return $db->quote($tag);
            }, $args['tags'])));

            $conditionParts[] = "id IN (
                            SELECT cId FROM tags_assignment INNER JOIN tags ON tags.id = tags_assignment.tagid
                            WHERE
                                ctype = 'object' AND LOWER(tags.name) IN (" . $tags . '))';
        }

        // paging
        if (isset($args['first'])) {
            $objectList->setLimit($args['first']);
        }

        if (isset($args['after'])) {
            $objectList->setOffset($args['after']);
        }

        // sorting
        if (!empty($args['sortBy'])) {
            $objectList->setOrderKey($args['sortBy']);
            if (!empty($args['sortOrder'])) {
                $objectList->setOrder($args['sortOrder']);
            }
        }

        // Include unpublished
        if (isset($args['published']) && $args['published'] === false) {
            $objectList->setUnpublished(true);
        }

        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $sqlListCondition = $configuration->getSqlObjectCondition();

        if ($sqlListCondition) {
            $conditionParts[] = '(' . $sqlListCondition . ')';
        }

        if (!$configuration->skipPermisssionCheck()) {
            // check permissions
            $workspaces = $db->fetchAllAssociative(
                'SELECT cpath, `read` FROM plugin_datahub_workspaces_object WHERE configuration = ?',
                [$configuration->getName()]
            );

            $permissionCondition = WorkspaceConditionBuilder::forConnection($db)
                ->build($tableName, 'key', $workspaces);

            if ($permissionCondition !== null) {
                $conditionParts[] = $permissionCondition;
            }
        }

        if (isset($args['filter'])) {
            $filter = json_decode($args['filter'], false);
            if (!$filter) {
                $jsonError = json_last_error_msg();
                throw new ClientSafeException(
                    'unable to decode filter. Filter must be valid JSON (use double-quoted keys, e.g. {"barcode": {"$in": ["123"]}}). JSON error: ' . $jsonError
                );
            }

            $className = $this->class->getName();
            $columns = $this->configuration->configuration['schema']['queryEntities'][$className]['columnConfig']['columns'];

            Helper::addJoins($objectList, $filter, $columns, $mappingTable);

            $filterCondition = Helper::buildSqlCondition($tableName, $filter, null, null, $mappingTable);
            $conditionParts[] = $filterCondition;
        }

        $condition = implode(' AND ', $conditionParts);
        $objectList->setCondition($condition);

        $objectList->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT, AbstractObject::OBJECT_TYPE_FOLDER, AbstractObject::OBJECT_TYPE_VARIANT]);

        $event = new ListingEvent(
            $objectList,
            $args,
            $context,
            $resolveInfo
        );
        $this->eventDispatcher->dispatch($event, ListingEvents::PRE_LOAD);
        $objectList = $event->getListing();

        $connection = [];
        $connection['edges'] = [$objectList, 'load'];
        $connection['totalCount'] = [$objectList, 'getTotalCount'];

        return $connection;
    }

    /**
     * @param ElementDescriptor|null $value
     * @param array $args
     * @param array $context
     *
     * @return mixed
     */
    public function resolveListingTotalCount($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        return $value['totalCount']();
    }

    /**
     * @param mixed $object
     * @param array $args
     * @param array $context
     * @param ResolveInfo|null $resolveInfo
     *
     * @return ElementDescriptor
     * @throws ClientSafeException
     */
    private function extractObjectData($object, $args, $context, $resolveInfo)
    {
        if (!$this->omitPermissionCheck) {
            if (!WorkspaceHelper::checkPermission($object, 'read')) {
                throw new ClientSafeException('permission denied. check your workspace settings');
            }
        }

        $data = new ElementDescriptor($object);
        $data['id'] = $object->getId();
        $this->getGraphQlService()->extractData($data, $object, $args, $context, $resolveInfo);

        return $data;
    }

    /**
     * @param bool $isFullpathSet
     * @param bool $isIdSet
     * @param array $args
     *
     * @return string
     */
    private function createArgumentErrorMessage($isFullpathSet, $isIdSet, $args, bool $hasAdditionalProvided = false)
    {
        if ($hasAdditionalProvided) {
            return 'object not found for provided identifiers';
        }
        if ($isIdSet && $isFullpathSet) {
            return 'either id or fullpath expected but not both';
        }
        if ($isIdSet) {
            return "object with id:'" . $args['id'] . "' not found";
        }
        if ($isFullpathSet) {
            return "object with fullpath:'" . $args['fullpath'] . "' not found";
        }

        return 'either id or fullpath expected';
    }
}
