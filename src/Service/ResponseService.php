<?php

declare(strict_types=1);

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

use Symfony\Component\HttpFoundation\JsonResponse;

/** @internal */
final class ResponseService implements ResponseServiceInterface
{
    /**
     * Removes CORS headers including Access-Control-Allow-Origin that should not be cached.
     */
    public function removeCorsHeaders(JsonResponse $response): void
    {
        $response->headers->remove('Access-Control-Allow-Origin');
        $response->headers->remove('Access-Control-Allow-Credentials');
        $response->headers->remove('Access-Control-Allow-Methods');
        $response->headers->remove('Access-Control-Allow-Headers');
    }

    public function addCorsHeaders(JsonResponse $response): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

        if ($origin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Auth-Token, X-API-Key, apikey, Authorization');
        $response->headers->set('Access-Control-Max-Age', '3600');
    }
}
