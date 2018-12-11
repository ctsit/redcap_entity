
# REDCap Entity Documentation

## Index
1. Intro and overview
2. Defining entity types via `redcap_entity_types()` hook
    1. Defining entity types
    2. Defining entity properties
    3. _(optional)_ Adding semantic to properties
3. Enabling database schema
4. Creating an entity list
    1. Creating a plugin for your list
    2. Defining the context of your list (global, Control Center, or project)
    3. _(optional)_ Defining page size 
5. Manipulating entities programmatically
    1. Loading an entity and retrieving data
    2. Creating a new entity
    3. Updating an entity
    4. Deleting an entity
    5. Loading multiple entities
    6. Querying entities
6. Customizations
    1. Customizing entities behavior
        1. Creating an entity class
        2. Overriding methods
    2. Customizing entity lists
        1. Creating a list class
        2. Overriding methods
7. Best practices
8. Logs and troubleshooting


## 1. Intro and overview
As we know, REDCap provides rich features for designing, storing, and managing data entry records. But what happens if we need to store something besides a record?

There are surely workarounds for this problem, such as creating a dedicated project for internal storage, appending rows to `redcap_metadata` table, using External Modules settings, adding entries into REDCap logs table, etc. All these are valid solutions, although bring more complexity for module developers (and contributors), and require an extra care to keep REDCap built-in tables safe and consistent.

Since UF CTSI team has been recurring facing this challenge, we have decided to build REDCap Entity, a framework that provides developer tools for desiging and creating custom data structures, which represent new "entities" in the system. CRUD operations are fully isolated from REDCap core's database tables.

Maybe the most powerful tool provided by this module is the Entity list builder, which is flexible enough to create pages that varies from simple lists to complex admin UIs. It includes pager, exposed filters, add/edit/delete operations, sortable table columns, and bulk operations. 1 working hour is quite enough to produce a result like this:

TODO: insert image

Our team is successfully using REDCap Entity to develop a few projects, such as Project Ownership and OnCore Client.

### How it works
This module is a developer's tool, so in order to design entity types, you need to create a new module that includes a hook, a less-than-10-line plugin, and (optionally) a couple of extensions of built-in classes. This documentation will walk you through that process.

### Where entities are stored
The entities are stored into db tables prefixed with `redcap_entity_`. Example: given a `department` entity type, its db table is named as `redcap_entity_department`. Tables creation/removal is triggered via UI.

## 2. Defining entity types via `redcap_entity_types()` hook

### 1. Defining entity types

This hook defines everything about your entity types. It should return an array of structures, keyed by the entity type machine name.

```
<?php

function redcap_entity_types() {
    $types = [];
    
    $types[<ENTITY_TYPE_1_KEY>] = <ENTITY_TYPE_1_STRUCTURE>;
    $types[<ENTITY_TYPE_2_KEY>] = <ENTITY_TYPE_2_STRUCTURE>;
    (...)
    $types[<ENTITY_TYPE_N_KEY>] = <ENTITY_TYPE_N_STRUCTURE>;
    
    return $types;
}
```


Each structure is an array that expects the following keys:

| Key                  | Required | Description   |
| -------------------- | -------- | ------------- |
| `label`              | Yes      | The entity type label. |
| `label_plural`       | No       | The plural entity type label (used on entity lists). |
| `icon`               | No       | The icon from REDCap repository that best describes your entity type (used on entity lists). Defaults to "application\_view\_columns". |
| `properties`         | Yes      | The list of entity property definitions - see __Defining entity properties__ section. |
| `special_keys`       | No       | Adds semantics to your properties - see __Adding semantic to properties__ section. |
| `class`              | No       | An array that defines the namespace and location of your entity type class (which represents your entity objects), keyed as follows:<br><br>```['name' => <CLASS_NAMESPACE>, 'path' => <RELATIVE_PATH_TO_CLASS_FILE>]```<br><br>If not set, the default class will be used - `REDCapEntity\Entity`. See __Customizing entities behavior__ to learn how to create a custom entity type class. |
| `allowed_operations` | No       | An array that lists the allowed entity operations. Accepts any combination of "create", "update", and "delete". Set an empty array (`[]`) to do not allow any operations. Defaults to `['create', 'update', 'delete']`. |
| `loggable_events` | No | The events that must be logged into REDCap Entity Logs page. Accepts any combination of "create", "update", and "delete".  Defaults to none. |
| `bulk_operations`    | No       | An array that

Example:

```
<?php

function redcap_entity_types() {
    $types = [];
    
    $types['institution'] = [
        'label' => 'Institution',
        'label_plural' => 'Institutions',
        'icon' => '',
        'properties' => [
            'name' => [
                'name' => 'Name'
                'type' => 'text',
                'required' => true,
            ],
        ],
        'special_keys' => [
            'label' => 'name',
        ],
    ];
    
    $types['department'] = [
        'label' => 'Department'.
        'label_plural' => 'Departments',
        'icon' => '',
        'properties' => [
            'name' => [
                'name' => 'Name',
                'type' => 'text',
                'required' => true,
            ],
            'institution' => [
                'name' => 'Institution',
                'type' => 'entity_reference',
                'entity_type' => 'institution',
                'required' => true,
            ],
        ],
        'special_keys' => [
            'label' => 'name',
        ],
        'bulk_operations' => [
            'delete' => [
            ],
        ],
    ];
   
    return $types;
}
```

### 2. Defining entity properties

As you might have seen from the example above, properties are defined as follows:

```
<?php

(...)
    'properties' => [
        <PROPERTY_1_KEY> => <PROPERTY_1_STRUCTURE>,
        <PROPERTY_2_KEY> => <PROPERTY_2_STRUCTURE>,
        (...)
        <PROPERTY_N_KEY> => <PROPERTY_N_STRUCTURE>,
    ],
(...)
```

Each property structure is an array that allows the following keys:

| Key                | Required | Description   |
| ------------------ | -------- | ------------- |
| `name`             | Yes      | The property label. |
| `type`             | Yes      | The data type. Supported types: `text`, `integer`, `text_long`, `project`, `entity_reference`, `price`, `time`. |
| `required`         | No       | Boolean that defines whether the property on required on forms. Defaults to `false`. |
| `disabled`         | No       | Boolean that defines whether the property should be disabled on forms. |
| `choices`          | No       |  |
| `choices_callback` | No       |  |
| `prefix`           | No       | Text that defines. |
