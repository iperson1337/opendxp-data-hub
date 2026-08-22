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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementInputProcessor;

use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Model\Document\PageSnippet;
use Override;

class Embed extends Base
{
    /**
     * @param PageSnippet $document
     * @param mixed $newValue
     * @param array $args
     * @param mixed $context
     */
    #[Override]
    public function process($document, $newValue, $args, $context, ResolveInfo $info)
    {
        $editableName = $newValue['_editableName'];
        $editableType = $newValue['_editableType'];

        $url = $newValue['url'];

        /** @var \OpenDxp\Model\Document\Editable\Embed $editable */
        $editable = $this->editableLoader->build($editableType);
        $editable->setName($editableName);
        $editable->setDataFromResource(serialize(['url' => $url]));                   // this should be at least valid for input, wysiwyg

        if (method_exists($document, 'setElement')) {
            $document->setElement($editableName, $editable);
        } else {
            $document->setEditable($editable);
        }
    }
}
