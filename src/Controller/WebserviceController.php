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
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Warning;
use GraphQL\Error\ClientAware;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Limits;
use GraphQL\GraphQL;
use GraphQL\Server\RequestError;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use OpenDxp;
use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\ExecutorEvents;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\ExecutorEvent;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\ExecutorResultEvent;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ClassTypeDefinitions;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Mutation\MutationType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Query\QueryType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\OpenDxpDataHubBundle;
use OpenDxp\Bundle\DataHubBundle\Service\CheckConsumerPermissionsService;
use OpenDxp\Bundle\DataHubBundle\Service\FileUploadService;
use OpenDxp\Bundle\DataHubBundle\Service\OutputCacheService;
use OpenDxp\Bundle\DataHubBundle\Service\ResponseServiceInterface;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Controller\FrontendController;
use OpenDxp\Helper\LongRunningHelper;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Logger;
use OpenDxp\Model\Factory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WebserviceController extends FrontendController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CheckConsumerPermissionsService $permissionsService,
        private readonly OutputCacheService $cacheService,
        private readonly FileUploadService $uploadService
    ) {
    }

    /**
     * @return JsonResponse
     *
     * @throws RequestError|Exception
     */
    public function webonyxAction(
        Service $service,
        LocaleServiceInterface $localeService,
        Factory $modelFactory,
        Request $request,
        LongRunningHelper $longRunningHelper,
        ResponseServiceInterface $responseService
    ) {
        $clientname = $request->attributes->getString('clientname');
        $variableValues = null;

        if ($request->isMethod('OPTIONS')) {
            $response = new JsonResponse(null, 204);
            $responseService->addCorsHeaders($response);

            return $response;
        }

        $configuration = Configuration::getByName($clientname);
        if (!$configuration || !$configuration->isActive()) {
            throw new NotFoundHttpException('No active configuration found for ' . $clientname);
        }

        if (!$this->permissionsService->performSecurityCheck($request, $configuration)) {
            throw new AccessDeniedHttpException('Permission denied, apikey not valid');
        }

        if ($response = $this->cacheService->load($request)) {
            Logger::debug('Loading response from cache');

            $responseService->addCorsHeaders($response);

            return $response;
        }

        Logger::debug('Cache entry not found');

        // context info, will be passed on to all resolver function
        $context = ['clientname' => $clientname, 'configuration' => $configuration];

        $config = $this->getParameter('opendxp_data_hub');

        if (isset($config['graphql']) && isset($config['graphql']['not_allowed_policy'])) {
            OpenDxpDataHubBundle::setNotAllowedPolicy($config['graphql']['not_allowed_policy']);
        }

        $longRunningHelper->addOpenDxpRuntimeCacheProtectedItems([OpenDxpDataHubBundle::RUNTIME_CONTEXT_KEY]);
        RuntimeCache::set(OpenDxpDataHubBundle::RUNTIME_CONTEXT_KEY, $context);

        ClassTypeDefinitions::build($service, $context);

        $queryType = new QueryType($service, $localeService, $modelFactory, $this->eventDispatcher, [], $context);
        $mutationType = new MutationType($service, $localeService, $modelFactory, $this->eventDispatcher, [], $context);

        try {
            $schemaConfig = [
                'query' => $queryType,
            ];
            if (!$mutationType->isEmpty()) {
                $schemaConfig['mutation'] = $mutationType;
            }
            $schema = new \GraphQL\Type\Schema(
                $schemaConfig
            );
        } catch (Exception $e) {
            Warning::enable(false);
            $schema = new \GraphQL\Type\Schema(
                [
                    'query' => $queryType,
                    'mutation' => $mutationType,
                ]
            );
            $schema->assertValid();
            Logger::error($e);

            throw $e;
        }

        $contentType = $request->headers->get('content-type') ?? '';

        if (mb_stripos($contentType, 'multipart/form-data') !== false) {
            $input = $this->uploadService->parseUploadedFiles($request);
        } else {
            $input = json_decode($request->getContent(), true);
        }

        $query = $input['query'] ?? '';

        try {
            $rootValue = null;

            // Ограничения глубины/сложности запроса: 0 в конфиге отключает правило.
            $validators = DocumentValidator::allRules();
            $depthLimit = Limits::queryDepth();
            $complexityLimit = Limits::queryComplexity();
            if ($depthLimit > 0) {
                $validators[QueryDepth::class] = new QueryDepth($depthLimit);
            }
            if ($complexityLimit > 0) {
                $validators[QueryComplexity::class] = new QueryComplexity($complexityLimit);
            }

            $event = new ExecutorEvent(
                $request,
                $query,
                $schema,
                $context
            );

            $this->eventDispatcher->dispatch($event, ExecutorEvents::PRE_EXECUTE);

            if ($event->getRequest() instanceof Request) {
                $variableValues = $event->getRequest()->request->all('variables');
            }

            if (!$variableValues) {
                $variableValues = $input['variables'] ?? null;
            }

            $configAllowIntrospection = true;
            if (isset($config['graphql']) && isset($config['graphql']['allow_introspection'])) {
                $configAllowIntrospection = $config['graphql']['allow_introspection'];
            }

            $disableIntrospection = !$configAllowIntrospection || (isset($configuration->getSecurityConfig()['disableIntrospection']) && $configuration->getSecurityConfig()['disableIntrospection']);

            // Правило передаём в набор валидаторов запроса, а не в глобальный реестр
            // DocumentValidator::addRule: тот живёт весь процесс и «залипает» между
            // конфигурациями в worker-режиме.
            $validators[DisableIntrospection::class] = new DisableIntrospection(
                $disableIntrospection ? DisableIntrospection::ENABLED : DisableIntrospection::DISABLED
            );

            $result = GraphQL::executeQuery(
                $event->getSchema(),
                $event->getQuery(),
                $rootValue,
                $event->getContext(),
                $variableValues,
                null,
                null,
                $validators
            );

            $exResult = new ExecutorResultEvent($request, $result);
            $this->eventDispatcher->dispatch($exResult, ExecutorEvents::POST_EXECUTE);
            $result = $exResult->getResult();

            if (OpenDxp::inDebugMode()) {
                $debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
                $output = $result->toArray($debug);
            } else {
                $output = $result->toArray();
            }
        } catch (Exception $e) {
            // Клиенту уходит только текст ClientAware-исключений; всё остальное
            // (ошибки БД с фрагментами SQL, пути ФС) остаётся в логе.
            Logger::error($e);
            $clientSafe = $e instanceof ClientAware && $e->isClientSafe();
            $output = [
                'errors' => [
                    [
                        'message' => $clientSafe || OpenDxp::inDebugMode() ? $e->getMessage() : 'Internal server error',
                    ],
                ],
            ];
        }

        $response = new JsonResponse($output);

        $responseService->removeCorsHeaders($response);
        $this->cacheService->save($request, $response);
        $responseService->addCorsHeaders($response);

        return $response;
    }
}
