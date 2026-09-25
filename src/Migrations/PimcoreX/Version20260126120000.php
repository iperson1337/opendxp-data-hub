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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataHubBundle\Migrations\PimcoreX;

use Doctrine\DBAL\Schema\Schema;
use OpenDxp\Migrations\BundleAwareMigration;

final class Version20260126120000 extends BundleAwareMigration
{
    protected function getBundleName(): string
    {
        return 'OpenDxpDataHubBundle';
    }

    public function getDescription(): string
    {
        return 'Create plugin_datahub_api_keys table for storing API keys separately from configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS `plugin_datahub_api_keys` (
                `config_name` VARCHAR(80) NOT NULL,
                `api_keys` JSON NOT NULL,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`config_name`)
            )
            COLLATE=\'utf8mb4_general_ci\'
            ENGINE=InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS `plugin_datahub_api_keys`');
    }
}
