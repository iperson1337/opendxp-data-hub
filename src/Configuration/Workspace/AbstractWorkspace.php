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

namespace OpenDxp\Bundle\DataHubBundle\Configuration\Workspace;

use OpenDxp\Model\AbstractModel;

/**
 * @method \OpenDxp\Bundle\DataHubBundle\Configuration\Workspace\Dao getDao()
 * @method void save()
 */
abstract class AbstractWorkspace extends AbstractModel
{
    /**
     * @var string
     */
    public $configuration;

    /**
     * @var int
     */
    public $cid;

    /**
     * @var string
     */
    public $cpath;

    /**
     * @var bool
     */
    public $create = false;

    /**
     * @var bool
     */
    public $read = false;

    /**
     * @var bool
     */
    public $update = false;

    /**
     * @var bool
     */
    public $delete = false;

    public function getConfiguration(): string
    {
        return $this->configuration;
    }

    public function setConfiguration(string $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function setCid(int $cid): void
    {
        $this->cid = $cid;
    }

    public function getCpath(): string
    {
        return $this->cpath;
    }

    public function setCpath(string $cpath): void
    {
        $this->cpath = $cpath;
    }

    public function isCreate(): bool
    {
        return $this->create;
    }

    public function setCreate(bool $create): void
    {
        $this->create = $create;
    }

    /**
     * @return bool
     */
    public function getRead()
    {
        return $this->read;
    }

    public function setRead(bool $read): void
    {
        $this->read = $read;
    }

    public function isUpdate(): bool
    {
        return $this->update;
    }

    public function setUpdate(bool $update): void
    {
        $this->update = $update;
    }

    public function isDelete(): bool
    {
        return $this->delete;
    }

    public function setDelete(bool $delete): void
    {
        $this->delete = $delete;
    }
}
