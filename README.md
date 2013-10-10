Keen IO PHP Library
===================
The Keen IO API lets developers build analytics features directly into their apps.

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

Usage
---

This client was built using [Guzzle](http://guzzlephp.org/), a PHP HTTP client & framework for building RESTful web service clients.

When you first create a new `KeenIOClient` instance, you can pass in your Project Id and API Keys, as well as an optional `version`
property that is used to version the API url and Service Description used by the Web Service Client.  For certain API Resources, the Master API Key is required and can also be passed in the configuration array.  

For Requests, the Client will determine what API Key should be passed based on the type of Request and configuration in the
[Service Description](/src/Resources/config/keen-io-3_0.json).

For a list of required and available parameters for the different Resources, please consult the Keen IO 
[API Reference](https://keen.io/docs/api/reference/).


####Configure the Client

#######Example
```php
use KeenIO\Client\KeenIOClient;

$client = KeenIOClient::factory([ 'projectId' => $projectId, 'writeKey' => $writeKey, 'readKey' => $readKey ]);

```

####Configuration can be updated to reuse the same Client:

######Example
```php

$client->getConfig()->set('masterKey', $masterApiKey );
$client->getConfig()->set('projectId', $newProjectId );

####Send an event to Keen
Once you've created a `KeenIOClient`, sending events is simple:

######Example
```php
$event = [ 'purchase' => [ 'item' => 'Golden Elephant' ] ];

$client->addEvent( [ 'event_collection' => 'purchases', 'data' => $event ] );
```

####Send batched events to Keen
You can upload multiple Events to multiple Event Collections at once!

######Example
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

####Send batched events in Parallel
Useful for large batch processing jobs.  From the [Guzzle docs](http://guzzlephp.org/webservice-client/webservice-client.html#executing-commands-in-parallel) on parallel commands:
> he client will serialize each request and send them all in parallel. If an error is encountered during the transfer, then a Guzzle\Service\Exception\CommandTransferException is thrown, which allows you to retrieve a list of commands that succeeded and a list of commands that failed.

######Example:
```php
$eventChunks = array_chunk( $events, 500 );
foreach( $eventChunks as $eventChunk )
{
	$commands[] = $this->getCommand( "sendEvents", [ 'data' => [ 'purchases' => $eventChunk ] ] );
}

$result = $this->execute( $commands );
```

####Get Analysis on Events
All Analysis Resources are supported.  See the [API Reference](https://keen.io/docs/api/reference/) Docs for required parameters.
You can also check the [Service Description](/src/Resources/config/keen-io-3_0.json) for configured API Endpoints.

######Example
```php

//Count
$totalPurchases = $client->count([ 'event_collection' => 'purchases' ]);

//Count Unqiue
$totalItems = $client->countUnique([ 'event_collection' => 'purchases', 'target_property' => 'purchase.item' ]);

//Select Unique
$items = $client->selectUnique([ 'event_collection' => 'purchases', 'target_property' => 'purchase.item' ]);

//Multi Analysis
$analyses = [
	'clicks'					=> [ "analysis_type" => "count" ],
	'average price'	=> [ "analysis_type" => "average", "target_property" => "purchase.price" ]
];
$stats = $client->multiAnalysis([ 'event_collection' => 'purchases', 'analyses' => $analyses ]);
```

###Create a Scoped Key

######Example
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

