<?php
	define("API_KEY_FILENAME","api.key");
	define("API_KEY_LENGTH",30);
	define("SERVERS_CONF_FILENAME","servers.conf");
	if($phpinstances != null) {
		$output = array();
		exec("pidof php | wc -w", $output;
		$newinstances = $output[0];
		if($newinstances <= $phpinstances) {
			echo "Server no longer running, dying.";
			die();
		}
	} else {
		$output = array();
		exec("pidof php | wc -w", $output;
		$phpinstances = $output[0];
	}
	$method = $_GET['method'];
	$key = $_GET['apikey'];
	if(file_exists("../".API_KEY_FILENAME)) {
		$f = fopen("../".API_KEY_FILENAME, 'r');
		$api_key = fgets($f);
		fclose($f);
	} else {
		echo "Failed to find an API key - has the main process run?";
	}
	
	if($_GET['apikey'] == $api_key) {
		switch($_GET['method']) {
			case "add_server":
				if(is_string($_GET['server_id_str']) && stristr($_GET['server_id_str'],":")) {
					$file = SERVERS_CONF_FILENAME;
					$current = file_get_contents($file);
					$current .= $_GET['server_id_str']."\n";
					file_put_contents($file, $current);
				} else {
					echo "Invalid server string argument.";
				}
			break;
			case "rem_server":
				if(is_string($_GET['server_id_str']) && stristr($_GET['server_id_str'],":")) {
					$data = file(SERVERS_CONF_FILENAME);
					$output = array();
					foreach($data as $line) {
						if(trim($line) != $_GET['server_id_str']) {
							$output[] = $line;
						}
					}
					 $fp = fopen(SERVERS_CONF_FILENAME, "w+");
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
