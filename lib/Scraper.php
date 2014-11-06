<?php

class Scraper
{


	const PAG_LOG_NAME = 'scraped-pages.log';
	const IMG_LOG_NAME = 'scraped-images.log';


	public static $instance;


	private $urls = [];
	private $imgs = [];
	private $pagePatterns = [];
	private $imagePatterns = [];
	private $destination;
	private $verbose;


	public static function init ()
	{

		if ( !isset( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;

	}


	public function run ( $url, $destination='./tmp', $recursive=false, $pages=null, $images=null, $continue=false, $verbose=false )
	{

		$this->verbose = $verbose;

		$this->out( "Starting Scrape At: " . $url );

		$this->destination = $destination;

		if ( substr( $this->destination , strlen( $this->destination ) - 1, 1 ) == '/' ) {
			$this->destination = substr( $this->destination , 0, strlen( $this->destination ) - 1 );
		}

		if ( !file_exists( $this->destination . '/' ) ) {
			mkdir( $this->destination . '/' );
		}

		$this->out( "Saving images to: " . $this->destination );

		/* If page patterns are defined parse them for later use. */
		if ( $pages != null ) {
			$this->pagePatterns = $this->stringToPatternArray( $pages );
		}

		/* If image patterns are defined parse them for later use. */
		if ( $images != null ) {
			$this->imagePatterns = $this->stringToPatternArray( $images );
		}

		if ( $continue ) {

			$this->out( "Continuing scraping from log files." );
			$this->loadLogData();

		}
		else {

			/* Delete and recreate log. */
			$this->deleteLogs();

		}

		error_reporting(E_ERROR | E_PARSE);

		$this->scrapeRecursion( $url, $recursive );

	}


	private function scrapeRecursion ( $url, $recursive )
	{

		$this->out( 'Scraping Page: ' . $url );

		$page_content = file_get_contents( $url );

		$doc = new DOMDocument();
		$doc->loadHTML( $page_content );

		$this->logPage( $url );

		$url_data = parse_url( $url );

		$this->downloadImagesFromDoc( $doc );

		if ( $recursive ) {

			$links = $doc->getElementsByTagName( 'a' );

			foreach ( $links as $link ) {

				$next_url = $link->attributes->getNamedItem( 'href' )->value;

				if ( substr( $next_url, 0, 1 ) == '/' ) {
					$next_url = 'http://' . $url_data['host'] . $next_url;
				}

				if ( array_search( $next_url, $this->urls ) === false && $this->acceptedUrl( $next_url, $this->pagePatterns ) ) {
					$this->scrapeRecursion( $next_url, $recursive );
				}

			}

		}

	}


	private function downloadImagesFromDoc ( DOMDocument $doc )
	{

		$imgs = $doc->getElementsByTagName( 'img' );

		foreach ( $imgs as $img ) {

			$img_url = $img->attributes->getNamedItem( 'src' )->value;

			if ( array_search( $img_url, $this->imgs ) === false && $this->acceptedUrl( $img_url, $this->imagePatterns ) ) {
				$this->out( 'Image: ' . $img_url );
				$img_nam = explode( '/', $img_url );
				$img_content = file_get_contents( $img_url );
				file_put_contents( $this->destination . '/' . $img_nam[count($img_nam)-1], $img_content );
				$this->logImage( $img_url );
			}

		}

	}


	private function acceptedUrl ( $url, Array $patterns )
	{

		if ( count( $patterns ) == 0 ) {
			return true;
		}

		foreach ( $patterns as $pattern ) {
			if ( preg_match( '/' . $pattern . '/', $url ) == 1 ) {
				$this->out( 'Matched pattern: ' . $pattern );
				return true;
			}
		}

		return false;

	}


	private function stringToPatternArray ( $str )
	{
		$escChars = ['/'];

		foreach ( $escChars as $char ) {
			$str = str_replace( $char, '\\' . $char, $str );
		}

		$str = str_replace( '*', '(.*)', $str );

		$array = explode( ',', $str );

		$this->out( $array );

		return $array;

	}


	private function out ( $message )
	{

		if ( $this->verbose ) {

			if ( is_string( $message ) ) {
				echo $message . "\n";
			}
			else {
				var_dump( $message );
			}

		}

	}


	private function logPage ( $url )
	{

		$this->urls[] = $url;

		file_put_contents( $this->destination . '/' . self::PAG_LOG_NAME, $url . "\n", FILE_APPEND );

	}


	private function logImage ( $url )
	{

		$this->imgs[] = $url;

		file_put_contents( $this->destination . '/' . self::IMG_LOG_NAME, $url . "\n", FILE_APPEND );

	}


	private function loadLogData ()
	{

		/* Load pages from log. */
		$this->urls = file( $this->destination . '/' . self::PAG_LOG_NAME, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES );

		/* Load images from log. */
		$this->imgs = file( $this->destination . '/' . self::IMG_LOG_NAME, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES );

	}


	private function deleteLogs ()
	{

		unlink( $this->destination . '/' . self::PAG_LOG_NAME );
		unlink( $this->destination . '/' . self::IMG_LOG_NAME );

	}


}
