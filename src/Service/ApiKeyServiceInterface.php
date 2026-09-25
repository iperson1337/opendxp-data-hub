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

use InvalidArgumentException;

/**
 * Storage and validation of API keys of a datahub configuration.
 */
interface ApiKeyServiceInterface
{
    /**
     * Checks whether the given key is one of the keys stored for the configuration.
     * Implementations must compare in constant time (hash_equals) against every stored key.
     */
    public function validateApiKey(string $configName, string $apiKey): bool;

    /**
     * Returns the stored key list (for display in the admin UI).
     *
     * @return string[]
     */
    public function getApiKeys(string $configName): array;

    /**
     * Replaces the stored key list of the configuration.
     *
     * @param string[] $apiKeys
     *
     * @throws InvalidArgumentException if a key does not satisfy the minimum length
     */
    public function saveApiKeys(string $configName, array $apiKeys): void;

    /**
     * Removes all keys of the configuration.
     */
    public function deleteApiKeys(string $configName): void;
}
