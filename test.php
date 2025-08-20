<?php
echo "<pre>";
echo "User: " . shell_exec("whoami");
echo "Sudo test: " . shell_exec("/usr/bin/sudo whoami 2>&1");
echo "Firewall test: " . shell_exec("/usr/bin/sudo /usr/bin/firewall-cmd --state 2>&1");
echo "</pre>";
?>

