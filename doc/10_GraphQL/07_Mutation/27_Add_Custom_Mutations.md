# Add Custom Mutations

You can extend the mutation schema and add your custom mutations in the following way.

See [Events and Event Listeners](https://docs.opendxp.io/docs/core-framework/Development_Documentation/Extending_OpenDxp/Event_API_and_Event_Manager.html)
if you need more information on OpenDxp's event mechanism. 

```php
\OpenDxp::getEventDispatcher()->addListener(\OpenDxp\Bundle\DataHubBundle\Event\GraphQL\MutationEvents::PRE_BUILD,
    function (\OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\MutationTypeEvent $event) {
        $config = $event->getConfig();

        $opName = "performCustomMutation";

        $inputType = new \GraphQL\Type\Definition\InputObjectType([
            'name' => "MyCustomInputTypeName",
            'fields' => [
                'myCustomFieldName' => [
                    'type' => \GraphQL\Type\Definition\Type::string()
                ]
            ]
        ]);

        $operation = [
            'type' => Type::string(),           // the result type
            'args' => [
                'id' => ['type' => Type::nonNull(\GraphQL\Type\Definition\Type::int())],
                'input' => ['type' => $inputType],
            ], 'resolve' => function ($source, $args, $context, \GraphQL\Type\Definition\ResolveInfo $info) {
                // do something here
                $id = $args['id'];
                return "claiming that item " . $id . " has been updated to `" . $args['input']['myCustomFieldName'] . "`";
            }

        ];

        $config['fields'][$opName] = $operation;
        $event->setConfig($config);
    });
```
<div class="image-as-lightbox"></div>

![iExplorer](../../img/graphql/add_mutation.png)

 
