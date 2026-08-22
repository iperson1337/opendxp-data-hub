# DataObject Queries

## Suppored Data Types

Also check out the OpenDxp's [data type documentation](https://docs.opendxp.io/docs/core-framework/Development_Documentation/Objects/Object_Classes/Data_Types/index.html). 

* Advanced Many-to-Many Relation
* Advanced Many-to-Many Object Relation
* Block
* Boolean Select
* [Calculated Value](https://docs.opendxp.io/docs/core-framework/Development_Documentation/Objects/Object_Classes/Data_Types/Calculated_Value_Type.html)
* Checkbox
* Classification Store
* Country
* Countries (Multiselect)
* Date
* DateTime
* Email
* External Image
* Gender
* [Field-Collections](https://docs.opendxp.io/docs/core-framework/Development_Documentation/Objects/Object_Classes/Data_Types/Fieldcollections.html)
* Firstname
* Geobounds
* Geopoint
* Geopolygon
* Image
* Image Advanced
* Input
* InputQuantityValue
* Language
* Languages (Multiselect)
* Lastname
* Link
* Many-to-One Relation
* Many-to-Many Relation
* Many-to-Many Object Relation
* Multiselect
* Newsletter Active
* Newsletter Confirmed
* Numeric
* Quantity Value
* Reverse Many-to-Many Object Relation
* RgbaColor
* Select
* Slider
* StructuredTable
* Table
* Textarea
* Time
* [URL Slug](https://docs.opendxp.io/docs/core-framework/Development_Documentation/Objects/Object_Classes/Data_Types/Others.html)
* Video
* Wysiwyg

## Available Query Operators

Check out the [operators section](../08_Operators/README.md) for more information.

## Get single Data Object

Base structure for getting single data object: 

```graphql
{
  getNews(id: 4) {
  ...
  }
} 
    
```

### Get single Data Object by an identifier field (`identifierFields`)

> **Custom extension** of this fork — not part of stock Pimcore Data Hub.

Out of the box a single object can only be fetched by `id` or `fullpath`. This fork lets you
expose arbitrary class fields (e.g. `barcode`, `code`, `ntin`) as additional lookup arguments
on the `get<Class>` query, via the per-entity `identifierFields` setting.

#### Configuration

Under the query entity in the GraphQL configuration, list the fields to expose:

```yaml
schema:
    queryEntities:
        News:
            identifierFields:
                - id
                - barcode
                - code
                - ntin
            columnConfig:
                # ...
```

Every listed field — except `id` / `fullpath` / `defaultLanguage` — becomes an optional
argument on `getNews(...)`. The GraphQL argument type is derived from the Pimcore field
definition: `numeric` → `Float`, `checkbox` / `boolean` / `booleanSelect` → `Boolean`,
everything else → `String`. Fields that do not exist on the class are ignored.

#### Querying

```graphql
{
  getNews(barcode: "5053990127641") { id title barcode }
}
```

```graphql
{
  getNews(code: "12345") { id title code }
}
```

#### Behaviour

- Returns a **single** object — the **first** match (`LIMIT 1`), **including unpublished** ones.
- When only identifier field(s) are provided (no `id` / `fullpath`): the resolver tries the
  model method `getBy<Field>($value, 1)` for each provided field in order (works when the field
  has an index/lookup enabled in Pimcore) and returns the first hit; otherwise it falls back to
  a listing with `WHERE <field> = <value>`.
- When `id` / `fullpath` are also provided, all supplied arguments are combined with `AND`.
- `uuid` is supported automatically whenever the class defines a `uuid` field — even if it is
  not listed in `identifierFields` — and a dedicated `get<Class>ByUuid(uuid: "...")` query is
  also generated.
- If nothing matches, the query fails with a `ClientSafeException`.

> Implementation: argument generation in
> `src/GraphQL/Query/QueryType.php` (`buildDataObjectQueries`) and resolution in
> `src/GraphQL/Resolver/QueryType.php` (`resolveObjectGetter`).

## Get List of Data Objects 

Base structure for getting a list of data objects, restricted by IDs: 

```graphql
{
  getNewsListing(ids: "4,5") {
     edges {
    ...
```

Base structure for getting a list of data objects, restricted by fullpath:

```graphql
{
  getNewsListing(fullpaths: "/NewsArticle,/NewsArticle2") {
    totalCount
    edges {
      node {
        id        
      }
    }
  }
}
```

Sometimes it can happen that the fullpath already contains a comma. To make sure the comma is not
interpreted as a list separator in this case, you can quote the path:

```graphql
{
  getNewsListing(fullpaths: "'/NewsArticle,Headline','/NewsArticle2'") {
    totalCount
    edges {
      node {
        id        
      }
    }
  }
}
```
 
 
#### Pagination
Pagination can be applied as query parameters.

```graphql
{
  # 'first' is the limit
  # 'after' the offset
  getManufacturerListing(first: 3, after: 1) {
    edges {
      node {
        id
        name
      }
    }
  }
}
```

#### Simple Sorting
Sorting can be applied as query parameters, for example sort by name, descending.

```graphql
{
  getManufacturerListing(sortBy: "name", sortOrder: "DESC") {
    edges {
      node {
        id
        name
      }
    }
  }
}
```

#### Filtering

You can use OpenDxp's webservice filter logic as described 
[here](https://docs.opendxp.io/docs/core-framework/Development_Documentation/Web_Services/Query_Filters.html) 
for filtering listing requests.

For details see [filtering documentation page](./10_Filtering.md)


## Localization of Queries
Queries can be localized For details see the [localization documentation page](./08_Localization.md).


## Extend Data Object Queries
It is possible to add custom query data types and query operators. For details see detail documentation
pages: 
* [Add a custom query datatype](./15_Add_Custom_Query_Datatype.md)
* [Add a custom query operator](./16_Add_Custom_Query_Operator.md)


## More Examples
See following list for more examples with data object queries: 

- [Manufacturer Listing with sorting and paging](./11_Query_Samples/20_Sample_Manufacturer_Listing.md)
- [Many-to-Many Object Relation](./11_Query_Samples/21_Sample_ManyToMany_Object_Relation.md)
- [Advanced Many-to-Many Object Relation](./11_Query_Samples/22_Sample_Advanced_ManyToMany_Object_Relation.md)
- [Advanced Many-to-Many Relation Metadata](./11_Query_Samples/23_Sample_Advanced_ManyToMany_Relation_Metadata.md)
- [Field-Collections on Data Objects](./11_Query_Samples/24_Sample_Fieldcollections.md)
- [Objects Parent/Children/Siblings](./11_Query_Samples/25_Sample_Parent_Children_Siblings.md)
- [Get linked data](./11_Query_Samples/26_Sample_Get_Linked_Data.md)
- [Translate Values](./11_Query_Samples/27_Sample_Translate_Values.md)

