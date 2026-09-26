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

namespace OpenDxp\Bundle\DataHubBundle\Controller;

use Exception;
use OpenDxp;
use OpenDxp\Bundle\DataHubBundle\ConfigEvents;
use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Bundle\DataHubBundle\Event\AdminEvents;
use OpenDxp\Bundle\DataHubBundle\Event\Config\SpecialEntitiesEvent;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\Installer;
use OpenDxp\Bundle\DataHubBundle\Model\SpecialEntitySetting;
use OpenDxp\Bundle\DataHubBundle\Service\ApiKeyServiceInterface;
use OpenDxp\Bundle\DataHubBundle\Service\ExportService;
use OpenDxp\Bundle\DataHubBundle\Service\ImportService;
use OpenDxp\Bundle\DataHubBundle\WorkspaceHelper;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Model\Exception\ConfigWriteException;
use OpenDxp\Model\User;
use OpenDxp\Tool\Admin;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/admin/opendxpdatahub/config')]
class ConfigController extends \OpenDxp\Controller\UserAwareController
{
    use JsonHelperTrait;

    public const CONFIG_NAME = 'plugin_datahub_config';

    public function __construct(
        private readonly ApiKeyServiceInterface $apiKeyService
    ) {
    }

    /**
     * @param Configuration $configuration
     */
    private function buildItem($configuration): array
    {
        $type = $configuration->getType() ?: 'graphql';
        $name = $configuration->getName();

        return [
            'id' => $name,
            'text' => htmlspecialchars((string) $name),
            'type' => 'config',
            'iconCls' => 'plugin_opendxp_datahub_icon_' . $type,
            'expandable' => false,
            'leaf' => true,
            'adapter' => $type,
            'writeable' => $configuration->isWriteable(),
            'permissions' => [
                'delete' => $configuration->isAllowed('delete'),
                'update' => $configuration->isAllowed('update'),
            ],
        ];
    }

    #[Route('/list')]
    public function listAction(Request $request): JsonResponse
    {
        // check permissions
        $this->checkPermission(self::CONFIG_NAME);

        $list = Configuration::getList();

        $event = new GenericEvent($this);
        $event->setArgument('list', $list);
        OpenDxp::getEventDispatcher()->dispatch($event, AdminEvents::CONFIGURATION_LIST);
        $list = $event->getArgument('list');

        $tree = [];

        $groups = [];
        /** @var Configuration $item */
        foreach ($list as $item) {
            if ($item->isAllowed('read')) {
                if ($item->getGroup()) {
                    if (empty($groups[$item->getGroup()])) {
                        $groups[$item->getGroup()] = [
                            'id' => 'group_' . $item->getName(),
                            'text' => htmlspecialchars($item->getGroup()),
                            'expandable' => true,
                            'leaf' => false,
                            'allowChildren' => true,
                            'iconCls' => 'opendxp_icon_folder',
                            'group' => $item->getGroup(),
                            'children' => [],
                        ];
                    }
                    $groups[$item->getGroup()]['children'][] = $this->buildItem($item);
                } else {
                    $tree[] = $this->buildItem($item);
                }
            }
        }

        $sortFunc = fn ($a, $b) => strtolower((string) $a['text']) <=> strtolower((string) $b['text']);

        //sort group children
        foreach ($groups as &$group) {
            usort($group['children'], $sortFunc);
        }

        //sort items
        $tree = array_merge($tree, $groups);

        usort($tree, $sortFunc);

        return $this->json($tree);
    }

    /**
     * @throws ConfigWriteException
     */
    #[Route('/delete', methods: ['POST'])]
    public function deleteAction(Request $request): ?JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        if ((new Configuration(null, null))->isWriteable() === false) {
            throw new ConfigWriteException();
        }

        try {
            $name = $request->request->getString('name');

            $config = Configuration::getByName($name);
            if (!$config instanceof Configuration) {
                throw new Exception('Name does not exist.');
            }
            if ($config->isWriteable() === false) {
                throw new ConfigWriteException();
            }
            if (!$config->isAllowed('delete')) {
                throw $this->createAccessDeniedHttpException();
            }

            WorkspaceHelper::deleteConfiguration($config);

            $this->apiKeyService->deleteApiKeys($name);

            $config->delete();

            return $this->json(['success' => true]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @throws ConfigWriteException
     */
    #[Route('/add', methods: ['POST'])]
    public function addAction(Request $request): ?JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        if ((new Configuration(null, null))->isWriteable() === false) {
            throw new ConfigWriteException();
        }

        try {
            $path = $request->request->getString('path');
            $name = $request->request->getString('name');
            $type = $request->request->getString('type');

            $currentUser = Admin::getCurrentUser();
            if (!$currentUser || !$currentUser->isAdmin()) {
                throw $this->createAccessDeniedHttpException();
            }

            $config = Configuration::getByName($name);

            if ($config instanceof Configuration) {
                throw new Exception('Name already exists.');
            }

            $config = new Configuration($type, $path, $name);
            $config->save();

            return $this->json(['success' => true, 'name' => $name]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    #[Route('/clone', methods: ['POST'])]
    public function cloneAction(Request $request): ?JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        try {
            $name = $request->request->getString('name');

            $config = Configuration::getByName($name);
            if ($config instanceof Configuration) {
                throw new Exception('Name already exists.');
            }

            $originalName = $request->request->getString('originalName');
            $originalConfig = Configuration::getByName($originalName);
            if (!$originalConfig) {
                throw new Exception('Configuration not found');
            }
            if ($originalConfig->isWriteable() === false) {
                throw new ConfigWriteException();
            }
            if (!$originalConfig->isAllowed('update')) {
                throw $this->createAccessDeniedHttpException();
            }
            $this->checkPermissionsHasOneOf(['plugin_datahub_admin', 'plugin_datahub_adapter_' . $originalConfig->getType()]);

            $originalConfig->setName($name);
            $originalConfig->save();

            return $this->json(['success' => true, 'name' => $name]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('/get')]
    public function getAction(Request $request, Service $graphQlService, EventDispatcherInterface $eventDispatcher): JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        $name = $request->query->getString('name');

        $configuration = Configuration::getByName($name);
        if (!$configuration) {
            throw new Exception('Datahub configuration ' . $name . ' does not exist.');
        }
        if (!$configuration->isAllowed('read')) {
            throw $this->createAccessDeniedHttpException();
        }

        $config = $configuration->getConfiguration();
        $config['schema']['queryEntities'] = array_values($config['schema']['queryEntities'] ?? []);
        $config['schema']['mutationEntities'] = array_values($config['schema']['mutationEntities'] ?? []);
        $config['schema']['specialEntities'] ??= [];

        if (!$config['schema']['specialEntities']) {
            $config['schema']['specialEntities'] = [];
        }

        $coreSettings = [
            new SpecialEntitySetting(
                'document',
                true,
                true,
                true,
                true,
                $config['schema']['specialEntities']['document']['read'] ?? false,
                $config['schema']['specialEntities']['document']['create'] ?? false,
                $config['schema']['specialEntities']['document']['update'] ?? false,
                $config['schema']['specialEntities']['document']['delete'] ?? false
            ),
            new SpecialEntitySetting(
                'document_folder',
                true,
                false,
                false,
                true,
                $config['schema']['specialEntities']['document_folder']['read'] ?? false,
                $config['schema']['specialEntities']['document_folder']['create'] ?? false,
                $config['schema']['specialEntities']['document_folder']['update'] ?? false,
                $config['schema']['specialEntities']['document_folder']['delete'] ?? false
            ),
            new SpecialEntitySetting(
                'asset',
                true,
                true,
                true,
                true,
                $config['schema']['specialEntities']['asset']['read'] ?? false,
                $config['schema']['specialEntities']['asset']['create'] ?? false,
                $config['schema']['specialEntities']['asset']['update'] ?? false,
                $config['schema']['specialEntities']['asset']['delete'] ?? false
            ),
            new SpecialEntitySetting(
                'asset_folder',
                true,
                true,
                true,
                true,
                $config['schema']['specialEntities']['asset_folder']['read'] ?? false,
                $config['schema']['specialEntities']['asset_folder']['create'] ?? false,
                $config['schema']['specialEntities']['asset_folder']['update'] ?? false,
                $config['schema']['specialEntities']['asset_folder']['delete'] ?? false
            ),
            new SpecialEntitySetting(
                'asset_listing',
                true,
                true,
                true,
                true,
                $config['schema']['specialEntities']['asset_listing']['read'] ?? false,
                $config['schema']['specialEntities']['asset_listing']['create'] ?? false,
                $config['schema']['specialEntities']['asset_listing']['update'] ?? false,
                $config['schema']['specialEntities']['asset_listing']['delete'] ?? false
            ),
            new SpecialEntitySetting(
                'object_folder',
                true,
                true,
                true,
                true,
                $config['schema']['specialEntities']['object_folder']['read'] ?? false,
                $config['schema']['specialEntities']['object_folder']['create'] ?? false,
                $config['schema']['specialEntities']['object_folder']['update'] ?? false,
                $config['schema']['specialEntities']['object_folder']['delete'] ?? false
            ),
            new SpecialEntitySetting(
                'translation',
                true,
                false,
                false,
                false,
                $config['schema']['specialEntities']['translation_listing']['read'] ?? false,
                $config['schema']['specialEntities']['translation_listing']['create'] ?? false,
                $config['schema']['specialEntities']['translation_listing']['update'] ?? false,
                $config['schema']['specialEntities']['translation_listing']['delete'] ?? false
            ),
            new SpecialEntitySetting(
                'translation_listing',
                true,
                false,
                false,
                false,
                $config['schema']['specialEntities']['translation_listing']['read'] ?? false,
                $config['schema']['specialEntities']['translation_listing']['create'] ?? false,
                $config['schema']['specialEntities']['translation_listing']['update'] ?? false,
                $config['schema']['specialEntities']['translation_listing']['delete'] ?? false
            ),
        ];

        $specialSettingsEvent = new SpecialEntitiesEvent($coreSettings, $config);
        $eventDispatcher->dispatch($specialSettingsEvent, ConfigEvents::SPECIAL_ENTITIES);

        $finalSettings = [];

        foreach ($specialSettingsEvent->getSpecialSettings() as $item) {
            $finalSettings[$item->getName()] = $item;
        }

        $config['schema']['specialEntities'] = $specialSettingsEvent->getSpecialSettings();

        //TODO we probably need this stuff only for graphql stuff
        $supportedQueryDataTypes = $graphQlService->getSupportedDataObjectQueryDataTypes();
        $supportedMutationDataTypes = $graphQlService->getSupportedDataObjectMutationDataTypes();

        // Add API keys from database to configuration for UI display
        $apiKeys = $this->apiKeyService->getApiKeys($name);
        if (!empty($apiKeys)) {
            if (!isset($config['security'])) {
                $config['security'] = [];
            }
            $config['security']['apikey'] = $apiKeys;
        }

        return new JsonResponse(
            [
                'name' => $configuration->getName(),
                'configuration' => $config,
                'userPermissions' => [
                    'update' => $configuration->isAllowed('update'),
                    'delete' => $configuration->isAllowed('delete'),
                ],
                'supportedGraphQLQueryDataTypes' => $supportedQueryDataTypes,
                'supportedGraphQLMutationDataTypes' => $supportedMutationDataTypes,
                'modificationDate' => $config['general']['modificationDate'],
            ]
        );
    }

    #[Route('/save')]
    public function saveAction(Request $request): ?JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        try {
            $data = $request->request->getString('data');
            $modificationDate = $request->request->getInt('modificationDate', 0);

            $dataDecoded = json_decode($data, true);

            $name = $dataDecoded['general']['name'];
            $config = Configuration::getByName($name);
            if ($config->isWriteable() === false) {
                throw new ConfigWriteException();
            }
            if (!$config->isAllowed('update')) {
                throw $this->createAccessDeniedHttpException();
            }
            $configuration = $config->getConfiguration();

            $savedModificationDate = 0;

            if ($configuration && isset($configuration['general']['modificationDate'])) {
                $savedModificationDate = $configuration['general']['modificationDate'];
            }

            if ($modificationDate < $savedModificationDate) {
                throw new Exception('The configuration was modified during editing, please reload the configuration and make your changes again');
            }

            // Only datahub administrators may change security settings, workspaces or SQL conditions
            if (!$this->isDatahubAdmin() && $this->restrictedSectionsChanged($name, $configuration ?: [], $dataDecoded)) {
                return $this->json(
                    ['success' => false, 'message' => 'Only Datahub administrators may change security settings, workspaces or SQL conditions'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $dataDecoded['general']['modificationDate'] = time();

            $keys = ['queryEntities', 'mutationEntities'];
            foreach ($keys as $key) {
                $transformedEntities = [];
                if ($dataDecoded['schema'][$key]) {
                    foreach ($dataDecoded['schema'][$key] as $entity) {
                        $transformedEntities[$entity['id']] = $entity;
                    }
                }
                $dataDecoded['schema'][$key] = $transformedEntities;
            }

            if ($dataDecoded['schema']['specialEntities']) {
                $transformedEntities = [];

                foreach ($dataDecoded['schema']['specialEntities'] as $entity) {
                    $transformedEntities[$entity['name']] = [
                        'read' => $entity['readAllowed'],
                        'create' => $entity['createAllowed'],
                        'update' => $entity['updateAllowed'],
                        'delete' => $entity['deleteAllowed'],
                    ];

                    $dataDecoded['schema']['specialEntities'] = $transformedEntities;
                }
            }

            // Handle API keys: save to database and remove from configuration
            if (isset($dataDecoded['security']['apikey'])) {
                $apiKeys = $this->parseApiKeys($dataDecoded['security']['apikey']);

                if (!empty($apiKeys)) {
                    $this->apiKeyService->saveApiKeys($name, $apiKeys);
                } else {
                    $this->apiKeyService->deleteApiKeys($name);
                }

                unset($dataDecoded['security']['apikey']);
            }

            $config->setConfiguration($dataDecoded);

            if ($config->isAllowed('read') && $config->isAllowed('update')) {
                $config->save();

                return $this->json(['success' => true, 'modificationDate' => $dataDecoded['general']['modificationDate']]);
            } else {
                return $this->json(['success' => false, 'permissionError' => true]);
            }
        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function isDatahubAdmin(): bool
    {
        $user = Admin::getCurrentUser();
        if (!$user) {
            return false;
        }

        return $user->isAdmin() || $user->isAllowed(Installer::DATAHUB_ADMIN_PERMISSION);
    }

    /**
     * Compares the security block, the workspaces and general.sqlObjectCondition of the stored
     * configuration with the submitted one.
     */
    private function restrictedSectionsChanged(string $name, array $existing, array $submitted): bool
    {
        $existingSecurity = $existing['security'] ?? [];
        $existingSecurity['apikey'] = $this->apiKeyService->getApiKeys($name);

        $submittedSecurity = $submitted['security'] ?? [];
        $submittedSecurity['apikey'] = $this->parseApiKeys($submittedSecurity['apikey'] ?? []);

        $pairs = [
            [$this->normalizeSecurity($existingSecurity), $this->normalizeSecurity($submittedSecurity)],
            [$this->normalizeWorkspaces($existing['workspaces'] ?? []), $this->normalizeWorkspaces($submitted['workspaces'] ?? [])],
            [
                (string) ($existing['general']['sqlObjectCondition'] ?? ''),
                (string) ($submitted['general']['sqlObjectCondition'] ?? ''),
            ],
        ];

        foreach ($pairs as [$before, $after]) {
            if ($this->normalize($before) !== $this->normalize($after)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeSecurity(array $security): array
    {
        $keys = is_array($security['apikey'] ?? null) ? $security['apikey'] : [];
        sort($keys);

        return [
            'method' => (string) ($security['method'] ?? ''),
            'skipPermissionCheck' => (bool) ($security['skipPermissionCheck'] ?? false),
            'disableIntrospection' => (bool) ($security['disableIntrospection'] ?? false),
            'apikey' => array_values($keys),
        ];
    }

    private function normalizeWorkspaces(array $workspaces): array
    {
        $result = [];
        foreach (['document', 'asset', 'object'] as $type) {
            $rows = [];
            foreach ((array) ($workspaces[$type] ?? []) as $space) {
                if (!is_array($space)) {
                    continue;
                }
                $rows[] = [
                    'cpath' => (string) ($space['cpath'] ?? ''),
                    'create' => (bool) ($space['create'] ?? false),
                    'read' => (bool) ($space['read'] ?? false),
                    'update' => (bool) ($space['update'] ?? false),
                    'delete' => (bool) ($space['delete'] ?? false),
                ];
            }
            usort($rows, static fn (array $a, array $b): int => json_encode($a) <=> json_encode($b));
            $result[$type] = $rows;
        }

        return $result;
    }

    /**
     * Recursively ksorts arrays and returns a canonical JSON representation.
     */
    private function normalize(mixed $value): string
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = is_array($v) ? json_decode($this->normalize($v), true) : $v;
            }
            if (!array_is_list($value)) {
                ksort($value);
            }
        }

        return (string) json_encode($value);
    }

    /**
     * Accepts the textarea value (newline separated) or an array and returns a clean key list.
     *
     * @return string[]
     */
    private function parseApiKeys(mixed $apiKeys): array
    {
        if (is_string($apiKeys)) {
            $apiKeys = explode("\n", $apiKeys);
        }
        if (!is_array($apiKeys)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($key): string => is_scalar($key) ? trim((string) $key) : '', $apiKeys),
            static fn (string $key): bool => $key !== ''
        ));
    }

    /**
     * @throws Exception
     */
    #[Route('/get-explorer-url')]
    public function getExplorerUrlAction(RouterInterface $routingService, Request $request): ?JsonResponse
    {
        $name = $request->query->getString('name');

        $url = $routingService->generate('admin_opendxpdatahub_config', ['clientname' => $name]);
        if ($url) {
            return $this->json(['explorerUrl' => $url]);
        } else {
            throw new Exception('unable to resolve');
        }
    }

    #[Route('/thumbnail-tree')]
    public function thumbnailTreeAction(Request $request): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $thumbnails = [];

        $list = new \OpenDxp\Model\Asset\Image\Thumbnail\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $thumbnails[] = [
                'id' => $item->getName(),
                'text' => $item->getName(),
            ];
        }

        return $this->jsonResponse($thumbnails);
    }

    #[Route('/permissions-users', methods: ['GET'])]
    public function getPermissionUsersAction(Request $request): JsonResponse
    {
        $type = $request->query->getString('type', 'user');

        $list = new User\Listing();
        if ($type === 'role') {
            $list = new User\Role\Listing();
        }

        $list->setCondition('type = ? AND id != 1', [$type]);
        $list->setOrder('ASC');
        $list->setOrderKey('name');

        $users = [];
        foreach ($list->getItems() as $user) {
            if ($user->getId() && $user->getName() != 'system') {
                $users[] = [
                    'id' => $user->getId(),
                    'text' => $user->getName(),
                    'elementType' => 'user',
                ];
            }
        }

        return $this->jsonResponse($users);
    }

    #[Route('/export', methods: ['GET'])]
    public function exportConfiguration(Request $request, ExportService $exportService): Response
    {
        $this->checkPermission(self::CONFIG_NAME);

        $name = $request->query->getString('name');
        $configuration = Configuration::getByName($name);
        if (!$configuration) {
            throw new Exception('Datahub configuration ' . $name . ' does not exist.');
        }
        if (!$configuration->isAllowed('read')) {
            throw $this->createAccessDeniedHttpException();
        }

        $json = $exportService->exportConfigurationJson($configuration);
        $filename = sprintf(
            'datahub_%s_%s_export.json',
            $configuration->getType(),
            $configuration->getName()
        );
        $response = new Response($json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    #[Route('/import', methods: ['POST'])]
    public function importConfiguration(Request $request, ImportService $importService): JsonResponse
    {
        try {
            $this->checkPermission(self::CONFIG_NAME);
            // Allow only Pimcore admins to import configurations
            $currentUser = Admin::getCurrentUser();
            if (!$currentUser || !$currentUser->isAdmin()) {
                throw $this->createAccessDeniedHttpException();
            }
            $json = file_get_contents($_FILES['Filedata']['tmp_name']);
            $configuration = $importService->importConfigurationJson($json);

            $response = $this->jsonResponse([
                'success' => true,
                'type' => $configuration->getType(),
                'name' => $configuration->getName(),
            ]);
            // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
            // Ext.form.Action.Submit and mark the submission as failed
            $response->headers->set('Content-Type', 'text/html');

            return $response;
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
