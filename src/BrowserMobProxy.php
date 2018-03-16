<?php
namespace Sdxp;

use GuzzleHttp\Client;

/**
 * Class BrowserMobProxy
 * @package Sdxp
 */
class BrowserMobProxy
{
	/**
	 * Client $client
	 * */
	protected $client;
	/**
	 * The url of the BrowserMob service
	 *
	 * @var string
	 */
	protected $browserMobUrl;
	/**
	 * An array of open ports, each a proxy created when @see BrowserMobProxy::createProxy() is invoked
	 *
	 * @var int[]
	 */
	protected $proxies = [];
	/**
	 * The hostname of the BrowserMob service and hostname of the proxies created by @see BrowserMobProxy::createProxy()
	 *
	 * @var string
	 */
	protected $proxyHostname;

	/**
	 * BrowserMobProxy constructor
	 *
	 * It constructs the object and creates the initial proxy server
	 *
	 * @param       $browserMobAddress
	 * @param array $proxyParams The parameters that will be the payload to set up the initial proxy. @see BrowserMobProxy::createProxy()
	 */
	public function __construct($browserMobAddress, array $proxyParams = [])
	{
		$urlParts = parse_url($browserMobAddress);
		$this->proxyHostname = $urlParts['host'];
		$this->browserMobUrl = sprintf('%s://%s:%d/', $urlParts['scheme'], $urlParts['host'], $urlParts['port']);;
		$this->client = new Client(['base_uri' => $this->browserMobUrl]);

		$this->createProxy($proxyParams);
	}

	/**
	 * Creates a new proxy with the BrowserMob proxy service
	 *
	 * @param array $parameters The payload to submit. The following are the allowed keys.
	 * 		'port'              => (integer) The specific port to start the proxy service on.
	 * 										 Optional, default is generated and returned in response.
	 *
	 * 		'proxyUsername'     => (string) The username to use to authenticate with the chained proxy. Optional, default to null.
	 *
	 * 		'proxyPassword'     => (string) The password to use to authenticate with the chained proxy. Optional, default to null.
	 *
	 * 		'bindAddress'       => (string) If running BrowserMob Proxy in a multi-homed environment, specify a desired bind
	 * 							            address. Optional, default to "0.0.0.0".
	 *
	 * 		'serverBindAddress' => (string) If running BrowserMob Proxy in a multi-homed environment, specify a desired
	 * 									    server bind address. Optional, default to "0.0.0.0".
	 *
	 * 		'useEcc'            => (boolean) True, Uses Elliptic Curve Cryptography for certificate impersonation. Optional, default
	 * 							             to "false".
	 *
	 * 		'trustAllServers'   => (boolean) True, Disables verification of all upstream servers' SSL certificates. All
	 * 								         upstream servers will be trusted, even if they do not present valid certificates
	 * 								         signed by certification authorities in the JDK's trust store. Optional, default
	 * 								         to "false".
	 *
	 * @return int The port of the newly created proxy.
	 * */
	public function createProxy($parameters = []) : int
	{
		$response = $this->client->post('proxy', $parameters);
		$resBody = json_decode($response->getBody(), true);
		$this->proxies[] = $resBody['port'];
		return $resBody['port'];
	}

	
}
