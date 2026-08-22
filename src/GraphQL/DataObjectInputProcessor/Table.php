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
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use Override;

class Table extends Base
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
     * @param mixed $newValue
     * @param array $args
     * @param array $context
     *
     * @throws Exception
     */
    #[Override]
    public function process($object, $newValue, $args, $context, ResolveInfo $info)
    {
        $attribute = $this->getAttribute();
        $objectBrickParts = Service::parseObjectBrickFieldName($attribute);

        if (empty($objectBrickParts)) {
            $getter = 'get' . ucfirst((string) $attribute);
            $currentTable = $object->$getter();
        } else {
            $currentTable = Service::getValueFromObjectBrick(
                $object,
                $objectBrickParts['brickType'],
                $objectBrickParts['brickKey'],
                $objectBrickParts['brickDescriptor'] ?? null
            );
        }

        Service::setValue($object, $attribute, function ($container, $setter) use ($newValue, $currentTable) {
            $newTable = [];

            if ($newValue === null) {
                return $container->$setter($currentTable);
            }

            if (! ($newValue['replace'] ?? false)) {
                if (count($currentTable) > 0) {
                    foreach ($currentTable as $row) {
                        $newTable[] = $row;
                    }
                } elseif ($tableHeader = $this->processors['tableHeader']) {
                    $newTable[] = $tableHeader;
                }
            } elseif ($tableHeader = $this->processors['tableHeader']) {
                $newTable[] = $tableHeader;
            }

            if (is_array($newValue['rows'])) {
                foreach ($newValue['rows'] as $row) {
                    $values = array_values($row);
                    $newTable[] = $values;
                }

                return $container->$setter($newTable);
            }

            return null;
        });
    }
}
