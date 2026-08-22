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

namespace OpenDxp\Bundle\DataHubBundle\Command\Configuration;

use Exception;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Model\Tool\SettingsStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateLegacyConfig extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('datahub:configuration:migrate-legacy-config')
            ->setDescription('Migrate legacy configurations (datahub-configurations.php) to YAML or settings store, depending on your configuration.');
    }

    private function loadLegacyConfigs(string $fileName): array
    {
        $file = \OpenDxp\Config::locateConfigFile($fileName);
        $configs = [];

        if (file_exists($file)) {
            $configs = @include $file;
        }

        return $configs;
    }

    private function migrateToSettingsStore(string $id, string $scope, array $configs, bool $overwriteExistingConfig = false): void
    {
        if (count($configs) > 0) {
            $existingConfig = SettingsStore::get($id, $scope);
            if (!$existingConfig || $overwriteExistingConfig) {
                SettingsStore::set($id, json_encode($configs), 'string', $scope);
            }
        }
    }

    private function migrateConfiguration(string $fileName, string $scope): void
    {
        $configs = $this->loadLegacyConfigs($fileName);
        $configs = $configs['list'] ?? [];
        foreach ($configs as $key => $config) {
            $id = $config['general']['name'];
            $this->migrateToSettingsStore((string)$id, $scope, $config);
        }
    }

    /**
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrateConfiguration('datahub-configurations.php', 'opendxp_data_hub');
        if (defined('Symfony\Component\Console\Command\Command::SUCCESS')) {
            return Command::SUCCESS;
        } else {
            return 0;
        }
    }
}
