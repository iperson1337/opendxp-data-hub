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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use Override;

class Fieldcollections extends Base
{
    /**
     * @var array
     */
    protected $processors;

    public function __construct(array $nodeDef, array $processors)
    {
        parent::__construct($nodeDef);
        $this->processors = $processors;
    }

    /**
     * @param Concrete|AbstractData $object
     * @param array $newValue
     * @param array $args
     * @param array $context
     *
     * @throws Exception
     */
    #[Override]
    public function process($object, $newValue, $args, $context, ResolveInfo $info)
    {
        $attribute = $this->getAttribute();
        $getter = 'get' . ucfirst((string) $attribute);
        $setter = 'set' . ucfirst((string) $attribute);
        $currentCollection = $object->$getter();
        if ($currentCollection instanceof Fieldcollection) {
            $currentItems = $currentCollection->getItems() ?: [];
        } else {
            $currentItems = [];
        }

        // auto increment on group level!
        $autoIdx = 0;
        $newItems = [];

        if (! ($newValue['replace'] ?? false)) {
            foreach ($currentItems as $currentItem) {
                $newItems[$currentItem->getIndex()] = $currentItem;
            }
        }

        $itemGroups = $newValue['items'];
        foreach ($itemGroups as $fcKey => $groupItems) {
            $typeProcessor = $this->processors[$fcKey] ?? [];
            foreach ($groupItems as $groupItemData) {
                if (array_key_exists('index', $groupItemData)) {
                    $index = $groupItemData['index'];
                } else {
                    $index = $autoIdx;
                    $autoIdx++;
                }

                $fc = $newItems[$index] ?? null;

                if (!$fc || $fc->getType() != $fcKey) {
                    // either index does not exist or type does not match
                    $modelFactory = $this->getGraphQlService()->getModelFactory();
                    $className = 'OpenDxp\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst((string) $fcKey);
                    /** @var AbstractData $fc */
                    $fc = $modelFactory->build($className);
                }
                $fc->setIndex($index);

                foreach ($groupItemData as $key => $value) {
                    if (isset($typeProcessor[$key])) {
                        $processor = $typeProcessor[$key];
                        call_user_func_array($processor, [$fc, $value, $args, $context, $info]);
                    }
                }
                $newItems[$index] = $fc;
            }
        }

        ksort($newItems);
        $newCollection = new Fieldcollection();
        $newCollection->setItems($newItems);
        $object->$setter($newCollection);
    }
}
