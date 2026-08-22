<?php

declare(strict_types=1);

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

use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

class GeneralTypeFactory
{
    use ServiceTrait;

    public static $registry = [];

    /**
     * @var string
     */
    protected $className;

    public function __construct(Service $graphQlService, string $className)
    {
        $this->className = $className;
        $this->setGraphQLService($graphQlService);
    }

    /**
     * @return mixed
     */
    public function build()
    {
        if (!isset(self::$registry[$this->className])) {
            $operatorImpl = new $this->className($this->getGraphQlService());
            self::$registry[$this->className] = $operatorImpl;
        }

        return self::$registry[$this->className];
    }
}
