# PHP Serializer
## Table of Contents

* [Installation](#installation)
* [Quick Start](#quick_start)
* [Usage](#usage)
* [How to Contribute](#contribute)

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

Php serializen allows you to switch between: 
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

    public function getParts()
    {
        return $this->parts;
    }

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

and also in revere direction: 
####json --- arrays --- objects

```php

```