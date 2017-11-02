<?php

// Create a class to look up stock quotes
class stock_quotes {

	// Create empty private array to fill with stock quotes
	private $stock_quotes = array();

	// Set a prefix for Redis/cache keys and a timeout/expiration for said keys
	// Setting Redis timeout to zero (0) disables caching
	private $redis_key_prefix = 'stocks_';
	private $redis_expire = 60;

	// Set timeout (for Redis and cURL) as well as user agent for HTTP(S) requests to Yahoo Finance SQL API
	private $timeout = 2;
	private $user_agent = 'EricOC Stock Quote PHP Library / https://github.com/ericoc/old-stock-quotes-php-lib/';

	// Create constructor function to connect to Redis
	function __construct () {

		// Connect to Redis
		if ( (isset($this->redis_expire)) && ($this->redis_expire > 0) ) {

			$this->redis = new \Redis();
			$connect = @$this->redis->connect('127.0.0.1', 6379, $this->timeout);

			if (!$connect) {
				$this->redis = null;
			}
		}

	} // End constructor

	// Create destructor function to disconnect from Redis
	function __destruct () {

		// Disconnect from Redis
		if (isset($this->redis)) {
			$this->redis->close();
		}

	} // End destructor

	// Create a function to lookup stock quotes using Yahoo SQL API
	private function lookup_stock_quotes (array $lookup_stock_symbols) {

		// Build the URL to hit to retrieve stock quotes
		$url_base = 'https://query.yahooapis.com/v1/public/yql';
		$url_stock_symbols = "('" . implode("','", $lookup_stock_symbols) . "')";
		$url_query = '?q=select+*+from+yahoo.finance.quote+where+symbol+in+' . $url_stock_symbols;
		$url_options = '&format=json&diagnostics=false&env=store://datatables.org/alltableswithkeys';
		$url = $url_base . $url_query . $url_options;

		// Hit the Yahoo SQL API to retrieve stock quotes
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, $this->user_agent);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		curl_setopt($c, CURLOPT_TIMEOUT, $this->timeout);
		$r = curl_exec($c);
		$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		curl_close($c);

		// Decode the JSON response from the Yahoo SQL API
		$json = json_decode($r);
		if (isset($json->query->results->quote)) {
			$raw_stock_quotes = $json->query->results->quote;
		} else {
			$raw_stock_quotes = null;
		}

		// Add the single stock quote to the internal array if there is only one
		if (is_object($raw_stock_quotes)) {
			$this->add_stock_quote($raw_stock_quotes);

		// Loop through each stock quote adding the objects to the internal array if there are multiple
		} elseif (is_array($raw_stock_quotes)) {

			foreach ($raw_stock_quotes as $stock_quote) {
				$this->add_stock_quote($stock_quote);
			}
		}

	} // End lookup_stock_quotes function

	// Create a function to cache a stock quote for a specific stock symbol
	private function cache_write_stock_quote ($stock_quote) {

		// Cache the stock quote by temporarily storing the serialized information in Redis
		if (isset($this->redis)) {
			$key = $this->redis_key_prefix . $stock_quote->Symbol;
			$value = serialize($stock_quote);
			if ($this->redis->setex($key, $this->redis_expire, $value)) {
				return true;
			} else {
				return false;
			}

		// Return false if Redis is not available
		} else {
			return false;
		}

	} // End cache_write_stock_quote function

	// Create a function to check the cache for a specific stock symbol
	private function cache_read_stock_quote ($stock_symbol) {

		// Proceed only if Redis is available
		if (isset($this->redis)) {

			// Check Redis to see if a quote for the stock symbol exists in the cache
			$key = $this->redis_key_prefix . $stock_symbol;
			$exists = $this->redis->exists($key);

			// Return the result from the cache if it exists, otherwise return null
			if ($exists === true) {
				return unserialize($this->redis->get($key));
			} else {
				return null;
			}

		// Return null if Redis is not available
		} else {
			return null;
		}

	} // End cache_read_stock_quote function

	// Create a function to add a stock quote to the internal array
	private function add_stock_quote ($stock_quote) {

		// Add the stock quote to the internal array and cache it
		if ( (isset($stock_quote->Symbol)) && (!is_null($stock_quote->Symbol)) && (!empty($stock_quote->Symbol)) && (is_string($stock_quote->Symbol)) ) {
			$this->stock_quotes[$stock_quote->Symbol] = $stock_quote;
			$this->cache_write_stock_quote($stock_quote);
		}

	} // End add_stock_quote function

	// Create a function to return stock quotes from the internal array
	public function get_stock_quotes ($requested_stock_symbols = array()) {

		// Explode string out in to an array if necessary
		if ( (is_string($requested_stock_symbols)) && (!is_array($requested_stock_symbols)) ) {
			$requested_stock_symbols = explode(' ', $requested_stock_symbols);
		}

		// Ensure all stock symbols that are being requested are upper-case
		$requested_stock_symbols = array_map('strtoupper', $requested_stock_symbols);

		// Loop through each stock symbol that is being requested to check if their quotes already exist in the internal array
		$lookup_stock_symbols = array();
		foreach ($requested_stock_symbols as $requested_stock_symbol) {

			// Only add a stock symbol to lookup array if its quote is not already known
			if (!array_key_exists($requested_stock_symbol, $this->stock_quotes)) {

				// Check the cache for the stock quote as well
				$check_cache = $this->cache_read_stock_quote($requested_stock_symbol);
				if ( (isset($check_cache)) && (is_object($check_cache)) && (!is_null($check_cache)) ) {
					$this->stock_quotes[$requested_stock_symbol] = $check_cache;

				// Finally commit to only looking up stock quotes for symbols where the quote is not already known or in the cache
				} else {
					array_push($lookup_stock_symbols, $requested_stock_symbol);
				}
			}
		}

		// Look up what ever stock quotes are not already in the internal array - adding them to the internal array of objects
		if ( (isset($lookup_stock_symbols)) && (!empty($lookup_stock_symbols)) ) {
			$this->lookup_stock_quotes($lookup_stock_symbols);
		}

		// Create an array to only return the requested stock symbols
		$return = array();
		foreach ($requested_stock_symbols as $requested_stock_symbol) {
			if (array_key_exists($requested_stock_symbol, $this->stock_quotes)) {
				$return[$requested_stock_symbol] = $this->stock_quotes[$requested_stock_symbol];
			}
		}

		// Return array of objects; key is stock symbol, value is object of the stock quote
		return $return;

	} // End get_stock_quotes function

} // End stock_quotes class
