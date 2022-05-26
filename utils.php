<?php

	function generate_string($length=10, $count=1, $set='eng,num,rus', $chars='', $sep='', $to_md5=false) {
		$result = array();
		$str_collection = array();
		$accept = array('eng', 'num', 'rus', 'spc');
		$characters = array();
		
		$characters_eng = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$characters_rus = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ';
		$characters_spc = '!@#$%^&*()_+=-~`\'"<>?/|\\';
		$characters_num = '0123456789';
		
		$characters_set = explode(',', $set);

		if (!empty($characters_set)) {
			for ($i = 0; $i < count($characters_set); $i++) {
				if (in_array($characters_set[$i], $accept)) {
					$var = '$characters_' . $characters_set[$i];
					$characters[] = eval("return {$var};");
				}
			}
		}
		$characters = implode('', $characters) . $chars;
		
		if (!empty($characters)) {
			if ($count == 1) {
				for ($i = 0; $i < $length; $i++) {
					$result[] = mb_substr($characters, mt_rand(0, mb_strlen($characters) -1), 1);
				}
				$result = (!$to_md5) ? implode($sep, $result) : md5(implode($sep, $result));
			} else if ($count > 1) {
				for ($i = 0; $i < $count; $i++) {
					for ($j = 0; $j < $length; $j++) {
						$str_collection[] = mb_substr($characters, mt_rand(0, mb_strlen($characters) -1), 1);
					}
					$result[] = (!$to_md5) ? implode($sep, $str_collection) : md5(implode($sep, $str_collection));	
					$str_collection = array();
				}
				
			}
		}
		
		return (!empty($result)) ? $result : '';
	}

	function encrypt_str($str, $key, $method='no') {
		$result = '';
		$encoded = array();
		$methods = array(
			'no',
			'gzdeflate',
			'gzencode',
			'gzcompress'
		);
		$method = (in_array($method, $methods)) ? $method : 'no';
		
		if (!empty($str)) {
			for ($i = 0; $i < strlen($str); $i++) {
				$encoded[$i] = ($str[$i] ^ $key[$i % strlen($key)]);
			}
			$encoded = implode('', $encoded);
			$encoded = ($method != 'no') ? $method($encoded) : $encoded;
			$encoded = 'crypt:' . bin2hex($encoded);
			$result = $encoded;
		}
		
		return $result;
	}
	
	function decrypt_str($str, $key, $method='no') {
		$methods = array(
			'no',
			'gzinflate',
			'gzdecode',
			'gzuncompress'
		);
		$method = (in_array($method, $methods)) ? $method : 'no';
		$decoded = array();
		
		if (!preg_match('/^crypt:/', $str)) die ('Data set error');
		$str = preg_replace('/(crypt:)/', '', $str);
		$str = hex2bin($str);
		
		if ($method != 'no') {
			if (!$str = @$method($str)) {
				die ('Data set error');
			}
		}

		if (!empty($str)) {
			for ($i = 0; $i < strlen($str); $i++) {
				$decoded[$i] = ($str[$i] ^ $key[$i % strlen($key)]);
			}
		}

		$decoded = implode('', $decoded);
		
		return $decoded;
	}

	function is_image_file($fname) {
		$types = array(
			1 => 'gif',
			2 => 'jpeg',
			3 => 'png',
			18 => 'webp'
		);
		$result = 0;
		
		$exif_imagetype = exif_imagetype($fname);
		if ($exif_imagetype == IMAGETYPE_GIF || $exif_imagetype == IMAGETYPE_JPEG || $exif_imagetype == IMAGETYPE_PNG || $exif_imagetype == IMAGETYPE_WEBP) {
			$result = $types[$exif_imagetype];
		}
		return $result;
	}
	
	function returnBytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val) -1]);
		$val = (int)substr($val, 0, strlen($val) -1);
		
		switch ($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		
		return $val;
	}
	
	function formatBytes($size, $precision = 2) {
		$base = log($size, 1024);
		$suffixes = array('', 'kb', 'mb', 'gb', 'tb');
		return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}
	
	function secs_to_time($secs) {
		$result = array();
		
		if ($secs == 0) $result[] = '00:00';
	
		$result[] = sprintf("%'.02d", floor($secs / 3600));
		$result[] = sprintf("%'.02d", floor($secs / 60) % 60);
		$result[] = sprintf("%'.02d", floor($secs % 60));
		
		return implode(':', $result);
	}
	
	function format_time($secs) {
		$result = array();
		
		if ($secs >= 31536000) {
			$result[] = sprintf('%dy', floor($secs / 31536000));
			$result[] = sprintf('%dd', floor($secs % 31536000 / 86400));
			$result[] = sprintf('%dh', floor($secs % 86400 / 3600));
			$result[] = sprintf('%dm', floor($secs % 86400 % 3600 / 60));
			$result[] = sprintf('%ds', floor($secs % 86400 % 3600 % 60));
		} else if ($secs >= 86400 && $secs < 31536000) {
			$result[] = sprintf('%dd', floor($secs / 86400));
			$result[] = sprintf('%dh', floor($secs % 86400 / 3600));
			$result[] = sprintf('%dm', floor($secs / 60 % 60));
			$result[] = sprintf('%ds', floor($secs % 60));
		} else if ($secs >= 3600 && $secs < 86400) {
			$result[] = sprintf('%dh', floor($secs / 3600));
			$result[] = sprintf('%dm', floor($secs / 60 % 60));
			$result[] = sprintf('%ds', floor($secs % 60));
		} else if ($secs >= 60 && $secs < 3600) {
			$result[] = sprintf('%dm', floor($secs / 60));
			$result[] = sprintf('%ds', floor($secs % 60));
		} else if ($secs < 60) {
			$result[] = sprintf('%ds', $secs);
		}
		
		return implode(' ', $result);		
	}
	
	function fix_files_uploads_array($files) {
		$result = array();
		
		if (!empty($files)) {
			foreach($files as $key => $v) {
				for ($i = 0; $i < count($v['name']); $i++) {
					if (!empty($v['name'][$i])) {
						$result[] = array('name' => $v['name'][$i], 'type' => $v['type'][$i], 'tmp_name' => $v['tmp_name'][$i], 'size' => $v['size'][$i], 'error' => $v['error'][$i]);
					}
				}
			}
		}

		return $result;
	}
	
	function get_str_count($file) {
		$result = 0;
		
		if (file_exists($file)) {
			$array = explode(PHP_EOL, file_get_contents($file));
			$result = count($array);
		}
		
		return $result;
	}
	
	function highlight_search_query($text, $query) {
		$result = '';
		if (!empty($query)) {
			$result = $text;
			$words = explode(' ', $query);
			
			if (!empty($words)) {
				for ($i = 0; $i < count($words); $i++) {
					$word = $words[$i];

					$result = preg_replace("/$word/isu", '<b style="background: #f9ff4e; text-decoration: underline;">\\0</b>', $result);
				}
			}
		} else {
		    $result = $text;
		}
		
		return $result;
	}
	
	function is_mobile() {
		$result = false;
		$userAgent = @$_SERVER['HTTP_USER_AGENT'];
		if (preg_match('/(android|ios|blackberry|opera mini|vantgo|blazer|elaine|hiptop|palm|plucker|xiino|kindle|mobile|mmp|midp|pocket|symbian|smartphone|treo|up.browser|up.link|vodafone|wap|sony|nokia|samsung|epoc|palm|wap1|wap2|xda-)/is', $userAgent)) {
			$result = true;
		}

		return $result;
	}
	
	function escape_search_query($q) {
		$result = $q;
		
		$result = str_replace('/', '', $result);
		$result = str_replace('\\', '', $result);
		$result = str_replace('?', '', $result);
		$result = str_replace('*', '', $result);
		$result = str_replace('&', '', $result);
		$result = str_replace('=', '', $result);
		$result = str_replace('-', '', $result);
		$result = str_replace('+', '', $result);
		
		return $result;
	}
	
	function __http_request($url, $post_data=null, $response_headers=false) {
		$ch = curl_init();
		$result = array();
		
		$headers = array(
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'Accept-Language: en-US,en;q=0.5',
			'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0'
		);
		
		if (!empty($post_data)) {
			$post_data = http_build_query($post_data);
		}
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if (!empty($post_data)) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		
		try {
			if ($response_headers) {
				$result[] = curl_getinfo($ch);
			}
			$result[] = curl_exec($ch);
		} catch (Exception $e) {
			echo $e -> getMessage();
			echo (curl_error($ch));
		}
		curl_close($ch);

		return (!empty($result)) ? implode('', $result) : false;		
	}

?>