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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL;

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;

class ElementDescriptor extends BaseDescriptor
{
    public function __construct(?ElementInterface $element = null)
    {
        parent::__construct();
        if ($element) {
            $this->offsetSet('id', $element->getId());
            $this->offsetSet('__elementType', \OpenDxp\Model\Element\Service::getElementType($element));
            $this->offsetSet('__elementSubtype', $element instanceof Concrete ? $element->getClass()->getName() : $element->getType());

            if ($element instanceof Concrete) {
                $subtype = $element->getClass()->getName();

                $this->offsetSet('__elementType', 'object');
                $this->offsetSet('__elementSubtype', $subtype);
            } elseif ($element instanceof Asset) {
                $this->offsetSet('__elementType', 'asset');
                $this->offsetSet('__elementSubtype', $element->getType());
            } elseif ($element instanceof Document) {
                $this->offsetSet('__elementType', 'document');
                $this->offsetSet('__elementSubtype', $element->getType());
            }
        }
    }
}
