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

namespace OpenDxp\Bundle\DataHubBundle;

use Exception;
use GraphQL\Error\ClientAware;
use Throwable;

class MySafeException extends Exception implements ClientAware
{
    /**
     * @param string|null $category
     * @param string $message
     * @param int $code
     */
    public function __construct(
        protected $category = null,
        $message = '',
        $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function isClientSafe(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category ?: 'datahub';
    }
}
