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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\FieldHelper;

use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Asset\Image;
use OpenDxp\Model\Asset\Video;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Отвергает GraphQL-запрос с именем превью, которого нет в конфигурации.
 *
 * Имя конфига ищется регистронезависимо (ci-collation в `settings_store`), но в путь
 * превью попадает та строка, которую написал потребитель: `Config\Dao::getByName()`
 * делает `setName($id)` -> `$data['id'] = $id` -> `setName($data['id'])`. Поэтому
 * `thumbnail: "small"` при конфиге `Small` на первый взгляд не ошибка — превью
 * отдаётся, — но лежит оно в отдельной ветке `image-thumb__<id>__small/`, мимо
 * прогрева, и генерируется заново на каждый запрос.
 *
 * Молчаливой такая опечатка быть не должна, поэтому имя сверяется точно, с учётом
 * регистра. Совпадение без учёта регистра попадает в текст ошибки как подсказка.
 *
 * Логирование опционально: по умолчанию `NullLogger`, приложение подставляет свой
 * канал и маркер через `config/services.yaml` — бандл не зависит от кода приложения.
 */
class ThumbnailNameValidator
{
    /**
     * Превью дерева ассетов в админке. Конфига в `settings_store` для него нет —
     * `Image\Thumbnail\Config::getByName()` собирает его отдельной веткой.
     */
    private const SYSTEM_PREVIEW_THUMBNAIL = 'opendxp-system-treepreview';

    /**
     * Служебные конфиги, которых нет в списке: Pimcore собирает их на лету — `_auto_<md5>`
     * для превью, заданного массивом параметров, `opendxp-download-<id>-<hash>` для
     * скачивания из админки. Имя такого конфига возвращает сам Pimcore, а не потребитель.
     */
    private const INTERNAL_NAME_PREFIXES = ['_auto_', 'opendxp-download-'];

    private const TYPE_IMAGE = 'image';

    private const TYPE_VIDEO = 'video';

    private LoggerInterface $logger;

    /** @var array<string, list<string>> 'image'|'video' => имена конфигов, как они заданы */
    private array $knownNames = [];

    /** @var array<string, true> уже залогированные имена — одна запись на запрос, не на объект */
    private array $reported = [];

    public function __construct(
        ?LoggerInterface $logger = null,
        private readonly string $logMarker = 'DATA_ANOMALY'
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Проверяет имя превью, пришедшее из GraphQL-запроса. Не строки (готовый объект
     * конфига) и системное превью дерева пропускаются без проверки.
     *
     * @throws ClientSafeException если конфига с таким именем (с учётом регистра) нет
     */
    public function assertNameExists(Asset $asset, mixed $requested): void
    {
        if (!is_string($requested) || $requested === '' || $this->isInternalName($requested)) {
            return;
        }

        $known = $this->knownThumbnailNames($this->thumbnailType($asset));

        // Пустой список — конфигурация недоступна (не поднят storage, пустая инсталляция).
        // Молча ронять все запросы в такой ситуации хуже, чем пропустить проверку.
        if ($known === [] || in_array($requested, $known, true)) {
            return;
        }

        $canonical = $this->findCanonicalName($known, $requested);
        $message = $this->buildMessage($known, $requested, $canonical);

        $this->reportOnce($requested, $canonical, $message);

        throw new ClientSafeException($message);
    }

    /**
     * @param list<string> $known
     */
    private function findCanonicalName(array $known, string $requested): ?string
    {
        foreach ($known as $name) {
            if (strcasecmp($name, $requested) === 0) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param list<string> $known
     */
    private function buildMessage(array $known, string $requested, ?string $canonical): string
    {
        if ($canonical !== null) {
            return sprintf(
                'Thumbnail "%s" does not exist, did you mean "%s"? Thumbnail names are case-sensitive.',
                $requested,
                $canonical
            );
        }

        return sprintf(
            'Thumbnail "%s" does not exist. Available thumbnails: %s.',
            $requested,
            implode(', ', $known)
        );
    }

    private function reportOnce(string $requested, ?string $canonical, string $message): void
    {
        if (isset($this->reported[$requested])) {
            return;
        }

        $this->reported[$requested] = true;

        $kind = $canonical !== null ? 'thumbnail_name_case_mismatch' : 'unknown_thumbnail_name';

        $this->logger->warning(
            sprintf('%s %s: %s', $this->logMarker, $kind, $message),
            [
                'requested_thumbnail' => $requested,
                'canonical_thumbnail' => $canonical,
            ]
        );
    }

    private function isInternalName(string $requested): bool
    {
        if ($requested === self::SYSTEM_PREVIEW_THUMBNAIL) {
            return true;
        }

        foreach (self::INTERNAL_NAME_PREFIXES as $prefix) {
            if (str_starts_with($requested, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function thumbnailType(Asset $asset): string
    {
        return $asset instanceof Asset\Video ? self::TYPE_VIDEO : self::TYPE_IMAGE;
    }

    /**
     * @return list<string>
     */
    private function knownThumbnailNames(string $type): array
    {
        if (!isset($this->knownNames[$type])) {
            $this->knownNames[$type] = $this->loadThumbnailNames($type);
        }

        return $this->knownNames[$type];
    }

    /**
     * @return list<string>
     */
    protected function loadThumbnailNames(string $type): array
    {
        $configs = $type === self::TYPE_VIDEO
            ? (new Video\Thumbnail\Config\Listing())->getThumbnails()
            : (new Image\Thumbnail\Config\Listing())->getThumbnails();

        return array_values(array_map(static fn ($config): string => $config->getName(), $configs));
    }
}
