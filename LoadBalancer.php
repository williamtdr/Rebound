<?php
exec("/sbin/sysctl net.ipv4.ip_forward=1 ; /sbin/iptables --new POCKETMINELB ; /sbin/iptables --insert INPUT --proto udp --match state --state NEW --dport 19132 -j POCKETMINELB ; /sbin/iptables --insert POCKETMINELB --jump LOG --log-prefix=\"MCPE_NEW_CONNECTION \" ; /sbin/iptables -t nat -A POSTROUTING -j MASQUERADE");

$handle = popen('/usr/bin/tail -f /var/log/kern.log', 'r');

$isEstablished = array();

$available_servers = array();

public function readAvailableServers() {
}

/* MAIN TASK */
echo "Minecraft: Pocket Edition LoadBalancer";
echo "by sekjun9878, williamtdr";
echo "Reading server configuration file...";
if(file_exists("servers.conf")) {
	echo "Loading servers into array...";
	$this->readAvailableServers();
} else {
	echo "First-time launch, creating new configuration file.";
	echo "You should stop this program and add some servers to servers.conf.";
	if(shell_exec("touch servers.conf") != "") {
		echo "Failed to create configuration file, aborting.";
		die();
	} else {
		echo "Created file successfully. Loading servers into array...";
		$this->readAvailableServers();
	}
}
while(true) {
    $string = fgets($handle);
	$this->readAvailableServers();
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