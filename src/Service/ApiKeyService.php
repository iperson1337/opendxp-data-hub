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

use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use JsonException;
use OpenDxp\Db;

/**
 * Stores the API keys of datahub configurations in the table `plugin_datahub_api_keys`
 * (created by Migrations\PimcoreX\Version20260126120000 and by the Installer).
 *
 * SECURITY NOTE: keys are stored in PLAINTEXT (as a JSON array), because the admin UI has to
 * display the configured keys back to the user. The table `plugin_datahub_api_keys` must therefore
 * be treated as a secret store: restrict database access to it, never include it in exports,
 * dumps or backups that are shared with third parties, and never log its content.
 *
 * Key comparison is done in constant time (hash_equals) against every stored key.
 */
class ApiKeyService implements ApiKeyServiceInterface
{
    public const TABLE_NAME = 'plugin_datahub_api_keys';

    public const MIN_KEY_LENGTH = 16;

    public function __construct(
        private ?Connection $db = null
    ) {
    }

    public function validateApiKey(string $configName, string $apiKey): bool
    {
        if ($apiKey === '') {
            return false;
        }

        return self::matchesAny($apiKey, $this->getApiKeys($configName));
    }

    public function getApiKeys(string $configName): array
    {
        $json = $this->getDb()->fetchOne(
            'SELECT `api_keys` FROM `' . self::TABLE_NAME . '` WHERE `config_name` = ?',
            [$configName]
        );

        if ($json === false || $json === null || $json === '') {
            return [];
        }

        try {
            $keys = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (!is_array($keys)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($key): string => is_scalar($key) ? (string) $key : '', $keys),
            static fn (string $key): bool => $key !== ''
        ));
    }

    public function saveApiKeys(string $configName, array $apiKeys): void
    {
        $normalized = [];
        foreach ($apiKeys as $apiKey) {
            if (!is_scalar($apiKey)) {
                throw new InvalidArgumentException('API keys must be strings');
            }
            $apiKey = trim((string) $apiKey);
            if ($apiKey === '') {
                continue;
            }
            if (strlen($apiKey) < self::MIN_KEY_LENGTH) {
                throw new InvalidArgumentException(
                    sprintf('API key does not satisfy the minimum length of %d characters', self::MIN_KEY_LENGTH)
                );
            }
            $normalized[] = $apiKey;
        }

        $normalized = array_values(array_unique($normalized));

        if ($normalized === []) {
            $this->deleteApiKeys($configName);

            return;
        }

        $json = json_encode($normalized, JSON_THROW_ON_ERROR);
        $db = $this->getDb();

        $affected = $db->executeStatement(
            'UPDATE `' . self::TABLE_NAME . '` SET `api_keys` = ?, `updated_at` = NOW() WHERE `config_name` = ?',
            [$json, $configName]
        );

        if ($affected === 0) {
            $exists = $db->fetchOne(
                'SELECT 1 FROM `' . self::TABLE_NAME . '` WHERE `config_name` = ?',
                [$configName]
            );
            if (!$exists) {
                $db->executeStatement(
                    'INSERT INTO `' . self::TABLE_NAME . '` (`config_name`, `api_keys`, `updated_at`) VALUES (?, ?, NOW())',
                    [$configName, $json]
                );
            }
        }
    }

    public function deleteApiKeys(string $configName): void
    {
        $this->getDb()->executeStatement(
            'DELETE FROM `' . self::TABLE_NAME . '` WHERE `config_name` = ?',
            [$configName]
        );
    }

    /**
     * Constant-time check whether the candidate equals any of the given keys.
     * Every key is compared (no early return) so that timing does not reveal the position of a match.
     *
     * @param string[] $keys
     */
    public static function matchesAny(string $candidate, array $keys): bool
    {
        $matched = false;
        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }
            // bitwise OR keeps evaluating hash_equals for every key
            $matched = hash_equals($key, $candidate) | $matched;
        }

        return (bool) $matched;
    }

    private function getDb(): Connection
    {
        if ($this->db === null) {
            $this->db = Db::get();
        }

        return $this->db;
    }
}
