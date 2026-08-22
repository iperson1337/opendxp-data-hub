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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\Query;

use Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\QueryTypeEvent;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\QueryEvents;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ClassTypeDefinitions;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\AssetListing;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\QueryType as QueryTypeResolver;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\TranslationListing;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\PermissionInfoTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\Factory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QueryType extends ObjectType
{
    use ServiceTrait;
    use PermissionInfoTrait;

    /**
     * @var LocaleServiceInterface
     */
    protected $localeService;

    /**
     * @var Factory
     */
    protected $modelFactory;

    /**
     * @param array $config
     * @param array $context
     *
     * @throws Exception
     */
    public function __construct(
        Service $graphQlService,
        LocaleServiceInterface $localeService,
        Factory $modelFactory,
        private EventDispatcherInterface $eventDispatcher,
        $config = [],
        $context = []
    ) {
        if (!isset($config['name'])) {
            $config['name'] = 'Query';
        }
        $this->setGraphQLService($graphQlService);
        $this->localeService = $localeService;
        $this->modelFactory = $modelFactory;

        $this->build($config, $context);
        parent::__construct($config);
    }

    /**
     * @param array $config
     * @param array $context
     */
    public function buildFolderQueries($type, &$config = [], $context = [])
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();

        if (isset($entities[$type . '_folder']['read']) && $entities[$type . '_folder']['read']) {
            $resolver = $this->getResolver();

            if ($type === 'asset') {
                $graphQlType = $this->getGraphQlService()->getAssetTypeDefinition('_' . $type . '_folder');
            } elseif ($type === 'document') {
                $graphQlType = $this->getGraphQlService()->getDocumentTypeDefinition('_' . $type . '_folder');
            } else {
                $graphQlType = $this->getGraphQlService()->getDataObjectTypeDefinition('_' . $type . '_folder');
            }

            // GETTER DEFINITION
            $defGet = [
                'name' => 'get' . ucfirst((string) $type) . 'Folder',
                'args' => [
                    'id' => ['type' => Type::int()],
                    'fullpath' => ['type' => Type::string()],
                    'defaultLanguage' => ['type' => Type::string()],
                ],
                'type' => $graphQlType,
                'resolve' => [$resolver, 'resolve' . ucfirst((string) $type) . 'FolderGetter'],
            ];

            $config['fields']['get' . ucfirst((string) $type) . 'Folder'] = $defGet;
        }
    }

    /**
     * @param array $config
     * @param array $context
     */
    public function buildAssetQueries(&$config = [], $context = [])
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();
        $service = $this->getGraphQlService();
        $assetType = $service->buildAssetType('asset');

        if ($entities['asset']['read'] ?? false) {
            $resolver = $this->getResolver();

            // GETTER DEFINITION
            $defGet = [
                'name' => 'getAsset',
                'args' => [
                    'id' => ['type' => Type::int()],
                    'fullpath' => ['type' => Type::string()],
                    'defaultLanguage' => ['type' => Type::string()],
                ],
                'type' => $assetType,
                'resolve' => $resolver->resolveAssetGetter(...),
            ];

            $config['fields']['getAsset'] = $defGet;
        }
    }

    /**
     * @param array $config
     * @param array $context
     */
    public function buildDocumentQueries(&$config = [], $context = [])
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();

        if (isset($entities['document']['read']) && $entities['document']['read']) {
            $resolver = $this->getResolver();

            // GETTER DEFINITION
            $defGet = [
                'name' => 'getDocument',
                'args' => [
                    'id' => ['type' => Type::int()],
                    'path' => ['type' => Type::string(), 'description' => "Get document by 'path' is deprecated as it is wrongly named. The 'path' argument will be replaced by 'fullpath' for Release 1.0."],
                    'fullpath' => ['type' => Type::string()],
                    'defaultLanguage' => ['type' => Type::string()],
                ],
                'type' => $this->getGraphQlService()->getDocumentTypeDefinition('document'),
                'resolve' => $resolver->resolveDocumentGetter(...),
            ];

            $config['fields']['getDocument'] = $defGet;
        }
    }

    /**
     * @param ClassDefinition|null $class
     * @param mixed $configuration
     *
     * @return QueryTypeResolver
     */
    protected function getResolver($class = null, $configuration = null)
    {
        $resolver = new QueryTypeResolver($this->eventDispatcher, $class, $configuration, $this->omitPermissionCheck);
        $resolver->setGraphQlService($this->getGraphQlService());

        return $resolver;
    }

    /**
     * @param array $config
     * @param array $context
     *
     * @throws Exception
     */
    public function buildDataObjectQueries(&$config = [], $context = []): void
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getQueryEntities();

        foreach ($entities as $entity) {
            $class = ClassDefinition::getByName($entity);
            if (!$class) {
                Logger::error('class ' . $entity . ' not found');

                continue;
            }

            $resolver = $this->getResolver($class, $configuration);
            $ucFirstClassName = ucfirst((string) $class->getName());

            // GETTER DEFINITION
            $defGet = [
                'name' => 'get' . $ucFirstClassName,
                'args' => [
                    'id' => ['type' => Type::int()],
                    'fullpath' => ['type' => Type::string()],
                    'defaultLanguage' => ['type' => Type::string()],
                ],
                'type' => ClassTypeDefinitions::get($class),
                'resolve' => $resolver->resolveObjectGetter(...),
            ];

            if (method_exists($class, 'getFieldDefinition') && $class->getFieldDefinition('uuid')) {
                $defGetByUuid = [
                    'name' => 'get' . $ucFirstClassName . 'ByUuid',
                    'args' => [
                        'uuid' => ['type' => Type::nonNull(Type::string())],
                        'defaultLanguage' => ['type' => Type::string()],
                    ],
                    'type' => ClassTypeDefinitions::get($class),
                    'resolve' => [$resolver, 'resolveObjectGetter'],
                ];
                $config['fields']['get' . $ucFirstClassName . 'ByUuid'] = $defGetByUuid;
            }

            // add additional identifier fields as arguments
            $entityConfig = $configuration->getQueryEntityConfig($class->getName());
            $identifierFields = [];
            if (is_array($entityConfig) && isset($entityConfig['identifierFields']) && is_array($entityConfig['identifierFields'])) {
                $identifierFields = $entityConfig['identifierFields'];
            }
            foreach ($identifierFields as $identifierField) {
                if (in_array($identifierField, ['id', 'fullpath', 'defaultLanguage'], true)) {
                    continue;
                }
                if (!isset($defGet['args'][$identifierField])) {
                    $graphQlType = Type::string();

                    // determine a field type to map to GraphQL type
                    if (method_exists($class, 'getFieldDefinition')) {
                        $fieldDef = $class->getFieldDefinition($identifierField);
                        if ($fieldDef && method_exists($fieldDef, 'getFieldtype')) {
                            $fieldType = $fieldDef->getFieldtype();
                            switch ($fieldType) {
                                case 'numeric':
                                    $graphQlType = Type::float();
                                    break;
                                case 'checkbox':
                                case 'boolean':
                                case 'booleanSelect':
                                    $graphQlType = Type::boolean();
                                    break;
                                default:
                                    $graphQlType = Type::string();
                                    break;
                            }
                        }
                    }

                    $defGet['args'][$identifierField] = ['type' => $graphQlType];
                }
            }

            // LISTING DEFINITION
            $edgeType = new ObjectType(
                [
                    'name' => $ucFirstClassName . 'Edge',
                    'fields' => [
                        'cursor' => Type::string(),
                        'node' => [
                            'type' => ClassTypeDefinitions::get($class),
                            'resolve' => $resolver->resolveEdge(...),
                        ],
                    ],
                ]
            );

            $listingType = new ObjectType(
                [
                    'name' => $ucFirstClassName . 'Connection',
                    'fields' => [

                        'edges' => [
                            'type' => Type::listOf($edgeType),
                            'resolve' => $resolver->resolveEdges(...),
                        ],
                        'totalCount' => [
                            'description' => 'The total count of all queryable objects for this schema listing',
                            'resolve' => $resolver->resolveListingTotalCount(...),
                            'type' => Type::int(),
                        ],
                    ],
                ]
            );

            $defListing = [
                'name' => 'get' . $ucFirstClassName . 'Listing',
                'args' => [
                    'ids' => ['type' => Type::string()],
                    'fullpaths' => [
                        'type' => Type::string(),
                        'description' => 'Comma separated list of fullpath',
                    ],
                    'tags' => ['type' => Type::string(),  'description' => 'Comma separated list of tag names'],
                    'defaultLanguage' => ['type' => Type::string()],
                    'first' => ['type' => Type::int()],
                    'after' => ['type' => Type::int()],
                    'sortBy' => ['type' => Type::listOf(Type::string())],
                    'sortOrder' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => 'Sort by ASC or DESC, use the same position as the sortBy argument for each column to sort by',
                    ],
                    'filter' => ['type' => Type::string()],
                    'published' => ['type' => Type::boolean()],
                ],
                'type' => $listingType,
                'resolve' => $resolver->resolveListing(...),
            ];

            if (!isset($config['fields'])) {
                $config['fields'] = [];
            }

            $config['fields']['get' . $ucFirstClassName . 'Listing'] = $defListing;
            $config['fields']['get' . $ucFirstClassName] = $defGet;
        }
    }

    /**
     * @param array $config
     * @param array $context
     *
     * @throws Exception
     */
    public function buildAssetListingQueries(&$config = [], $context = []): void
    {
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();

        if (!isset($entities['asset_listing']['read']) || !$entities['asset_listing']['read']) {
            return;
        }

        $listResolver = new AssetListing($this->getGraphQlService(), $this->eventDispatcher);
        $assetTree = $this->getGraphQlService()->buildGeneralType('asset_tree');

        $edgeType = new ObjectType(
            [
                'name' => 'AssetEdge',
                'fields' => [
                    'cursor' => Type::string(),
                    'node' => [
                        'type' => $assetTree,
                        'resolve' => $listResolver->resolveEdge(...),
                    ],
                ],
            ]
        );

        $listingType = new ObjectType(
            [
                'name' => 'AssetConnection',
                'fields' => [
                    'edges' => [
                        'type' => Type::listOf($edgeType),
                        'resolve' => $listResolver->resolveEdges(...),
                    ],
                    'totalCount' => [
                        'description' => 'The total count of all queryable assets for this schema listing',
                        'resolve' => $listResolver->resolveListingTotalCount(...),
                        'type' => Type::int(),
                    ],
                ],
            ]
        );

        $defListing = [
            'name' => 'getAssetListing',
            'args' => [
                'ids' => ['type' => Type::string()],
                'fullpaths' => [
                    'type' => Type::string(),
                    'description' => 'Comma separated list of fullpath',
                ],
                'defaultLanguage' => ['type' => Type::string()],
                'first' => ['type' => Type::int()],
                'after' => ['type' => Type::int()],
                'sortBy' => ['type' => Type::listOf(Type::string())],
                'sortOrder' => [
                    'type' => Type::listOf(Type::string()),
                    'description' => 'Sort by ASC or DESC, use the same position as the sortBy argument for each column to sort by',
                ],
                'filter' => ['type' => Type::string()],
                'published' => ['type' => Type::boolean()],
            ],
            'type' => $listingType,
            'resolve' => $listResolver->resolveListing(...),
        ];

        $config['fields']['getAssetListing'] = $defListing;
    }

    public function buildTranslationQueries(array &$config = [], array $context = [])
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();
        $service = $this->getGraphQlService();
        $translationType = $service->buildTranslationType('translation');

        if ($entities['translation']['read'] ?? false) {
            $resolver = $this->getResolver();

            // GETTER DEFINITION
            $defGet = [
                'name' => 'getTranslation',
                'args' => [
                    'key' => ['type' => Type::string()],
                    'languages' => [
                        'type' => Type::string(),
                        'description' => 'e.g.: "en,de,fr ..."',
                    ],
                    'domain' => [
                        'type' => Type::string(),
                        'description' => 'default value: messages',
                    ],
                ],
                'type' => $translationType,
                'resolve' => $resolver->resolveTranslationGetter(...),
            ];

            $config['fields']['getTranslation'] = $defGet;
        }
    }

    public function buildTranslationListingQueries(array &$config, array $context)
    {
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();

        if (!isset($entities['translation_listing']['read']) || !$entities['translation_listing']['read']) {
            return;
        }

        $listResolver = new TranslationListing($this->getGraphQlService(), $this->eventDispatcher);
        $translation = $this->getGraphQlService()->buildTranslationType('translation');

        $edgeType = new ObjectType(
            [
                'name' => 'TranslationEdge',
                'fields' => [
                    'cursor' => Type::string(),
                    'node' => [
                        'type' => $translation,
                        'resolve' => $listResolver->resolveEdge(...),
                    ],
                ],
            ]
        );

        $listingType = new ObjectType(
            [
                'name' => 'TranslationConnection',
                'fields' => [
                    'edges' => [
                        'type' => Type::listOf($edgeType),
                        'resolve' => $listResolver->resolveEdges(...),
                    ],
                    'totalCount' => [
                        'description' => 'The total count of all queryable translations for this schema listing',
                        'resolve' => $listResolver->resolveListingTotalCount(...),
                        'type' => Type::int(),
                    ],
                ],
            ]
        );

        $defListing = [
            'name' => 'getTranslationListing',
            'args' => [
                'keys' => [
                    'type' => Type::string(),
                    'description' => 'e.g.: "key-1,key 2,key_3"',
                ],
                'languages' => [
                    'type' => Type::string(),
                    'description' => 'e.g.: "en,de,fr ..."',
                ],
                'first' => ['type' => Type::int()],
                'after' => ['type' => Type::int()],
                'sortBy' => ['type' => Type::listOf(Type::string())],
                'sortOrder' => [
                    'type' => Type::listOf(Type::string()),
                    'description' => 'Sort by ASC or DESC, use the same position as the sortBy argument for each column to sort by',
                ],
                'domain' => [
                    'type' => Type::string(),
                    'description' => 'default value: messages',
                ],
            ],
            'type' => $listingType,
            'resolve' => $listResolver->resolveListing(...),
        ];

        $config['fields']['getTranslationListing'] = $defListing;
    }

    /**
     * @param array $config
     * @param array $context
     *
     * @throws Exception
     */
    public function build(&$config = [], $context = [])
    {
        $event = new QueryTypeEvent(
            $this,
            $config,
            $context
        );
        $this->eventDispatcher->dispatch($event, QueryEvents::PRE_BUILD);

        $config = $event->getConfig();
        $context = $event->getContext();

        $this->buildAssetQueries($config, $context);
        $this->buildTranslationQueries($config, $context);
        $this->buildDocumentQueries($config, $context);
        $this->buildDataObjectQueries($config, $context);
        $this->buildAssetListingQueries($config, $context);
        $this->buildTranslationListingQueries($config, $context);
        $this->buildFolderQueries('asset', $config, $context);
        $this->buildFolderQueries('document', $config, $context);
        $this->buildFolderQueries('object', $config, $context);

        $event->setConfig($config);
        $event->setContext($context);
        $this->eventDispatcher->dispatch($event, QueryEvents::POST_BUILD);
        $config = $event->getConfig();
    }
}
