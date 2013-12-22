<?php
/* CONFIGURATION */
define("SERVERS_CONF_FILENAME", "servers.conf");
define("API_BIND_ADDR", "0.0.0.0");

exec("/sbin/sysctl net.ipv4.ip_forward=1 ; /sbin/iptables --new POCKETMINELB ; /sbin/iptables --insert INPUT --proto udp --match state --state NEW --dport 19132 -j POCKETMINELB ; /sbin/iptables --insert POCKETMINELB --jump LOG --log-prefix=\"MCPE_NEW_CONNECTION \" ; /sbin/iptables -t nat -A POSTROUTING -j MASQUERADE");

$handle = popen('/usr/bin/tail -f /var/log/kern.log', 'r');

$isEstablished = array();

$available_servers = array();

function readAvailableServers() {
	$available_servers = file(SERVERS_CONF_FILENAME, FILE_IGNORE_NEW_LINES);
}

/* MAIN TASK */
echo "Minecraft: Pocket Edition Loadbalancer";
echo "by sekjun9878, williamtdr";
echo "Reading server configuration file...";
if(file_exists(SERVERS_CONF_FILENAME)) {
	echo "Loading servers into array...";
	$this->readAvailableServers();
} else {
	echo "First-time launch, creating new configuration file.";
	echo "You should stop this program and add some servers to ".SERVERS_CONF_FILENAME.".";
	if(shell_exec("touch ".SERVERS_CONF_FILENAME) != "") {
		echo "Failed to create configuration file, aborting.";
		die();
	} else {
		echo "Created file successfully. Loading servers into array...";
		$this->readAvailableServers();
	}
}
echo "Starting the API...";
exec("screen -dmS PMLB-API php -S ".API_BIND_ADDR.":8000 -t api/");

while(true) {
    $string = fgets($handle);
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
