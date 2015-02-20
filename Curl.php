<?php

/**
 * Class CMS_Curl
 * @author Štěpán Pernica, stepan.pernica@gmail.com
 */


use Nette\Object;


class CMS_Curl extends Object
{
	const CERTIFICATE_PATH = "/cert.crt";
	const DEFAULT_UA = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17';

	/**
	 * @param $URL
	 * @param bool $ssl
	 * @return bool|mixed
	 */
	public static function get($URL, $ssl = FALSE)
	{
		try {
			$ch = curl_init();
			if (FALSE === $ch) {
				throw new Exception('failed to initialize');
			}

			$timeout = 10;
			curl_setopt( $ch , CURLOPT_URL , $URL );
			curl_setopt( $ch , CURLOPT_RETURNTRANSFER , 1 );
			curl_setopt( $ch , CURLOPT_CONNECTTIMEOUT , $timeout );
			curl_setopt( $ch , CURLOPT_USERAGENT, !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : self::DEFAULT_UA);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

			if ($ssl) {
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
				curl_setopt($ch, CURLOPT_CAINFO, __DIR__. self::CERTIFICATE_PATH);
			} else {
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			}

			$tmp = curl_exec( $ch );

			if (FALSE === $tmp) {
				throw new Exception(curl_error($ch), curl_errno($ch));
			}

			/* Check for 404 (file not found). */
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			curl_close( $ch );

			if($httpCode == 404) {
				$tmp = FALSE;
			}

		} catch(Exception $e) {
			trigger_error(sprintf('Failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
			return FALSE;
		}

		return $tmp;
	}



	/**
	 * @param $url
	 * @return string
	 */
	public static function getLocation($url, $ssl = FALSE) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_HEADER,         true);
		curl_setopt($ch, CURLOPT_NOBODY,         true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT,        10);
		curl_setopt( $ch , CURLOPT_USERAGENT, !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : self::DEFAULT_UA);
		if ($ssl) {
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_CAINFO, __DIR__. self::CERTIFICATE_PATH);
		} else {
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		}

		$r = curl_exec($ch);

		preg_match_all('/^Location:(.*)$/mi', $r, $matches);
		if (!empty($matches)) {
			$url = end($matches);
			return !empty($url[0]) ? $url[0] : NULL;
		}

		return NULL;
	}



	/**
	 * @param $url
	 * @return string
	 */
	public static function getSize($url, $ssl = TRUE) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_HEADER,         true);
		curl_setopt($ch, CURLOPT_NOBODY,         true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT,        10);
		curl_setopt( $ch , CURLOPT_USERAGENT, !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : self::DEFAULT_UA);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		if ($ssl) {
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_CAINFO, __DIR__. self::CERTIFICATE_PATH);
		} else {
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		}

		$r = curl_exec($ch);
		foreach(explode("\n", $r) as $header) {
			if(strpos($header, 'Content-Length:') === 0) {
				return self::formatBytes(trim(substr($header,16)));
			}
		}
		return '';
	}



	/**
	 * Convert to human readable format
	 * @param $bytes
	 * @param int $precision
	 * @return string
	 */
	public static function formatBytes($bytes, $precision = 1)
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= (1 << (10 * $pow));

		return round($bytes, $precision) . ' ' . $units[$pow];
	}
} 
