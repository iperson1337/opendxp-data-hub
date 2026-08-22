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

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Document;

class DocumentType extends UnionType
{
    use ServiceTrait;

    protected $types;

    /**
     * @var EmailType
     */
    protected $emailType;

    /**
     * @var LinkType
     */
    protected $linkType;

    /**
     * @var SnippetType
     */
    protected $snippetType;

    /**
     * @var HardlinkType
     */
    protected $hardlinkType;

    /**
     * @var PageType
     */
    protected $pageType;

    /**
     * @var array
     */
    protected $customTypes;

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, PageType $pageType, LinkType $linkType, EmailType $emailType, HardlinkType $hardlinkType, SnippetType $snippetType, $config = [])
    {
        $this->pageType = $pageType;
        $this->hardlinkType = $hardlinkType;
        $this->linkType = $linkType;
        $this->emailType = $emailType;
        $this->snippetType = $snippetType;

        $this->types = [$emailType, $hardlinkType, $linkType, $pageType, $snippetType];
        $this->setGraphQLService($graphQlService);

        parent::__construct($config);
    }

    /**
     * @throws Exception
     */
    public function getTypes(): array
    {
        $folderType = $this->getGraphQlService()->getDocumentTypeDefinition('_document_folder');

        return array_merge($this->types, [$folderType], $this->customTypes);
    }

    /**
     * @param array $customDataTypes
     */
    public function registerCustomDataType($customDataTypes)
    {
        $this->customTypes = $customDataTypes;
    }

    /**
     * @return array
     */
    public function getCustomDataTypes()
    {
        return $this->customTypes;
    }

    public function resolveType($element, $context, ResolveInfo $info)
    {
        $element = Document::getById($element['id']);
        if ($element instanceof Document\Page) {
            return $this->pageType;
        } elseif ($element instanceof Document\Link) {
            return $this->linkType;
        } elseif ($element instanceof Document\Email) {
            return $this->emailType;
        } elseif ($element instanceof Document\Hardlink) {
            return $this->hardlinkType;
        } elseif ($element instanceof Document\Snippet) {
            return $this->snippetType;
        } elseif ($element instanceof Document\Folder) {
            return $this->getGraphQlService()->getDocumentTypeDefinition('_document_folder');
        }

        return null;
    }
}
