# Add a Custom Query Operator

For adding a new query operator two steps are necessary: 
- add a type definition
- add the operator implementation

### Type Definition

Add a section similar to this one to your `services.yml` file.

```yaml
    opendxp.datahub.graphql.querytypegenerator_operator_mycustomoperator:
        class: OpenDxp\Bundle\DataHubBundle\GraphQL\QueryOperatorConfigGenerator\MyCustomOperator
        tags:
            - { name: opendxp.datahub.graphql.dataobjectquerytypegenerator, id: typegenerator_queryoperator_mycustomoperator }                        
```

For reference, have a look at a look at the 
[`Trimmer operator`](https://github.com/open-dxp/data-hub-bundle/blob/1.x/src/GraphQL/Query/Operator/Trimmer.php).

### Operator Implementation

You have to provide both JavaScript code dealing with the UI configuration aspects specific to  your operator
and the server-side PHP implementation doing the actual calculations. 

A JS sample can be found [here](https://github.com/open-dxp/data-hub-bundle/blob/1.x/src/Resources/public/js/queryoperator/Trimmer.js). 

:::info

Note that the namespace would be `opendxp.plugin.datahub.operator.mycustomoperator`.

:::

Make sure, that your extension gets loaded. See [OpenDxp Bundles](https://docs.opendxp.io/docs/core-framework/Development_Documentation/Extending_OpenDxp/Bundle_Developers_Guide/OpenDxp_Bundles/index.html)
docs page for further details.

Next thing is to provide the server-side implementation.
A sample can be found [here](https://github.com/open-dxp/data-hub-bundle/blob/1.x/src/GraphQL/Query/Operator/Trimmer.php). 

Finally, we have to define how the operator instances get created.
In most cases we use the `DefaultOperatorFactory` for that:

```yaml
    opendxp.datahub.graphql.dataobjectqueryoperator.factory.mycustomoperator:
        class: OpenDxp\Bundle\DataHubBundle\GraphQL\Query\Operator\Factory\DefaultOperatorFactory
        arguments:
            $className: OpenDxp\Bundle\DataHubBundle\GraphQL\Query\Operator\MyCustomOperator
        tags:
            - { name: opendxp.datahub.graphql.dataobjectqueryoperator_factory, id: mycustomoperator }
```
