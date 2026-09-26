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

final class Version20221212152145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace childs with children in configs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE settings_store SET data=REPLACE(data, \'"childs":\', \'"children":\') WHERE scope IN (\'opendxp_data_hub\',\'pimcore_data_hub\');');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE settings_store SET data=REPLACE(data, \'"children":\', \'"childs":\') WHERE scope IN (\'opendxp_data_hub\',\'pimcore_data_hub\');');
    }
}
