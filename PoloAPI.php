<?php

/**
 * Full wrapper for all methods documented at https://poloniex.com/support/api/
 * All command names are the same as in the API documentation except for returnTradeHistory as it exists in both the public and trading APIs..
 */

class Poloniex {

	protected $apiKey;
	protected $apiSecret;

	protected $publicUrl = "https://poloniex.com/public";
	protected $tradingApiUrl = "https://poloniex.com/tradingApi";

	public function __construct($apiKey = null, $apiSecret = null) {
		$this->apiKey = $apiKey;
		$this->apiSecret = $apiSecret;
	}

	protected function callPublic($call) {
		$uri = $this->publicUrl.'?'.http_build_query($call);
		return json_decode(file_get_contents($uri), true);
	}

	private function callTrading(array $req = array()) {
		// API settings
		$key = $this->apiKey;
		$secret = $this->apiSecret;

		// generate a nonce to avoid problems with 32bit systems
		$mt = explode(' ', microtime());
		$req['nonce'] = $mt[1].substr($mt[0], 2, 6);

		// generate the POST data string
		$post_data = http_build_query($req, '', '&');
		$sign = hash_hmac('sha512', $post_data, $secret);

		// generate the extra headers
		$headers = array(
			'Key: '.$key,
			'Sign: '.$sign,
		);

		// curl handle (initialize if required)
		static $ch = null;
		if (is_null($ch)) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT,
				'Mozilla/4.0 (compatible; Poloniex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')'
			);
		}
		curl_setopt($ch, CURLOPT_URL, $this->tradingApiUrl);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		// run the query
		$res = curl_exec($ch);

		if ($res === false) throw new Exception('Curl error: '.curl_error($ch));
		$dec = json_decode($res, true);
		if (!$dec){
			return false;
		}else{
			return $dec;
		}
	}

	//Public API Methods

	/**
	 * Returns the ticker for all markets.
	 * @return array
	 */
	public function returnTicker() {
		return $this->callPublic(
			array(
				'command' => 'returnTicker',
			)
		);
	}

	/**
	 * Returns the 24-hour volume for all markets, plus totals for primary currencies.
	 * @return array
	 */
	public function return24hVolume() {
		return $this->callPublic(
			array(
				'command' => 'return24hVolume',
			)
		);
	}

	/**
	 * Returns the order book for a given market, as well as a sequence number for use with the Push API and an indicator specifying whether the market is frozen.
	 * @param string $currencyPair Set to all to get the order books of all markets. Otherwise define a currency pair such as BTC_ETH
	 * @param integer $depth Limits the market to a certain amount of orders.
	 * @return array
	 */
	public function returnOrderBook($currencyPair = 'all', $depth = null) {
		return $this->callPublic(
			array(
				'command' => 'returnOrderBook',
				'currencyPair' => $currencyPair,
				'depth' => $depth,
			)
		);
	}

	/**
	 * Returns the past 200 trades for a given market, or up to 50,000 trades between a range specified in UNIX timestamps by the "start" and "end" GET parameters.
	 * @param string $currencyPair Example: BTC_ETH
	 * @param $start UNIX timestamp
	 * @param $end UNIX timestamp
	 * @return array
	 */
	public function returnPublicTradeHistory($currencyPair, $start = null, $end = null) {
		return $this->callPublic(
			array(
				'command' => 'returnTradeHistory',
				'currencyPair' => $currencyPair,
				'start' => $start,
				'end' => $end,
			)
		);
	}

	/**
	 * Returns candlestick chart data.
	 * @param string $currencyPair Example: BTC_ETH
	 * @param integer $period Candlestick period in seconds; valid values are 300, 900, 1800, 7200, 14400, and 86400.
	 * @param $start UNIX timestamp
	 * @param $end UNIX timestamp
	 * @return array
	 */
	public function returnChartData($currencyPair, $period, $start, $end) {
		return $this->callPublic(
			array(
				'command' => 'returnChartData',
				'currencyPair' => $currencyPair,
				'period' => $period,
				'start' => $start,
				'end' => $end,
			)
		);
	}

	/**
	 * Returns information about currencies.
	 * @return array
	 */
	public function returnCurrencies() {
		return $this->callPublic('returnCurrencies');
	}

	/**
	 * Returns the list of loan offers and demands for a given currency, specified by the "currency" GET parameter.
	 * @param string $currency Example: BTC
	 * @return array
	 */
	public function returnLoanOrders($currency) {
		return $this->callPublic(
			array(
				'command' => 'returnLoanOrders',
				'currency' => $currency,
			)
		);
	}

	//Trading API Methods

	/**
	 * Returns all of your available balances.
	 * @return array
	 */
	public function returnBalances() {
		return $this->callTrading( 
			array(
				'command' => 'returnBalances'
			)
		);
	}
	
	/**
	 * Returns all of your balances, including available balance, balance on orders, and the estimated BTC value of your balance. By default, this call is limited to your exchange account unless account is specified.
	 * @param $account Set the "account" POST parameter to "all" to include your margin and lending accounts.
	 * @return array
	 */
	public function returnCompleteBalances($account = null) {
		return $this->callTrading( 
			array(
				'command' => 'returnCompleteBalances',
				'account' => $account,
			)
		);
	}
	
	/**
	 * Returns all of your deposit addresses.
	 * @return array
	 */
	public function returnDepositAddresses() {		
		return $this->callTrading( 
			array(
				'command' => 'returnDepositAddresses',
			)
		);
	}
	
	/**
	 * Generates a new deposit address for the currency specified by the "currency" POST parameter.
	 * Only one address per currency per day may be generated, and a new address may not be generated before the previously-generated one has been used.
	 * @param $currency
	 * @return array
	 */
	public function generateNewAddress($currency) {		
		return $this->callTrading( 
			array(
				'command' => 'returnOpenOrders',
				'currency' => $currency,
			)
		);
	}

	/**
	 * Returns your deposit and withdrawal history within a range, specified by the "start" and "end" POST parameters, both of which should be given as UNIX timestamps.
	 * @param $start UNIX timestamp
	 * @param $end UNIX timestamp
	 * @return array
	 */
	public function returnDepositsWithdrawals($start, $end) {		
		return $this->callTrading( 
			array(
				'command' => 'returnDepositsWithdrawals',
				'start' => $start,
				'end' => $end,
			)
		);
	}

	/**
	 * Returns your open orders for a given market.
	 * @param $currencyPair Specify given market, e.g. "BTC_XCP". Defaults to "all" to return open orders for all markets.
	 * @return array
	 */
	public function returnOpenOrders($currencyPair = 'all') {		
		return $this->callTrading( 
			array(
				'command' => 'returnOpenOrders',
				'currencyPair' => $currencyPair,
			)
		);
	}
	
	/**
	 * Returns your trade history for a given market. If you do not specify a range, it will be limited to one day.
	 * @param $currencyPair Specifies which market, e.g. "BTC_XCP". Defaults to "all" to return your trade history for all markets. 
	 * @param $start UNIX timestamp
	 * @param $end UNIX timestamp
	 * @return array
	 */
	public function returnTradeHistory($currencyPair = 'all', $start = null, $end = null) {		
		return $this->callTrading( 
			array(
				'command' => 'returnTradeHistory',
				'currencyPair' => $currencyPair,
				'start' => $start,
				'end' => $end,
			)
		);
	}

	/**
	 * Returns all trades involving a given order, specified by the "orderNumber" POST parameter. If no trades for the order have occurred or you specify an order that does not belong to you, you will receive an error.
	 * @param $currency
	 * @return array
	 */
	public function returnOrderTrades($orderNumber) {		
		return $this->callTrading( 
			array(
				'command' => 'returnOrderTrades',
				'orderNumber' => $orderNumber,
			)
		);
	}	

	/**
	 * Places a limit buy order in a given market.
	 * You may optionally set "fillOrKill", "immediateOrCancel", "postOnly" to 1. A fill-or-kill order will either fill in its entirety or be completely aborted. An immediate-or-cancel order can be partially or completely filled, but any portion of the order that cannot be filled immediately will be canceled rather than left on the order book. A post-only order will only be placed if no portion of it fills immediately; this guarantees you will never pay the taker fee on any part of the order that fills.
	 * @param $currencyPair
	 * @param $rate
	 * @param $amount
	 * @return integer If successful, the method will return the order number.
	 */
	public function buy($currencyPair, $rate, $amount) {
		return $this->callTrading( 
			array(
				'command' => 'buy',	
				'currencyPair' => $currencyPair,
				'rate' => $rate,
				'amount' => $amount,
			)
		);
	}

	/**
	 * Places a sell order in a given market. Parameters and output are the same as for the buy method.
	 * @param $currencyPair
	 * @param $rate
	 * @param $amount
	 * @return integer If successful, the method will return the order number.
	 */	
	public function sell($currencyPair, $rate, $amount) {
		return $this->callTrading( 
			array(
				'command' => 'sell',	
				'currencyPair' => $currencyPair,
				'rate' => $rate,
				'amount' => $amount,
			)
		);
	}
	
	/**
	 * Cancels an order you have placed in a given market.
	 * @param $orderNumber
	 * @return array
	 */	
	public function cancelOrder($orderNumber) {
		return $this->callTrading( 
			array(
				'command' => 'cancelOrder',	
				'orderNumber' => $orderNumber,
			)
		);
	}
	
	/**
	 * Cancels an order and places a new one of the same type in a single atomic transaction, meaning either both operations will succeed or both will fail.
	 * @param $orderNumber
	 * @param $rate
	 * @param $amount You may optionally specify "amount" if you wish to change the amount of the new order.
	 * @return array
	 */	
	public function moveOrder($orderNumber, $rate, $amount = null) {
		return $this->callTrading( 
			array(
				'command' => 'cancelOrder',	
				'orderNumber' => $orderNumber,
				'rate' => $rate,
				'amount' => $amount,
			)
		);
	}
}
?>
