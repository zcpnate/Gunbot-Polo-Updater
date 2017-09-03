<?php
require_once('PoloAPI.php');
require_once('BittrexAPI.php');

//////////// Make sure to enable PHP Curl and OpenSSL extension in your php.ini file

////// NEW Poloniex API Key + Secret////////////
$pKey = "";
$pSec = "";
////////////////////////////////////////////////

////// NEW Bittrex API Key + Secret ////////////////
$bKey = "";
$bSec = "";
//////////////////////////////////////////////////////

////config path uncomment and adjust for your OS/Location
//$cpath = '/var/www/config/config.js';  //// Linux

$cpath = 'C:\gb405\config.js'; //// Windows

///////////////////////////////Poloniex//////////////////////////////////////////////////
///High Level Coins
$pHighVolumeMin = '1200'; ///// Min Volume in BTC over 24hr to consider HIGH coin
$pHighStrategy = 'stepgain'; //// High Coin Strategy
$pHighBuyAmount = '.08'; //// High Coin Buy Amount
////For SG Sell Only
$pHighSELLLVL1 = '1.5'; ////High Coin SG lvl1
$pHighSELLLVL2 = '1.6'; ////High Coin SG lvl2
$pHighSELLLVL3 = '70'; ////High Coin SG lvl3

///Medium Level Coins
$pMedVolumeMin = '600'; ///// Min Volume in BTC over 24hr to consider MEDIUM coin
$pMedStrategy = 'stepgain'; //// Med Coin Strategy
$pMedBuyAmount = '.05'; //// Med Coin Buy Amount
//// For SG Sell Only
$pMedSELLLVL1 = '1.5'; ////Med Coin SG lvl1
$pMedSELLLVL2 = '1.6'; ////Med Coin SG lvl2
$pMedSELLLVL3 = '70'; ////Med Coin SG lvl2
/////////////////////////////////////////////////////////////////////////////////////////
$pManualConfig = array();
///////////////////////////////////////////////////////////////////////////////////////////


//////////////////////////////////Bittrex//////////////////////////////////////////////////
///High Level Coins
$bHighVolumeMin = '1200'; ///// Min Volume in BTC over 24hr to consider HIGH coin
$bHighStrategy = 'bbstepgain'; //// High Coin Strategy
$bHighBuyAmount = '.08'; //// High Coin Buy Amount
////For SG Sell Only
$bHighSELLLVL1 = '2'; ////High Coin SG lvl1
$bHighSELLLVL2 = '2.5'; ////High Coin SG lvl2
$bHighSELLLVL3 = '70'; ////High Coin SG lvl3

///Medium Level Coins
$bMedVolumeMin = '800'; ///// Min Volume in BTC over 24hr to consider MEDIUM coin
$bMedStrategy = 'bbstepgain'; //// Med Coin Strategy
$bMedBuyAmount = '.05'; //// Med Coin Buy Amount
//// For SG Sell Only
$bMedSELLLVL1 = '1'; ////Med Coin SG lvl1
$bMedSELLLVL2 = '1.5'; ////Med Coin SG lvl2
$bMedSELLLVL3 = '70'; ////Med Coin SG lvl3
/////////////////////////////////////////////////////////////////////////////////////////
$bManualConfig = array();
/////////////////////////////////////////////////////////////////////////////////////////


///Put your Override settings here for Help Coins -- Coins you own but no longer meet the min volume.
$overrideStrategy = 'stepgain'; ////Help Coin Strategy
$override = array( 'SELLLVL1'=> 0.8,'SELLLVL'=>1, 'BUY_ENABLED'=>false ); ////Help Coin Override Settings

///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////DO NOT EDIT BELOW HERE//////////////////
///////////////////////////////////////////////////////

//////Array Wildcard Search Functions/////////////////
function array_key_exists_wildcard ( $array, $search, $return = '' ) {
    $search = str_replace( '\*', '.*?', preg_quote( $search, '/' ) );
    $result = preg_grep( '/^' . $search . '$/i', array_keys( $array ) );
    if ( $return == 'key-value' )
        return array_intersect_key( $array, array_flip( $result ) );
    return $result;
}
 
function array_value_exists_wildcard ( $array, $search, $return = '' ) {
    $search = str_replace( '\*', '.*?', preg_quote( $search, '/' ) );
    $result = preg_grep( '/^' . $search . '$/i', array_values( $array ) );
    if ( $return == 'key-value' )
        return array_intersect( $array, $result );
    return $result;
}
 


/////////////////////////////////////////////////////




///////////////////Poloniex Calculations///////////////
if($pKey){
$pApi = new Poloniex($pKey,$pSec);

$pBalances = $pApi->returnBalances();


///////////////Polo TA Section////////////////////////
date_default_timezone_set( 'UTC' );
$values = $pApi->returnChartData( 'USDT_BTC',900, strtotime( "-24 hours" ), time() );
//print_r( $values);
$close = array();
$high = array();
$low = array();
$wa = array();
$open = array();

foreach ( $values as $a )
{
    $open[] = $a['open'];
    $close[] = $a['close'];
    $high[] = $a['high'];
    $low[] = $a['low'];
    $wa[] = $a['weightedAverage'];	
}

//$macd = trader_macdfix($wa,14);
//print_r($macd);
$adx = trader_adx($high, $low, $close,14);
//echo "adx";
//print_r($adx);
//$dx = trader_dx($high, $low, $close,20);
//print_r($dx);
//$plusDi = trader_plus_di($high, $low, $close, 14);
//$minusDi = trader_minus_di($high, $low, $close, 14);
//echo "plusdi";
//print_r($plusDi);
//echo "minusdi";
//print_r($minusDi);

$ap = trader_avgprice($open,$high,$low,$close);
//print_r($ap);

$newdata = array();
for ($i=0; $i < count($values); $i++)
{
//        $cadx = $adx[$i];
//	$cpdi = $plusDi[$i];
//	$cmdi = $minusDi[$i];
//	$cap = $ap[$i];
	$price = "";
	$direction = "";
        $btcpanic = "";
	$trend = "";

	if($adx[$i] > 20){
	$trend = "strong";
	}elseif($adx[$i] < 20){
	$trend = "stable";
	}


	$change = "";
	$change = (1 - $ap[$i-1] / $ap[$i]) * 100;
	if($change > .5 && $trend == 'strong'){
		$btcpanic = "yes";
	}else{
		$btcpanic = "no";
	}	
	if($change > 0){
		$direction = "up";
	}else{
		$direction = "down";
	}
	$newdata[$i] = array("epoch"=>date("Y-m-d H:i:s", substr($values[$i]['date'], 0, 10)),"adx"=>$adx[$i],"trend"=>$trend,"ap"=>$ap[$i],"direction"=>$direction,"BTCpanic"=>$btcpanic,"change"=>$change);

	    
}
//print_r($newdata);

//$combine = array_combine($adx, $dx);
//print_r($combine);
//print_r($newdata[count($newdata)-1]);
if($newdata[count($newdata)-1]['BTCpanic'] == 'yes'){
	
$btcpanic = "yes";
}
//$btcpanic = "yes";

$pBalances = array_filter($pBalances, function($value) {return $value !== '0.00000000';});

unset($pBalances['USDT']);
unset($pBalances['BTC']);

$pBalanceCoins = array_keys($pBalances);


$pTicker = $pApi->returnTicker();

$pSearch = "BTC_*";

$pBTCpairs = array_key_exists_wildcard( $pTicker, $pSearch, 'key-value' ) ;

$pTradable = array_filter($pBTCpairs, function ($var) use ($pMedVolumeMin) {
    return ($var['baseVolume'] > $pMedVolumeMin);
});

foreach ($pManualConfig as &$value) {
    $value = 'BTC_'.$value;
}
unset($value);

//print_r($pManualConfig);

//print_r($pTradable);

$pManualConfig = array_flip($pManualConfig);

//print_r($pManualConfig);

$pTradable = array_diff_key($pTradable,$pManualConfig);

//print_r($pTradable);


$pHighCoins = array_filter($pTradable, function ($var) use ($pHighVolumeMin) {
    return ($var['baseVolume'] > $pHighVolumeMin);
});

//print_r($pHighCoins);

$pMedCoins = array_filter($pTradable, function ($var) use ($pHighVolumeMin) {
    return ($var['baseVolume'] < $pHighVolumeMin);
});

//print_r($pMedCoins);


$pTradableNames = array_keys($pTradable);

$activeCoinData = array();

/////////TA For Tradable Coins//////////
for ($i=0; $i < count($pTradableNames); $i++)
{
$activeCoinData[$pTradableNames[$i]] = array("adx"=>'',"trend"=>'',"ap"=>'',"direction"=>'',"change"=>'',"pump"=>'',"dump"=>'');

$coinVals = $pApi->returnChartData($pTradableNames[$i],900,strtotime( "-24 hours" ), time() );

$cadx = array();
$cap = array();
$cclose = array();
$chigh = array();
$clow = array();
$copen = array();

foreach($coinVals as $a){
	$copen[] = $a['open']*100000000;
	$cclose[] = $a['close']*100000000;
	$chigh[] = $a['high']*100000000;
	$clow[] = $a['low']*100000000;
}

$cadx = trader_adx($chigh, $clow, $cclose, 14);
$cap = trader_avgprice($copen,$chigh,$clow,$cclose);

$coinData = array();
$currentADX = "";
$currentAP = "";
$lastAP = "";
$trend = "";
$change = "";
$pump = "";
$dump = "";
$direction = "";

$lastAP = $cap[count($cap)-2];
$currentADX = $cadx[count($cadx)-1];
$currentAP = $cap[count($cap)-1];
if($currentADX >= 22){
	$trend = "strong";
}elseif($currentADX < 22){
	$trend = "stable";
	$pump = "no";
	$dump = "no";
	$direction = "steady";
}
$change = (1 - $lastAP / $currentAP) * 100;
if($change > 1 && $trend == 'strong'){
	$pump = "yes";
	$dump = "no";
}elseif($change > 0 && $trend == 'strong'){
        $dump = "no";
        $pump = "coming";
}

if($change > 0){
	$direction = "up";
}
if($change < -.8 && $trend == 'strong'){
	$dump = "yes";
	$pump = "no";
}elseif($change <= 0 && $trend == 'strong'){
        $dump = "coming";
        $pump = "no";
}

if($change < 0){
	$direction = "down";
}

$activeCoinData[$pTradableNames[$i]]['change'] = $change;
$activeCoinData[$pTradableNames[$i]]['direction'] = $direction;
$activeCoinData[$pTradableNames[$i]]['dump'] = $dump;
$activeCoinData[$pTradableNames[$i]]['pump'] = $pump;
$activeCoinData[$pTradableNames[$i]]['trend'] = $trend;
$activeCoinData[$pTradableNames[$i]]['adx'] = $currentADX; 
$activeCoinData[$pTradableNames[$i]]['ap'] = $currentAP; 


}

//print_r($activeCoinData);

$pDumpCoins = array_filter($activeCoinData, function ($var) use ($dump) {
    return ($var['dump'] == 'yes');
});
$pPumpCoins = array_filter($activeCoinData, function ($var) use ($pump) {
    return ($var['pump'] == 'yes');
});

//print_r($pDumpCoins);
//print_r($pPumpCoins);

$pDumpNames = array_keys($pDumpCoins);

//print_r($pDumpNames);


$pOwnedPairs = $pBalanceCoins;

foreach ($pOwnedPairs as &$value) {
    $value = 'BTC_'.$value;
}
unset($value);
$pTradableNames = array_diff($pTradableNames, $pDumpNames);
$pOwnedPairs = array_diff($pOwnedPairs, $pDumpNames);

//print_r($pOwnedPairs);
//print_r($pTradableNames);

$pHelpCoins = array_diff($pOwnedPairs, $pTradableNames);
for ($i=0; $i < count($pDumpNames); $i++)
{
$pHelpCoins[] = $pDumpNames[$i];
}
//print_r($pHelpCoins);
//print_r($coins);
//print_r($pOwnedPairs);




$pHighCoins = array_keys($pHighCoins);
$pMedCoins = array_keys($pMedCoins);

$pHighCoins = array_flip($pHighCoins);
$pMedCoins = array_flip($pMedCoins);
$pHelpCoins = array_flip($pHelpCoins);
//$pOwnedPairs = array_flip($pOwnedPairs);

//print_r($pHighCoins);


$pHighStrat['BTC_TRADING_LIMIT'] = (float)$pHighBuyAmount;
$pMedStrat['BTC_TRADING_LIMIT'] = (float)$pMedBuyAmount;

if(fnmatch("*stepgain*",$pHighStrategy)){
	$pHighStrat['SELLLVL1'] = (float)$pHighSELLLVL1;
	$pHighStrat['SELLLVL2'] = (float)$pHighSELLLVL2;
	$pHighStrat['SELLLVL3'] = (float)$pHighSELLLVL3;

	$pHighOverride = $pHighStrat; 
}else{
	$pHighOverride = $pHighStrat;
}

if(fnmatch("*stepgain*",$pMedStrategy)){
        $pMedStrat['SELLLVL1'] = (float)$pMedSELLLVL1;
        $pMedStrat['SELLLVL2'] = (float)$pMedSELLLVL2;
        $pMedStrat['SELLLVL3'] = (float)$pMedSELLLVL3;
	
	$pMedOverride = $pMedStrat;
}else{
        $pMedOverride = $pMedStrat;
}


if($btcpanic == 'yes'){
$pHighStrategy = $overrideStrategy;
$pMedStrategy = $overrideStrategy;
$pHighOverride = $override;
$pMedOverride = $override;
}




foreach($pHighCoins as &$value){
$value = array('strategy' => $pHighStrategy, 'override' => $pHighOverride);
}

foreach($pMedCoins as &$value){
$value = array('strategy' => $pMedStrategy, 'override' => $pMedOverride);
}


foreach($pHelpCoins as &$value){
$value = array('strategy' => $overrideStrategy, 'override' => $override);
}

$pNewPairs = array_merge($pHighCoins,$pMedCoins,$pHelpCoins);

}
////////////////End Poloniex Calculations/////////////////////////////////

////////////////////Bittrex Calculations//////////////////////////////////
if($bKey){
$bApi = new Client ($bKey, $bSec);

$bTicker = $bApi->getMarketSummaries();

$bSearch = "BTC-*";

$bTicker = json_decode(json_encode($bTicker), True);

if($bKey){
$bBalances = $bApi->getBalances();
}

$bBalances = json_decode(json_encode($bBalances), True);

$bOwnedPairs = array();

for ($i=0; $i < count($bBalances); $i++)
{
        $bBalance = $bBalances[$i]['Available'];
     if ($bBalance !== 0){
        $bOwnedPairs[] = $bBalances[$i]['Currency']; 
    }
}

$bOwnedPairs = array_flip($bOwnedPairs);
unset($bOwnedPairs['USDT']);
unset($bOwnedPairs['BTC']);

$bOwnedPairs = array_keys($bOwnedPairs);

foreach ($bOwnedPairs as &$value) {
    $value = 'BTC-'.$value;
}
unset($value);

foreach ($bManualConfig as &$value) {
    $value = 'BTC-'.$value;
}
unset($value);

//print_r($bBalances);


//$bBTCpairs = array_value_exists_wildcard( $bTicker, $bSearch, '' ) ;
$bBTCpairs = array();

for ($i=0; $i < count($bTicker); $i++)
{
	$bMarket = $bTicker[$i]['MarketName'];
      if (fnmatch("BTC-*",$bMarket)){
	$bBTCpairs[] = $bTicker[$i];
     }
}

$bTradable = array_filter($bBTCpairs, function ($var) use ($bMedVolumeMin) {
    return ($var['BaseVolume'] > $bMedVolumeMin);
});


$bHighCoins = array_filter($bTradable, function ($var) use ($bHighVolumeMin) {
    return ($var['BaseVolume'] > $bHighVolumeMin);
});

//print_r($pHighCoins);

$bMedCoins = array_filter($bTradable, function ($var) use ($bHighVolumeMin) {
    return ($var['BaseVolume'] < $bHighVolumeMin);
});



$bHighCoinNames = array();
$bMedCoinNames = array();

$bHighCoins = array_values($bHighCoins);
$bMedCoins = array_values($bMedCoins);


for ($i=0; $i < count($bHighCoins); $i++)
{
        $bHighCoinNames[] = $bHighCoins[$i]['MarketName'];
}
for ($i=0; $i < count($bMedCoins); $i++)
{
        $bMedCoinNames[] = $bMedCoins[$i]['MarketName'];
}

$bHighCoins = array_flip($bHighCoinNames);
$bMedCoins = array_flip($bMedCoinNames);

$bTradableNames = array_keys(array_merge($bHighCoins,$bMedCoins));


//print_r($bOwnedPairs);
//print_r($bTradableNames);

$bTradableNames = array_diff($bTradableNames, $bManualConfig);
//print_r($bTradableNames);
$bHelpCoins = array_diff($bOwnedPairs, $bTradableNames);


$bHelpCoins = array_flip($bHelpCoins);

//print_r($bHighCoinNames);
//print_r($bMedCoinNames);

$bHighStrat['BTC_TRADING_LIMIT'] = (float)$bHighBuyAmount;
$bMedStrat['BTC_TRADING_LIMIT'] = (float)$bMedBuyAmount;

if(fnmatch("*stepgain*",$bHighStrategy)){
        $bHighStrat['SELLLVL1'] = (float)$bHighSELLLVL1;
        $bHighStrat['SELLLVL2'] = (float)$bHighSELLLVL2;
        $bHighStrat['SELLLVL3'] = (float)$bHighSELLLVL3;

        $bHighOverride = $bHighStrat;
}else{
        $bHighOverride = $bHighStrat;
}

if(fnmatch("*stepgain*",$bMedStrategy)){
        $bMedStrat['SELLLVL1'] = (float)$bMedSELLLVL1;
        $bMedStrat['SELLLVL2'] = (float)$bMedSELLLVL2;
        $bMedStrat['SELLLVL3'] = (float)$bMedSELLLVL3;

        $bMedOverride = $bMedStrat;
}else{
        $bMedOverride = $bMedStrat;
}

foreach($bHighCoins as &$value){
$value = array('strategy' => $bHighStrategy, 'override' => $bHighOverride);
}

foreach($bMedCoins as &$value){
$value = array('strategy' => $bMedStrategy, 'override' => $bMedOverride);
}


foreach($bHelpCoins as &$value){
$value = array('strategy' => $overrideStrategy, 'override' => $override);
}

$bNewPairs = array_merge($bHighCoins,$bMedCoins,$bHelpCoins);
}
//////////////////////End Bittrex Calculations////////////////////////////

//////////////Config Updating/////////////////////

$jsonString = file_get_contents($cpath);

$data = json_decode($jsonString, true);

$pManualConfig = array_flip($pManualConfig);

$pManualCoins = array();

for ($i=0; $i < count($pManualConfig); $i++)
{
//print_r( $data['pairs']['poloniex'][$pManualConfig[$i]]);

	if(array_key_exists($pManualConfig[$i],$data['pairs']['poloniex'])){

        $pManualCoins[$pManualConfig[$i]]  = $data['pairs']['poloniex'][$pManualConfig[$i]];

	}
}
//print_r($pManualCoins);

$pNewPairs = array_merge($pNewPairs, $pManualCoins);

$bManualCoins = array();

for ($i=0; $i < count($bManualConfig); $i++)
{

        if(array_key_exists($bManualConfig[$i],$data['pairs']['bittrex'])){

        $bManualCoins[$bManualConfig[$i]]  = $data['pairs']['bittrex'][$bManualConfig[$i]];

        }
}
//print_r($bManualCoins);

$bNewPairs = array_merge($bNewPairs, $bManualCoins);


$data['pairs']['poloniex'] = array();


$data['pairs']['bittrex'] = array();

//$pHighStrat = $data['strategies'][$pHighStrategy];
//$pMedStrat = $data['strategies'][$pMedStrategy];

if($pKey){
$data['pairs']['poloniex'] = $pNewPairs;
}
if($bKey){
$data['pairs']['bittrex'] = $bNewPairs;
}

$newJsonString = json_encode($data, JSON_PRETTY_PRINT);
file_put_contents($cpath, $newJsonString);

?>
