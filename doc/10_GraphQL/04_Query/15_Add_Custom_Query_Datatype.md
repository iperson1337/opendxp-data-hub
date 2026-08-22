# Add a Custom Query Datatype

For adding a new query data type two steps are necessary: 
- add a type definition
- provide a resolver implementation (optional)


To add a type definition, add a section similar to this one to your `services.yml` file.

```yaml
    opendxp.datahub.graphql.dataobjectquerytypegenerator_datatype_mycustomdatatype:
        class: OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryFieldConfigGenerator\MyCustomDatatype
        tags:
            - { name: opendxp.datahub.graphql.dataobjectquerytypegenerator, id: typegenerator_dataobjectquerydatatype_mycustomdatatype }                        
```

For reference, have a look at a look at the 
[`Link datatype`](https://github.com/open-dxp/data-hub-bundle/blob/1.x/src/GraphQL/DataObjectQueryFieldConfigGenerator/Link.php).
It also shows how specific attributes are resolved. 

If you don't provide a resolver function then the getter method is called instead. 
