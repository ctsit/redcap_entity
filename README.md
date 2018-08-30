# REDCap Entity
Provides features to design, store and manage custom entities in REDCap.



## Introduction

If you need to store, list, and manage custom content on REDCap (that cannot be expressed via data entry records), this module can come in handy for you and your team.

REDCap Entity provides:

- A high level way to define the data structure of your entity
- A flexible entity list engine (table-formatted, including pager, exposed filters and sortable columns)
- Admin operations (create, update, delete)
- API to manage your entities programmatically (CRUD)

It is important to emphasize that REDCap Entity works as a feature provider for custom external modules, **so developing a custom EM is required**.



## Prerequisites

- REDCap >= 8.4.3



## Installation

- Clone this repo into `<redcap-root>/modules/redcap_entity_api_v<version_number>` or download it from [REDCap Repo](https://redcap.vanderbilt.edu/consortium/modules/).
- Go to **Control Center > Manage External Modules** and enable REDCap Entity.



## Creating an entity type

The following step-by-step will walk you through a creation of a custom entity type from scratch. At this point, it is assumed **you have your own external module to work on**.

Obs.: a working EM that implements this example is provided at `examples/redcap_cars`.

### 1. Implement redcap_entity_types()

Your entity type is defined on `redcap_entity_types` hook. There, you specify:

- a label (e.g. "Car")
- the properties of your entity (e.g. car model, year, brand, color)
- the permitted operations (e.g. add, edit, delete)



ExternalModule.php

```php
<?php

    function redcap_entity_types() {
    	$types = [];
    
        $types['car_brand'] = [
            'label' => 'Car brand',
            'label_plural' => 'Car brands',
            'class' => [
                'name' => 'REDCapCar\Entity\CarBrand',
                'path' => 'classes/entity/CarBrand.php',
            ],
            'properties' => [
                'name' => [
                    'name' => 'Name',
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
                'project' => 'project_id',
            ],
            'operations' => ['create', 'update', 'delete'],
        ];
    
		$types['car'] = [
            'label' => 'Car',
            'label_plural' => 'Cars',
            'class' => [
                'name' => 'REDCapCar\Entity\Car',
                'path' => 'classes/entity/Car.php',
            ],
            'properties' => [
                'model' => [
                    'name' => 'Model',
                    'type' => 'text',
                    'required' => true,
                ],
                'year' => [
                    'name' => 'Year',
                    'type' => 'int',
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
                'employee' => [
                    'name' => 'Responsible employee',
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
                'label' => 'model',
                'project' => 'project_id',
                'author' => 'employee',
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