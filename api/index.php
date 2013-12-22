<?php
	define("API_KEY_FILENAME","api.key");
	define("API_KEY_LENGTH",30);
	define("SERVERS_CONF_FILENAME","servers.conf");
	$method = $_GET['method'];
	$key = $_GET['apikey'];
	function generateAPIkey($length = API_KEY_LENGTH) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $randomString;
	}
	if(file_exists(API_KEY_FILENAME)) {
		$api_key = file(API_KEY_FILENAME,FILE_IGNORE_NEW_LINES)[0];
	} else {
		echo "Generating new API key (".API_KEY_FILENAME.") @ ".API_KEY_LENGTH." chars.\n";
		if(shell_exec("touch ".API_KEY_FILENAME) != "") {
			echo "Failed to create API key file, aborting.\n";
			die();
		} else {
			echo "Created API key file.\n";
			$api_key = generateAPIkey();
			echo "Your API key is: ".$api_key."\n";
			$handle = fopen(API_KEY_FILENAME,"w");
			fwrite($handle,$api_key);
			fclose($handle);
		}
	}
	
	if($_GET['apikey'] == $api_key) {
		switch($_GET['method']) {
			case "add_server":
				if(is_string($_GET['server_id_str']) && stristr($_GET['server_id_str'],":")) {
					$file = SERVER_CONF_FILENAME;
					$current = file_get_contents($file);
					$current .= $_GET['server_id_str']."\n";
					file_put_contents($file, $current);
				} else {
					echo "Invalid server string argument.";
				}
			break;
			case "rem_server":
				if(is_string($_GET['server_id_str']) && stristr($_GET['server_id_str'],":")) {
					$data = file(SERVER_CONF_FILENAME);
					$output = array();
					foreach($data as $line) {
						if(trim($line) != $_GET['server_id_str']) {
							$output[] = $line;
						}
					}
					 $fp = fopen(SERVER_CONF_FILENAME, "w+");
					 flock($fp, LOCK_EX);
					 foreach($out as $line) {
						 fwrite($fp, $line);
					 }
					 flock($fp, LOCK_UN);
					 fclose($fp);
				} else {
					echo "Invalid server string argument.";
				}
			break;
			case "rem_player":
				//exec("/sbin/iptables -t nat -D PREROUTING --src ".$_GET
			break;
			default:
				echo "Invalid method.";
		}
	} else {
		echo "Invalid API key, aborting!";
	}
?>
