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

namespace OpenDxp\Bundle\DataHubBundle\Service;

use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Concrete;

final class DataObjectByUuidResolver
{
    public function resolve(string $className, string $uuid): ?Concrete
    {
        $className = trim($className);
        $uuid = trim($uuid);
        if ($className === '' || $uuid === '') {
            return null;
        }

        $classDefinition = ClassDefinition::getByName($className);
        if (!$classDefinition || !$classDefinition->getFieldDefinition('uuid')) {
            return null;
        }

        $listingClass = '\\OpenDxp\\Model\\DataObject\\' . $className . '\\Listing';
        if (!class_exists($listingClass)) {
            return null;
        }

        $listing = new $listingClass();
        $listing->setUnpublished(true);
        $listing->setCondition('uuid = ?', [$uuid]);
        $listing->setLimit(1);
        $items = $listing->load();

        $first = $items[0] ?? null;

        return $first instanceof Concrete ? $first : null;
    }
}
