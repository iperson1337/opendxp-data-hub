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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\FieldHelper;

use Error;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use Override;

class DocumentFieldHelper extends AbstractFieldHelper
{
    /**
     * @param array $data
     * @param Asset $container
     * @param array $args
     * @param array $context
     * @param ResolveInfo $resolveInfo
     */
    #[Override]
    public function doExtractData(FieldNode $ast, &$data, $container, $args, $context, $resolveInfo = null)
    {
        $astName = $ast->name->value;

        // sometimes we just want to expand relations just to throw them away afterwards because not requested
        if ($this->skipField($container, $astName)) {
            return;
        }

        $getter = 'get' . ucfirst((string) $astName);
        $arguments = $this->getArguments($ast);
        $languageArgument = $arguments['language'] ?? null;

        $realName = $astName;

        if (method_exists($container, $getter)) {
            if ($languageArgument) {
                if ($ast->alias) {
                    // defer it
                    $data[$realName] = (fn ($source, $args, $context, ResolveInfo $info) => $container->$getter($args['language'] ?? null));
                } else {
                    $data[$realName] = $container->$getter($languageArgument);
                }
            } else {
                try {
                    $data[$realName] = $container->$getter();
                } catch (Error $e) {
                    Logger::error($e);
                }
            }
        }
    }
}
