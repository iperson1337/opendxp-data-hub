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

use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Logger;
use Symfony\Component\HttpFoundation\Request;

class CheckConsumerPermissionsService
{
    public const string TOKEN_HEADER = 'X-API-Key';

    /**
     * @deprecated the query-string parameter `apikey` is deprecated, use the X-API-Key header instead
     */
    public const string QUERY_PARAM = 'apikey';

    private static bool $queryParamDeprecationLogged = false;

    public function __construct(
        private readonly ApiKeyServiceInterface $apiKeyService
    ) {
    }

    public function performSecurityCheck(Request $request, Configuration $configuration): bool
    {
        $securityConfig = $configuration->getSecurityConfig();
        if (($securityConfig['method'] ?? '') !== Configuration::SECURITYCONFIG_AUTH_APIKEY) {
            return false;
        }

        $apiKey = $this->extractApiKey($request);
        if ($apiKey === '') {
            return false;
        }

        // Keys stored in the database (plugin_datahub_api_keys)
        $valid = $this->apiKeyService->validateApiKey((string) $configuration->getName(), $apiKey);

        // Legacy fallback: keys still present in the (YAML) security config
        $legacyKeys = $this->getLegacyKeys($securityConfig);
        if ($legacyKeys !== []) {
            // evaluate both, no short-circuit
            $valid = (bool) ((int) $valid | (int) $this->matchesAny($apiKey, $legacyKeys));
        }

        return $valid;
    }

    private function extractApiKey(Request $request): string
    {
        $apiKey = (string) $request->headers->get('apikey', '');
        if ($apiKey === '') {
            $apiKey = (string) $request->headers->get(static::TOKEN_HEADER, '');
        }
        if ($apiKey === '') {
            $apiKey = $request->query->getString(self::QUERY_PARAM);
            if ($apiKey !== '' && !self::$queryParamDeprecationLogged) {
                self::$queryParamDeprecationLogged = true;
                Logger::warning(
                    'DEPRECATED: passing the datahub API key via the query-string parameter "apikey" is deprecated '
                    . 'and will be removed in a future release. Send it in the "' . static::TOKEN_HEADER . '" header instead.'
                );
            }
        }

        return $apiKey;
    }

    /**
     * @return string[]
     */
    private function getLegacyKeys(array $securityConfig): array
    {
        $keys = $securityConfig['apikey'] ?? [];
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        return array_values(array_filter(
            array_map(static fn ($key): string => is_scalar($key) ? (string) $key : '', $keys),
            static fn (string $key): bool => $key !== ''
        ));
    }

    /**
     * Constant-time comparison against every key (no early return).
     *
     * @param string[] $keys
     */
    private function matchesAny(string $candidate, array $keys): bool
    {
        $matched = 0;
        foreach ($keys as $key) {
            $matched |= (int) hash_equals($key, $candidate);
        }

        return $matched === 1;
    }
}
