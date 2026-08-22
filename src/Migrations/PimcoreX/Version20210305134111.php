<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
        SettingsStore::set('BUNDLE_INSTALLED__OpenDxp\\Bundle\\DataHubBundle\\OpenDxpDataHubBundle', true, 'bool', 'pimcore');
    }

    public function down(Schema $schema): void
    {
        // nothing to do
    }
}
