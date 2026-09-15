# Changelog

## [1.0.2] - 2026-09-15

### Добавлено
- Строгая сверка имени превью в GraphQL (`ThumbnailNameValidator`). Имя конфига ищется
  регистронезависимо (ci-коллация `settings_store`), но в путь превью попадает та строка,
  которую написал потребитель, поэтому `thumbnail: "small"` при конфиге `Small` не ошибка
  на первый взгляд — превью отдаётся, но лежит в отдельной ветке
  `image-thumb__<id>__small/`, мимо прогрева, и генерируется заново на каждый запрос.
  Теперь такое имя отвергается, а совпадение без учёта регистра попадает в текст ошибки
  как подсказка. Логирование опционально (`NullLogger` по умолчанию) — канал и маркер
  подставляет приложение.
- Сверка вызывается из всех путей, принимающих имя от потребителя: `getAssetThumbnail()`
  и `Resolver\AssetType::resolveDimensions()` (последний уходит в
  `Asset::getThumbnail()`/`getImageThumbnail()` напрямую, мимо хелпера).

### Исправлено
- Алиасные запросы превью шли в `Asset::getThumbnail()` напрямую с зашитым `deferred=false`,
  то есть аргумент `deferred` из запроса не действовал вовсе; теперь идут через
  `getAssetFieldHelper()->getAssetThumbnail()`, где работают и сверка имени, и аргумент.
  Дефолты сохранены: `false` для алиасной ветки, `true` для не-алиасного `fullpath`
  (в AST дефолты схемы не попадают, а `Asset::getThumbnail()` без второго аргумента
  подразумевает `deferred=true`).

### Примечание к переносу на OpenDXP
Имена системных превью отличаются от Pimcore и переименованы вместе с классом:
`pimcore-system-treepreview` → `opendxp-system-treepreview`,
префикс `pimcore-download-` → `opendxp-download-` (сверено с
`OpenDxp\Model\Asset\Image\Thumbnail\Config` и `DownloadImageThumbnailHandler`).
Со старыми именами валидатор отвергал бы легитимные служебные превью.

