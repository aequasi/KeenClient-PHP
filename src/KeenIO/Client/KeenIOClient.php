<?php

namespace KeenIO\Client;

use Guzzle\Service\Client;
use Guzzle\Common\Collection;
use Guzzle\Service\Description\ServiceDescription;

/**
 * Class KeenIOClient
 *
 * @package KeenIO\Client
 */
class KeenIOClient extends Client
{
	/**
	 * Factory to create new KeenIOClient instance. 
	 * 
	 * @param array $config
	 * @returns \KeenIO\Client\KeenIOClient
	 */
	public static function factory( $config = array() )
	{
		$default = array( 
			'baseUrl'	=> "https://api.keen.io/{version}/",
			'version'	=> '3.0',
			'masterKey'	=> null,
			'writeKey'	=> null,
			'readKey'	=> null,
			'projectId'	=> null
		);

		/* Create client configuration */
		$config = Collection::fromConfig( $config, $default );

		/* A bit strange: We need to make sure all of these items are also passed to each of the API commands -
			Doing it this way allows the Service Definitions to set what API Key accordingly (Read|Write|Master). */
		$parameters = array();
		foreach( array( 'masterKey', 'writeKey', 'readKey', 'projectId' ) as $key )
		{
			$parameters[ $key ] = $config->get( $key );
		}
		$config->set( 'command.params', $parameters );

		/* Create new KeenIOClient with our Configuration */
		$client = new self( $config->get('baseUrl'), $config );
	
		/* Set the Service Definition from the versioned file */
		$file = 'keen-io-' . str_replace( '.', '_', $client->getConfig('version') ) . '.json';
		$client->setDescription( ServiceDescription::factory( __DIR__ . "/../Resources/config/{$file}" ) );

		/* Set the content type header to use "application/json" for all requests */
		$client->setDefaultOption( 'headers', [ 'Content-Type' => 'application/json' ] );

		return $client;
	}

	/**
	 * Bulk insert events into a single event collection.
	 * TODO: Better error handling needed?
	 *
	 * @param string $collection
	 * @param array $events
	 * @param int $batches
	 * @throws InvalidArgumentException if $size is set higher than 5000
	 * @return array
	 */
	public function addBatchedEvents( $collection, $events = array(), $size = 500 )
	{
		$commands = array();

		$eventChunks = array_chunk( $events, $size );
		foreach( $eventChunks as $eventChunk )
		{
			$commands[] = $this->getCommand( "sendEvents", [ 'data' => [ $collection => $eventChunk ] ] );
		}

		try
		{
			$result = $this->execute( $commands );
		}
		catch ( CommandTransferException $e )
		{
			return array( 
				'total'		=> sizeof( $eventChunks ), 
				'succeeded'	=> sizeof( $e->getSuccessfulCommands() ),
				'failed'		=> sizeof( $e->getFailedCommands() )
			);
		}

		return array( 'batches' => sizeof( $eventChunks ), 'succeeded' => sizeof( $result ), 'failed' => 0 );
	}
	
	/**
	 * Get a scoped key for an array of filters
	 *
	 * @param $apiKey - the master API key to use for encryption
	 * @param $filters - what filters to encode into a scoped key
	 * @param $allowed_operations - what operations the generated scoped key will allow
	 * @return string
	 */
	public function getScopedKey( $apiKey, $filters, $allowed_operations, $source = MCRYPT_DEV_RANDOM )
	{
		$this->validateConfiguration();

		$options = array( 'filters' => $filters );

		if ($allowed_operations)
			$options['allowed_operations'] = $allowed_operations;

		$optionsJson = $this->padString( json_encode( $options ) );

		$ivLength = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC );
		$iv = mcrypt_create_iv( $ivLength, $source );

		$encrypted = mcrypt_encrypt( MCRYPT_RIJNDAEL_128, $apiKey, $optionsJson, MCRYPT_MODE_CBC, $iv );

		$ivHex = bin2hex( $iv );
		$encryptedHex = bin2hex( $encrypted );

		$scopedKey = $ivHex . $encryptedHex;

		return $scopedKey;
	}

	/**
	 * Decrypt a scoped key (primarily used for testing)
	 *
	 * @param $apiKey - the master API key to use for decryption
	 * @param $scopedKey
	 * @return mixed
	 */
	public function decryptScopedKey( $apiKey, $scopedKey )
	{
		$ivLength = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC ) * 2;
		$ivHex = substr( $scopedKey, 0, $ivLength );

		$encryptedHex = substr( $scopedKey, $ivLength );

		$resultPadded = mcrypt_decrypt(
			MCRYPT_RIJNDAEL_128,
			$apiKey,
			pack( 'H*', $encryptedHex ),
			MCRYPT_MODE_CBC,
			pack( 'H*', $ivHex )
		);

		$result = $this->unpadString( $resultPadded );

		$options = json_decode( $result, true );

		return $options;
	}


	/**
	 * Implement PKCS7 padding
	 *
	 * @param $string
	 * @param int $blockSize
	 * @return string
	 */
	protected function padString( $string, $blockSize = 32 )
	{
		$paddingSize = $blockSize - ( strlen( $string ) % $blockSize );
		$string .= str_repeat( chr( $paddingSize ), $paddingSize );

		return $string;
	}

	/**
	 * Remove padding for a PKCS7-padded string
	 *
	 * @param $string
	 * @return string
	 */
	protected function unpadString($string)
	{
		$len = strlen( $string );
		$pad = ord( $string[ $len - 1 ] );

		return substr( $string, 0, $len - $pad );
	}
}
