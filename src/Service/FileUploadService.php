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

use GraphQL\Error\InvariantViolation;
use GraphQL\Server\RequestError;
use Symfony\Component\HttpFoundation\Request;

class FileUploadService
{
    /**
     * @throws RequestError
     */
    public function parseUploadedFiles(Request $request): array
    {
        $this->validateParsedBody($request);

        $bodyParams = $request->request->all();

        if (!isset($bodyParams['map'])) {
            throw new RequestError('The request must define a `map`');
        }

        $map = json_decode((string) $bodyParams['map'], true);
        $result = json_decode((string) $bodyParams['operations'], true);

        foreach ($map as $fileKey => $locations) {
            foreach ($locations as $location) {
                $items = &$result;

                foreach (explode('.', (string) $location) as $key) {
                    if (!isset($items[$key]) || !is_array($items[$key])) {
                        $items[$key] = [];
                    }

                    $items = &$items[$key];
                }

                $items = $request->files->get($fileKey);
            }
        }

        return $result;
    }

    /**
     * Validates that the request meet our expectations
     */
    protected function validateParsedBody(Request $request): void
    {
        $bodyParams = $request->request->all();

        if (empty($bodyParams)) {
            throw new InvariantViolation(
                'Request is expected to provide parsed body for "multipart/form-data" requests but got empty array'
            );
        }
    }
}
