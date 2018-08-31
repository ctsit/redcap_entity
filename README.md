# REDCap Entity
Provides features to design, store and manage custom entities in REDCap.

## Introduction

Let's say your team needs to create and manage additional content in REDCap that cannot be expressed as regular data entries (e.g. protocols, drugs, sites, prescriptions, papers, etc).

In this case, a few tasks need to be addressed like:

- Define your custom content storage (e.g. create a SQL table)
- Implement a form to add/edit your content
- Implement a page that lists your content
- Implement features to help you navigate through the list like a pager, filters, etc 

## Prerequisites

- REDCap >= 8.7.0

## Installation

- Clone this repo into `<redcap-root>/modules/redcap_entity_api_v<version_number>` or download it from [REDCap Repo](https://redcap.vanderbilt.edu/consortium/modules/).
- Go to **Control Center > Manage External Modules** and enable REDCap Entity.

## Creating an entity type

### 1. Create your external module

REDCap Entity does not create entities by itself. It requires a **child module** to define the structure of your entities.

So let's call our example module "REDCap Protocols".

config.json

```json
{
    "name": "REDCap Protocols",
}
```

Obs.: if you are not familiar with External Modules development, check this documentation.

### 2. Implement redcap_entity_types()

Your entity type is defined on `redcap_entity_types` hook. There, you specify:

- a label (e.g. "Protocol")
- the properties of your entity (e.g. title, status, PI, study site)
- the permitted operations (e.g. add, edit, delete)

Here we are going to define 2 entities, Protocol and Study site.

ExternalModule.php

```php
<?php

    function redcap_entity_types() {
    	$types = [];
    
        $types['study_site'] = [
            'label' => 'Study site',
            'label_plural' => 'Study sites',
            'class' => [
                'name' => 'REDCapCar\Entity\StudySite',
                'path' => 'classes/entity/StudySite.php',
            ],
            'properties' => [
                'name' => [
                    'name' => 'name',
                    'type' => 'text',
                    'required' => true,
                ],
                'project_id' => [
                    'name' => 'Project ID',
                    'type' => 'project',
                    'required' => true,
                ],
            ],
            'special_keys' => [
                'label' => 'name',
            ],
            'operations' => ['create', 'update', 'delete'],
        ];
    
		$types['protocol'] = [
            'label' => 'Protocol',
            'label_plural' => 'Protocols',
            'class' => [
                'name' => 'REDCapCar\Entity\Protocol',
                'path' => 'classes/entity/Protocol.php',
            ],
            'properties' => [
                'title' => [
                    'name' => 'Title',
                    'type' => 'text',
                    'required' => true,
                ],
                'status' => [
                    'name' => 'Status',
                    'type' => 'text',
                    'required' => true,
                ],
                'brand' => [
                    'name' => 'Brand',
                    'type' => 'entity_reference',
                    'entity_type' => 'car_brand',
                ],
                'color' => [
                    'name' => 'Color',
                    'type' => 'text',
                    'choices' => [
                    	'black' => 'Black',
                    	'blue' => 'Blue',
                    	'green' => 'Green',
                    	'red' => 'Red',
                    	'silver' => 'Silver',
                    	'white' => 'White',
                    ],
                ],
                'pi' => [
                    'name' => 'PI',
                    'type' => 'user',
                ],
                'driver' => [
                    'name' => 'Driver',
                    'type' => 'user',
                ],
                'project_id' => [
                    'name' => 'Project ID',
                    'type' => 'project',
                    'required' => true,
                ],
            ],
            'special_keys' => [
                'label' => 'title',
            ],
            'operations' => ['create', 'update', 'delete'],
            'bulk_operations' => [
                'remove_driver' => [
                    'label' => 'Remove driver',
                    'method' => 'removeDriver',
                    'messages' => [
                        'confirmation' => 'The drivers will be removed. This action cannot be undone.',
                        'success' => 'The drivers have been removed successfully',
                    ],
                ],
            ],
        ];

        return $types;
    }

```



##### label

Defines the label of your entity. It is used on entity lists.



##### label_plural

##### class

##### properties

Types:

- `text`
- `int`
- `date`
- `json`
- `blob`
- `project`
- `user`
- `entity_reference`: other entities can be referenced via this type, which requires an extra key - `entity_type`, defining the target entity type.

Dropdowns:

##### special_keys

- `label`
- `project`
- `author`

##### operations

##### bulk_operations





### 2. Entity class

A class is required to instantiate your entity. All properties defined on `hook_entity_types` must be defined



classes/entity/Car.php

```php
<?php
    
namespace REDCapCar\Entity;

use REDCapEntity\Entity;
    
class Car extends Entity {
    protected $model;
    protected $year;
    protected $brand;
    protected $color;
    protected $driver;
    protected $project_id;
}
```





## Creating a list of entities



## Customizing the entity form



## Using the API





### CRUD

#### Create

#### Retrieve

#### Update

#### Delete



### Query