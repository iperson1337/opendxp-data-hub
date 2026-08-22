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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentType;

use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;

class PageType extends PageSnippetType
{
    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, DocumentElementType $documentElementType, $config = ['name' => 'document_page'], $context = [])
    {
        parent::__construct($graphQlService, $documentElementType, $config, $context);
    }
}
