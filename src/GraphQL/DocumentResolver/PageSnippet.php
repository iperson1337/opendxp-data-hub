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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentResolver;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Editable;
use OpenDxp\Model\Document\Editable\Areablock;
use OpenDxp\Model\Document\Editable\BlockInterface;

class PageSnippet
{
    use ServiceTrait;

    /**
     * @param array $value
     * @param array $args
     * @param array $context
     *
     * @return array|null
     *
     * @throws Exception
     */
    public function resolveElements($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $documentId = $value['id'];
        $getInheritedValuesInput = $args['getInheritedValues'] ?? false;
        $document = Document::getById($documentId, ['force' => $getInheritedValuesInput]);

        if ($document instanceof Document\PageSnippet) {
            $result = [];
            $sortBy = [];

            $getInheritedValues = Document\PageSnippet::getGetInheritedValues();
            Document\PageSnippet::setGetInheritedValues($getInheritedValuesInput);

            $elements = $document->getEditables();

            Document\PageSnippet::setGetInheritedValues($getInheritedValues);

            $service = $this->getGraphQlService();
            $supportedTypeNames = $service->getSupportedDocumentElementQueryDataTypes();

            foreach ($elements as $name => $element) {
                $elementType = $element->getType();
                if (in_array($elementType, $supportedTypeNames)) {
                    $result[] = $element;
                    $sortBy[$name] = $this->getElementSortIndex($name, $elements);
                }
            }

            usort($result,
                // "Natural order" comparison so that "10" is ordered after "2"
                fn (Editable $a, Editable $b) => strnatcmp($sortBy[$a->getName()], $sortBy[$b->getName()]));

            return $result;
        }

        return null;
    }

    /**
     * Return a string to sort the elements by to get them in the same order as they are in the blocks.
     *
     * @param string $elementName
     * @param Editable[] $elements
     *
     * @return string
     */
    private function getElementSortIndex($elementName, $elements)
    {
        // "areablock:1.block:2.input" => ["areablock", "1", "block", "2", "input"]
        $parts = preg_split('/:(\d+)\./', $elementName, -1, PREG_SPLIT_DELIM_CAPTURE);

        $sortIndices = [];
        $blockName = '';

        for ($i = 1, $count = count($parts); $i < $count; $i += 2) {
            $blockName .= $parts[$i - 1]; // "areablock"
            $blockKey = $parts[$i]; // "1"

            $block = $elements[$blockName] ?? null;
            if ($block instanceof BlockInterface) {
                $indices = $block->getData();
                if ($block instanceof Areablock) {
                    $indices = array_column($indices, 'key');
                }

                $index = array_search($blockKey, $indices);
                if ($index !== false) {
                    $sortIndices[] = $index;
                }
            }

            $blockName .= ":$blockKey."; // "areablock:1."
        }

        return implode(' ', $sortIndices);
    }
}
