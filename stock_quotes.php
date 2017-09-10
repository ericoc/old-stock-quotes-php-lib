<?php

// Load stock_quotes class
require_once('stock_quotes.class.php');

// Create stock quotes object and get quotes for various stock symbols
$stocks = new stock_quotes;
$quotes = $stocks->get_stock_quotes(array('indu', '^ixic', '^gspc', 'aapl', 'hpq', 'goog', 'nope', 'snap'));

// Print the stock quote information
print_r($quotes);

echo "===\n";

$quotes2 = $stocks->get_stock_quotes('MSFT GOOG yelp SPLK');

// Loop through each stock quote that was retrieved
foreach ($quotes2 as $quote2) {

	// Print the stock symbol
	echo '[' . $quote2->Symbol . '] ';

	// Proceed with showing the stock quote information if there is a valid company name
	if (isset($quote2->Name)) {

		echo $quote2->Name;

		echo ' | Change: $' . number_format($quote2->Change, 2, '.', ',');

		if (isset($quote2->DaysLow)) {
			echo ' / Low: $' . number_format($quote2->DaysLow, 2, '.', ',');
		}

		if (isset($quote2->DaysHigh)) {
			echo ' / High: $' . number_format($quote2->DaysHigh, 2, '.', ',');
		}

	// Otherwise, show an error if there is no company name for the stock symbol
	} else {
		echo 'Invalid stock symbol';
	}

	echo "\n";
}
