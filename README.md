# REDCap Entity
Provides features to design, store and manage custom entities in REDCap.

## Prerequisites

- REDCap >= 8.7.0

## Installation

- Clone this repo into `<redcap-root>/modules/redcap_entity_api_v<version_number>` or download it from [REDCap Repo](https://redcap.vanderbilt.edu/consortium/modules/).
- Go to **Control Center > Manage External Modules** and enable REDCap Entity.

## Introduction

Let's say your team needs to create and manage additional content in REDCap that cannot be expressed as regular data entries (e.g. protocols, drugs, sites, prescriptions, papers, etc).

In this case, a few aspects need to be covered such as:

- The data structure + the storage location (e.g. custom SQL table)
- The data input workflow (e.g. a form to add/edit content, which validates submissions to ensure consistency)
- The view of your data (e.g. a page that lists your content, which may include features like pager, filters, etc.)

These steps can take weeks of design and development work, and that's why REDCap Entity was designed - to save your team's time. Here is an example of an admin UI that can be easily generated via REDCap Entity:

TODO: insert image

## How it works

This module is a developer's tool, so in order to design entity types, you need to create a new module that contains a hook (which defines the structure) and a less-than-10-line plugin (to render the list or your admin UI).

## Where entities are stored
The entities are stored into db tables prefixed with `redcap_entity_`. Example: given a `protocol` entity type, its db table is named as `redcap_entity_protocol`.

The 2 biggest advantages of this model are:

- Flexibility: each entity type has its own table, with its own properties/columns
- DB integrity: all DB operations are fully isolated from REDCap core's database tables

Tables creation/removal can be managed via UI (only by admins) or programmatically triggered via one-time hooks such as `redcap_module_system_enable()`.

Now, let's finally get started - the next sections will walk you through the process of desiging and managing your entities.

## Setting up entity types

### Step 1. Create your external module

REDCap Entity does not create entities by itself. It requires a **child module** to define the structure of your entities.

So let's call our example module "REDCap Protocols".

config.json

```json
{
    "name": "REDCap Protocols",
}
```

Obs.: if you are not familiar with External Modules development, check [this documentation](https://github.com/vanderbilt/redcap-external-modules).


### Step 2. Implement redcap_entity_types()

Your entity type is defined on `redcap_entity_types` hook. There, you essentially specify:

- a label (e.g. "Protocol")
- the properties of your entity (e.g. title, status, PI, study site)

Here we are going to define 2 entities, Protocol and Study site.

ExternalModule.php

```php
<?php

    function redcap_entity_types() {
        $types = [];
    
        $types['study_site'] = [
            'label' => 'Study site',
            'label_plural' => 'Study sites',
            'properties' => [
                'name' => [
                    'name' => 'Name',
                    'type' => 'text',
                    'required' => true,
                ],
            ],
            'special_keys' => [
                'label' => 'name',
            ],
        ];
    
        $types['protocol'] = [
            'label' => 'Protocol',
            'label_plural' => 'Protocols',
            'properties' => [
                'number' => [
                    'name' => 'Number',
                    'type' => 'text',
                    'required' => true,
                ],
                'title' => [
                    'name' => 'Title',
                    'type' => 'text',
                    'required' => true,
                ],
                'status' => [
                    'name' => 'Status',
                    'type' => 'text',
                    'choices' => [
                        'in_study' => 'In Study',
                        'pending' => 'Pending',
                        'expired' => 'Expired',
                    ],
                    'required' => true,
                ],
                'created_by' => [
                    'name' => 'Created by',
                    'type' => 'user',
                    'required' => true,
                ],
                'project_id' => [
                    'name' => 'Project ID',
                    'type' => 'project',
                    'required' => true,
                ],
                'study_site' => [
                    'name' => 'Study site',
                    'type' => 'entity_reference',
                    'entity_type' => 'study_site',
                ],
                'pi' => [
                    'name' => 'PI',
                    'type' => 'user',
                ],
            ],
            'special_keys' => [
                'label' => 'number',
                'type' => 'project',
                'author' => 'created_by',
            ],
        ];

        return $types;
    }

```

Here is the list of available settings:

#### label

Defines the label of your entity type.

#### label_plural

Defines the plural label of your entity type.

#### properties

Properties are defined by an array of settings, keyed by an identifier. There are 2 required settings: **name** and **type**. Example:

```php
<?php

    'title' => [
        'name' => 'Title',
        'type' => 'text',
    ],
    'description' => [
        'name' => 'Description',
        'type' => 'long_text',
    ],
```

The property type specifies the data type, which helps the form builder (see next sections) to validate your data input. Here is a list of valid types:

- `text`
- `long_text`
- `integer`
- `date`
- `json`
- `project`
- `email`
- `user`
- `entity_reference`: other entities can be referenced via this type, which requires an extra key - `entity_type`, defining the target entity type.

If your property should be presented as a list of options, you can specify it via **choices** setting, which is an array of labels, keyed by the option value. Alternatively, you can set **choices_callback** to specify a callable string (e.g. function name, class method), which returns the keyed array of options. There are 2 types of lists: "dropdown" and "radios", which can be set via **choices_type** (if blank, "dropdown" is set). Example:

```php
<?php

    'type' => [
        'name' => 'Type',
        'type' => 'text',
        'choices' => [
            'a' => 'Type A',
            'b' => 'Type B',
            'c' => 'Type C',
        ],
    ],
    'status' => [
        'name' => 'Status',
        'type' => 'text',
        'choices_callback' => 'MyCustomModule::getStatusList',
        'choices_type' => 'radios',
    ],
```


#### special_keys

As shown on the previous example, `special_keys` can be used to add semantic to your properties. By doing that you are basically telling REDCap Entity what a particular field means. There are 3 types available:

- `label`: Use this setting if a property of your entity represents the label (e.g. Name, Title, etc). If not set, REDCap Entity will take the internal (auto-incremented) ID as the default label.
- `project`: Use this setting if your entity type is project contextualized (i.e. not global), and you want to specify a property to store the project ID. By setting this special key, 2 features are enabled:
-- The field automatically receives the current project ID (if available) on entity creation
-- Entity lists are automatically filtered by the current project.
- `author`: Use this setting to tell REDCap Entity that the given property should store the content author. By doing that, the field automatically receives the current user on entity creation.

### Step 3. Creating the database table

#### Alternative 1: Via UI

Go to **Control Center > Entity DB Manager** - you will be able to see your entity types.

TODO: insert image.

Once you hit "create db table" for each one of your entity types - and proceed with the confirmation modal - a new db table is created, and your entity type is finally enabled!

TODO: insert image

In the same way, you can drop the table by clicking on "delete db table" and proceed with the confirmation modal.

TODO: insert image

**Important:** During your design/dev process you can troubleshoot your `hook_entity_type()` definitions. If there is something wrong with your definition arrays, your entity type is listed as "Invalid", and you can see a list of issues to be addressed.

TODO: insert image

#### Alternative 2: Programmatically

If you want to skip the manual creation via UI and trigger your table creation on module enable, add the following code to your external module class:

```
<?php

function redcap_module_system_enable($version) {
    \REDCapEntity\EntityDB::buildSchema($this->PREFIX);
}
```

Don't forget to allow `redcap_module_system_enable()` hook on config.json:

```json
{
   "permissions": [
       "redcap_module_system_enabled"
   ]
}
```

Don't worry about enabling your module multiple times - if the table already exists, nothing will happen to it. However, if you want to reset your table for every enable event, you can explicitly set it on the 2nd paremeter: 

```
<?php

\REDCapEntity\EntityDB::buildSchema($this->PREFIX, true)
```

## Building an entity list / admin UI

#### Step 1. Creating plugin files

pages/study-sites.php

```php
<?php

use REDCapEntity\EntityList;

$list = new EntityList('study_site', $module);
$list->render('control_center'); // Context: Control Center.
```

pages/protocols.php (analogous to study-sites.php)

```php
<?php

use REDCapEntity\EntityList;

$list = new EntityList('protocol', $module);
$list->render('project'); // Context: project.
```

## Customizing the entity form

TODO.

## Manipulating your entities programmatically

#### Create

```php
<?php

$factory = new \REDCapEntity\EntityFactory();
$entity = $factory->create('protocol', [
    'number' => 'TEST123',
    'title' => 'Test Protocol',
    'status' => 'in_study',
]);

echo $entity->getId();
```

#### Retrieve

```php
<?php

$factory = new \REDCapEntity\EntityFactory();
$entity = $factory->getInstance('protocol', 1);

// Print entity data.
print_r($entity->getData());
```

To retrieve multiple entities, you can pass a list of IDs to `loadInstances()`:

```php
<?php

$entities = $factory->loadInstances('protocol', [1, 2, 3]);

// Print entities data.
foreach ($entities as $entity) {
    print_r($entity->getData());
}
```

#### Update

```php
<?php

if ($entity->setData(['status' => 'expired', 'pi' => 'pi_user'])) {
    $entity->save();
}
else {
    // Get a list of properties that failed on update
    print_r($entity->getErrors());
}
```

#### Delete

```php
<?php

$entity->delete();
```

#### Query entities

To query entities, there is no need to use SQL - you may use the provided query framework. For instance, the code above retrieves protocol objects, filtered by PIs that match the current user, sorting results by title:

```php
<?php

$factory = new \REDCapEntity\EntityFactory();
$results = $factory->query('protocol')
    ->condition('pi', USERID)
    ->orderBy('title')
    ->execute();
```

For further information, you may open `classes/EntityQuery.php` file and explore the `EntityQuery` class methods that you can use.

## Customizing the entity business logic

To customize the business logic of your entity, you may extend the `Entity` class, which is the default class used to instantiate entities. There are 2 steps for it:

#### Step 1. Create your entity class

Let's say classes/Protocol.php

```php
<?php

namespace REDCapProtocols\Protocol;

use REDCapEntity\Entity;

class Protocol extends Entity {
    function save() {
        if (empty($this->pi)) {
            // Saving the PI as the project creator by default.
            global $Proj;
            $this->pi = $Proj->project['created_by'];
        }

        parent::save();
    }
}
```

#### Step 2. Reference your class on `redcap_entity_types()`:

```php
    $types['protocol'] = [
        'class' => [
            'path' => 'classes/Protocol.php', // Autoloads class file.
            'name' => 'REDCapProtocols\Protocol',
        ],
```

For further information, you may open `classes/Entity.php` file and explore the `Entity` class methods that you can override.
