KvDynamoDb
==========

Key Value Persistence implemtation for dynamoDB, requires Generis 2.7


Code used to generate table
===========================

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


Persistence
===========

'serviceState' => array(
    'driver' => 'oat\kvDynamoDb\model\DynamoDbDriver',
    'key' => '***',
    'secret' => '***',
    'region' => 'eu-west-1',
    'table' => 'taoKeyValueStorage'
),
