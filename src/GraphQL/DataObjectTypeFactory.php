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
use OpenDxp\Model\DataObject\ClassDefinition;

class DataObjectTypeFactory
{
    use ServiceTrait;

    public static $registry = [];

    public function __construct(
        Service $graphQlService,
        protected string $className
    ) {
        $this->setGraphQLService($graphQlService);
    }

    /**
     * @return mixed
     */
    public function build(string $className, $config = [], $context = [])
    {
        if (!isset(self::$registry[$className])) {
            $class = ClassDefinition::getByName($className);
            
            // Check if class definition exists to prevent getId() errors
            if (!$class) {
                // Log warning and return null instead of throwing exception
                error_log("Warning: Class definition with name '{$className}' does not exist, skipping");
                return null;
            }
            
            $operatorImpl = new $this->className(
                $this->getGraphQlService(),
                $className,
                $class->getId(),
                $config,
                $context
            );
            self::$registry[$className] = $operatorImpl;
        }

        return self::$registry[$className];
    }
}
