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
use OpenDxp\Bundle\DataHubBundle\GraphQL\Mutation\MutationType;
use OpenDxp\Model\Document\PageSnippet;
use Override;

class Scheduledblock extends Base
{
    use EditablesTrait;

    /**
     * @param PageSnippet $document
     * @param mixed $newValue
     * @param array $args
     * @param mixed $context
     */
    #[Override]
    public function process($document, $newValue, $args, $context, ResolveInfo $info)
    {
        $editableType = $newValue['_editableType'];

        $editable = $this->editableLoader->build($editableType);

        $editableName = $newValue['_editableName'];
        $editable->setName($editableName);

        $typeCache = &MutationType::$typeCache;

        $indices = [];
        if (is_array($newValue)) {
            if (array_key_exists('indices', $newValue)) {
                $indices = $newValue['indices'];
            }
        }

        $idx = 0;
        if (isset($newValue['items'])) {
            foreach ($newValue['items'] as $blockItem) {
                $editables = $blockItem['editables'] ?? [];
                $date = $blockItem['date'];

                if ($blockItem['replace'] ?? true) {
                    $this->cleanEditables($document, $editableName . ':' . ($idx + 1));
                }

                $indices[$idx] = [
                    'key' => $idx,
                    'date' => $date,
                ];

                foreach ($editables as $editableType => $listByType) {
                    foreach ($listByType as $editableData) {
                        $editableData['_editableName'] = $editableName . ':' . ($idx + 1) . '.' . $editableData['_editableName'];
                        $editableData['_editableType'] = $editableType;
                        $typeDefinition = $typeCache[$editableType];
                        $processor = $typeDefinition['processor'];
                        call_user_func_array($processor, [$document, $editableData, $args, $context, $info]);
                    }
                }

                $idx++;
            }
        }

        ksort($indices);

        $editable->setDataFromEditmode($indices);

        if (method_exists($document, 'setElement')) {
            $document->setElement($editableName, $editable);
        } else {
            $document->setEditable($editable);
        }
    }
}
