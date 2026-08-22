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

namespace OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model;

use OpenDxp\Model\DataObject\OwnerAwareFieldInterface;
use OpenDxp\Model\Element\ElementInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PermissionEvent extends Event
{
    /**
     * @var bool
     */
    protected $isGranted = true;

    /**
     * @return OwnerAwareFieldInterface|ElementInterface
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @param OwnerAwareFieldInterface|ElementInterface $element
     */
    public function setElement($element): void
    {
        $this->element = $element;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isGranted(): bool
    {
        return $this->isGranted;
    }

    public function setIsGranted(bool $isGranted): void
    {
        $this->isGranted = $isGranted;
    }

    /**
     * @param ElementInterface|OwnerAwareFieldInterface $element
     * @param string $type
     */
    public function __construct(
        protected $element,
        protected $type
    ) {
    }
}
