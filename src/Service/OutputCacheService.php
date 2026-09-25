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

namespace OpenDxp\Bundle\DataHubBundle\Service;

use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use OpenDxp;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\OutputCachePreLoadEvent;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\OutputCachePreSaveEvent;
use OpenDxp\Bundle\DataHubBundle\Event\GraphQL\OutputCacheEvents;
use OpenDxp\Logger;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OutputCacheService
{
    /**
     * @var bool
     */
    private $cacheEnabled = false;

    /**
     * The cached items lifetime in seconds
     *
     * @var int
     */
    private $lifetime = 30;

    /**
     * @var EventDispatcherInterface
     */
    public $eventDispatcher;

    public function __construct(ContainerBagInterface $container, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        $dataHubConfig = $container->get('opendxp_data_hub');
        if (isset($dataHubConfig['graphql'])) {
            if (isset($dataHubConfig['graphql']['output_cache_enabled'])) {
                $this->cacheEnabled = filter_var($dataHubConfig['graphql']['output_cache_enabled'], FILTER_VALIDATE_BOOLEAN);
            }

            if (isset($dataHubConfig['graphql']['output_cache_lifetime'])) {
                $this->lifetime = intval($dataHubConfig['graphql']['output_cache_lifetime']);
            }
        }
    }

    /**
     * @return mixed
     */
    public function load(Request $request)
    {
        if (!$this->useCache($request)) {
            return null;
        }

        $cacheKey = $this->computeKey($request);

        return $this->loadFromCache($cacheKey);
    }

    /**
     * @param array $extraTags
     */
    public function save(Request $request, JsonResponse $response, $extraTags = []): void
    {
        if ($this->useCache($request) && $this->isCacheableResponse($response)) {
            $clientname = $request->attributes->getString('clientname');
            $extraTags = array_merge(['output', 'datahub', $clientname], $extraTags);

            $cacheKey = $this->computeKey($request);

            $event = new OutputCachePreSaveEvent($request, $response);
            $this->eventDispatcher->dispatch($event, OutputCacheEvents::PRE_SAVE);

            $this->saveToCache($cacheKey, $response, $extraTags);
        }
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function loadFromCache($key)
    {
        return \OpenDxp\Cache::load($key);
    }

    /**
     * @param string $key
     * @param mixed $item
     * @param array $tags
     */
    protected function saveToCache($key, $item, $tags = []): void
    {
        \OpenDxp\Cache::save($item, $key, $tags, $this->lifetime);
    }

    private function computeKey(Request $request): string
    {
        $clientname = $request->attributes->getString('clientname');
        $input = $this->readInput($request);

        // В ключ входят query, variables и operationName — раньше variables, пришедшие
        // формой (не JSON-телом), в ключ не попадали, и разные запросы делили один кэш.
        $keyData = [
            'query' => $input['query'] ?? '',
            'variables' => $input['variables'] ?? null,
            'operationName' => $input['operationName'] ?? null,
        ];

        return md5('output_' . $clientname . json_encode($keyData));
    }

    /**
     * @return array<string, mixed>
     */
    private function readInput(Request $request): array
    {
        $input = [];
        $content = $request->getContent();
        if (is_string($content) && $content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }
        if (!isset($input['query']) && $request->request->has('query')) {
            $input['query'] = $request->request->get('query');
        }
        if (!isset($input['variables']) && $request->request->has('variables')) {
            $variables = $request->request->all()['variables'] ?? null;
            $input['variables'] = is_string($variables) ? json_decode($variables, true) : $variables;
        }
        if (!isset($input['operationName']) && $request->request->has('operationName')) {
            $input['operationName'] = $request->request->get('operationName');
        }

        return $input;
    }

    /**
     * Кэшируем только операции `query`: мутация с тем же телом в течение TTL
     * иначе не выполнялась, а отдавала кэшированный `success: true`.
     * Multipart-запросы (загрузка файлов) не кэшируются вовсе.
     */
    private function isCacheableRequest(Request $request): bool
    {
        if (mb_stripos((string) $request->headers->get('content-type', ''), 'multipart/form-data') !== false) {
            return false;
        }

        $query = $this->readInput($request)['query'] ?? null;
        if (!is_string($query) || trim($query) === '') {
            return false;
        }

        try {
            $document = Parser::parse($query);
        } catch (\Throwable) {
            return false;
        }

        foreach ($document->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode && $definition->operation !== 'query') {
                return false;
            }
        }

        return true;
    }

    /**
     * Ответы с ошибками (в том числе транзиентными) в кэш не попадают.
     */
    private function isCacheableResponse(JsonResponse $response): bool
    {
        if (!$response->isSuccessful()) {
            return false;
        }

        $payload = json_decode((string) $response->getContent(), true);

        return is_array($payload) && empty($payload['errors']);
    }

    private function useCache(Request $request): bool
    {
        if (!$this->cacheEnabled) {
            Logger::debug('Output cache is disabled');

            return false;
        }

        if (!$this->isCacheableRequest($request)) {
            return false;
        }

        if (OpenDxp::inDebugMode()) {
            $disableCacheForSingleRequest = filter_var($request->query->get('opendxp_nocache', 'false'), FILTER_VALIDATE_BOOLEAN)
            || filter_var($request->query->get('opendxp_outputfilters_disabled', 'false'), FILTER_VALIDATE_BOOLEAN);

            if ($disableCacheForSingleRequest) {
                Logger::debug('Output cache is disabled for this request');

                return false;
            }
        }

        // So far, cache will be used, unless the listener denies it
        $event = new OutputCachePreLoadEvent($request, true);
        $this->eventDispatcher->dispatch($event, OutputCacheEvents::PRE_LOAD);

        return $event->isUseCache();
    }
}
