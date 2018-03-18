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
		$response = $this->client->post('proxy', ['form_params' => $parameters]);
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
		list($parameters, $port) = $this->processArguments(\func_num_args(), $name, $parameters);
		$this->client->put("proxy/{$port}/har", ['form_params' => $parameters]);
	}

	/**
	 * Starts a new page on the existing HAR.
	 *
	 * @param string $name
	 * @param array  $parameters The payload to submit. The following are the allowed keys.
	 * 		'pageRef' 	=> (string) The string name of the first page ref that should be used in the HAR.
	 * 					            Optional, default to "Page N" where N is the next page number.
	 * 		'pageTitle' => (string) The title of new HAR page. Optional, default to pageRef.
	 */
	public function newHarPage($name = self::DEFAULT_PORT, array $parameters = [])
	{
		list($parameters, $port) = $this->processArguments(\func_num_args(), $name, $parameters);

		$this->client->put("proxy/{$port}/har", ['form_params' => $parameters]);
	}

	/**
	 * Get the HAR contents
	 *
	 * Returns the JSON/HAR content representing all the HTTP traffic passed through the proxy
	 *
	 * @param string $name
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function getHar($name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];
		return $this->client->get("proxy/{$port}/har")->getBody();
	}

	/**
	 * Displays whitelisted items
	 *
	 * @param string $name
	 * @return \Psr\Http\Message\StreamInterface
	 *
	 */
	public function getWhitelist($name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];
		return $this->client->get("proxy/{$port}/whitelist")->getBody();
	}

	/**
	 * Sets a list of URL patterns to whitelist
	 *
	 * @param string $name
	 * @param array  $parameters The payload to submit. The following are the allowed keys.
	 * 		'regex'  => (string) A comma separated list of regular expressions.
	 * 		'status' => (int) The HTTP status code to return for URLs that do not match the whitelist.
	 *
	 */
	public function whitelist($name = self::DEFAULT_PORT, array $parameters = [])
	{
		list($parameters, $port) = $this->processArguments(\func_num_args(), $name, $parameters);

		$this->client->put("proxy/{$port}/whitelist", ['form_params' => $parameters]);
	}

	/**
	 * Clears all URL patterns from the whitelist
	 *
	 * @param string $name
	 * @return \Psr\Http\Message\StreamInterface
	 *
	 */
	public function clearWhiteList($name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];
		return $this->client->delete("proxy/{$port}/whitelist")->getBody();
	}

	/**
	 * Displays blacklisted items
	 *
	 * @param string $name
	 * @return \Psr\Http\Message\StreamInterface
	 *
	 */
	public function getBlacklist($name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];
		return $this->client->get("proxy/{$port}/blacklist")->getBody();
	}

	/**
	 * Set a URL to blacklist
	 *
	 * @param string $name
	 * @param array  $parameters The payload to submit. The following are the allowed keys.
	 * 		'regex'  => (string) The blacklist regular expression.
	 * 		'status' => (int) The HTTP status code to return for URLs that are blacklisted.
	 * 		'method' => (string) The regular expression for matching HTTP method (GET, POST, PUT, etc). Optional, by default processing all HTTP method.
	 *
	 */
	public function blacklist($name = self::DEFAULT_PORT, array $parameters = [])
	{
		list($parameters, $port) = $this->processArguments(\func_num_args(), $name, $parameters);

		$this->client->put("proxy/{$port}/blacklist", ['form_params' => $parameters]);
	}

	/**
	 * Clears all URL patterns from the blacklist
	 *
	 * @param string $name
	 * @return \Psr\Http\Message\StreamInterface
	 *
	 */
	public function clearBlacklist($name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];
		return $this->client->delete("proxy/{$port}/blacklist")->getBody();
	}

	/**
	 * Limit the bandwidth through the proxy on the '[port]'
	 *
	 * @param string $name
	 * @param array  $parameters The payload to submit. The following are the allowed keys.
	 * 		'downstreamKbps'    => (int) Sets the downstream bandwidth limit in kbps. Optional.
	 * 		'upstreamKbps'      => (int) Sets the upstream bandwidth limit kbps. Optional, by default unlimited.
	 * 		'downstreamMaxKB'   => (int) Specifies how many kilobytes in total the client is allowed to download through the proxy. Optional, by default unlimited.
	 * 		'upstreamMaxKB'     => (int) Specifies how many kilobytes in total the client is allowed to upload through the proxy. Optional, by default unlimited.
	 * 		'latency'           => (int) Add the given latency to each HTTP request. Optional, by default all requests are invoked without latency.
	 * 		'enable'            => (boolean) A boolean that enable bandwidth limiter. Optional, by default to "false", but setting any of the properties above will implicitly enable throttling
	 * 		'payloadPercentage' => (int) Specifying what percentage of data sent is payload, e.g. use this to take into account overhead due to tcp/ip. Optional.
	 * 		'maxBitsPerSecond'  => (int) The max bits per seconds you want this instance of StreamManager to respect. Optional.
	 *
	 */
	public function limitBandwidth($name = self::DEFAULT_PORT, array $parameters = [])
	{
		list($parameters, $port) = $this->processArguments(\func_num_args(), $name, $parameters);

		$this->client->put("proxy/{$port}/limit", ['form_params' => $parameters]);
	}

	/**
	 * Displays the amount of data remaining to be uploaded/downloaded until the limit is reached
	 *
	 * @param string $name
	 * @return \Psr\Http\Message\StreamInterface
	 *
	 */
	public function getRemainingDataAmount($name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];
		return $this->client->get("proxy/{$port}/limit")->getBody();
	}

	/**
	 * Set and override HTTP Request headers
	 *
	 * @param string $name
	 * @param array  $parameters The payload to submit where key is a header name (such as "User-Agent") and value is a
	 * 							 value of HTTP header to setup (such as "BrowserMob-Agent").
	 *
	 * @example $instance->setOrOverrideHeaders('default', ["User-Agent" => "BrowserMob-Agent"]);
	 *
	 */
	public function setOrOverrideHeaders($name = self::DEFAULT_PORT, array $parameters = [])
	{
		list($parameters, $port) = $this->processArguments(\func_num_args(), $name, $parameters);

		$this->client->post("proxy/{$port}/headers", ['json' => $parameters]);
	}

	/**
	 * Overrides normal DNS lookups and remaps the given hosts with the associated IP address
	 *
	 * @param string $name
	 * @param array  $parameters The payload to submit where the keys are host names (such as "example.com") and values
	 * 							 are IP addresses (such as "1.2.3.4"').
	 *
	 * Example: overrideDNS('default',[
	 * 		"example.com" => "1.2.3.4",
	 * 		"foobar.com" => "10.10.10.10"
	 * ]);
	 *
	 */
	public function overrideDNS($name = self::DEFAULT_PORT, array $parameters = [])
	{
		list($parameters, $port) = $this->processArguments(\func_num_args(), $name, $parameters);

		$this->client->POST("proxy/{$port}/hosts", ['json' => $parameters]);
	}

	/**
	 * Sets automatic basic authentication for the specified domain
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $domain
	 * @param string $name
	 */
	public function autoBasicAuth($username, $password, $domain, $name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];

		$this->client->post("proxy/{$port}/auth/basic/{$domain}", [
			'json' => [
				'username' => $username,
				'password' => $password
			]
		]);
	}

	/**
	 * Wait until all request are made
	 *
	 * @param string $name
	 * @param array  $parameters The payload to submit. The following are the allowed keys.
	 * 		'quietPeriodInMs' => (int) Wait until all request are made. Optional.
	 * 		'timeoutInMs'     => (int) Sets quiet period in milliseconds. Optional.
	 *
	 */
	public function waitForNetworkTrafficToStop($name = self::DEFAULT_PORT, array $parameters = [])
	{
		list($parameters, $port) = $this->processArguments(\func_num_args(), $name, $parameters);

		$this->client->put("proxy/{$port}/wait", ['form_params' => $parameters]);
	}

	/**
	 * Handles different proxy timeouts
	 *
	 * @param string $name
	 * @param array  $parameters The payload to submit. The following are the allowed keys.
	 * 		'requestTimeout'    => (int) Request timeout in milliseconds. A timeout value of -1 is interpreted as infinite timeout. Optional, default to "-1".
	 * 		'readTimeout'       => (int) Read timeout in milliseconds. Which is the timeout for waiting for data or, put differently, a maximum period inactivity between two consecutive data packets). A timeout value of zero is interpreted as an infinite timeout. Optional, default to "60000".
	 * 		'connectionTimeout' => (int) Determines the timeout in milliseconds until a connection is established. A timeout value of zero is interpreted as an infinite timeout. Optional, default to "60000".
	 * 		'dnsCacheTimeout'   => (int) Sets the maximum length of time that records will be stored in this Cache. A nonpositive value disables this feature (that is, sets no limit). Optional, default to "0".Example: {"connectionTimeout" : "500", "readTimeout" : "200"}
	 *
	 */
	public function timeout($name = self::DEFAULT_PORT, array $parameters = [])
	{
		list($parameters, $port) = $this->processArguments(\func_num_args(), $name, $parameters);

		$this->client->put("roxy/{$port}/timeout", ['json' => $parameters]);
	}

	/**
	 * This allows redirects
	 *
	 * All URL matching the given $matchRegex will be redirected to the given $replacementURL
	 *
	 * @param string $matchRegex     The regex to match incoming URL against
	 * @param string $replacementURL The URL that matching URL will be directed to
	 * @param string $name
	 */
	public function urlRewrite($matchRegex, $replacementURL, $name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];

		$this->client->put("proxy/{$port}/rewrite", [
			'matchRegex' => $matchRegex,
			'replace' => $replacementURL
		]);
	}

	/**
	 * Removes all URL redirection rules currently in effect
	 *
	 * @param string $name
	 * @return \Psr\Http\Message\StreamInterface
	 *
	 */
	public function removeUrlRewriteRule($name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];
		return $this->client->delete("proxy/{$port}/rewrite")->getBody();
	}

	/**
	 * Set the retry count
	 *
	 * @param int $retryCount The number of times a method will be retried.
	 * @param string $name
	 *
	 */
	public function retryCount($retryCount, $name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];

		$this->client->put("proxy/{$port}/retry", [
			'form_params' => [
				'retrycount' => $retryCount
			]
		]);
	}

	/**
	 * Clears the DNS cache
	 *
	 * @param string $name
	 * @return \Psr\Http\Message\StreamInterface
	 *
	 */
	public function clearDnsCache($name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];
		return $this->client->delete("proxy/{$port}/dns/cache")->getBody();
	}

	/**
	 * Create your own request interception
	 *
	 * @param string $javascript Interceptor rules.
	 * @param string $name
	 *
	 */
	public function addJavascriptRequestInterceptors($javascript, $name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];

		$this->client->post("proxy/{$port}/filter/request", [
			'body' => $javascript
		]);
	}

	/**
	 * Create your own response interception
	 *
	 * @param string $javascript Interceptor rules.
	 * @param string $name
	 *
	 */
	public function addJavascriptResponseInterceptors($javascript, $name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];

		$this->client->post("proxy/{$port}/filter/response", [
			'body' => $javascript
		]);
	}

	/**
	 * Shuts down the proxy and closes the port.
	 *
	 * @param string $name
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function close($name = self::DEFAULT_PORT)
	{
		$port = $this->proxies[$name];
		return $this->client->delete("proxy/{$port}");
	}

	/**
	 * Get the array of ports each of which represents a proxy server
	 *
	 * @return int[]
	 */
	public function getProxyPorts()
	{
		return $this->proxies;
	}

	/**
	 * @param $numberOfArgs
	 * @param $name
	 * @param $parameters
	 * @return array
	 */
	protected function processArguments($numberOfArgs, $name, $parameters): array
	{
		switch ($numberOfArgs)
		{
			case 1:
				if (\is_array($name))
				{
					$parameters = $name;
					$port = $this->proxies[self::DEFAULT_PORT];
				}
			break;

			case 0:
				$port = $this->proxies[self::DEFAULT_PORT];
			break;

			default:
				$port = $this->proxies[$name];
			break;
		}
		return [$parameters, $port];
	}
}
