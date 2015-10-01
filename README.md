KvDynamoDb
==========

Key Value Persistence implemtation for dynamoDB, requires Generis 2.7


Code used to generate table
===========================
```php
$client->createTable(array(
  'AttributeDefinitions' => array(
    array(
      'AttributeName' => 'key',
      'AttributeType' => 'S'
    )
  ) ,
  'KeySchema' => array(
    array(
      'AttributeName' => 'key',
      'KeyType' => 'HASH'
    )
  ) ,
  'ProvisionedThroughput' => array(
    'ReadCapacityUnits' => 10,
    'WriteCapacityUnits' => 5
  ) ,
  'TableName' => "taoKeyValueStorage"
)); 
```

Persistence
===========
```php
'serviceState' => array(
    'driver' => 'oat\kvDynamoDb\DynamoDbDriver',
    'client' => array(
        'region' => 'eu-west-1',
        'scheme' => 'http'
    ),
    'table' => 'taoKeyValueStorage'
),
```
