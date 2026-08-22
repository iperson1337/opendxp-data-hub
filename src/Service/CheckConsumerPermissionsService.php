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

use App\Service\DataHub\ApiKeyService;
use OpenDxp\Bundle\DataHubBundle\Configuration;
use Symfony\Component\HttpFoundation\Request;

class CheckConsumerPermissionsService
{
    public const string TOKEN_HEADER = 'X-API-Key';

    public function __construct(
        private readonly ApiKeyService $apiKeyService
    ) {
    }

    public function performSecurityCheck(Request $request, Configuration $configuration): bool
    {
        $securityConfig = $configuration->getSecurityConfig();
        if (($securityConfig['method'] ?? '') === Configuration::SECURITYCONFIG_AUTH_APIKEY) {
            $apiKey = $request->headers->get('apikey');
            if (empty($apiKey)) {
                $apiKey = $request->headers->get(static::TOKEN_HEADER);
            }
            if (empty($apiKey)) {
                $apiKey = $request->query->getString('apikey');
            }

            if (empty($apiKey)) {
                return false;
            }

            // Strictly read API keys from database only
            return $this->apiKeyService->validateApiKey($configuration->getName(), $apiKey);
        }

        return false;
    }
}
