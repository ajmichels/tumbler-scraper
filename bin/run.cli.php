<?php

$params = [
	'p_help' => [ false, 'Display help information.', false ]
];

$switches = [
	'-?'     => 'p_help',
	'-h'     => 'p_help',
	'--help' => 'p_help'
];

$args = $_SERVER['argv'];
$argsLen = count( $args );

$args[0] = null;

for ( $i=0; $i<$argsLen; $i++ ) {
	//echo $args[$i] . "\n";
	if ( array_key_exists( trim( $args[$i] ), $switches ) ) {
		$paramKey = $switches[trim($args[$i])];
		//echo $paramKey . "\n";
		$param = $params[$paramKey];
		//print_r( $param );
		if ( $param[0] ) {
			if ( !array_key_exists( $i+1, $args ) ) {
				echo 'The ' . $args[$i] . ' argument requires a value.';
				exit;
			}
			$paramValue = trim($args[$i+1]);
			if ( array_key_exists( $paramValue, $switches ) ) {
				echo 'The ' . $args[$i] . ' argument requires a value.';
				exit;
			}
			$$paramKey = $paramValue;
			$args[$i] = null;
			$args[$i+1] = null;
			$i++;
		}
		else {
			$$paramKey = true;
			$args[$i] = null;
		}
	}
}

if ( isset( $p_help ) and $p_help == true ) {
	echo 'display help info';
	exit;
}

if ( $args[count($args)-1] == null ) {
	echo 'A url must be passed';
	exit;
}
else {
	$url = $args[count($args)-1];
}

foreach ( $params as $key => $param ) {
	if ( !isset( $$key ) ) {
		$$key = $param[2];
	}
}

require_once dirname( __FILE__ ) . '/../vendor/autoload.php';

Scraper::init()->run( $url, $p_destination, $p_recursive, $p_pag_fil, $p_img_fil, $p_cont, $p_verb );
