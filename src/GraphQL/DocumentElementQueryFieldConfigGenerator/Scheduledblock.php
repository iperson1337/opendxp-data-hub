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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementQueryFieldConfigGenerator;

use OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType\ScheduledblockDataType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType\ScheduledblockType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;

class Scheduledblock extends Base
{
    /** @var ScheduledblockDataType */
    protected $scheduledblockDataType;

    public function __construct(Service $graphQlService, ScheduledblockDataType $scheduledblockDataType)
    {
        $this->scheduledblockDataType = $scheduledblockDataType;
        parent::__construct($graphQlService);
    }

    /**
     * @return ScheduledblockType
     */
    public function getFieldType()
    {
        return \OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType\ScheduledblockType::getInstance($this->scheduledblockDataType);
    }
}
