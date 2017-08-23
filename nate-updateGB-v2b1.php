<?php

//////////// Make sure to enable PHP Curl extension in your php.ini file

////// NEW Poloniex API Key + Secret
$pkey = "";
$psec = "";
//////


////config path uncomment and adjust for your OS/Location
//$cpath = '/var/www/config/config.js';  //// Linux

$cpath = 'C:\Users\User\Desktop\Gunbot\config.js'; //// Windows


/////////
$volumeMin = '1000'; ///// Min Volume in BTC over 24hr to consider
$strategy = 'bbstepgain'; //// Strategy for normal trading

///Put your Override settings here for Help Coins -- Coins you own but no longer meet the min volume.
$overrideStrategy = 'bbstepgain'; //// Strategy for Help Coins
$override = array( 'SELLLVL1'=> 0.6,'SELLLVL'=>1, 'BUY_ENABLED'=>false ); //// Override settings for Help Coins

///////////////////////////////////////////////////////
////////////////DO NOT EDIT BELOW HERE/////////////////
///////////////////////////////////////////////////////
class poloniex {
                protected $api_key;
                protected $api_secret;
                protected $trading_url = "https://poloniex.com/tradingApi";
                protected $public_url = "https://poloniex.com/public";

                public function __construct($api_key, $api_secret) {
                        $this->api_key = $api_key;
                        $this->api_secret = $api_secret;
                }

                private function query(array $req = array()) {
                        // API settings
                        $key = $this->api_key;
                        $secret = $this->api_secret;

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
                        curl_setopt($ch, CURLOPT_URL, $this->trading_url);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

                        // run the query
                        $res = curl_exec($ch);

                        if ($res === false) throw new Exception('Curl error: '.curl_error($ch));
                        //echo $res;
                        $dec = json_decode($res, true);
                        if (!$dec){
                                //throw new Exception('Invalid data: '.$res);
                                return false;
                        }else{
                                return $dec;
                        }
                }
                



		protected function retrieveJSON($URL) {
                        $opts = array('http' =>
                                array(
                                        'method'  => 'GET',
                                        'timeout' => 10
                                )
                        );
                        $context = stream_context_create($opts);
                        $feed = file_get_contents($URL, false, $context);
                        $json = json_decode($feed, true);
                        return $json;
                }







                public function get_ticker($pair = "ALL") {
                        $pair = strtoupper($pair);
                        $prices = $this->retrieveJSON($this->public_url.'?command=returnTicker');
                        if($pair == "ALL"){
                                return $prices;
                        }else{
                                $pair = strtoupper($pair);
                                if(isset($prices[$pair])){
                                        return $prices[$pair];
                                }else{
                                        return array();
                                }
                        }
                }
                public function get_balances() {
                        return $this->query(
                                array(
                                        'command' => 'returnBalances'
                                )
                        );
                }


}
///////////////////////////////////////////////////////
$api = new Poloniex($pkey,$psec);

$balances = $api->get_balances();

$balances = array_filter($balances, function($value) {return $value !== '0.00000000';});

unset($balances['USDT']);
unset($balances['BTC']);

$balanceCoins = array_keys($balances);

$ticker = $api->get_ticker();
function array_key_exists_wildcard ( $array, $search, $return = '' ) {
    $search = str_replace( '\*', '.*?', preg_quote( $search, '/' ) );
    $result = preg_grep( '/^' . $search . '$/i', array_keys( $array ) );
    if ( $return == 'key-value' )
        return array_intersect_key( $array, array_flip( $result ) );
    return $result;
}

$search = "BTC_*";

$BTCpairs = array_key_exists_wildcard( $ticker, $search, 'key-value' ) ;

$highVolume = array_filter($BTCpairs, function ($var) use ($volumeMin) {
    return ($var['baseVolume'] > $volumeMin);
});

$coins = array_keys($highVolume);

$ownedPairs = $balanceCoins;

foreach ($ownedPairs as &$value) {
    $value = 'BTC_'.$value;
}
unset($value);

$helpcoins = array_diff($ownedPairs, $coins);

//print_r($helpcoins);
//print_r($coins);
//print_r($ownedPairs);


$coins = array_flip($coins);
$helpcoins = array_flip($helpcoins);

$jsonString = file_get_contents($cpath);


$data = json_decode($jsonString, true);

$data['pairs']['poloniex'] = array();


foreach($coins as &$value){
$value = array('strategy' => $strategy, 'override' => array());
}

foreach($helpcoins as &$value){
$value = array('strategy' => $overrideStrategy, 'override' => $override);
}

$newPairs = array_merge($coins,$helpcoins);


$data['pairs']['poloniex'] = $newPairs;

$newJsonString = json_encode($data, JSON_PRETTY_PRINT);
file_put_contents($cpath, $newJsonString);



?>
