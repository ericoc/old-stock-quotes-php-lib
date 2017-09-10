
# Stock Quote PHP Library

This was primarily a challenge to myself to create a PHP library/class that could look up stock quotes for stock symbols in a really efficient way. I wanted to avoid any unnecessary or extraneous HTTPS requests to whatever API I was using. The script uses [this Yahoo SQL API](https://developer.yahoo.com/yql/console/?q=select%20*%20from%20yahoo.finance.quote%20where%20symbol%20in%20(%22YHOO%22%2C%22AAPL%22%2C%22GOOG%22%2C%22MSFT%22)&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys) (click the `Test` button...)

The `stock_quotes` PHP class within [`stock_quotes.class.php`](stock_quotes.class.php) will only look up stock quotes that it does not already know, does so using a single HTTPS request for multiple stock symbols, and caches the stock quotes within Redis.

## Features

* Assuming you have nothing cached in Redis (by prior runs of the class/script for example), all of the stock symbols will be looked up using Yahoo's API and quotes will be retrieved.
* The stock quotes for each symbol being looked up are stored in the internal `$stock_quotes` array until the `stock_quotes` object is destroyed (like at the end of the script).
* The stock quotes are also stored ("cached") in Redis and automatically expire after `$redis_expire` seconds which is set in [`stock_quotes.class.php`](stock_quotes.class.php).
* Every call to the `get_stock_quotes()` method checks that there is not already a stock quote for each symbol within the internal `$stock_quotes` array as well as the Redis cache before hitting the Yahoo API to avoid any possibility of unnecessary HTTPS requests to the API.
* Furthermore, only a single HTTPS request is made to Yahoo API for each call to the `get_stock_quotes()` method (for any stock symbols which a quote is not known already) since a single HTTPS request can retrieve stock quotes for multiple stock symbols.

## Example

Running the [`stock_quotes.php`](stock_quotes.php) demo will return something like the following (albeit with different numbers surely):

	$ php stock_quotes.php
	Array
	(
	    [INDU] => stdClass Object
	        (
	            [symbol] => INDU
	            [AverageDailyVolume] =>
	            [Change] => +13.01
	            [DaysLow] => 21731.12
	            [DaysHigh] => 21846.63
	            [YearLow] => 17883.60
	            [YearHigh] => 22179.10
	            [MarketCapitalization] =>
	            [LastTradePriceOnly] => 21797.79
	            [DaysRange] => 21731.12 - 21846.63
	            [Name] => Dow Jones Industrial Average
	            [Symbol] => INDU
	            [Volume] => 289404747
	            [StockExchange] => DJI
	        )

	    [^IXIC] => stdClass Object
	        (
	            [symbol] => ^IXIC
	            [AverageDailyVolume] =>
	            [Change] => -37.6772
	            [DaysLow] => 6354.9556
	            [DaysHigh] => 6391.4087
	            [YearLow] => 5034.4102
	            [YearHigh] => 6460.8398
	            [MarketCapitalization] =>
	            [LastTradePriceOnly] => 6360.1914
	            [DaysRange] => 6354.9556 - 6391.4087
	            [Name] => NASDAQ Composite
	            [Symbol] => ^IXIC
	            [Volume] => 1558104187
	            [StockExchange] => NIM
	        )

	    [^GSPC] => stdClass Object
	        (
	            [symbol] => ^GSPC
	            [AverageDailyVolume] =>
	            [Change] => -3.67
	            [DaysLow] => 2459.40
	            [DaysHigh] => 2467.11
	            [YearLow] => 2083.79
	            [YearHigh] => 2490.87
	            [MarketCapitalization] =>
	            [LastTradePriceOnly] => 2461.43
	            [DaysRange] => 2459.40 - 2467.11
	            [Name] => S&P 500
	            [Symbol] => ^GSPC
	            [Volume] => 2003871859
	            [StockExchange] => SNP
	        )

	    [AAPL] => stdClass Object
	        (
	            [symbol] => AAPL
	            [AverageDailyVolume] => 27292000
	            [Change] => -2.63
	            [DaysLow] => 158.53
	            [DaysHigh] => 161.15
	            [YearLow] => 102.53
	            [YearHigh] => 164.94
	            [MarketCapitalization] => 819.36B
	            [LastTradePriceOnly] => 158.63
	            [DaysRange] => 158.53 - 161.15
	            [Name] => Apple Inc.
	            [Symbol] => AAPL
	            [Volume] => 28611535
	            [StockExchange] => NMS
	        )

	    [HPQ] => stdClass Object
	        (
	            [symbol] => HPQ
	            [AverageDailyVolume] => 10538500
	            [Change] => -0.16
	            [DaysLow] => 19.08
	            [DaysHigh] => 19.26
	            [YearLow] => 13.77
	            [YearHigh] => 19.78
	            [MarketCapitalization] => 32.14B
	            [LastTradePriceOnly] => 19.12
	            [DaysRange] => 19.08 - 19.26
	            [Name] => HP Inc.
	            [Symbol] => HPQ
	            [Volume] => 5177911
	            [StockExchange] => NYQ
	        )

	    [GOOG] => stdClass Object
	        (
	            [symbol] => GOOG
	            [AverageDailyVolume] => 1641720
	            [Change] => -9.45
	            [DaysLow] => 924.88
	            [DaysHigh] => 936.99
	            [YearLow] => 727.54
	            [YearHigh] => 988.25
	            [MarketCapitalization] => 641.95B
	            [LastTradePriceOnly] => 926.50
	            [DaysRange] => 924.88 - 936.99
	            [Name] => Alphabet Inc.
	            [Symbol] => GOOG
	            [Volume] => 1011538
	            [StockExchange] => NMS
	        )

	    [NOPE] => stdClass Object
	        (
	            [symbol] => NOPE
	            [AverageDailyVolume] =>
	            [Change] =>
	            [DaysLow] =>
	            [DaysHigh] =>
	            [YearLow] =>
	            [YearHigh] =>
	            [MarketCapitalization] =>
	            [LastTradePriceOnly] =>
	            [DaysRange] =>
	            [Name] =>
	            [Symbol] => NOPE
	            [Volume] =>
	            [StockExchange] =>
	        )

	    [SNAP] => stdClass Object
	        (
	            [symbol] => SNAP
	            [AverageDailyVolume] => 25914300
	            [Change] => +0.17
	            [DaysLow] => 15.10
	            [DaysHigh] => 15.80
	            [YearLow] => 11.28
	            [YearHigh] => 29.44
	            [MarketCapitalization] => 18.34B
	            [LastTradePriceOnly] => 15.32
	            [DaysRange] => 15.10 - 15.80
	            [Name] => Snap Inc.
	            [Symbol] => SNAP
	            [Volume] => 35956509
	            [StockExchange] => NYQ
	        )

	)
	===
	[MSFT] Microsoft Corporation | Change: $-0.36 / Low: $73.84 / High: $74.44
	[GOOG] Alphabet Inc. | Change: $-9.45 / Low: $924.88 / High: $936.99
	[YELP] Yelp Inc. | Change: $0.01 / Low: $43.41 / High: $44.25
	[SPLK] Splunk Inc. | Change: $-1.24 / Low: $67.12 / High: $68.76


### Explanation

Per the output above, each key in the final `$quotes` (and `$quotes2`) array is a stock symbol with the value being an object containing that stock symbols quote.

Some cool stuff is going on in the background here:

* In the example of [`stock_quotes.php`](stock_quotes.php), assuming Redis is empty, stock quotes are looked up at Yahoo for all of the requested stock symbols in `$quotes`:
	- INDU
	- \^IXIC
	- \^GSPC
	- AAPL
	- HPQ
	- GOOG
	- NOPE
	- SNAP
* However, in the second call to the `get_stock_quotes()` method via `$quotes2`, a quote for GOOG is already known within the internal `$stock_quotes` array so the Yahoo API is only contacted for these stock symbols:
	- MSFT
	- YELP
	- SPLK
* If [`stock_quotes.php`](stock_quotes.php) was executed twice within `$redis_expire` seconds (before the stock quotes expired in Redis), no requests would be made to Yahoo at all. All of the stock quotes would already be known since they are available in Redis!


### Other Notes

* Even invalid stock symbols are cached in Redis and stored in the internal `$stock_quotes` array so that they will not be looked up using the Yahoo API unnecessarily.
* `$redis_expire` can be set to zero (`0`) in [`stock_quotes.class.php`](stock_quotes.class.php) to disable caching to Redis entirely.
* If a stock quote for a stock symbol is found within the `$stock_quotes` array or the Redis cache, it is not re-written to the Redis cache (so it will still expire at `$redis_expire` seconds from when it was _originally_ stored in Redis).
	- This prevents very stale stock quote data from being served by Redis in case a single stock symbol is repeatedly being requested every few seconds (i.e. more often than `$redis_expire`).
* A handy bash one-liner to monitor stock quotes that are available in Redis along with their TTL:

		watch -n 2 'for x in $(echo "keys *" | redis-cli  | grep stocks_);do echo -n "$x " && echo "ttl $x" | redis-cli;done'

	- if you change `$redis_key_prefix` to something other than `stocks_` in [`stock_quotes.class.php`](stock_quotes.class.php), adjust the above bash command appropriately
