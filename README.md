# PHP Serializer
## Table of Contents

* [Installation](#installation)
* [Quick Start](#quick_start)
* [How to Contribute](#contribute)

#### other features:
- work recursively up to 50 nested objects/arrays
- handling \DateTime object
- implement Java style naming convention.

<a name="installation"></a>
## Installation

Add Serializer to your `composer.json` file. 

```json
{
  "require": {
    "itav/serializer": "~1.2"
  }
}
```
or simply  fire up on terminal:
```bash
composer require itav/serializer
```

Then at the top of your PHP script require the autoloader:

```bash
require 'vendor/autoload.php';
```                                 
<a name="quick_start"></a>
## Quick Start

Php Serializer allows you to switch between: 
####objects --- arrays --- json, xml

```php
<?php

use Itav\Component\Serializer\Serializer;

require_once '../vendor/autoload.php';

$serializer = new Serializer();

class Car
{
    private $model = 'SomeModel';
    private $color = 'red';
    /**
     * @var Part[]
     */
    private $parts;

    public function setParts($parts)
    {
        $this->parts = $parts;
    }
}
class Part
{
    private $number = 123;
    private $name = 'engine';

}

$car = new Car();
$car->setParts([new Part(), new Part(), new Part()]);

$array = $serializer->normalize($car);
$json = json_encode($array);

```

and also in reverse direction: 
####json --- arrays --- objects

```php
<?php

$json = <<<JSON
{  
   "model":"MyModel",
   "color":"red",
   "parts":[  
      {  
         "number":123,
         "name":"engine"
      },
      {  
         "number":124,
         "name":"lamp"
      },
      {  
         "number":125,
         "name":"wheel"
      }
   ]
}
JSON;

//$array = json_decode($json, true);

$array = [
    'model' => 'MyModel',
    'color' => 'red',
    'parts' => [
        0 => [
            'number' => 123,
            'name' => 'engine'
        ],
        1 => [
            'number' => 124,
            'name' => 'lamp'
        ],
        2 => [
            'number' => 125,
            'name' => 'wheel'
        ],
    ]
];

$car = $serializer->denormalize($array, Car::class);
```


 <a name="contribute"></a>
 ## How to Contribute
 write to me: sylwester7799@gmail.com
