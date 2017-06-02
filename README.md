# BlueDB

A PHP MySQL library with Database First model that lets you create Entities with base properties and complex relationships (*One-To-Many*, *Many-To-One*, *Many-To-Many*, *Table Inheritance*, *Associative tables*), supports queries with expressions, JSON encoding/decoding and is simple to use.

Latest release: [v1.1](https://github.com/GregaMohorko/bluedb/releases/latest)

## Documentation & Tutorials

You can read the documentation and tutorials under the [Wiki](https://github.com/GregaMohorko/bluedb/wiki).

## Short examples

Let's assume that we have an entity *User*.

Loading all entries:
```PHP
$all = User::loadList();
```
This would load all entries with all fields.

Let's say that we want only the *Username* fields:
```PHP
$all = User::loadList([User::UsernameField]);
```

How about loading only those users, whose *Username* starts with "Ja"?

Simple, we use the `Criteria` class:
```PHP
$criteria = new Criteria(User::class);
$criteria->add(Expression::startsWith(User::class, User::UsernameField, "Ja"));
$results = User::loadListByCriteria($criteria);
```

Encoding/decoding entities to/from JSON? No problem!
```PHP
$json = JSON::encode($entity);
$entity = JSON::decode($json);
```

Creating a new entry:
```PHP
$gordon = new User();
$gordon->Username = "Gordon";
User::save($gordon);
```

Updating entries:
```PHP
// let's change Gordons username to 'Freeman'
$gordon->Username = "Freeman";
User::update($gordon);
```

Deleting entries:
```PHP
// let's delete Gordon
User::delete($gordon);
```

These short examples were just the top of the iceberg, BlueDB has many many more cool features. To find them out and for more complex examples & tutorials, please go to [Wiki](https://github.com/GregaMohorko/bluedb/wiki).

## Requirements

PHP version >= 5.5

## License

[Apache License 2.0](./LICENSE)
