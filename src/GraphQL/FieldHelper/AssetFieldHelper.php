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

use GraphQL\Language\AST\FieldNode;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Asset\Image;
use OpenDxp\Model\Asset\Video;
use Override;

class AssetFieldHelper extends AbstractFieldHelper
{
    public function __construct(private readonly ?ThumbnailNameValidator $thumbnailNameValidator = null)
    {
        parent::__construct();
    }

    /**
     * Сверяет имя превью из GraphQL-запроса с конфигурацией — точно, с учётом регистра.
     * Вызывается всеми путями, которые принимают имя от потребителя, в том числе теми,
     * что идут в `Asset::getThumbnail()` напрямую, мимо `getAssetThumbnail()`.
     *
     * @throws \OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException
     */
    public function assertThumbnailNameExists(Asset $asset, mixed $thumbNailConfig): void
    {
        $this->thumbnailNameValidator?->assertNameExists($asset, $thumbNailConfig);
    }

    public function getVideoThumbnail(Asset\Video $asset, string | Video\Thumbnail\Config $thumbNailConfig, ?string $thumbNailFormat = null): mixed
    {
        if (isset($thumbNailFormat) && $thumbNailFormat !== 'image') {
            $value = $asset->getThumbnail($thumbNailConfig);
            if ($value) {
                $formats = $value['formats'] ?? [];
                $format = $formats[$thumbNailFormat] ?? null;
                if ($format) {
                    return $format;
                }
            }
        } else {
            return $asset->getImageThumbnail($thumbNailConfig);
        }

        return null;
    }

    public function getImageDocumentThumbnail(
        Asset $asset,
        string | Image\Thumbnail\Config $thumbNailConfig,
        ?string $thumbNailFormat = null,
        bool $deferred = false
    ): mixed {
        $thumb = null;

        if ($asset instanceof Asset\Document) {
            $thumb = $asset->getImageThumbnail($thumbNailConfig, deferred: $deferred);
        }

        if ($asset instanceof Asset\Video) {
            $thumb = $asset->getImageThumbnail($thumbNailConfig);
        }

        if ($asset instanceof Asset\Image) {
            $thumb = $asset->getThumbnail($thumbNailConfig, $deferred);
        }

        if (!$asset instanceof Asset\Video && isset($thumb, $thumbNailFormat)) {
            $thumb = $thumb->getAsFormat($thumbNailFormat);
        }

        return $thumb;
    }

    public function getAssetThumbnail(
        Asset $asset,
        string | Image\Thumbnail\Config | Video\Thumbnail\Config $thumbNailConfig,
        ?string $thumbNailFormat = null,
        bool $deferred = false
    ): mixed {
        $this->assertThumbnailNameExists($asset, $thumbNailConfig);

        if (($asset instanceof Asset\Video) && (is_string($thumbNailConfig) || $thumbNailConfig instanceof Video\Thumbnail\Config)) {
            return $this->getVideoThumbnail($asset, $thumbNailConfig, $thumbNailFormat);
        } else {
            return $this->getImageDocumentThumbnail($asset, $thumbNailConfig, $thumbNailFormat, $deferred);
        }
    }

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

        $getter = 'get'.ucfirst((string) $astName);
        $arguments = $this->getArguments($ast);
        $languageArgument = $arguments['language'] ?? null;
        $thumbnailArgument = $arguments['thumbnail'] ?? null;
        $thumbnailFormat = $arguments['format'] ?? null;

        $realName = $astName;

        if (($astName == 'fullpath' || $astName == 'data') && $thumbnailArgument && ($container instanceof Image || $container instanceof Video)) {
            if ($ast->alias) {
                // defer it
                $data[$realName] = function ($source, $args, $context, ResolveInfo $info) use ($container, $realName) {
                    // getAssetThumbnail, а не getThumbnail напрямую: только так работают сверка
                    // имени превью и значение аргумента `deferred` — у алиасных запросов флаг
                    // был зашит в false, то есть аргумент из запроса не действовал вовсе.
                    // Дефолт false сохраняет прежнее поведение (см. AssetType, поле fullpath).
                    $deferred = $args['deferred'] ?? false;

                    if ($realName === 'fullpath') {
                        return $this->getAssetThumbnail($container, $args['thumbnail'], null, $deferred);
                    }
                    if ($realName === 'data') {
                        $thumb = $this->getAssetThumbnail($container, $args['thumbnail'], null, $deferred);

                        return stream_get_contents($thumb->getStream());
                    }

                    return null;
                };
            } else {
                //TODO extract duplicate code
                if ($realName == 'fullpath') {
                    // ?? true — прежнее поведение этой ветки: раньше Asset::getThumbnail()
                    // звался здесь без второго аргумента, а у OpenDXP он по умолчанию
                    // deferred=true. Явный `deferred: false` из запроса теперь действует
                    // и здесь (в AST дефолты схемы не попадают).
                    $data[$realName] = $this->getAssetThumbnail(
                        $container,
                        $thumbnailArgument,
                        null,
                        $arguments['deferred'] ?? true
                    );
                } elseif ($realName == 'data') {
                    $thumb = $this->getAssetThumbnail($container, $thumbnailArgument, $thumbnailFormat);
                    if ($thumb) {
                        $data[$realName] = stream_get_contents($thumb->getStream());
                    }
                }
            }
        } else {
            if (method_exists($container, $getter)) {
                if ($languageArgument) {
                    if ($ast->alias) {
                        // defer it
                        $data[$realName] = (fn ($source, $args, $context, ResolveInfo $info) => $container->$getter($args['language'] ?? null));
                    } else {
                        $data[$realName] = $container->$getter($languageArgument);
                    }
                } else {
                    $data[$realName] = $container->$getter();
                }
            }
        }
    }
}
