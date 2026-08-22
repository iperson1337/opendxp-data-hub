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

use OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType\AreablockDataType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType\AreablockType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;

class Areablock extends Base
{
    protected $areablockDataType;

    public function __construct(Service $graphQlService, AreablockDataType $areablockDataType)
    {
        $this->areablockDataType = $areablockDataType;
        parent::__construct($graphQlService);
    }

    /**
     * @return AreablockType
     */
    public function getFieldType()
    {
        return \OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType\AreablockType::getInstance($this->areablockDataType);
    }
}
