<?php
namespace Sdxp;

use GuzzleHttp\Client;

/**
 * Class BrowserMobProxy
 * @see https://github.com/lightbody/browsermob-proxy
 * @package Sdxp
 */
class BrowserMobProxy
{
	/**
	 * The default port key
	 * */
	const DEFAULT_PORT = 'default';
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
		$this->browserMobUrl = sprintf('%s://%s:%d/', $urlParts['scheme'], $urlParts['host'], $urlParts['port']);
		$this->client = new Client(['base_uri' => $this->browserMobUrl]);

		$this->createProxy(self::DEFAULT_PORT, $proxyParams);
	}

	/**
	 * Creates a new proxy to run requests off of with the BrowserMob proxy service
	 *
	 * @param string $name       The name to associate with the port assigned to the new proxy.
	 * @param array  $parameters The payload to submit. The following are the allowed keys.
	 *        'port'              => (integer) The specific port to start the proxy service on.
	 *                                         Optional, default is generated and returned in response.
	 *        'proxyUsername'     => (string) The username to use to authenticate with the chained proxy. Optional, default to null.
	 *
	 *        'proxyPassword'     => (string) The password to use to authenticate with the chained proxy. Optional, default to null.
	 *
	 *        'bindAddress'       => (string) If running BrowserMob Proxy in a multi-homed environment, specify a desired bind
	 *                                        address. Optional, default to "0.0.0.0".
	 *
	 *        'serverBindAddress' => (string) If running BrowserMob Proxy in a multi-homed environment, specify a desired
	 *                                        server bind address. Optional, default to "0.0.0.0".
	 *
	 *        'useEcc'            => (boolean) True, Uses Elliptic Curve Cryptography for certificate impersonation. Optional, default
	 *                                         to "false".
	 *
	 *        'trustAllServers'   => (boolean) True, Disables verification of all upstream servers' SSL certificates. All
	 *                                         upstream servers will be trusted, even if they do not present valid certificates
	 *                                         signed by certification authorities in the JDK's trust store. Optional, default
	 *                                         to "false".
	 *
	 * @return int The port of the newly created proxy.
	 */
	public function createProxy($name, $parameters = []) : int
	{
		$response = $this->client->post('proxy', $parameters);
		$resBody = json_decode($response->getBody(), true);
		$this->proxies[$name] = $resBody['port'];
		return $resBody['port'];
	}

	/**
	 * Creates a new HAR attached to the proxy
	 *
	 * Indicates to the server to collect network information for the creation of a HAR
	 *
	 * @param string $name
	 * @param array  $parameters The payload to submit. The following are the allowed keys.
	 * 		'captureHeaders'	   => (boolean) capture headers or not. Optional, default to "false".
	 *		'captureCookies'	   => (boolean) capture cookies or not. Optional, default to "false".
	 *		'captureContent'	   => (boolean) capture content bodies or not. Optional, default to "false".
	 *		'captureBinaryContent' => (boolean) capture binary content or not. Optional, default to "false".
	 *		'initialPageRef'	   => (string) The string name of the first page ref that should be used in the HAR. Optional, default to "Page 1".
	 *		'initialPageTitle'	   => (string) The title of first HAR page. Optional, default to initialPageRef.
	 *
	 * @return void
	 */
	public function newHar($name = self::DEFAULT_PORT, array $parameters = [])
	{
		switch(\func_num_args())
		{
			case 1:
				if(\is_array($name))
				{
					$parameters = $name;
					$name = self::DEFAULT_PORT;
				}
			break;

			case 0:
				$name = self::DEFAULT_PORT;
			break;
		}

		$port = $this->proxies[$name];
		$this->client->put("proxy/{$port}/har", $parameters);
	}

	public function newHarPage()
	{
		
	}

	/**
	 * Get the HAR contents
	 *
	 * @param string $name
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function getHar($name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];
		return $this->client->get("proxy/{$port}/har")->getBody();
	}

	public function getProxyPorts()
	{
		return $this->proxies;
	}
}
