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

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/** @internal */
final class ResponseService implements ResponseServiceInterface
{
    /** @var list<string> */
    private array $allowedOrigins = [];

    public function __construct(?ContainerBagInterface $container = null)
    {
        if ($container && $container->has('opendxp_data_hub')) {
            $config = $container->get('opendxp_data_hub');
            $origins = $config['graphql']['cors_origins'] ?? [];
            $this->allowedOrigins = array_values(array_filter(array_map(
                static fn ($origin) => rtrim(trim((string) $origin), '/'),
                is_array($origins) ? $origins : [$origins]
            )));
        }
    }

    /**
     * Removes CORS headers including Access-Control-Allow-Origin that should not be cached.
     */
    public function removeCorsHeaders(JsonResponse $response): void
    {
        $response->headers->remove('Access-Control-Allow-Origin');
        $response->headers->remove('Access-Control-Allow-Credentials');
        $response->headers->remove('Access-Control-Allow-Methods');
        $response->headers->remove('Access-Control-Allow-Headers');
        $response->headers->remove('Vary');
    }

    public function addCorsHeaders(JsonResponse $response): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $origin = is_string($origin) ? rtrim($origin, '/') : null;

        // Раньше любой Origin отражался вместе с Allow-Credentials: true — классический
        // «reflect any origin + credentials». Credentials теперь только для origin из белого списка.
        if ($origin && $this->isAllowedOrigin($origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Vary', 'Origin');
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Auth-Token, X-API-Key, apikey, Authorization');
        $response->headers->set('Access-Control-Max-Age', '3600');
    }

    private function isAllowedOrigin(string $origin): bool
    {
        foreach ($this->allowedOrigins as $allowed) {
            if ($allowed === '*' || strcasecmp($allowed, $origin) === 0) {
                return true;
            }
        }

        return false;
    }
}
