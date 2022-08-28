<?php

	function scan_recursive($directory, $callback=null) {
		$directory = realpath($directory);
	 
		if ($d = opendir($directory)) {
			while($fname = readdir($d)) {
				if ($fname == '.' || $fname == '..') {
					continue;
				}
				else {
					if ($callback != null && is_callable($callback)) {
						$callback($directory . DIRECTORY_SEPARATOR . $fname);
					}
				}
				if (is_dir($directory . DIRECTORY_SEPARATOR . $fname)) {
					scan_recursive($directory . DIRECTORY_SEPARATOR . $fname, $callback);
				}
			}
			closedir($d);
		}
	}

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
	
	function value_by_perc($value, $perc=100) {
		return $perc * ($value / 100);
	}
	
	function get_perc($value, $max) {
		if ($max > 0) {
			return $value * (100 / $max);
		} else {
			return 0;
		}
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
	
	function time_ago($time, $advanced=true) {
		$current_time = time();
		$time_diff = $current_time - $time;
		$result = array();
		$months = array('Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня', 'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря');
		
		if ($time_diff == 0) return $time_diff . ' секунд назад';
		if ($time_diff <= 59) {
			$result[] = $time_diff . ' ' . true_wordform($time_diff, 'секунду', 'секунды', 'секунд') . ' назад';
		} else if ($time_diff >= 60 && $time_diff < 3600) {
			$min = floor($time_diff / 60);
			$sec = floor($time_diff % 60);
	
			$result[] = $min . ' ' . true_wordform($min, 'минуту', 'минуты', 'минут');
			if ($advanced) {
				$result[] = ' и ' . $sec . ' ' . true_wordform($sec, 'секунду', 'секунды', 'секунд');
			}
			$result[] = ' назад';

		} else if ($time_diff >= 3600 && $time_diff < 86400) {
			$hour = floor($time_diff / 3600);
			$min = floor($time_diff / 60 % 60);
			$sec = floor($time_diff % 60);

			$result[] = $hour . ' ' . true_wordform($hour, 'час', 'часа', 'часов');
			if ($advanced) {
				$result[] = $min . ' ' . true_wordform($min, 'минуту', 'минуты', 'минут') . ' и ';
				$result[] = $sec . ' ' . true_wordform($sec, 'секунду', 'секунды', 'секунд');
			}
			$result[] = ' назад';
		} else if ($time_diff >= 86400 && $time_diff < 31536000) { //172800
			$days = floor($time_diff / 86400);
			$hour = floor($time_diff % 86400 / 3600);
			$min = floor($time_diff / 60 % 60);
			$sec = floor($time_diff % 60);
			
			$result[] = $days . ' ' . true_wordform($days, 'день', 'дня', 'дней');
			if ($advanced) {
				$result[] = $hour . ' ' . true_wordform($hour, 'час', 'часа', 'часов');
				$result[] = $min . ' ' . true_wordform($min, 'минуту', 'минуты', 'минут') . ' и ';;
				$result[] = $sec . ' ' . true_wordform($sec, 'секунду', 'секунды', 'секунд');
			} else {
			
			}
			$result[] = ' назад';
		} else if ($time_diff > 31536000) {
			$years = floor($time_diff / 31536000);
			$result[] = $years . ' ' . true_wordform($years, 'год', 'года', 'лет');
			$result[] = ' назад';
		}
		
		return implode(' ', $result);
	}
	
	function parse_wget_log($file) {
		$result = array();
		$bytes = 0;
		$speed = 0;
		$time = 0;
		
		if (file_exists($file)) {
			$file = file_get_contents($file);
			preg_match('/Length: (\d+)/is', $file, $found);
			if (isset($found[1])) $result['length'] = $found[1];

			preg_match_all('/(\d+[K|M]?).*?(\d+)%\s+(\d+[.]?\d+[K|M])[\s+|=]((\d+m\d+s)|(\d+s))/', $file, $found);
			
			if (isset($found[2])) $result['perc'] = $found[2][count($found[2]) -1];
			
			if (isset($found[1])) {
				$download_bytes = $found[1][count($found[1]) -1];
				preg_match('/(\d+)([K|M])/', $download_bytes, $dw_data);
				
				$mod = $dw_data[2];
				$value = $dw_data[1];
				
				if ($mod == 'K') {
					$bytes = $value * 1024;
				} else if ($mod == 'M') {
					$bytes = $value * (1024 * 1024);
				}
			}
			
			if (isset($found[3])) {
				$current_speed = $found[3][count($found[3]) -1];
				preg_match('/(.*?)([M|K])/', $current_speed, $speed_found);
				
				$mod = $speed_found[2];
				$value = $speed_found[1];
				
				if ($mod == 'K') {
					$speed = $value * 1024;
				} else if ($mod == 'M') {
					$speed = $value * (1024 * 1024);
				}
			}
			
			if (isset($found[4])) {
				$current_time = $found[4][count($found[4]) -1];
				
				if (preg_match('/^\d+s$/', $current_time)) {
					preg_match('/^(\d+)s$/', $current_time, $time_found);
					$time = (int)$current_time[1];
				}
				
				if (preg_match('/^\d+m$/', $current_time)) {
					preg_match('/^(\d+)m$/', $current_time, $time_found);
					$time = (int)$time_found[1] * 60;
				}
				
				if (preg_match('/^\d+m\d+s$/', $current_time)) {
					preg_match('/^(\d+)m(\d+)s$/', $current_time, $time_found);
					$min = (int)$time_found[1];
					$sec = (int)$time_found[2];
					$time = ($min * 60) + $sec;
				}
			}
			
			$result['speed'] = floor($speed);
			$result['time'] = floor($time);
			$result['bytes'] = floor($bytes);
		}

		return $result;
	}
	
	function fix_files_uploads_array($files) {
		$result = array();
		
		if (!empty($files)) {
			foreach($files as $k => $v) {
				for ($i = 0; $i < count($v['name']); $i++) {
					if (!empty($v['name'][$i])) {
						$result[] = array('name' => $v['name'][$i], 'type' => $v['type'][$i], 'tmp_name' => $v['tmp_name'][$i], 'size' => $v['size'][$i], 'error' => $v['error'][$i]);
					}
				}
			}
		}

		return $result;
	}
	
	function true_wordform($num, $arg1, $arg2, $arg3) {
		$num_a = abs($num) % 100;
		$num_x = $num_a % 10;
		
		if ($num_a > 10 && $num_a < 20) return $arg3;
		if ($num_x > 1 && $num_x < 5) return $arg2;
		if ($num_x == 1) return $arg1;
		
		return $arg3;
	}
	
	function get_str_count($file) {
		$result = 0;
		
		if (file_exists($file)) {
			$array = explode(PHP_EOL, file_get_contents($file));
			$result = count($array);
		}
		
		return $result;
	}
	
	function get_words_count($text) {
		$result = 0;
		
		if (!empty($text)) {
			$words = explode(' ', $text);
			if (!empty($words)) $result = count($words);
		}
		
		return $result;
	}
	
	function get_word_pos($text, $word) {
		$result = 0;
		
		if (!empty($text)) {
			
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
					$word = test_input($word, true);
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
	
	function test_input($data, $strictly=false) {
		$data = trim($data);
		$data = strip_tags($data);
		$data = stripslashes($data);
		
		if ($strictly) {
			$data = str_replace('(', '', $data);
			$data = str_replace(')', '', $data);
			$data = str_replace('[', '', $data);
			$data = str_replace(']', '', $data);
			$data = str_replace('*', '', $data);
			$data = str_replace('?', '', $data);
		}
		return $data;
	}
	
	function uri_input($data) {
		$data = trim($data);
		$data = mb_strtolower($data);
		$data = str_replace(' ', '-', $data);
		
		return $data;
	}
	
	function validate_length($data, $min=0, $max=10) {
		return (((mb_strlen($data) >= $min)) && (mb_strlen($data) <= $max)) ? true : false;
	}
	
	function translit($input) {
		$result = '';
		$converter = array(
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
		);
		
		$result = $input;
		$result = mb_strtolower($result);
		$result = strtr($result, $converter);
		$result = mb_ereg_replace('[^-0-9a-z]', '-', $result);
		$result = mb_ereg_replace('[-]+', '-', $result);
		$result = trim($result);
		
		return $result;
	}
	
	function match_str($input, $strings, $delimiter=',') {
		$strings = explode($delimiter, $strings);
		return in_array($input, $strings) ? $input : $strings[0];
	}
	
	function __http_request($url, $post_data=null, $addheaders=array(), $response_headers=false) {
		$ch = curl_init();
		$result = array();
		
		$headers = array(
			'Accept-Language: ru-RU,ru;q=0.9',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36',
			'Accept-Encodig: gzip, deflate, br',
			'cache-control: no-cache',
			//':method: GET',
			//':path: /',
			//':scheme: https',
			'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
			//'dnt: 1',
			//'pragma: no-cache',
			//'sec-ch-ua-mobile: ?0',
			//'sec-ch-ua-platform: "Windows"',
			//'sec-fetch-dest: document',
			//'sec-fetch-mode: navigate',
			//'sec-fetch-site: same-origin',
			//'sec-fetch-user: ?1',
			//'upgrade-insecure-requests: 1'
		);
		
		$headers = array_merge($headers, $addheaders);
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if (!empty($post_data)) {
			$post_data = http_build_query($post_data);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		//curl_setopt($ch, CURLOPT_COOKIE, DOCROOT . 'tmp/cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, DOCROOT . 'tmp/cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEJAR, DOCROOT . 'tmp/cookies.txt');
		
		try {
			if ($response_headers) {
				$result[] = implode(PHP_EOL, curl_getinfo($ch));
			}
			$result[] = curl_exec($ch);
		} catch (Exception $e) {
			echo $e -> getMessage();
			echo (curl_error($ch));
		}
		curl_close($ch);

		return (!empty($result)) ? implode('', $result) : false;		
	}
	
	function file_get_contents_parts($file, $save_path, $chunk_size=1048576, $callback=null, $param='') {
		try {
			$info = get_headers($file, 1);
			$hFile = fopen($file, 'rb');
			$sFile = fopen($save_path, 'wb');
			$i = 0;
			
			while (!feof($hFile)) {
				fwrite($sFile, fread($hFile, $chunk_size));
				
				if (isset($callback)) {
					//call_user_func($callback, ftell($hFile), $info['Content-Length']);
					$callback(ftell($hFile), $info['Content-Length'], $param);
				}
			}
			
			fclose($hFile);
			fclose($sFile);
			
		} catch (Exception $e) {
			trigger_error("file_get_contents_chunked::" . $e -> getMessage(), E_USER_NOTICE);
			return false;
		}
		return true;
	}
	
	function readfile_chunk($file, $chunk_size=1048576) {
		$file = fopen($file, 'rb');
		while (!feof($file)) {
			echo fread($file, 1048576);
		}
		fclose($file);		
	}
	
	function updateGlobal($param, $value) {
		WO_DB_Update(TGLOB, 
			array('value' => $value),
			array(['param', '=', $param])
		);
	}
	
	function if_array_exists($array, $value, $default='') {
		return isset($array[$value]) ? !empty($array[$value]) ? $array[$value] : $default : $default;
	}
	
	function send_404() {
		header('HTTP/1.1 404 Not Found');
		echo '<html>';
		echo '<head>';
		echo '<title>Ninja angry</title>';
		echo '</head>';
		echo '<body bgcolor="#fff">';
		echo '<center><h1>404 Not Found</h1></center>';
		echo '<hr>';
		echo '<center>' . $_SERVER['SERVER_SOFTWARE'] . '</center>';
		echo '</body>';
		echo '</html>';
		
		exit;
	}
	
	function eachArray($array, $callback) {
		if (!empty($array)) {
			for ($i = 0; $i < count($array); $i++) {
				if (isset($callback) && is_callable($callback)) call_user_func($callback, $array[$i]);
			}
		}
	}
	
	function make_assoc_array($keys_array, $values_array) {
		$result = array();
		
		if (!empty($keys_array)) {
			for ($i = 0; $i < count($keys_array); $i++) {
				$result[$keys_array[$i]] = $values_array[$i];
			}
		}
		
		return $result;
	}
	
	function __redirect($location) {
		header('HTTP/1.1 302 Found');
		header('Location: ' . $location);
	}
	
	function json_array_keys($json) {
		$result = array();
		$result = array_keys(json_decode($json, true));
		return $result;
	}
	
	function json_array_values($json) {
		$result = array();
		$result = array_values(json_decode($json, true));
		return $result;
	}
	
	function json_in_array($needle, $haystack) {
		return in_array($needle, json_decode($haystack, true));
	}	

?>
