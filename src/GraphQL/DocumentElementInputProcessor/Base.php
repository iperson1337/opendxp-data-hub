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
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Document\Editable\Loader\EditableLoaderInterface;
use OpenDxp\Model\Document\PageSnippet;

abstract class Base
{
    use ServiceTrait;

    /**
     * @var EditableLoaderInterface
     */
    protected $editableLoader;

    public function __construct(EditableLoaderInterface $editableLoader, Service $graphQlService)
    {
        $this->editableLoader = $editableLoader;
        $this->graphQlService = $graphQlService;
    }

    /**
     * @param PageSnippet $document
     * @param mixed $newValue
     * @param array $args
     * @param mixed $context
     */
    public function process($document, $newValue, $args, $context, ResolveInfo $info)
    {
        $editableName = $newValue['_editableName'];
        $editableType = $newValue['_editableType'];

        $text = $newValue['text'];

        $editable = $this->editableLoader->build($editableType);
        $editable->setName($editableName);
        $editable->setDataFromResource($text);                   // this should be at least valid for input, wysiwyg

        if (method_exists($document, 'setElement')) {
            $document->setElement($editableName, $editable);
        } else {
            $document->setEditable($editable);
        }
    }
}
