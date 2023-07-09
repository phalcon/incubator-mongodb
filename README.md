# Phalcon\Incubator\MongoDB

[![Discord](https://img.shields.io/discord/310910488152375297?label=Discord)](http://phalcon.io/discord)
[![Packagist Version](https://img.shields.io/packagist/v/phalcon/incubator-mongodb)](https://packagist.org/packages/phalcon/incubator-mongodb)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/phalcon/incubator-mongodb)](https://packagist.org/packages/phalcon/incubator-mongodb)
[![codecov](https://codecov.io/gh/phalcon/incubator-mongodb/branch/master/graph/badge.svg)](https://codecov.io/gh/phalcon/incubator-mongodb)
[![Packagist](https://img.shields.io/packagist/dd/phalcon/incubator-mongodb)](https://packagist.org/packages/phalcon/incubator-mongodb/stats)

## Issues tracker

https://github.com/phalcon/incubator/issues

## What is it

Set of helpers - simplifying working with mongodb via AR paradigm. 

## Helper 

`Phalcon\Incubator\MongoDB\Helper`

| Method                               | Description                                            |
|--------------------------------------|--------------------------------------------------------|
| `Helper::isValidObjectId($id)`       | Checks if id parameter is a valid ObjectID             |
| `Helper::convertDatetime($datetime)` | Converts a DateTime object to UTCDateTime from MongoDB |

## Collection Manager

Manager controls the initialization of collections, keeping record of relations between the different collections of the application.

```php
use Phalcon\Incubator\MongoDB\Mvc\Collection\Manager;

$di->set(
    'collectionsManager',
    function () {
        return new Manager();
    }
);
```

## Collection

ActiveRecord class for the management of MongoDB collections.

### Defining collection

```php
use Phalcon\Incubator\MongoDB\Mvc\Collection;

class RobotsCollection extends Collection
{
    public $code;

    public $theName;

    public $theType;

    public $theYear;
}

$robots = new RobotsCollection($data);
```

### Search examples

```php
use MongoDB\BSON\ObjectId;

// How many robots are there?
$robots = RobotsCollection::find();

echo "There are ", count($robots), "\n";

// How many mechanical robots are there?
$robots = RobotsCollection::find([
    [
        "type" => "mechanical",
    ],
]);

echo "There are ", count(robots), "\n";

// Get and print virtual robots ordered by name
$robots = RobotsCollection::findFirst([
    [
        "type" => "virtual",
    ],
    "order" => [
        "name" => 1,
    ],
]);

foreach ($robots as $robot) {
    echo $robot->name, "\n";
}

// Get first 100 virtual robots ordered by name
$robots = RobotsCollection::find([
    [
        "type" => "virtual",
    ],
    "order" => [
        "name" => 1,
    ],
    "limit" => 100,
]);

foreach (RobotsCollection as $robot) {
    echo $robot->name, "\n";
}

$robot = RobotsCollection::findFirst([
    [
        "_id" => new ObjectId("45cbc4a0e4123f6920000002"),
    ],
]);

// Find robot by using \MongoDB\BSON\ObjectId object
$robot = RobotsCollection::findById(
    new ObjectId("545eb081631d16153a293a66")
);

// Find robot by using id as sting
$robot = RobotsCollection::findById("45cbc4a0e4123f6920000002");

// Validate input
if ($robot = RobotsCollection::findById($_POST["id"])) {
    // ...
}
```

### Adding behavior

```php
use Phalcon\Incubator\MongoDB\Mvc\Collection;
use Phalcon\Incubator\MongoDB\Mvc\Collection\Behavior\Timestampable;

class RobotsCollection extends Collection
{
    public $code;

    public $theName;

    public $theType;

    public $theYear;
    
    protected function onConstruct()
    {
         $this->addBehavior(
             new Timestampable(
                 [
                     "beforeCreate" => [
                         "field"  => "created_at",
                         "format" => "Y-m-d",
                     ],
                 ]
             )
         );
    }
}
```
