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

use OpenDxp\Bundle\DataHubBundle\Configuration;

class ExportService
{
    public function exportConfigurationJson(Configuration $configuration): string
    {
        $configuration = clone $configuration;
        $data = json_decode(json_encode($configuration));

        unset(
            $data->configuration->general->modificationDate,
            $data->configuration->general->createDate,
        );

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
