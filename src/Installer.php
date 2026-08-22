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

namespace OpenDxp\Bundle\DataHubBundle;

use Exception;
use OpenDxp\Bundle\DataHubBundle\Controller\ConfigController;
use OpenDxp\Db;
use OpenDxp\Extension\Bundle\Installer\Exception\InstallationException;
use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use OpenDxp\Logger;
use OpenDxp\Model\Tool\SettingsStore;
use OpenDxp\Model\User\Permission\Definition;
use Override;

class Installer extends SettingsStoreAwareInstaller
{
    const DATAHUB_PERMISSION_CATEGORY = 'Datahub';

    const DATAHUB_ADAPTER_PERMISSION = 'plugin_datahub_adapter_graphql';

    const DATAHUB_ADMIN_PERMISSION = 'plugin_datahub_admin';

    #[Override]
    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    #[Override]
    public function install(): void
    {
        try {
            // create backend permission
            Definition::create(ConfigController::CONFIG_NAME)->setCategory(self::DATAHUB_PERMISSION_CATEGORY)->save();
            Definition::create(self::DATAHUB_ADAPTER_PERMISSION)->setCategory(self::DATAHUB_PERMISSION_CATEGORY)->save();
            Definition::create(self::DATAHUB_ADMIN_PERMISSION)->setCategory(self::DATAHUB_PERMISSION_CATEGORY)->save();

            $types = ['document', 'asset', 'object'];

            $db = Db::get();
            foreach ($types as $type) {
                $db->executeQuery('
                    CREATE TABLE IF NOT EXISTS `plugin_datahub_workspaces_' . $type . "` (
                        `cid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
                        `cpath` VARCHAR(765) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
                        `configuration` VARCHAR(80) NOT NULL DEFAULT '0',
                        `create` TINYINT(1) UNSIGNED NULL DEFAULT '0',
                        `read` TINYINT(1) UNSIGNED NULL DEFAULT '0',
                        `update` TINYINT(1) UNSIGNED NULL DEFAULT '0',
                        `delete` TINYINT(1) UNSIGNED NULL DEFAULT '0',
                        PRIMARY KEY (`cid`, `configuration`)
                        )
                    COLLATE='utf8mb4_general_ci'
                    ENGINE=InnoDB
                    ;
                ");
            }
        } catch (Exception $e) {
            Logger::warn($e);

            throw new InstallationException($e->getMessage());
        }

        parent::install();
    }

    #[Override]
    public function isInstalled(): bool
    {
        // When switching to SettingsStoreAwareInstaller, we need to explicitly mark this bundle installed, if Settingstore entry doesn't exists and datahub permission is installed
        // e.g. updating from 1.0.* to 1.1.*
        $installEntry = SettingsStore::get($this->getSettingsStoreInstallationId(), 'opendxp');
        if (!$installEntry) {
            $db = Db::get();
            $check = $db->fetchOne('SELECT `key` FROM users_permission_definitions where `key` = ?', [ConfigController::CONFIG_NAME]);
            if ($check) {
                SettingsStore::set('BUNDLE_INSTALLED__OpenDxp\\Bundle\\DataHubBundle\\OpenDxpDataHubBundle', true, 'bool', 'opendxp');

                return true;
            }
        }

        return parent::isInstalled();
    }

    public function getLastMigrationVersionClassName(): ?string
    {
        return null;
    }
}
