<?php
$start = microtime(true);
/* CONFIGURATION */
define("SERVERS_CONF_FILENAME", "servers.conf");
define("API_KEY_FILENAME","api.key");
define("API_BIND_ADDR", "0.0.0.0");
define("API_KEY_LENGTH",30);

if (0 != posix_getuid()) {
    echo "Please run this script as root\n.";
    die();
}

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

if (PHP_VERSION_ID < 50207) {
    define('PHP_MAJOR_VERSION',   $version[0]);
    define('PHP_MINOR_VERSION',   $version[1]);
    define('PHP_RELEASE_VERSION', $version[2]);
}

if(!(PHP_MAJOR_VERSION >= 5 && PHP_MINOR_VERSION >=4)) {
	echo "Using PHP < 5.4.0. API will fail to run.\n";
}

exec("/sbin/sysctl net.ipv4.ip_forward=1 ; /sbin/iptables --new POCKETMINELB ; /sbin/iptables --insert INPUT --proto udp --match state --state NEW --dport 19132 -j POCKETMINELB ; /sbin/iptables --insert POCKETMINELB --jump LOG --log-prefix=\"MCPE_NEW_CONNECTION \" ; /sbin/iptables -t nat -A POSTROUTING -j MASQUERADE");

$netlog = popen('/usr/bin/tail -f /var/log/kern.log', 'r');

$isEstablished = array();

$available_servers = array();

function readAvailableServers() {
	$available_servers = file(SERVERS_CONF_FILENAME, FILE_IGNORE_NEW_LINES);
}

/* MAIN TASK */
echo "Minecraft: Pocket Edition Loadbalancer\n";
echo "by sekjun9878, williamtdr\n";
echo "Reading server configuration file...\n";
if(file_exists(SERVERS_CONF_FILENAME)) {
	echo "Loading servers into array...\n";
	readAvailableServers();
} else {
	echo "First-time launch, creating new configuration file.\n";
	echo "You should stop this program and add some servers to ".SERVERS_CONF_FILENAME.".\n";
	if(shell_exec("touch ".SERVERS_CONF_FILENAME) != "") {
		echo "Failed to create configuration file, aborting.\n";
		die();
	} else {
		exec("chmod 777 ".SERVERS_CONF_FILENAME);
		echo "Created file successfully. Loading servers into array...\n";
		readAvailableServers();
	}
}
echo "Starting the API...\n";
function generateAPIkey($length = API_KEY_LENGTH) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, strlen($characters) - 1)];
	}
	return $randomString;
}
if(file_exists(API_KEY_FILENAME)) {
	$f = fopen("../".API_KEY_FILENAME, 'r');
	$api_key = fgets($f);
	fclose($f);
} else {
	echo "Generating new API key (".API_KEY_FILENAME.") @ ".API_KEY_LENGTH." chars.\n";
	if(shell_exec("touch ".API_KEY_FILENAME) != "") {
		echo "Failed to create API key file, aborting.\n";
		die();
	} else {
		echo "Created API key file.\n";
		$api_key = generateAPIkey();
		echo "Your API key is: ".$api_key."\n";
		$handle = fopen(API_KEY_FILENAME,"w+");
		fwrite($handle,$api_key);
		fclose($handle);
		exec("chmod 777 ".API_KEY_FILENAME);
	}
}
if(exec("command -v screen") == "") {
	echo "Screen isn't installed, and this program won't work without it. Installing.";
	shell_exec("apt-get install screen");
	if(exec("command -v screen") == "") {
		echo "Screen install failed. Please install it manually. Exiting...";
		die();
	} else {
		echo "Screen installed successfully!";
	}
}
exec("screen -dmS PMLB-API php -S ".API_BIND_ADDR.":8007 -t api/");
$time_taken = microtime(true) - $start;
echo "Done! (".round($time_taken,4)."ms)";
while(true) {
    $string = fgets($netlog);
	readAvailableServers();
    if(strpos($string, 'MCPE_NEW_CONNECTION') !== false) {
        preg_match_all("/SRC=.+?\..+?\..+?\..+?/", $string, $output);
        $SOURCE_IP = str_replace("SRC=", '', $output[0][0]);
        if(!isset($isEstablished[$SOURCE_IP])) {
            $RAND_SERVER = $available_servers[array_rand($available_servers)];
            exec("/sbin/iptables -t nat -A PREROUTING --src $SOURCE_IP --proto udp --dport 19132 -j DNAT --to-destination $RAND_SERVER");
            $isEstablished[$SOURCE_IP] = true;
			// add server disconnect check here
            echo "NEW CONN SOURCE IP: $SOURCE_IP REDIRECT TO $RAND_SERVER\n";
        }
    }
}
?>
