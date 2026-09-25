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

namespace OpenDxp\Bundle\DataHubBundle\Migrations\PimcoreX;

use Doctrine\DBAL\Schema\Schema;
use OpenDxp\Migrations\BundleAwareMigration;
use OpenDxp\Model\Tool\SettingsStore;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20210305134111 extends BundleAwareMigration
{
    protected function getBundleName(): string
    {
        return 'OpenDxpDataHubBundle';
    }

    protected function checkBundleInstalled(): bool
    {
        //need to always return true here, as the migration is setting the bundle installed
        return true;
    }

    public function up(Schema $schema): void
    {
        // scope 'opendxp', не 'pimcore': id переименовали при порте, а аргумент scope — нет.
        // SettingsStoreAwareInstaller::isInstalled() читает именно 'opendxp', поэтому со старым
        // значением бандл навсегда числился неустановленным. Свип неймспейсов литералы не видит.
        SettingsStore::set('BUNDLE_INSTALLED__OpenDxp\\Bundle\\DataHubBundle\\OpenDxpDataHubBundle', true, 'bool', 'opendxp');
    }

    public function down(Schema $schema): void
    {
        // nothing to do
    }
}
