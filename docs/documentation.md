
# REDCap Entity Documentation

## Index
1. Intro and overview
2. Defining entity types via `redcap_entity_types()` hook
    1. Defining entity types
    2. Defining entity properties
    3. _(optional)_ Adding semantic to properties
3. Creating DB tables
    1. Via UI
    2. Programmatically (module enable)
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
As we know, REDCap provides rich features for designing, storing, and managing data entry records. However, what happens if there is a need to storage and manage non-record data?

There are surely workarounds for this problem, such as creating a dedicated project for internal storage, using External Modules settings, adding extra rows to `redcap_metadata` table, inserting entries into REDCap logs table, etc. Despite all these are valid solutions, they bring more complexity and require an extra care to keep REDCap built-in tables safe and consistent.

Since UF CTSI team has been facing this challenge repeatedly, we have decided to build REDCap Entity - a framework for desiging and creating custom data structures, which represent new "entities" in the system. It's flexible and safe, since CRUD operations are fully isolated from REDCap core's database tables.

Maybe the most attracting tool provided by this module is the Entity List Builder, which can be used to build simple lists or even complex admin UIs. It includes pager, exposed filters, add/edit/delete operations, sortable table columns, and bulk operations. They can be integrated either on your project and on Control Center. One working hour is quite enough to produce a result like this:

TODO: insert image

Our team is successfully using REDCap Entity to develop a few projects, such as Project Ownership and OnCore Client.

### How it works
This module is a developer's tool, so in order to design entity types, you need to create a new module that contains a hook (which defines the structure) and a less-than-10-line plugin (to render the list). For complex customizations, the built-in classes can be extended.

### Storage
The entities are stored into db tables prefixed with `redcap_entity_`. Example: given a `department` entity type, its db table is named as `redcap_entity_department`. Tables creation/removal is triggered via UI or via enabling/updating/disabling the module.

## 2. Defining entity types via `redcap_entity_types()` hook

### 2.1. Defining entity types

Implement `redcap_entity_types()` to define your entity types. Each entity type should specify a key and a structured array.

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


Each structure is an array that expects/accepts the following keys:

| Key                  | Required | Description   |
| -------------------- | -------- | ------------- |
| `label`              | Yes      | The entity type label. |
| `label_plural`       | No       | The plural entity type label (used on entity lists). |
| `icon`               | No       | The icon from REDCap repository that best describes your entity type (used on entity lists). Defaults to "application\_view\_columns". |
| `properties`         | Yes      | Defines the properties / data structure of list of entity type. Each property is translated into a db table column - see __Defining entity properties__ section. |
| `special_keys`       | No       | Adds semantics to your properties (e.g. label, project ID) - see __Adding semantic to properties__ section. |
| `class`              | No       | An array that defines your entity class (it's analogous to the Model class in a MVC architecture), keyed as follows:<br><br>```['name' => <CLASS>, 'path' => <RELATIVE_PATH_TO_CLASS_FILE>]```<br><br>If not set, the default class is used - `\REDCapEntity\Entity`. See __Customizing entities behavior__ to learn how to create a custom entity type class. |
| `form_class`              | No       | An array that defines the class of your entity add/edit form (which represents your entity objects), keyed as follows:<br><br>```['name' => <CLASS>, 'path' => <RELATIVE_PATH_TO_CLASS_FILE>]```<br><br>If not set, the default class is used - `\REDCapEntity\EntityForm`. See __Customizing an entity form__ to learn how to customize an entity form. |
| `allowed_operations` | No       | An array that defines the allowed operations on lists. Accepts any combination of "create", "update", and "delete" (e.g. `['update', 'delete']`). Leave blank to do not allow any operations. |
| `loggable_events` | No | The events that must be logged into REDCap Entity Logs page. Accepts any combination of "create", "update", and "delete".  Defaults to none. |
| `bulk_operations`    | No       | An array that defines the available bulk operations

Example:

```
<?php

function redcap_entity_types() {
    $types = [];

    $types['department'] = [
        'label' => 'Department'.
        'label_plural' => 'Departments',
        'icon' => 'home_pencil',
        'properties' => [
            'name' => [
                'name' => 'Name',
                'type' => 'text',
                'required' => true,
            ],
            'institution' => [
                'name' => 'Institution',
                'type' => 'text',
                'choices' => [
                    'inst_1' => 'Institution 1',
                    'inst_2' => 'Institution 2',
                    'inst_3' => 'Institution 3',
                ],
                'required' => true,
            ],
            'project_id' => [
                'name' => 'Project ID',
                'type' => 'project',
                'required' => true,
            ],
            'contact_email' => [
                'name' => 'Contact email',
                'type' => 'email',
            ],
            'comments' => [
                'name' => 'Comments',
                'type' => 'long_text',
            ],
        ],
        'operations' => ['create', 'update', 'delete'],
        'special_keys' => [
            'label' => 'label',
            'project' => 'project_id',
        ],
        'bulk_operations' => [
            'delete' => [
                'name' => 'Delete',
                'method' => 'delete',
                'messages' => [
                    'success' => 'The departments have been deleted.',
                ],
            ],
        ],
    ];
   
    return $types;
}
```

### 2.2. Defining entity properties

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
| `type`             | Yes      | The data type. Supported types: `text`, `integer`, `email`, `project`, `record`, `date`, `boolean`, `json`, `long_text`, `entity_reference`, `price`, `json`. |
| `required`         | No       | Boolean that defines whether the property on required on forms. Defaults to `false`. |
| `disabled`         | No       | Boolean that defines whether the property should be disabled on forms. Defaults to `false`. |
| `choices`          | No       | Set this field if your field should present a list of valid options. Expects an array of options labels, keyed by value. Example: `['option_1' => 'Option 1', 'option_2' => 'Option 2']` |
| `choices_callback` | No       | Same as `choices`, but instead of giving a fixed list of options, here you define a callable string which returns the options array. |
| `choices_type`     | No       | If `choices` or `choices callback` this setting defines the display mode of the selection options. Accepts "dropdown" and "radios". Defaults to "dropdown".|
| `prefix`           | No       | Sets a prefix to a form field. |
| `entity_type`      | No       | Required only if type = `entity_reference`. Sets the target entity type to be referenced. |


### 2.3. Adding semantic to properties

As shown on the previous example, `special_keys` can be used to add semantic to your properties. There are 2 types available:

- **label:** the property that carries the label of your entity
- **project:** the property carries the context project ID, which means that the entity is only accessible within that particular project


```
<?php

function redcap_entity_types() {
(...)
        'special_keys' => [
            'label' => <LABEL_FIELD>,
            'project' => <PROJECT_FIELD>,
        ],
(...)
}
```

Note that defining `special_keys` is not required, but it automates features on entity lists.

## 2. Creating DB tables

Implementing `hook_entity_types()` is not enough to enable your entity type. The next and final step is creating your entity db table. There are two available methods: via UI and programmatically.

### 3.1. Via UI

Go to Control Center > Entity DB Manager. Taking the Departments Manager module as our example, you should see the following list:

TODO: insert image

### 3.1. Programmatically (module enable)

This is very useful for skipping the manual creation via UI and triggering your table creation when your module is enabled. To do that you need to add the following code to your module:

```
<?php

function redcap_module_system_enable($version) {
    \REDCapEntity\EntityDB::buildSchema($this->PREFIX);
}
```

If your module is enabled multiple times, it does not reset your table - unless you explicitly set it by the 2nd paremeter: `\REDCapEntity\EntityDB::buildSchema($this->PREFIX, true)`.
