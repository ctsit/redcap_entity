# REDCap Entity Developers Guide

[![DOI](https://zenodo.org/badge/137758590.svg)](https://zenodo.org/badge/latestdoi/137758590)

Provides features to design, store and manage custom entities in REDCap.  This document describes how to write modules that use REDCap Entity to create, store, manage and present novel data types in REDCap.

## Prerequisites

- REDCap >= 8.7.0


## Easy Installation
REDCap Entity is available in the [REDCap Repo](https://redcap.vanderbilt.edu/consortium/modules/index.php).  To install it follow these steps:

- Access your REDCap installation's _View modules available in the REDCap repo_ button at **Control Center > External Modules** to download _REDCap Entity_.
- Once download, enable REDCap Entity. The module will be enabled globally.


## Manual Installation
- Clone this repo into `<redcap-root>/modules/redcap_entity_v0.0.0`.
- Go to **Control Center > External Modules** and enable REDCap Entity. The module will be enabled globally.


## Introduction

This module was designed to help teams that needs to create and manage additional content in REDCap that cannot be expressed as regular data entries (e.g. protocols, drugs, sites, prescriptions, papers, etc).

To develop this kind of feature, a few tasks need to be addressed such as:

- Design the data structure
- Choose the storage method/location
- Define the data input workflow
- Make the content accessible

REDCap Entity covers all aspects of these tasks. Here is an example of an admin UI that can be generated via this module:

TODO: insert image (list)
TODO: insert image (form)

Most of what you see on images above is configurable: the labels, the columns, the data types, the available operations, the filters, the page size, even the icon!

UF's CTS-IT team has successfully developed 2 modules using REDCap Entity:

- [Project Ownership](https://github.com/ctsit/project_ownership)
- [REDCap OnCore Client](https://github.com/ctsit/redcap_oncore_client)

## How it works

This module is a developer's tool, so in order to design entity types, you need to create a new module that contains a hook (which defines your entity structure) and a less-than-10-line plugin (to render the list or your admin UI).

Advanced customizations are also possible by extending the PHP classes provided by this module.

## Where entities are stored
The entities are stored into DB tables prefixed with `redcap_entity_`. Example: given a `protocol` entity type, its DB table is named as `redcap_entity_protocol`.

The 2 main advantages of this model are:

- Flexibility: each entity type has its own table, with its own properties/columns
- Database integrity: all operations are fully isolated from REDCap core's tables

Tables creation/removal can be managed via an UI provided by this module (only accessible by admins) or programmatically triggered via one-time hooks such as `redcap_module_system_enable()`.

Now, let's finally get started - the next sections will walk you through the process of desiging and managing your entities.

**Important:** all code examples to be explored have been wrapped up and placed at `examples/protocols_basic_v0.0.0` and `examples/protocols_advanced_v0.0.0` folders, which are external modules that can be used as templates to develop your own entities.

## Setting up entity types

### Step 1. Create your external module

REDCap Entity does not create entities by itself. It requires a **child module** to define the structure of your entities.

So let's call our example module "REDCap Protocols".

config.json

```json
{
    "name": "REDCap Protocols",
    "authors": [
        {
            "name": "Your name",
            "email": "youremail@example.com",
            "institution": "Your institution"
        }
    ],

}
```

Obs.: if you are not familiar with External Modules development, check [this documentation](https://github.com/vanderbilt/redcap-external-modules).


### Step 2. Implement redcap_entity_types()

Your entity type is defined on `redcap_entity_types` hook. There, you essentially specify:

- a label (e.g. "Protocol")
- the properties of your entity (e.g. title, status, PI, study site)

Here we are going to define 2 entities, Study site and Protocol.

ExternalModule.php

```php
<?php

    function redcap_entity_types() {
        $types = [];

        $types['study_site'] = [
            'label' => 'Study site',
            'label_plural' => 'Study sites',
            'icon' => 'home_pencil',
            'properties' => [
                'name' => [
                    'name' => 'Name',
                    'type' => 'text',
                    'required' => true,
                ],
                'address' => [
                    'name' => 'Address',
                    'type' => 'long_text',
                    'required' => true,
                ],
                'contact_email' => [
                    'name' => 'Contact email',
                    'type' => 'email',
                    'required' => true,
                ],
            ],
            'special_keys' => [
                'label' => 'name', // "name" represents the entity label.
            ],
        ];

        $types['protocol'] = [
            'label' => 'Protocol',
            'label_plural' => 'Protocols',
            'icon' => 'codebook',
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
                'label' => 'number', // "number" represents the entity label.
                'project' => 'project_id', // "project_id" represents the project which the entity belongs to.
                'author' => 'created_by', // "created_by" represents the entity author's username.
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

#### icon

Defines the icon that best describes your entity type. Valid icons can be found on REDCap images folder: `Resources/images`. Disregard file extension when using this setting (e.g. to choose "codebook.png" file, type "codebook" only).

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
- `date` (stored as [Epoch time](https://en.wikipedia.org/wiki/Unix_time))
- `json`
- `project`
- `email`
- `user`
- `entity_reference`: other entities can be referenced via this type, which requires an extra key - `entity_type`, defining the target entity type. Example ("protocol" referencing "study site"):

```php
<?php
    $types['protocol'] = [
        'properties' =>
            'study_site' => [
                'name' => 'Study site',
                'type' => 'entity_reference',
                'entity_type' => 'study_site',
            ],
```

If your property is required, you can enable `required` setting. Example:

```php
<?php

    'number' => [
        'name' => 'Number',
        'type' => 'text',
        'required' => true,
    ],
```

If your property needs to be presented as a list of options, you may set `choices` - a setting that expects an array of labels, keyed by option values. Example:

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

Extracted from "protocol" example:

```php
<?php

    'status' => [
        'name' => 'Status',
        'type' => 'text',
        'choices' => [
            'in_study' => 'In study',
            'pending' => 'Pending',
            'expired' => 'Expired',
        ],
    ],
```

Alternatively, you can set `choices_callback`, a setting that expects a callable string (i.e. function name, class method, etc) that returns the same `choices` structure. There are 2 types of lists: "dropdown" and "radios", which can be set via `choices_type` (if blank, "dropdown" is set). Example:

```php
<?php

    'status' => [
        'name' => 'Status',
        'type' => 'text',
        'choices_callback' => 'MyCustomModule::getStatusList',
        'choices_type' => 'radios',
    ],
```


#### special_keys

As you can see from the `protocol` entity type example, `special_keys` is used to add semantics to your properties. In other workds, you are telling REDCap Entity that a field has a special meaning. There are 3 special keys available:

- `label`: Use this setting if a property of your entity represents the label (e.g. Name, Title, etc). If not set, REDCap Entity takes the internal (auto-incremented) ID as the default label.
- `project`: Use this setting if your entity type is project contextualized (i.e. each project has its own of entities, which should not be visible from other projects). In this case, you need to specify a property to store the project ID (obs.: don't forget to set the property type as "project").
- `author`: Use this setting to tell REDCap Entity that the given property should store the content author. By doing that, the field automatically receives the current user on entity creation.

Extracted from "protocol" example:

```php
<?php

    'special_keys' => [
        'label' => 'number', // "number" represents the entity label.
        'project' => 'project_id', // "project_id" represents the project which the entity belongs to.
        'author' => 'created_by', // "created_by" represents the entity author's username
    ],
```

### Step 3. Firing database tables up

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

Don't worry about enabling your module multiple times - if the table already exists, nothing happens to it. However, if you want to reset your table for every enable event, you can explicitly set it on the 2nd paremeter:

```
<?php

\REDCapEntity\EntityDB::buildSchema($this->PREFIX, true)
```

So far we have covered the data structure design and the storage method. Let's now move forward to data input and visualization.

## Building an entity list / admin UI

#### Step 1. Defining links on config.json

Let's create 2 page links: one for study sites (on Control Center) and other one for protocols (on projects):

config.json
```
    "links": {
        "control-center": [
            {
                "name": "Study sites",
                "icon": "home_pencil",
                "url": "plugins/study-sites.php"
            }
        ],
        "project": [
            {
                "name": "Protocols",
                "icon": "codebook",
                "url": "plugins/protocols.php"
            }
        ]
    }
```

Here is the "Study sites" link, accessible from Control Center:

TODO: insert image

Here is the "Protocols" link, accessible from projects in which Protocols module is enabled:

TODO: insert image

#### Step 2. Creating plugins

Let's create the files referenced on the previous step.

pages/study-sites.php

```php
<?php

use REDCapEntity\EntityList;

$list = new EntityList('study_site', $module);
$list->setOperations(['create', 'update', 'delete']) // Allowing all operations.
    ->render('control_center'); // Context: Control Center.
```

By clicking on "Study sites" link, we can see the following result:

TODO: insert image

We can add a new study site by clicking on "+ Study site" button

TODO: insert image (form)

The form builder handles input validation, based on the data structure. Here is an example of error handling:

TODO: insert image

And here is the result after a sucessfull submit:

TODO: insert image (list after save)

Analogously, let's create pages/protocols.php file

```php
<?php

use REDCapEntity\EntityList;

$list = new EntityList('protocol', $module);
$list->setOperations(['create', 'update', 'delete'])
    ->render('project'); // Context: project.
```

Here is the result by clicking on "Protocols" link.

TODO: insert image.

There is a series of customizations that can be done, summarized on the example below:

```php
<?php
// TODO.
```

And here is the result:

TODO: insert image

Obs.: there is a 3rd possible context besides control center and projects: "global", which is the same context of pages like Home, My Projects, etc. [Project Ownership](https://github.com/ctsit/project_ownership) module is a good example of global context usage.

For further information, you may open `classes/EntityList.php` file and explore the `EntityList` class methods that you can use. For advanced needs which `EntityList`cannot cover, you can extend this class and use your own class instead. [Project Ownership](https://github.com/ctsit/project_ownership) module is a good example of advanced customization.

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

To customize the business logic of your entity, you may extend the `Entity` class, which is the default class used to instantiate entities. There are 2 steps to do it:

#### Step 1. Create your entity class

Let's say classes/Protocol.php

```php
<?php

namespace REDCapProtocols\Protocol;

use REDCapEntity\Entity;

class Protocol extends Entity {
    function approve() {
        $this->setData(['status' => 'in_study']);
        $this->save();
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

#### Bonus: Adding bulk operations

TODO.

## Customizing the entity form

TODO.
