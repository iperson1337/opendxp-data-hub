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

use Exception;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType\VideoType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;

class Video extends Base
{
    /**
     * @throws Exception
     */
    public function __construct(Service $graphQlService)
    {

        //        $this->assetType = $assetType;
        parent::__construct($graphQlService);
    }

    /**
     * @return VideoType
     */
    public function getFieldType()
    {
        $service = $this->getGraphQlService();
        $assetType = $service->buildAssetType('asset');

        return \OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType\VideoType::getInstance($this->getGraphQlService(), $assetType);
    }
}
