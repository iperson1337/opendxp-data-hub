# Индикатор «Идёт выполнение»

Под деревом конфигов во вкладке Data Hub показывается полоса «Идёт выполнение (N)»: по
прогресс-бару на каждый выполняющийся конфиг. Клик по бару открывает конфиг. Рядом с самим
конфигом в дереве появляются иконка загрузки и процент. Когда ничего не выполняется, полоса
скрыта.

Data Hub сам ничего не выполняет. Прогресс отдают бандлы адаптеров, и подключение к индикатору
**необязательно**: бандл, который не реализует интерфейс, в индикаторе просто не появляется.

## Как подключить свой адаптер

Реализовать `OpenDxp\Bundle\DataHubBundle\Execution\RunningExecutionProviderInterface` и
зарегистрировать класс сервисом. С `autoconfigure: true` тег
`opendxp.datahub.running_execution_provider` ставится сам, без него тег нужно указать вручную.

```php
use OpenDxp\Bundle\DataHubBundle\Execution\RunningExecution;
use OpenDxp\Bundle\DataHubBundle\Execution\RunningExecutionProviderInterface;

final class MyRunningExecutionProvider implements RunningExecutionProviderInterface
{
    public function getRunningExecutions(): iterable
    {
        foreach ($this->progressStore->allRunning() as $name => $progress) {
            yield new RunningExecution($name, $progress->done, $progress->total, 'этап: загрузка');
        }
    }
}
```

- Возвращать только то, что выполняется **сейчас**. Права пользователя проверять не нужно:
  конфиги без права `read` эндпоинт отфильтрует сам.
- `total = 0` означает, что объём ещё неизвестен: бар показывает только имя конфига.
- `message` необязателен, он выводится во всплывающей подсказке над баром.
- Метод должен быть дешёвым: один запрос на все конфиги адаптера, без загрузки DataObject.
  Если поставщик бросит исключение, оно уйдёт в лог, а остальные адаптеры покажутся как обычно.
- Прогресс должен храниться там, где его видит веб-под: в БД или общем кеше (Redis), а не в
  файловом `cache.app`, если воркер работает в другом поде.

Примеры реализаций: `RunningImportProvider` в `iperson1337/opendxp-data-importer` (очередь
импорта, один `GROUP BY`) и `RunningAssetAssignmentProvider` в
`iperson1337/opendxp-asset-assignment-bundle` (кеш, один `getItems()`).

## Нагрузка

- Вкладка опрашивает `GET /admin/opendxpdatahub/config/running-executions`, только пока вкладка
  Data Hub активна и окно браузера видно. Когда пользователь возвращается, вкладка сразу делает
  внеочередной запрос.
- Сводка всех поставщиков кешируется на `cache_ttl` секунд без привязки к пользователю, так что
  поставщики вызываются не чаще раза за `cache_ttl` на пул кеша, сколько бы вкладок ни было открыто.
  Если указать в `cache_pool` общий пул (Redis), получится один вызов на весь кластер, а не на
  каждый под.

## Настройки

```yaml
opendxp_data_hub:
    running_executions:
        enabled: true             # false — индикатор скрыт и не опрашивает сервер
        cache_ttl: 5              # секунды, 0 — без кеша
        cache_pool: cache.app     # например, opendxp.cache.pool
        active_poll_interval: 10  # секунды, пока что-то выполняется
        idle_poll_interval: 30    # секунды, когда ничего не выполняется
```

## JS-событие

После каждого опроса на `document` срабатывает `opendxp.datahub.runningExecutions` с
`detail.items`: те же элементы, что отдаёт эндпоинт (`name`, `processed`, `total`,
`progress`, `message`). На него может подписаться панель адаптера, чтобы не опрашивать
прогресс отдельно:

```js
document.addEventListener("opendxp.datahub.runningExecutions", function (e) {
    const mine = e.detail.items.find(item => item.name === configName);
});
```
