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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\Mutation;

use Exception;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp;
use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\MutationTypeEvent;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\MutationEvents;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementTag;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ElementIdentificationTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ElementTagTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\PermissionInfoTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\WorkspaceHelper;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Asset\Folder;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\AbstractElement;
use OpenDxp\Model\Element\DuplicateFullPathException;
use OpenDxp\Model\Element\Service as ElementService;
use OpenDxp\Model\Factory;
use OpenDxp\Model\Version;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MutationType extends ObjectType
{
    use ServiceTrait;
    use PermissionInfoTrait;
    use ElementIdentificationTrait;
    use ElementTagTrait;

    /** @var array */
    public static $documentElementTypes = null;

    /**
     * @var LocaleServiceInterface
     */
    protected $localeService;

    /**
     * @var Factory
     */
    protected $modelFactory;

    public static $typeCache = [];

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
            $config['name'] = 'Mutations';
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
     *
     * @throws Exception
     */
    public function build(&$config = [], $context = [])
    {
        $config['fields'] = [];
        $event = new MutationTypeEvent(
            $this,
            $config,
            $context
        );
        $this->eventDispatcher->dispatch($event, MutationEvents::PRE_BUILD);

        $config = $event->getConfig();
        $context = $event->getContext();

        $this->buildDataObjectMutations($config, $context);
        $this->buildCreateAssetMutation($config, $context);
        $this->buildUpdateAssetMutation($config, $context);

        $this->buildUpdateDocumentMutation($config, $context, 'create', 'email');
        $this->buildUpdateDocumentMutation($config, $context, 'update', 'email');
        $this->buildUpdateDocumentMutation($config, $context, 'create', 'page');
        $this->buildUpdateDocumentMutation($config, $context, 'update', 'page');
        $this->buildUpdateDocumentMutation($config, $context, 'create', 'link');
        $this->buildUpdateDocumentMutation($config, $context, 'update', 'link');

        $this->buildCreateFolderMutation('asset', $config, $context);
        $this->buildCreateFolderMutation('object', $config, $context);
        $this->buildCreateFolderMutation('document', $config, $context);
        $this->buildUpdateFolderMutation('asset', $config, $context);
        $this->buildUpdateFolderMutation('object', $config, $context);
        $this->buildUpdateFolderMutation('document', $config, $context);
        $this->buildDeleteAssetMutation($config, $context);
        $this->buildDeleteDocumentMutation($config, $context);
        $this->buildDeleteFolderMutation('asset', $config, $context);
        $this->buildDeleteFolderMutation('document', $config, $context);
        $this->buildDeleteFolderMutation('object', $config, $context);

        $event->setConfig($config);
        $event->setContext($context);
        $this->eventDispatcher->dispatch($event, MutationEvents::POST_BUILD);
        $config = $event->getConfig();

        if (isset($config['fields']) && count($config['fields']) > 1) {
            ksort($config['fields']);
        }
    }

    /**
     * //TODO this is currently for document_pages
     *
     * @param array $config
     * @param array $context
     *
     * @throws Exception
     */
    public function buildUpdateDocumentMutation(&$config, $context, $mutationType, $documentType)
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();

        if (isset($entities['document']['update']) && $entities['document']['update']) {
            $queryResolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\QueryType($this->eventDispatcher, null, $configuration);
            $queryResolver->setGraphQlService($this->getGraphQlService());

            $queryResolver = $queryResolver->resolveDocumentGetter(...);

            $opName = $mutationType . 'Document' . ucfirst((string) $documentType);

            $service = $this->getGraphQlService();
            $graphQlDocumentType = $service->getDocumentTypeDefinition('document_' . $documentType);    // this is for the return stuff

            $updateResultType = new ObjectType([
                'name' => ucfirst($opName) . 'Result',
                'fields' => [
                    'success' => ['type' => Type::boolean()],
                    'message' => ['type' => Type::string()],
                    'document' => [
                        'args' => ['defaultLanguage' => ['type' => Type::string()]],
                        'type' => $graphQlDocumentType,
                        'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($queryResolver) {
                            if ($value['success'] === true) {
                                $args['id'] = $value['id'];
                                $value = $queryResolver($value, $args, $context, $info);
                            }

                            return $value;
                        },
                    ],
                ],
            ]);

            if ($mutationType == 'create') {
                $args = [
                    'key' => ['type' => Type::nonNull(Type::string())],
                    'path' => ['type' => Type::string()],
                    'parentId' => ['type' => Type::int()],
                    'published' => ['type' => Type::boolean(), 'description' => 'Default is true!'],
                    'userId' => ['type' => Type::int()],
                ];
            } else {
                $args = [
                    'id' => ['type' => Type::int()],
                    'fullpath' => ['type' => Type::string()],
                    'omitVersionCreate' => ['type' => Type::boolean()],
                    'userId' => ['type' => Type::int()],
                ];
            }

            $inputTypeGetter = 'getDocument' . ucfirst((string) $documentType) . 'MutationInputType';
            $inputProcessorFn = 'processDocument' . ucfirst((string) $documentType) . 'MutationInput';

            $processors = [];
            $inputType = $this->{$inputTypeGetter}($context, $processors);

            $inputTypeName = 'document_' . $documentType . '_input';
            self::$typeCache[$inputTypeName] = $inputType;

            $args = array_merge($args, [
                'input' => $inputType,
            ]);

            $me = $this;
            $updateField = [
                'type' => $updateResultType,
                'args' => $args, 'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($documentType, $inputProcessorFn, $processors, $mutationType, $me) {
                    if ($mutationType == 'update') {
                        /** @var Document $element */
                        $element = $me->getElementByTypeAndIdOrPath($args, 'document');

                        if (!WorkspaceHelper::checkPermission($element, 'update')) {
                            return [
                                'success' => false,
                                'message' => 'not allowed to update document',
                            ];
                        }
                    } else {
                        $parent = null;

                        if (isset($args['parentId'])) {
                            $parent = Document::getById($args['parentId']);
                        } elseif (isset($args['path'])) {
                            $parent = Document::getByPath($args['path']);
                        }

                        if (!$parent) {
                            return [
                                'success' => false,
                                'message' => 'unable to resolve parent',
                            ];
                        }

                        if (!WorkspaceHelper::checkPermission($parent, 'create')) {
                            return [
                                'success' => false,
                                'message' => 'not allowed to create document',
                            ];
                        }

                        $className = 'OpenDxp\\Model\\Document\\' . ucfirst((string) $documentType);
                        $factory = OpenDxp::getContainer()->get('opendxp.model.factory');
                        /** @var Document $element */
                        $element = $factory->build($className);

                        $element->setParentId($parent->getId());
                        $element->setKey($args['key']);
                        $element->setPublished($args['published'] ?? true);
                    }

                    $tags = [];
                    if (isset($args['input'])) {
                        $me->{$inputProcessorFn}($value, $args, $context, $info, $element, $processors);
                        if (isset($args['input']['tags']) && ($tag_input = $args['input']['tags'])) {
                            $tags = $me->getTagsFromInput($tag_input);
                            if (false === $tags) {
                                return [
                                    'success' => false,
                                    'message' => 'no "id" nor "path" tag data defined for tag, or tag not found',
                                ];
                            }
                        }
                    }

                    $me->saveElement($element, $args);

                    if ($tags) {
                        $me->setTags('document', $element->getId(), $tags);
                    }

                    return [
                        'success' => true,
                        'message' => 'document updated: ' . $element->getId(),
                        'id' => $element->getId(),
                    ];
                },
            ];

            $config['fields'][$opName] = $updateField;
        }
    }

    /**
     * @param array $context
     * @param array $processors
     *
     * @return array
     */
    public function getDocumentEmailMutationInputType($context, &$processors = [])
    {
        $service = $this->getGraphQlService();

        $elementTypes = $service->getSupportedDocumentElementMutationDataTypes();
        $elementFields = [];
        $processors = [];
        foreach ($elementTypes as $elementType) {
            $typedef = self::$typeCache[$elementType] ?? $service->buildDocumentElementDataMutationType($elementType);
            self::$typeCache[$elementType] = $typedef;
            $elementFields[$elementType] = Type::listOf($typedef['arg']);
            $processors[$elementType] = $typedef['processor'];
        }

        $elementInputTypeList = new InputObjectType([
            'name' => 'document_emailmutationelements',
            'fields' => $elementFields,
        ]);

        $inputTypeName = 'document_email_input';
        $inputType = self::$typeCache[$inputTypeName] ??
            new InputObjectType([
                'name' => $inputTypeName,
                'fields' => [
                    'key' => Type::string(),
                    'published' => Type::boolean(),
                    'module' => Type::string(),
                    'controller' => Type::string(),
                    'action' => Type::string(),
                    'template' => Type::string(),
                    'elements' => $elementInputTypeList,
                    'subject' => Type::string(),
                    'from' => Type::string(),
                    'replyTo' => Type::string(),
                    'to' => Type::string(),
                    'cc' => Type::string(),
                    'bcc' => Type::string(),
                    'tags' => ElementTag::getElementTagInputTypeDefinition(),
                ],
            ]);

        return $inputType;
    }

    /**
     * @param array $context
     * @param array $processors
     *
     * @return array
     */
    public function getDocumentLinkMutationInputType($context, &$processors = [])
    {
        $inputType = $this->getGraphQlService()->getDocumentTypeDefinition('document_link_input');

        return $inputType;
    }

    /**
     * @param ElementDescriptor|null $value
     * @param array $args
     * @param mixed $context
     * @param Document\Link $element
     * @param array $processors
     */
    public static function processDocumentLinkMutationInput($value, $args, $context, ResolveInfo $info, $element, $processors)
    {
        $inputValues = $args['input'];

        foreach ($inputValues as $key => $value) {
            if ($key == 'object') {
                Logger::debug('test');
                $type = $value['type'];
                $id = $value['id'];
                $target = \OpenDxp\Model\Element\Service::getElementById($type, $id);
                $element->setElement($target);
            } elseif ($key == 'tags') {
                //skip it to process in callee method
            } elseif ($key == 'href') {
                $element->setDirect($value);
                $element->setLinktype('direct');
            } else {
                $setter = 'set' . ucfirst((string) $key);

                $element->$setter($value);
            }
        }
    }

    /**
     * @param ElementDescriptor|null $value
     * @param array $args
     * @param mixed $context
     * @param Document\Page|Document\Email $element
     * @param array $processors
     *
     * @return void
     */
    public function processDocumentEmailMutationInput($value, $args, $context, ResolveInfo $info, $element, $processors)
    {
        self::processDocumentPageMutationInput($value, $args, $context, $info, $element, $processors);
    }

    /**
     * @param ElementDescriptor|null $value
     * @param array $args
     * @param mixed $context
     * @param Document\Page|Document\Email $element
     * @param array $processors
     */
    public function processDocumentPageMutationInput($value, $args, $context, ResolveInfo $info, $element, $processors)
    {
        $inputValues = $args['input'];
        foreach ($inputValues as $key => $value) {
            if ($key == 'editableUpdateStrategy') {
                if ($value == 'replaceAll') {
                    $element->setEditables([]);
                }
            } elseif ($key == 'editables') {
                $element->getEditables();

                foreach ($value as $elementType => $elementTypeValues) {
                    if ($processor = $processors[$elementType] ?? null) {
                        foreach ($elementTypeValues as $elementTypeValue) {
                            $elementTypeValue['_editableType'] = $elementType;
                            call_user_func_array($processor, [$element, $elementTypeValue, $args, $context, $info]);
                        }
                    }
                }
            } elseif ($key == 'tags') {
                //skip it to process in callee method
            } else {
                $setter = 'set' . ucfirst((string) $key);

                $element->$setter($value);
            }
        }
    }

    /**
     * @param array $context
     * @param array $processors
     *
     * @return array
     */
    public function getDocumentPageMutationInputType($context, &$processors = [])
    {
        $service = $this->getGraphQlService();

        $elementTypes = $service->getSupportedDocumentElementMutationDataTypes();
        $elementFields = [];
        $processors = [];
        foreach ($elementTypes as $elementType) {
            $typedef = self::$typeCache[$elementType] ?? $service->buildDocumentElementDataMutationType($elementType);
            self::$typeCache[$elementType] = $typedef;
            $elementFields[$elementType] = Type::listOf($typedef['arg']);
            $processors[$elementType] = $typedef['processor'];
        }

        $elementInputTypeList = self::$typeCache['document_pagemutationelements'] ?? null;
        if (!$elementInputTypeList) {
            $elementInputTypeList = new InputObjectType([
                'name' => 'document_pagemutationelements',
                'fields' => $elementFields,
            ]);

            self::$typeCache['document_pagemutationelements'] = $elementInputTypeList;
            self::$documentElementTypes = $elementInputTypeList;
        }

        if (!isset(self::$typeCache['overwrite_strategy'])) {
            self::$typeCache['overwrite_strategy'] = new EnumType([
                'name' => 'overwrite_strategy',
                'values' => [
                    'overwrite',
                    'update',
                ],
            ]);
        }

        $inputTypeName = 'document_page_input';
        $inputType = self::$typeCache[$inputTypeName] ??
            new InputObjectType([
                'name' => $inputTypeName,
                'fields' => [
                    'key' => Type::string(),
                    'published' => Type::boolean(),
                    'module' => Type::string(),
                    'controller' => Type::string(),
                    'action' => Type::string(),
                    'template' => Type::string(),
                    'editableUpdateStrategy' => self::$typeCache['overwrite_strategy'],
                    'editables' => $elementInputTypeList,
                    'tags' => ElementTag::getElementTagInputTypeDefinition(),
                ],
            ]);

        return $inputType;
    }

    /**
     * @param array $config
     * @param array $context
     *
     * @throws Exception
     */
    public function buildDataObjectMutations(&$config = [], $context = [])
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getMutationEntities();

        foreach ($entities as $entity) {
            $class = ClassDefinition::getByName($entity);
            if (!$class) {
                Logger::error('class ' . $entity . ' not found');

                continue;
            }
            $entityConfig = $configuration->getMutationEntityConfig($entity);

            $queryResolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\QueryType($this->eventDispatcher, $class, $configuration);
            $queryResolver->setGraphQlService($this->getGraphQlService());

            $modelFactory = $this->modelFactory;
            $localeService = $this->localeService;
            $createOperationName = 'create' . ucfirst((string) $entity);
            $updateOperationName = 'update' . ucfirst((string) $entity);

            if (isset($entityConfig['create']) && $entityConfig['create']) {
                // create
                $createResultType = new ObjectType([
                    'name' => 'Create' . ucfirst((string) $entity) . 'Result',
                    'fields' => [
                        'success' => ['type' => Type::boolean()],
                        'message' => ['type' => Type::string()],
                        'output' => [
                            'args' => ['defaultLanguage' => ['type' => Type::string()]],
                            'type' => \OpenDxp\Bundle\DataHubBundle\GraphQL\ClassTypeDefinitions::get($class),
                            'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($queryResolver) {
                                if ($value['success'] === true) {
                                    $args['id'] = $value['id'];
                                    $value = $queryResolver->resolveObjectGetter($value, $args, $context, $info);
                                }

                                return $value;
                            },
                        ],
                    ],
                ]);

                $opName = $createOperationName;

                $this->generateInputFieldsAndProcessors($inputFields, $processors, $context, $entity, $class);

                $inputFields['tags'] = ElementTag::getElementTagInputTypeDefinition();

                $inputTypeName = 'Update' . ucfirst((string) $entity) . 'Input';
                $inputType = self::$typeCache[$inputTypeName] ?? new InputObjectType([
                        'name' => $inputTypeName,
                        'fields' => $inputFields,
                    ]);
                self::$typeCache[$inputTypeName] = $inputType;

                $me = $this;

                $createField = [
                    'type' => $createResultType,
                    'args' => [
                        // key is not mandatory as I'll probably add a way to create a new object by fullpath
                        'key' => ['type' => Type::nonNull(Type::string())],
                        'path' => ['type' => Type::string()],
                        'parentId' => ['type' => Type::int()],
                        'parentUuid' => ['type' => Type::string(), 'description' => 'Resolve parent DataObject by UUID (same class as entity, e.g. parent Territory)'],
                        'defaultLanguage' => ['type' => Type::string()],
                        'published' => ['type' => Type::boolean(), 'description' => 'Default is true!'],
                        'omitMandatoryCheck' => ['type' => Type::boolean()],
                        'userId' => ['type' => Type::int()],
                        'type' => ['type' => Type::string()],
                        'input' => $inputType,
                    ], 'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($entity, $modelFactory, $processors, $localeService, $me) {
                        $parent = null;

                        if (isset($args['parentId'])) {
                            $parent = DataObject::getById($args['parentId']);
                        } elseif (isset($args['path'])) {
                            $parent = DataObject::getByPath($args['path']);
                        } elseif (!empty($args['parentUuid']) && is_string($args['parentUuid'])) {
                            $parent = $me->resolveDataObjectByUuid(ucfirst($entity), trim($args['parentUuid']));
                        }

                        //TODO maybe add error code?
                        if (!$parent) {
                            return [
                                'success' => false,
                                'message' => 'unable to resolve parent (provide parentId, path, or parentUuid)',
                            ];
                        }

                        /** @var Configuration $configuration */
                        $configuration = $context['configuration'];
                        if (!$me->omitPermissionCheck && !WorkspaceHelper::checkPermission($parent, 'create')) {
                            return [
                                'success' => false,
                                'message' => 'not allowed to create object ' . $entity,
                            ];
                        }

                        $published = true;
                        // default is true!
                        if (isset($args['published'])) {
                            $published = $args['published'];
                        }

                        $key = $args['key'];
                        $key = DataObject\Service::getValidKey($key, 'object');

                        $className = 'OpenDxp\\Model\\DataObject\\' . ucfirst((string) $entity);
                        /** @var Concrete $newInstance */
                        $newInstance = $modelFactory->build($className);
                        $newInstance->setPublished($published);
                        $newInstance->setParent($parent);
                        $newInstance->setKey($key);

                        if (isset($args['type']) && ($args['type'] == 'object' || $args['type'] == 'variant')) {
                            $newInstance->setType($args['type']);
                        }

                        $resolver = $me->getUpdateObjectResolver($processors, $localeService, $newInstance, true);

                        $returnValue = call_user_func_array($resolver, [$value, $args, $context, $info]);
                        if (isset($returnValue['success']) === true &&
                            $returnValue['success'] === false) {
                            return $returnValue;
                        }

                        if (isset($args['omitMandatoryCheck'])) {
                            $newInstance->setOmitMandatoryCheck($args['omitMandatoryCheck']);
                        }

                        $tags = [];
                        if (isset($args['input'])) {
                            $inputValues = $args['input'];
                            foreach ($inputValues as $key => $value) {
                                //TODO: ask open-dxp/opendxp to implement something like Asset::setTags
                                if ($key == 'tags') {
                                    $tags = $me->getTagsFromInput($value);
                                    if (false === $tags) {
                                        return [
                                            'success' => false,
                                            'message' => 'no "id" nor "path" tag data defined for tag, or tag not found',
                                        ];
                                    }
                                }
                            }
                        }

                        try {
                            $me->saveElement($newInstance, $args);
                        } catch (DuplicateFullPathException) {
                            return [
                                'success' => false,
                                'message' => 'creating failed: Duplicate path',
                            ];
                        } catch (Exception $e) {
                            return [
                                'success' => false,
                                'message' => 'creating failed: ' . $e->getMessage(),
                            ];
                        }

                        if ($tags) {
                            $me->setTags('object', $newInstance->getId(), $tags);
                        }

                        return [
                            'success' => true,
                            'message' => 'object created: ' . $newInstance->getId(),
                            'id' => $newInstance->getId(),
                        ];
                    },
                ];

                $config['fields'][$opName] = $createField;
            }

            if (isset($entityConfig['update']) && $entityConfig['update']) {

                // update
                $opName = $updateOperationName;

                $updateResultType = new ObjectType([
                    'name' => 'Update' . ucfirst((string) $entity) . 'Result',
                    'fields' => [
                        'success' => ['type' => Type::boolean()],
                        'message' => ['type' => Type::string()],
                        'output' => [
                            'args' => ['defaultLanguage' => ['type' => Type::string()]],
                            'type' => \OpenDxp\Bundle\DataHubBundle\GraphQL\ClassTypeDefinitions::get($class),
                            'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($queryResolver) {
                                if ($value['success'] === true) {
                                    $args['id'] = $value['id'];
                                    $value = $queryResolver->resolveObjectGetter($value, $args, $context, $info);
                                }

                                return $value;
                            },
                        ],
                    ],
                ]);

                $this->generateInputFieldsAndProcessors($inputFields, $processors, $context, $entity, $class);

                $inputFields['tags'] = ElementTag::getElementTagInputTypeDefinition();

                $inputTypeName = 'Update' . ucfirst((string) $entity) . 'Input';
                $inputType = self::$typeCache[$inputTypeName] ?? new InputObjectType([
                    'name' => $inputTypeName,
                    'fields' => $inputFields,
                ]);
                self::$typeCache[$inputTypeName] = $inputType;

                $updateField = [
                    'type' => $updateResultType,
                    'args' => [
                        'id' => ['type' => Type::int()],
                        'fullpath' => ['type' => Type::string()],
                        'parentId' => ['type' => Type::int()],
                        'parentUuid' => ['type' => Type::string(), 'description' => 'Resolve parent DataObject by UUID (same class as entity)'],
                        'defaultLanguage' => ['type' => Type::string()],
                        'omitMandatoryCheck' => ['type' => Type::boolean()],
                        'omitVersionCreate' => ['type' => Type::boolean()],
                        'userId' => ['type' => Type::int()],
                        'input' => ['type' => $inputType],
                    ], 'resolve' => $this->getUpdateObjectResolver($processors, $localeService, null, $this->omitPermissionCheck),
                ];

                $config['fields'][$opName] = $updateField;
            }

            $this->buildCreateByUuidMutation(
                $config,
                $entity,
                $class,
                $createOperationName,
            );
            $this->buildUpdateByUuidMutation(
                $config,
                $entity,
                $class,
                $updateOperationName
            );
            $this->buildUpsertByUuidMutation(
                $config,
                $entity,
                $class,
                $createOperationName,
                $updateOperationName
            );

            if (isset($entityConfig['delete']) && $entityConfig['delete']) {
                $opName = 'delete' . ucfirst((string) $entity);

                $deleteResultType = new ObjectType([
                    'name' => 'Delete' . ucfirst((string) $entity) . 'Result',
                    'fields' => [
                        'success' => ['type' => Type::boolean()],
                        'message' => ['type' => Type::string()],
                    ],
                ]);

                $me = $this;
                $deleteField = [
                    'type' => $deleteResultType,
                    'args' => [
                        'id' => ['type' => Type::int()],
                        'fullpath' => ['type' => Type::string()],
                    ], 'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($me) {
                        try {
                            /** @var Configuration $configuration */
                            $configuration = $context['configuration'];

                            $object = $me->getElementByTypeAndIdOrPath($args, 'object');

                            if (!$object) {
                                return [
                                    'success' => false,
                                    'message' => 'unable to delete object. Unknown id or fullpath',
                                ];
                            }

                            if (!$me->omitPermissionCheck && !WorkspaceHelper::checkPermission($object, 'delete')) {
                                return [
                                    'success' => false,
                                    'message' => 'permission denied.',
                                ];
                            }
                            $object->delete();

                            return [
                                'success' => true,
                                'message' => '',
                            ];
                        } catch (Exception $e) {
                            return [
                                'success' => false,
                                'message' => $e->getMessage(),
                            ];
                        }
                    },
                ];

                $config['fields'][$opName] = $deleteField;
            }
        }
    }

    /**
     * Add create<Entity>ByUuid mutation for classes that have uuid field and create mutation.
     *
     * @param array<string, mixed> $config
     */
    private function buildCreateByUuidMutation(
        array &$config,
        string $entity,
        ClassDefinition $class,
        string $createOperationName
    ): void {
        if (!$class->getFieldDefinition('uuid')) {
            return;
        }

        $createField = $config['fields'][$createOperationName] ?? null;
        if (!is_array($createField)) {
            return;
        }

        $createResolve = $createField['resolve'] ?? null;
        if (!is_callable($createResolve)) {
            return;
        }

        $createByUuidOperationName = 'create' . ucfirst($entity) . 'ByUuid';
        if (isset($config['fields'][$createByUuidOperationName])) {
            return;
        }

        $createArgs = is_array($createField['args'] ?? null) ? $createField['args'] : [];
        $args = array_merge(
            ['uuid' => ['type' => Type::nonNull(Type::string())]],
            $createArgs
        );

        $className = $class->getName();
        $me = $this;
        $config['fields'][$createByUuidOperationName] = [
            'type' => $createField['type'],
            'args' => $args,
            'resolve' => static function ($value, array $args, $context, ResolveInfo $info) use (
                $className,
                $createResolve,
                $me
            ) {
                $uuid = trim((string) ($args['uuid'] ?? ''));
                if ($uuid === '') {
                    return [
                        'success' => false,
                        'message' => 'uuid is required',
                    ];
                }

                $existingObject = $me->resolveDataObjectByUuid($className, $uuid);
                if ($existingObject instanceof Concrete) {
                    return [
                        'success' => false,
                        'message' => 'object with uuid already exists',
                    ];
                }

                $createArgs = $args;
                unset($createArgs['uuid']);
                if (!isset($createArgs['input']) || !is_array($createArgs['input'])) {
                    $createArgs['input'] = [];
                }
                if (empty($createArgs['input']['uuid'])) {
                    $createArgs['input']['uuid'] = $uuid;
                }

                return $createResolve($value, $createArgs, $context, $info);
            },
        ];
    }

    /**
     * Add update<Entity>ByUuid mutation for classes that have uuid field and update mutation.
     *
     * @param array<string, mixed> $config
     */
    private function buildUpdateByUuidMutation(
        array &$config,
        string $entity,
        ClassDefinition $class,
        string $updateOperationName
    ): void {
        if (!$class->getFieldDefinition('uuid')) {
            return;
        }

        $updateField = $config['fields'][$updateOperationName] ?? null;
        if (!is_array($updateField)) {
            return;
        }

        $updateResolve = $updateField['resolve'] ?? null;
        if (!is_callable($updateResolve)) {
            return;
        }

        $updateByUuidOperationName = 'update' . ucfirst($entity) . 'ByUuid';
        if (isset($config['fields'][$updateByUuidOperationName])) {
            return;
        }

        $updateArgs = is_array($updateField['args'] ?? null) ? $updateField['args'] : [];
        unset($updateArgs['id'], $updateArgs['fullpath']);
        $args = array_merge(
            [
                'uuid' => ['type' => Type::nonNull(Type::string())],
                'key' => ['type' => Type::string()],
                'path' => ['type' => Type::string()],
                'published' => ['type' => Type::boolean()],
            ],
            $updateArgs
        );

        $className = $class->getName();
        $me = $this;
        $config['fields'][$updateByUuidOperationName] = [
            'type' => $updateField['type'],
            'args' => $args,
            'resolve' => static function ($value, array $args, $context, ResolveInfo $info) use (
                $className,
                $updateResolve,
                $me
            ) {
                $uuid = trim((string) ($args['uuid'] ?? ''));
                if ($uuid === '') {
                    return [
                        'success' => false,
                        'message' => 'uuid is required',
                    ];
                }

                $existingObject = $me->resolveDataObjectByUuid($className, $uuid);
                if (!$existingObject instanceof Concrete) {
                    return [
                        'success' => false,
                        'message' => 'unable to update object. Unknown uuid',
                    ];
                }

                $updateArgs = $args;
                unset($updateArgs['uuid']);
                $updateArgs['id'] = $existingObject->getId();

                if (isset($updateArgs['input']) && is_array($updateArgs['input'])) {
                    unset($updateArgs['input']['uuid']);
                }

                $updateResult = $updateResolve($value, $updateArgs, $context, $info);
                if (!is_array($updateResult) || (($updateResult['success'] ?? false) !== true)) {
                    return $updateResult;
                }

                $shouldUpdateSystemFields = isset($args['key']) || array_key_exists('published', $args);
                if (!$shouldUpdateSystemFields) {
                    return $updateResult;
                }

                $objectId = $updateResult['id'] ?? null;
                $object = is_int($objectId) ? DataObject::getById($objectId) : null;
                if (!$object instanceof Concrete) {
                    return [
                        'success' => false,
                        'message' => 'unable to update object. Unknown id after update',
                    ];
                }

                if (isset($args['key']) && trim((string) $args['key']) !== '') {
                    $object->setKey(DataObject\Service::getValidKey((string) $args['key'], 'object'));
                }
                if (array_key_exists('published', $args) && $args['published'] !== null) {
                    $object->setPublished((bool) $args['published']);
                }

                try {
                    $me->saveElement($object, $updateArgs);
                } catch (\Exception $e) {
                    return [
                        'success' => false,
                        'message' => 'updating system fields failed: ' . $e->getMessage(),
                    ];
                }

                return $updateResult;
            },
        ];
    }

    /**
     * Add upsert<Entity>ByUuid mutation for classes that have uuid field and both create/update mutations.
     *
     * @param array<string, mixed> $config
     */
    private function buildUpsertByUuidMutation(
        array &$config,
        string $entity,
        ClassDefinition $class,
        string $createOperationName,
        string $updateOperationName
    ): void {
        if (!$class->getFieldDefinition('uuid')) {
            return;
        }

        $createField = $config['fields'][$createOperationName] ?? null;
        $updateField = $config['fields'][$updateOperationName] ?? null;

        if (!is_array($createField) || !is_array($updateField)) {
            return;
        }

        $createResolve = $createField['resolve'] ?? null;
        $updateResolve = $updateField['resolve'] ?? null;
        if (!is_callable($createResolve) || !is_callable($updateResolve)) {
            return;
        }

        $upsertOperationName = 'upsert' . ucfirst($entity) . 'ByUuid';
        if (isset($config['fields'][$upsertOperationName])) {
            return;
        }

        $createArgs = is_array($createField['args'] ?? null) ? $createField['args'] : [];
        $updateArgs = is_array($updateField['args'] ?? null) ? $updateField['args'] : [];

        $args = [
            'uuid' => ['type' => Type::nonNull(Type::string())],
        ];

        foreach ($createArgs as $argName => $argConfig) {
            if ($argName === 'key') {
                $args[$argName] = ['type' => Type::string()];
                continue;
            }
            $args[$argName] = $argConfig;
        }

        foreach ($updateArgs as $argName => $argConfig) {
            if ($argName === 'id' || $argName === 'fullpath') {
                continue;
            }
            if (!isset($args[$argName])) {
                $args[$argName] = $argConfig;
            }
        }

        $className = $class->getName();
        $me = $this;
        $config['fields'][$upsertOperationName] = [
            'type' => $updateField['type'],
            'args' => $args,
            'resolve' => static function ($value, array $args, $context, ResolveInfo $info) use (
                $className,
                $createResolve,
                $updateResolve,
                $me
            ) {
                $uuid = trim((string) ($args['uuid'] ?? ''));
                if ($uuid === '') {
                    return [
                        'success' => false,
                        'message' => 'uuid is required',
                    ];
                }

                $existingObject = $me->resolveDataObjectByUuid($className, $uuid);
                if ($existingObject instanceof Concrete) {
                    $updateArgs = $args;
                    unset($updateArgs['uuid']);
                    $updateArgs['id'] = $existingObject->getId();

                    if (isset($updateArgs['input']) && is_array($updateArgs['input'])) {
                        unset($updateArgs['input']['uuid']);
                    }

                    return $updateResolve($value, $updateArgs, $context, $info);
                }

                $key = trim((string) ($args['key'] ?? ''));
                if ($key === '') {
                    return [
                        'success' => false,
                        'message' => 'key is required for create branch of upsert',
                    ];
                }

                $hasParentId = isset($args['parentId']) && $args['parentId'] !== null;
                $hasPath = isset($args['path']) && trim((string) ($args['path'] ?? '')) !== '';
                $hasParentUuid = isset($args['parentUuid']) && trim((string) ($args['parentUuid'] ?? '')) !== '';
                if (!$hasParentId && !$hasPath && !$hasParentUuid) {
                    return [
                        'success' => false,
                        'message' => 'parentId, path or parentUuid is required for create branch of upsert',
                    ];
                }

                $createArgs = $args;
                unset($createArgs['uuid']);
                if (!isset($createArgs['input']) || !is_array($createArgs['input'])) {
                    $createArgs['input'] = [];
                }
                if (empty($createArgs['input']['uuid'])) {
                    $createArgs['input']['uuid'] = $uuid;
                }

                return $createResolve($value, $createArgs, $context, $info);
            },
        ];
    }

    /**
     * @throws \Exception
     */
    private function resolveDataObjectByUuid(string $className, string $uuid): ?Concrete
    {
        $className = trim($className);
        $uuid = trim($uuid);

        if ($className === '' || $uuid === '') {
            return null;
        }

        $classDefinition = ClassDefinition::getByName($className);
        if (!$classDefinition || !$classDefinition->getFieldDefinition('uuid')) {
            return null;
        }

        $listingClass = '\\OpenDxp\\Model\\DataObject\\' . $className . '\\Listing';
        if (!class_exists($listingClass)) {
            return null;
        }

        $listing = new $listingClass();
        $listing->setUnpublished(true);
        $listing->setCondition('uuid = ?', [$uuid]);
        $listing->setLimit(1);
        $items = $listing->load();
        $first = $items[0] ?? null;

        return $first instanceof Concrete ? $first : null;
    }

    /**
     * @param array $inputFields
     * @param array $processors
     * @param array $context
     * @param string $entity
     * @param ClassDefinition|\OpenDxp\Model\DataObject\Fieldcollection\Definition $class
     *
     * @return void
     */
    public function generateInputFieldsAndProcessors(&$inputFields, &$processors, $context, $entity, $class)
    {
        $inputFields = [];
        $processors = [];

        if ($context['clientname']) {
            /** @var Configuration $configurationItem */
            $configurationItem = $context['configuration'];

            $columns = $configurationItem->getMutationColumnConfig($entity)['columns'] ?? [];

            if ($columns) {
                $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();

                foreach ($columns as $column) {
                    $result = $fieldHelper->getMutationFieldConfigFromConfig($column, $class);
                    if ($result) {
                        $inputFields[$result['key']] = $result['arg'];
                        $processor = $result['processor'];
                        $processors[$result['key']] = $processor;
                    }
                }
            }
        }
    }

    /**
     * @param array $processors
     * @param LocaleServiceInterface $localeService
     * @param object|null $object
     * @param bool $omitPermissionCheck
     *
     * @return callable(mixed $value, array $args, array $context, ResolveInfo $info): mixed
     */
    public function getUpdateObjectResolver($processors, $localeService, $object = null, $omitPermissionCheck = false)
    {
        $me = $this;

        return static function ($value, $args, $context, $info) use ($processors, $localeService, $object, $omitPermissionCheck, $me) {
            try {
                if (!$object) {
                    $object = $me->getElementByTypeAndIdOrPath($args, 'object');
                }

                $parent = null;
                if (isset($args['parentId'])) {
                    $parent = DataObject::getById($args['parentId']);
                } elseif (isset($args['path'])) {
                    $parent = DataObject::getByPath($args['path']);
                } elseif (!empty($args['parentUuid']) && is_string($args['parentUuid']) && $object instanceof Concrete) {
                    $parent = $me->resolveDataObjectByUuid($object->getClass()->getName(), trim($args['parentUuid']));
                }
                if ($parent && $object) {
                    $object->setParent($parent);
                }

                if (!$object) {
                    return [
                        'success' => false,
                        'message' => 'unable to update object. Unknown id or fullpath',
                    ];
                }

                if (!$omitPermissionCheck && !WorkspaceHelper::checkPermission($object, 'update')) {
                    return [
                        'success' => false,
                        'message' => 'permission denied.',
                    ];
                }

                if (isset($args['defaultLanguage'])) {
                    $localeService->setLocale($args['defaultLanguage']);
                }

                if (isset($args['omitMandatoryCheck'])) {
                    $object->setOmitMandatoryCheck($args['omitMandatoryCheck']);
                }

                $tags = [];
                if (isset($args['input'])) {
                    $dataIn = $args['input'];
                    if (is_array($dataIn)) {
                        foreach ($dataIn as $key => $value) {
                            if (isset($processors[$key])) {
                                $processor = $processors[$key];
                                call_user_func_array($processor, [$object, $value, $args, $context, $info]);
                            } elseif ($key === 'tags') {
                                $tags = $me->getTagsFromInput($value);
                                if (false === $tags) {
                                    return [
                                        'success' => false,
                                        'message' => 'no "id" nor "path" tag data defined for tag, or tag not found',
                                    ];
                                }
                            }
                        }
                    }
                }

                $me->saveElement($object, $args);

                if ($tags) {
                    $me->setTags('object', $object->getId(), $tags);
                }
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

            return [
                'success' => true,
                'message' => 'object ' . $object->getId() . ' updated',
                'id' => $object->getId(),
            ];
        };
    }

    /**
     * @param array $config
     * @param array $context
     */
    public function buildCreateAssetMutation(&$config, $context)
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();

        if (isset($entities['asset']['create']) && $entities['asset']['create']) {
            $queryResolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\QueryType($this->eventDispatcher, null, $configuration);
            $queryResolver->setGraphQlService($this->getGraphQlService());
            $queryResolver = $queryResolver->resolveAssetGetter(...);
            $service = $this->getGraphQlService();
            $assetType = $service->buildAssetType('asset');

            $createResultType = new ObjectType([
                'name' => 'CreateAssetResult',
                'fields' => [
                    'success' => ['type' => Type::boolean()],
                    'message' => ['type' => Type::string()],
                    'assetData' => [
                        'args' => ['defaultLanguage' => ['type' => Type::string()]],
                        'type' => $assetType,
                        'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($queryResolver) {
                            if ($args['id'] = $value['id'] ?? null) {
                                $value = $queryResolver($value, $args, $context, $info);
                            }

                            return $value;
                        },
                    ],
                ],
            ]);

            $opName = 'createAsset';
            $omitPermissionCheck = $this->omitPermissionCheck;

            $me = $this;
            $createField = [
                'type' => $createResultType,
                'args' => [
                    'filename' => ['type' => Type::nonNull(Type::string())],
                    'path' => ['type' => Type::string()],
                    'parentId' => ['type' => Type::int()],
                    'type' => ['type' => Type::nonNull(Type::string()), 'description' => 'image or whatever'],
                    'userId' => ['type' => Type::int()],
                    'input' => $this->getGraphQlService()->getAssetTypeDefinition('asset_input'),
                ], 'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($omitPermissionCheck, $me) {
                    $parent = null;

                    if (isset($args['parentId'])) {
                        $parent = Asset::getById($args['parentId']);
                    } elseif (isset($args['path'])) {
                        $parent = Asset::getByPath($args['path']);
                    }

                    //TODO maybe add error code?
                    if (!$parent) {
                        return [
                            'success' => false,
                            'message' => 'unable to resolve parent',
                        ];
                    }

                    if (!$omitPermissionCheck && !WorkspaceHelper::checkPermission($parent, 'create')) {
                        return [
                            'success' => false,
                            'message' => 'not allowed to create asset',
                        ];
                    }

                    $type = $args['type'];
                    $filename = $args['filename'];

                    $className = 'OpenDxp\\Model\\Asset\\' . ucfirst($type);
                    /** @var Asset $newInstance */
                    $newInstance = new $className();
                    $newInstance->setParentId($parent->getId());
                    $newInstance->setFilename($filename);

                    $tags = [];
                    if (isset($args['input'])) {
                        $inputValues = $args['input'];
                        foreach ($inputValues as $key => $value) {
                            //TODO: ask open-dxp/opendxp to implement something like Asset::setTags
                            if ($key == 'tags') {
                                $tags = $me->getTagsFromInput($value);
                                if (false === $tags) {
                                    return [
                                        'success' => false,
                                        'message' => 'no "id" nor "path" tag data defined for tag, or tag not found',
                                    ];
                                }
                            } else {
                                if ($key === 'data') {
                                    $value = base64_decode($value);
                                }
                                $setter = 'set' . ucfirst($key);
                                $newInstance->$setter($value);
                            }
                        }
                    }

                    try {
                        $me->saveElement($newInstance, $args);
                    } catch (DuplicateFullPathException) {
                        return [
                            'success' => false,
                            'message' => 'saving failed: Duplicate path',
                        ];
                    } catch (Exception $e) {
                        return [
                            'success' => false,
                            'message' => 'saving failed: ' . $e->getMessage(),
                        ];
                    }

                    if ($tags) {
                        $me->setTags('asset', $newInstance->getId(), $tags);
                    }

                    return [
                        'success' => true,
                        'message' => 'asset created: ' . $newInstance->getId(),
                        'id' => $newInstance->getId(),
                    ];
                },
            ];

            $config['fields'][$opName] = $createField;
        }
    }

    /**
     * @param array $config
     * @param array $context
     *
     * @throws Exception
     */
    public function buildUpdateAssetMutation(&$config, $context)
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();

        if (isset($entities['asset']['update']) && $entities['asset']['update']) {
            $queryResolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\QueryType($this->eventDispatcher, null, $configuration);
            $queryResolver->setGraphQlService($this->getGraphQlService());
            $queryResolver = $queryResolver->resolveAssetGetter(...);
            $service = $this->getGraphQlService();
            $assetType = $service->buildAssetType('asset');

            $updateResultType = new ObjectType([
                'name' => 'UpdateAssetResult',
                'fields' => [
                    'success' => ['type' => Type::boolean()],
                    'message' => ['type' => Type::string()],
                    'assetData' => [
                        'args' => ['defaultLanguage' => ['type' => Type::string()]],
                        'type' => $assetType,
                        'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($queryResolver) {
                            if ($value['success'] === true) {
                                $args['id'] = $value['id'];
                                $value = $queryResolver($value, $args, $context, $info);
                            }

                            return $value;
                        },
                    ],
                ],
            ]);

            $opName = 'updateAsset';

            $me = $this;
            $updateField = [
                'type' => $updateResultType,
                'args' => [
                    'id' => ['type' => Type::int()],
                    'fullpath' => ['type' => Type::string()],
                    'omitVersionCreate' => ['type' => Type::boolean()],
                    'userId' => ['type' => Type::int()],
                    'input' => $this->getGraphQlService()->getAssetTypeDefinition('asset_input'),
                ], 'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($me) {
                    /** @var Asset $element */
                    $element = $me->getElementByTypeAndIdOrPath($args, 'asset');
                    $tags = [];

                    if (isset($args['input'])) {
                        $inputValues = $args['input'];
                        foreach ($inputValues as $key => $value) {
                            //TODO: ask open-dxp/opendxp to implement something like Asset::setTags
                            if ($key == 'tags') {
                                $tags = $me->getTagsFromInput($value);
                                if (false === $tags) {
                                    return [
                                        'success' => false,
                                        'message' => 'no "id" nor "path" tag data defined for tag, or tag not found',
                                    ];
                                }
                            } else {
                                if ($key === 'data') {
                                    $value = base64_decode($value);
                                }
                                $setter = 'set' . ucfirst($key);
                                $element->$setter($value);
                            }
                        }
                    }

                    $me->saveElement($element, $args);

                    if ($tags) {
                        $me->setTags('asset', $element->getId(), $tags);
                    }

                    return [
                        'success' => true,
                        'message' => 'asset updated: ' . $element->getId(),
                        'id' => $element->getId(),
                    ];
                },
            ];

            $config['fields'][$opName] = $updateField;
        }
    }

    /**
     * @param string $type
     * @param array $config
     * @param array $context
     */
    public function buildCreateFolderMutation($type, &$config, $context)
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();

        if (isset($entities[$type . '_folder']['create']) && $entities[$type . '_folder']['create']) {
            $opName = 'create' . ucfirst($type) . 'Folder';
            $createResultType = new ObjectType([
                'name' => 'Create' . ucfirst($type) . 'FolderResult',
                'fields' => [
                    'success' => ['type' => Type::boolean()],
                    'message' => ['type' => Type::string()],
                    'id' => ['type' => Type::int()],
                ],
            ]);

            $args = [
                'path' => ['type' => Type::string()],
                'parentId' => ['type' => Type::int()],
                'userId' => ['type' => Type::int()],
            ];

            if ($type === 'asset') {
                $args['filename'] = ['type' => Type::nonNull(Type::string())];
            } else {
                $args['key'] = ['type' => Type::nonNull(Type::string())];
            }

            $resolverFn = $this->getCreateFolderResolver($type);
            $createField = [
                'type' => $createResultType,
                'args' => $args,
                'resolve' => $resolverFn,
            ];

            $config['fields'][$opName] = $createField;
        }
    }

    /**
     * @param string $elementType
     *
     * @return callable(mixed $value, array $args, array $context, ResolveInfo $info): mixed
     */
    public function getCreateFolderResolver($elementType)
    {
        $me = $this;

        return static function ($value, $args, $context, ResolveInfo $info) use ($elementType, $me) {
            $parent = null;

            if (isset($args['parentId'])) {
                $parent = ElementService::getElementById($elementType, $args['parentId']);
            } elseif (isset($args['path'])) {
                $parent = ElementService::getElementByPath($elementType, $args['path']);
            }

            if (!$parent) {
                return [
                    'success' => false,
                    'message' => 'unable to resolve parent',
                ];
            }

            if (!$me->omitPermissionCheck && !WorkspaceHelper::checkPermission($parent, 'create')) {
                return [
                    'success' => false,
                    'message' => 'not allowed to create ' . $elementType . 'folder ',
                ];
            }

            if ($elementType === 'asset') {
                $newInstance = new Folder();
                $newInstance->setFilename($args['filename']);
            } elseif ($elementType === 'object') {
                $newInstance = new DataObject\Folder();
                $newInstance->setKey($args['key']);
            } elseif ($elementType === 'document') {
                $newInstance = new Document\Folder();
                $newInstance->setKey($args['key']);
            } else {
                throw new Exception('ElementType not supported: ' . $elementType);
            }

            $newInstance->setParentId($parent->getId());

            if (isset($args['userId'])) {
                $newInstance->setUserOwner($args['userId']);
                $newInstance->setUserModification($args['userId']);
            }

            $newInstance->save();

            return [
                'success' => true,
                'message' => 'folder created: ' . $newInstance->getId(),
                'id' => $newInstance->getId(),
            ];
        };
    }

    /**
     * @param string $type
     * @param array $config
     * @param array $context
     */
    public function buildUpdateFolderMutation($type, &$config, $context)
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();

        if (isset($entities[$type . '_folder']['update']) && $entities[$type . '_folder']['update']) {
            // update
            $opName = 'update' . ucfirst($type) . 'Folder';

            $inputFields = [
                'parentId' => ['type' => Type::int()],
            ];
            if ($type === 'asset') {
                $inputFields['filename'] = ['type' => Type::string()];
            } else {
                $inputFields['key'] = ['type' => Type::string()];
            }
            $inputType = new InputObjectType([
                'name' => 'Update' . ucfirst($type) . 'FolderInput',
                'fields' => $inputFields,
            ]);

            $updateResultType = new ObjectType([
                'name' => 'Update' . ucfirst($type) . 'FolderResult',
                'fields' => [
                    'success' => ['type' => Type::boolean()],
                    'message' => ['type' => Type::string()],
                ],
            ]);

            $omitPermissionCheck = $this->omitPermissionCheck;

            $me = $this;
            $updateField = [
                'type' => $updateResultType,
                'args' => [
                    'id' => ['type' => Type::int()],
                    'fullpath' => ['type' => Type::string()],
                    'userId' => ['type' => Type::int()],
                    'input' => ['type' => $inputType],
                ], 'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($type, $omitPermissionCheck, $me) {
                    try {
                        /** @var Configuration $configuration */
                        $configuration = $context['configuration'];
                        $element = $me->getElementByTypeAndIdOrPath($args, $type);

                        if (!$omitPermissionCheck && !WorkspaceHelper::checkPermission($element, 'update')) {
                            return [
                                'success' => false,
                                'message' => 'permission denied.',
                            ];
                        }

                        $inputArgs = $args['input'] ?? [];

                        foreach ($inputArgs as $argKey => $argValue) {
                            $setter = 'set' . ucfirst($argKey);
                            $element->$setter($argValue);
                        }

                        if (isset($args['userId'])) {
                            $element->setUserModification($args['userId']);
                        }

                        $element->save();
                    } catch (Exception $e) {
                        return [
                            'success' => false,
                            'message' => $e->getMessage(),
                        ];
                    }

                    return [
                        'success' => true,
                        'message' => 'hurray',
                        'id' => $element->getId(),
                    ];
                },
            ];

            $config['fields'][$opName] = $updateField;
        }
    }

    /**
     * @param array $config
     * @param array $context
     */
    public function buildDeleteAssetMutation(&$config, $context)
    {
        $this->buildDeleteElementMutation($config, $context, 'asset');
    }

    /**
     * @param array $config
     * @param array $context
     * @param string $type
     */
    public function buildDeleteElementMutation(&$config, $context, $type)
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();

        if (isset($entities[$type]['delete']) && $entities[$type]['delete']) {
            $opName = 'delete' . ucfirst($type);

            $deleteResultType = new ObjectType([
                'name' => 'Delete' . ucfirst($type) . 'Result',
                'fields' => [
                    'success' => ['type' => Type::boolean()],
                    'message' => ['type' => Type::string()],
                ],
            ]);

            $omitPermissionCheck = $this->omitPermissionCheck;

            $me = $this;
            $deleteField = [
                'type' => $deleteResultType,
                'args' => [
                    'id' => ['type' => Type::int()],
                    'fullpath' => ['type' => Type::string()],
                ],
                'resolve' => static function ($value, $args) use ($type, $omitPermissionCheck, $me) {
                    try {
                        $idOrPath = $args['id'] ?? ($args['fullpath'] ?? null);
                        if (!$idOrPath) {
                            return [
                                    'success' => false,
                                    'message' => 'Missing required field id or fullpath to delete the asset.',
                                ];
                        }

                        $element = $me->getElementByTypeAndIdOrPath($args, $type);

                        if (!$omitPermissionCheck && !WorkspaceHelper::checkPermission($element, 'delete')) {
                            return [
                                    'success' => false,
                                    'message' => 'delete ' . $type . ' permission denied.',
                                ];
                        }
                        $result = ['success' => false];
                        $element->delete();

                        $result = [
                                'success' => true,
                                'message' => $type . ' ' . $idOrPath . ' deleted',
                            ];
                    } catch (Exception $e) {
                        $result['message'] = $e->getMessage();
                    }

                    return $result;
                },
            ];

            $config['fields'][$opName] = $deleteField;
        }
    }

    /**
     * @param array $config
     * @param array $context
     */
    public function buildDeleteDocumentMutation(&$config, $context)
    {
        $this->buildDeleteElementMutation($config, $context, 'document');
    }

    /**
     * @param string $type
     * @param array $config
     * @param array $context
     */
    public function buildDeleteFolderMutation($type, &$config, $context)
    {
        /** @var Configuration $configuration */
        $configuration = $context['configuration'];
        $entities = $configuration->getSpecialEntities();

        if (isset($entities[$type . '_folder']['delete']) && $entities[$type . '_folder']['delete']) {
            $opName = 'delete' . ucfirst($type) . 'Folder';

            $deleteResultType = new ObjectType([
                'name' => 'Delete' . ucfirst($type) . 'FolderResult',
                'fields' => [
                    'success' => ['type' => Type::boolean()],
                    'message' => ['type' => Type::string()],
                ],
            ]);

            $omitPermissionCheck = $this->omitPermissionCheck;

            $me = $this;
            $deleteField = [
                'type' => $deleteResultType,
                'args' => [
                    'id' => ['type' => Type::int()],
                    'fullpath' => ['type' => Type::string()],
                ], 'resolve' => static function ($value, $args, $context, ResolveInfo $info) use ($type, $omitPermissionCheck, $me) {
                    try {
                        $id = $args['id'];
                        /** @var Configuration $configuration */
                        $configuration = $context['configuration'];
                        $element = $me->getElementByTypeAndIdOrPath($args, $type);

                        if (!$omitPermissionCheck && !WorkspaceHelper::checkPermission($element, 'delete')) {
                            return [
                                'success' => false,
                                'message' => 'delete ' . $type . ' permission denied.',
                            ];
                        }
                        $element->delete();

                        return [
                            'success' => true,
                            'message' => '',
                        ];
                    } catch (Exception $e) {
                        return [
                            'success' => false,
                            'message' => $e->getMessage(),
                        ];
                    }
                },
            ];

            $config['fields'][$opName] = $deleteField;
        }
    }

    /**
     * @param string $elementType
     *
     * @return callable(mixed $value, array $args, array $context, ResolveInfo $info): mixed
     */
    public function getUpdateFolderResolver($elementType)
    {
        $me = $this;

        return static function ($value, $args, $context, ResolveInfo $info) use ($elementType, $me) {
            $parent = null;

            if (isset($args['parentId'])) {
                $parent = AbstractObject::getById($args['parentId']);
            } elseif (isset($args['path'])) {
                $parent = AbstractObject::getByPath($args['path']);
            }

            if (!$parent) {
                return [
                    'success' => false,
                    'message' => 'unable to resolve parent',
                ];
            }

            if (!$me->omitPermissionCheck && !WorkspaceHelper::checkPermission($parent, 'update')) {
                return [
                    'success' => false,
                    'message' => 'not allowed to create ' . $elementType . 'folder ',
                ];
            }

            if ($elementType === 'asset') {
                $newInstance = new Folder();
                $newInstance->setFilename($args['filename']);
            } else {
                $newInstance = new \OpenDxp\Model\DataObject\Folder();
                $newInstance->setKey($args['key']);
            }
            $newInstance->setParentId($parent->getId());

            $newInstance->save();

            return [
                'success' => true,
                'message' => 'folder created: ' . $newInstance->getId(),
                'id' => $newInstance->getId(),
            ];
        };
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !$this->config['fields'];
    }

    /**
     * @param AbstractElement|Asset|DataObject|Document $element
     * @param array $options
     */
    protected function saveElement($element, $options): void
    {
        if (
            isset($options['userId'])
            && empty($element->getId())
            && method_exists($element, 'setUserOwner')
        ) {
            $element->setUserOwner($options['userId']);
        }

        if (
            isset($options['userId'])
            && method_exists($element, 'setUserModification')
        ) {
            $element->setUserModification($options['userId']);
        }

        $omitVersionCreateBefore = Version::$disabled;

        if (isset($options['omitVersionCreate']) && $options['omitVersionCreate']) {
            Version::disable();
        }

        $element->save();

        if (isset($options['omitVersionCreate']) && $options['omitVersionCreate'] && !$omitVersionCreateBefore) {
            Version::enable();
        }
    }
}
