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

namespace OpenDxp\Bundle\DataHubBundle\Migrations\PimcoreX;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230329133119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'rename default dir for symfony-config files';
    }

    public function up(Schema $schema): void
    {
        $this->renameConfigFolder('data-hub', '-', '_');
    }

    public function down(Schema $schema): void
    {
        $this->renameConfigFolder('data_hub', '_', '-');
    }

    private function renameConfigFolder(string $folder, string $search, string $replace): void
    {
        // \Pimcore в OpenDXP не существует — глобальный класс называется \OpenDxp.
        // Свип порта ищет «Pimcore\» (с обратным слэшем ПОСЛЕ слова) и это место не видел:
        // здесь слэш стоит ПЕРЕД. Ни php -l, ни статанализ не ловят — падает только при
        // выполнении миграции, то есть на деплое.
        $configDir = \OpenDxp::getContainer()->getParameter('kernel.project_dir') . '/var/config/';
        if (is_dir($configDir . $folder)) {
            rename($configDir . $folder, $configDir . str_replace($search, $replace, $folder));
        }
    }
}
