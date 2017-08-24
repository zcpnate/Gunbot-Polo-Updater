<?php
//////////// Make sure to enable PHP Curl extension in your php.ini file

////// NEW Poloniex API Key + Secret
$pkey = "";
$psec = "";
//////

////config path uncomment and adjust for your OS/Location
$cpath = '/var/www/config/config.js';  //// Linux

//$cpath = 'C:\Users\User\Desktop\Gunbot\config.js'; //// Windows


///High Level Coins
$highVolumeMin = '1000'; ///// Min Volume in BTC over 24hr to consider HIGH coin
$highStrategy = 'bbstepgain'; //// High Coin Strategy
$highBuyAmount = '.08'; //// High Coin Buy Amount
////For SG Sell Only
$highSELLLVL1 = '2'; ////High Coin SG lvl1
$highSELLLVL2 = '2.5'; ////High Coin SG lvl2
$highSELLLVL3 = '70'; ////High Coin SG lvl3

///Medium Level Coins
$medVolumeMin = '600'; ///// Min Volume in BTC over 24hr to consider MEDIUM coin
$medStrategy = 'bbstepgain'; //// Med Coin Strategy
$medBuyAmount = '.05'; //// Med Coin Buy Amount
//// For SG Sell Only
$medSELLLVL1 = '1'; ////Med Coin SG lvl1
$medSELLLVL2 = '1.5'; ////Med Coin SG lvl2
$medSELLLVL3 = '70'; ////Med Coin SG lvl2


///Put your Override settings here for Help Coins -- Coins you own but no longer meet the min volume.
$overrideStrategy = 'bbstepgain'; ////Help Coin Strategy
$override = array( 'SELLLVL1'=> 0.6,'SELLLVL'=>1, 'BUY_ENABLED'=>false ); ////Help Coin Override Settings

///////////////////////////////////////////////////////
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

$tradable = array_filter($BTCpairs, function ($var) use ($medVolumeMin) {
    return ($var['baseVolume'] > $medVolumeMin);
});

//print_r($tradable);

$highCoins = array_filter($tradable, function ($var) use ($highVolumeMin) {
    return ($var['baseVolume'] > $highVolumeMin);
});

//print_r($highCoins);

$medCoins = array_filter($tradable, function ($var) use ($highVolumeMin) {
    return ($var['baseVolume'] < $highVolumeMin);
});

//print_r($medCoins);


$tradableNames = array_keys($tradable);

$ownedPairs = $balanceCoins;

foreach ($ownedPairs as &$value) {
    $value = 'BTC_'.$value;
}
unset($value);

$helpcoins = array_diff($ownedPairs, $tradableNames);

//print_r($helpcoins);
//print_r($coins);
//print_r($ownedPairs);




$highCoins = array_keys($highCoins);
$medCoins = array_keys($medCoins);

$highCoins = array_flip($highCoins);
$medCoins = array_flip($medCoins);
$helpcoins = array_flip($helpcoins);
//$ownedPairs = array_flip($ownedPairs);

$jsonString = file_get_contents($cpath);


$data = json_decode($jsonString, true);

$data['pairs']['poloniex'] = array();

//$highStrat = $data['strategies'][$highStrategy];
//$medStrat = $data['strategies'][$medStrategy];


$highStrat['BTC_TRADING_LIMIT'] = (float)$highBuyAmount;
$medStrat['BTC_TRADING_LIMIT'] = (float)$medBuyAmount;

if(fnmatch("*stepgain*",$highStrategy)){
	$highStrat['SELLLVL1'] = (float)$highSELLLVL1;
	$highStrat['SELLLVL2'] = (float)$highSELLLVL2;
	$highStrat['SELLLVL3'] = (float)$highSELLLVL3;

	$highOverride = $highStrat; 
}else{
	$highOverride = $highStrat;
}

if(fnmatch("*stepgain*",$medStrategy)){
        $medStrat['SELLLVL1'] = (float)$medSELLLVL1;
        $medStrat['SELLLVL2'] = (float)$medSELLLVL2;
        $medStrat['SELLLVL3'] = (float)$medSELLLVL3;
	
	$medOverride = $medStrat;
}else{
        $medOverride = $medStrat;
}


foreach($highCoins as &$value){
$value = array('strategy' => $highStrategy, 'override' => $highOverride);
}

foreach($medCoins as &$value){
$value = array('strategy' => $medStrategy, 'override' => $medOverride);
}


foreach($helpcoins as &$value){
$value = array('strategy' => $overrideStrategy, 'override' => $override);
}

$newPairs = array_merge($highCoins,$medCoins,$helpcoins);



$data['pairs']['poloniex'] = $newPairs;

$newJsonString = json_encode($data, JSON_PRETTY_PRINT);
file_put_contents($cpath, $newJsonString);



?>
