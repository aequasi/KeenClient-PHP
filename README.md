Keen IO PHP Library
===================
The Keen IO API lets developers build analytics features directly into their apps.

This client was built using [Guzzle](http://guzzlephp.org/), a PHP HTTP client
& framework for building RESTful web service clients.

Installation
------------
  1. edit `composer.json` file with following contents:

     ```json
     "require": {
        "keen-io/keen-io": "dev-master"
     }
     ```
  2. install composer via `curl -s http://getcomposer.org/installer | php` (on windows, download
     http://getcomposer.org/installer and execute it with PHP)
  3. run `php composer.phar install`

Use
---
Configure the service
```php
use KeenIO\Client\KeenIOClient;

$client = KeenIOClient::factory([ $projectId, $writeKey, $readKey ]);

```

###Send an event to Keen
Once you've set KEEN_PROJECT_ID and KEEN_WRITE_KEY, sending events is simple:

#####Example
```php
$event = [ 'purchase' => [ 'item' => 'Golden Elephant' ] ];

$client->addEvent( [ 'event_collection' => 'purchases', 'data' => $event ] );
```

###Send batched events to Keen
You can upload Events to multiple Event Collections at once.

#####Example
```php
$purchases = [
	[ 'purchase' => [ 'item' => 'Golden Elephant' ] ],
	[ 'purchase' => [ 'item' => 'Magenta Elephant' ] ]
];
$signUps = [
	[ 'name' => 'foo', 'email' => 'bar@baz.com' ]
];

$client->addEvents([ 'purchases' => $purchases, 'sign_ups' => $signUps ]); 
```

###Send batched events in parallel
Useful for large batch processing jobs.  From the [Guzzle docs](http://guzzlephp.org/webservice-client/webservice-client.html#executing-commands-in-parallel) on parallel commands:
	> he client will serialize each request and send them all in parallel. If an error is encountered during the transfer, then a Guzzle\Service\Exception\CommandTransferException is thrown, which allows you to retrieve a list of commands that succeeded and a list of commands that failed.

#####Example:
```php
$eventChunks = array_chunk( $events, 500 );
foreach( $eventChunks as $eventChunk )
{
	$commands[] = $this->getCommand( "sendEvents", [ 'data' => [ 'purchases' => $eventChunk ] ] );
}

$result = $this->execute( $commands );
```

###Get Analysis on Events
All Analysis Resources are supported.  See the [API Reference](https://keen.io/docs/api/reference/) Docs for required parameters.
Basic examples of a few below

#####Example
```php

//Count
$totalPurchases = $client->count([ 'event_collection' => 'purchases' ]);

//Count Unqiue
$totalItems = $client->countUnique([ 'event_collection' => 'purchases', 'target_property' => 'purchase.item' ]);

//Multi Analysis
$analyses = [
	'clicks'					=> [ "analysis_type" => "count" ],
	'average price'	=> [ "analysis_type" => "average", "target_property" => "purchase.price" ]
];
$stats = $client->multiAnalysis([ 'event_collection' => 'purchases', 'analyses' => $analyses ]);

###Create a Scoped Key

#####Example
```php
$filter = [
	'property_name'	=> 'id', 
	'operator'			=> 'eq', 
	'property_value'	=> '123'
];

$filters = [ $filter ];
$allowed_operations = [ 'read' ];

$scopedKey = $client->getScopedKey( <master api key>, $filters, $allowed_operations );
```

