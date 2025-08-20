<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('error_reporting', '32767');

$output = '';
$show_output = false;
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}

// Helper function
function firewalld_command(string $cmd1, string $cmd2 = ''): string {
    $result1 = shell_exec($cmd1);
    $result2 = $cmd2 ? shell_exec($cmd2) : '';
    $reload  = shell_exec("sudo firewall-cmd --reload");
    return trim($result1 . $result2 . $reload);
}

// RESET FIREWALL
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reset_firewall'])) {
    $output = '';
    $output .= shell_exec("sudo firewall-cmd --permanent --remove-rich-rules='$(sudo firewall-cmd --permanent --list-rich-rules)' 2>&1");
    $output .= shell_exec("sudo firewall-cmd --permanent --remove-icmp-block=echo-request 2>&1");
    $ports = explode(' ', trim(shell_exec("sudo firewall-cmd --list-ports")));
    foreach ($ports as $port) {
        $output .= shell_exec("sudo firewall-cmd --permanent --remove-port=$port 2>&1");
    }
    $output .= shell_exec("sudo firewall-cmd --reload 2>&1");
    $show_output = true;
}

// Main actions (allow/block/remove/unblock)
elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    $type   = $_POST['type'] ?? '';
    $value  = trim($_POST['value'] ?? '');
    $value2 = trim($_POST['value2'] ?? '');
    $proto  = $_POST['protocol'] ?? 'tcp';

    if ($proto === 'https') {
        $proto = 'tcp';
        $value2 = '443';
    }

    $error = '';
    if ($type === 'port' && !$value) {
        $error = 'Port is required.';
    } elseif (in_array($type, ['ip', 'icmpip']) && !$value) {
        $error = 'IP address is required.';
    } elseif ($type === 'ipport' && (!$value || !$value2)) {
        $error = 'Both IP and Port are required.';
    }

    if ($error) {
        $output = '<div class="alert alert-danger">'.$error.'</div>';
    } else {
        $cmd1 = '';
        $cmd2 = '';

        if ($type === 'ip') {
            $rule = "rule family=ipv4 source address=$value";
            if ($action === 'allow') {
                $cmd1 = "sudo firewall-cmd --permanent --add-rich-rule='$rule accept'";
            } elseif ($action === 'block') {
                $cmd1 = "sudo firewall-cmd --permanent --add-rich-rule='$rule drop'";
            } elseif ($action === 'remove') {
                $cmd1 = "sudo firewall-cmd --permanent --remove-rich-rule='$rule accept'";
            } elseif ($action === 'unblock') {
                $cmd1 = "sudo firewall-cmd --permanent --remove-rich-rule='$rule drop'";
            }

        } elseif ($type === 'port') {
            if ($action === 'allow') {
                $cmd1 = "sudo firewall-cmd --permanent --add-port=$value/$proto";
            } elseif ($action === 'block') {
                $cmd1 = "sudo firewall-cmd --permanent --remove-port=$value/$proto";
            } elseif ($action === 'remove') {
                $cmd1 = "sudo firewall-cmd --permanent --remove-port=$value/$proto";
            } elseif ($action === 'unblock') {
                $cmd1 = "sudo firewall-cmd --permanent --add-port=$value/$proto";
            }

        } elseif ($type === 'icmp') {
            if ($action === 'allow' || $action === 'unblock') {
                $cmd1 = "sudo firewall-cmd --permanent --remove-icmp-block=echo-request";
            } elseif ($action === 'block') {
                $cmd1 = "sudo firewall-cmd --permanent --add-icmp-block=echo-request";
            } elseif ($action === 'remove') {
                $cmd1 = "sudo firewall-cmd --permanent --remove-icmp-block=echo-request";
            }

        } elseif ($type === 'icmpip') {
            if ($action === 'allow') {
                $cmd1 = "sudo firewall-cmd --permanent --add-rich-rule='rule family=ipv4 source address=$value protocol value=icmp accept'";
            } elseif ($action === 'block') {
                $cmd1 = "sudo firewall-cmd --permanent --add-rich-rule='rule protocol value=icmp drop'";
            } elseif ($action === 'remove') {
                $cmd1 = "sudo firewall-cmd --permanent --remove-rich-rule='rule family=ipv4 source address=$value protocol value=icmp accept'";
            } elseif ($action === 'unblock') {
                $cmd1 = "sudo firewall-cmd --permanent --remove-rich-rule='rule protocol value=icmp drop'";
            }

        } elseif ($type === 'ipport') {
            $rule = "rule family=ipv4 source address=$value port port=$value2 protocol=$proto";
            if ($action === 'allow') {
                $cmd1 = "sudo firewall-cmd --permanent --add-rich-rule='$rule accept'";
            } elseif ($action === 'block') {
                $cmd1 = "sudo firewall-cmd --permanent --add-rich-rule='$rule drop'";
            } elseif ($action === 'remove') {
                $cmd1 = "sudo firewall-cmd --permanent --remove-rich-rule='$rule accept'";
            } elseif ($action === 'unblock') {
                $cmd1 = "sudo firewall-cmd --permanent --remove-rich-rule='$rule drop'";
            }
        }

        if ($cmd1) {
            $result = firewalld_command($cmd1, $cmd2);
            $show_output = true;

            if (strpos($result, 'success') !== false) {
                $output = '<div class="alert alert-success"><strong>Success!</strong> Command executed:<br><code>'.$cmd1.'</code>';
                if ($cmd2) $output .= '<br><code>'.$cmd2.'</code>';
                $output .= '</div>';
            } else {
                $output = '<div class="alert alert-warning"><strong>Warning:</strong> Operation may not have completed successfully.<br>Output:<br><pre>'.htmlspecialchars($result).'</pre></div>';
            }
        } else {
            $output = '<div class="alert alert-danger">Invalid command specified.</div>';
            $show_output = true;
        }
    }
}

// Get firewall status for display
$status = shell_exec("sudo firewall-cmd --state");
$is_active = (trim($status) === 'running');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FirewallD Web Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .card {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: 8px 8px 0 0 !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: white;
        }
        
        .status-card {
            border-left: 4px solid var(--primary-color);
        }
        
        .status-card.danger {
            border-left-color: var(--danger-color);
        }
        
        .status-card.success {
            border-left-color: var(--success-color);
        }
        
        .status-card.warning {
            border-left-color: var(--warning-color);
        }
        
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid var(--dark-color);
            overflow-x: auto;
        }
        
        .output-box {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .action-icon {
            font-size: 1.2rem;
            margin-right: 8px;
        }
        
        .tab-content {
            padding: 20px 0;
        }
        
        .nav-tabs .nav-link.active {
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
        }
        
        .nav-tabs .nav-link {
            color: #666;
        }
        
        .action-select option[value="allow"] {
            color: var(--success-color);
        }
        
        .action-select option[value="block"] {
            color: var(--danger-color);
        }
        
        .action-select option[value="remove"] {
            color: var(--warning-color);
        }
        
        .action-select option[value="unblock"] {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shield-alt me-2"></i>FirewallD Control Panel
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="badge bg-<?php echo $is_active ? 'success' : 'danger'; ?> me-3">
                    <i class="fas fa-<?php echo $is_active ? 'circle-check' : 'circle-xmark'; ?> me-1"></i>
                    <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                </span>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($show_output && $output): ?>
        <div class="row">
            <div class="col-md-12">
                <?php echo $output; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This interface allows direct modification of your server's firewall rules. Use with caution.
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-sliders-h me-2"></i>Firewall Configuration</span>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="firewallTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="rules-tab" data-bs-toggle="tab" data-bs-target="#rules" type="button" role="tab">Rules</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="reset-tab" data-bs-toggle="tab" data-bs-target="#reset" type="button" role="tab">Reset</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="firewallTabContent">
                            <div class="tab-pane fade show active" id="rules" role="tabpanel">
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="type" class="form-label">Rule Type</label>
                                        <select class="form-select" name="type" id="type" required>
                                            <option value="ip">IP Address</option>
                                            <option value="port">Port</option>
                                            <option value="icmp">ICMP (Ping Global)</option>
                                            <option value="icmpip">ICMP Whitelist IP</option>
                                            <option value="ipport">IP + Port</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="action" class="form-label">Action</label>
                                        <select class="form-select action-select" name="action" id="action" required>
                                            <option value="allow" style="color: var(--success-color);">
                                                <i class="fas fa-check-circle me-2"></i>Allow
                                            </option>
                                            <option value="block" style="color: var(--danger-color);">
                                                <i class="fas fa-ban me-2"></i>Block
                                            </option>
                                            <option value="remove" style="color: var(--warning-color);">
                                                <i class="fas fa-trash-alt me-2"></i>Remove Rule
                                            </option>
                                            <option value="unblock" style="color: var(--primary-color);">
                                                <i class="fas fa-unlock me-2"></i>Unblock
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3 ip-field">
                                        <label for="value" class="form-label">IP Address</label>
                                        <input type="text" class="form-control" name="value" id="value" placeholder="e.g., 192.168.1.100">
                                    </div>
                                    
                                    <div class="mb-3 port-field">
                                        <label for="value2" class="form-label">Port Number</label>
                                        <input type="text" class="form-control" name="value2" id="value2" placeholder="e.g., 443">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="protocol" class="form-label">Protocol</label>
                                        <select class="form-select" name="protocol" id="protocol">
                                            <option value="tcp">TCP</option>
                                            <option value="udp">UDP</option>
                                            <option value="https">HTTPS (443/TCP)</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Apply Rule
                                    </button>
                                </form>
                            </div>
                            
                            <div class="tab-pane fade" id="reset" role="tabpanel">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>Danger:</strong> This will remove all custom rules and reset the firewall to default state.
                                </div>
                                <form method="post">
                                    <input type="hidden" name="reset_firewall" value="1">
                                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to reset all firewall rules?')">
                                        <i class="fas fa-trash-alt me-2"></i>Reset Firewall to Default
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>Current Firewall Status
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="fas fa-door-open me-2"></i>Open Ports</h5>
                            <pre><?php echo htmlspecialchars(shell_exec("sudo firewall-cmd --list-ports")); ?></pre>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="fas fa-list-ol me-2"></i>Rich Rules</h5>
                            <pre><?php echo htmlspecialchars(shell_exec("sudo firewall-cmd --list-rich-rules")); ?></pre>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="fas fa-ban me-2"></i>ICMP Blocks</h5>
                            <pre><?php echo htmlspecialchars(shell_exec("sudo firewall-cmd --list-icmp-blocks")); ?></pre>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="fas fa-lock me-2"></i>Blocked IPs</h5>
                            <pre><?php 
                            $rich_rules_output = shell_exec("sudo firewall-cmd --list-rich-rules");
                            $blocked_ips = [];
                            
                            if (is_string($rich_rules_output)) {
                                foreach (explode("\n", $rich_rules_output) as $line) {
                                    if (strpos($line, 'drop') !== false && preg_match('/source address="?([0-9\.]+)"?/', $line, $m)) {
                                        $blocked_ips[] = $m[1];
                                    }
                                }
                                echo htmlspecialchars($blocked_ips ? implode("\n", $blocked_ips) : "No IPs currently blocked.");
                            } else {
                                echo htmlspecialchars("Failed to read rich rules (firewalld might not be active).");
                            }
                            ?></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-5 py-3 bg-light">
        <div class="container text-center text-muted">
            <small>FirewallD Web Control Panel &copy; <?php echo date('Y'); ?> | Powered by firewalld <?php echo htmlspecialchars(shell_exec("sudo firewall-cmd --version")); ?></small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dynamic form field visibility based on rule type
        document.getElementById('type').addEventListener('change', function() {
            const type = this.value;
            const ipField = document.querySelector('.ip-field');
            const portField = document.querySelector('.port-field');
            
            // Hide both fields initially
            ipField.style.display = 'none';
            portField.style.display = 'none';
            
            // Show relevant fields based on selection
            if (type === 'ip' || type === 'icmpip') {
                ipField.style.display = 'block';
                portField.style.display = 'none';
            } else if (type === 'port') {
                ipField.style.display = 'none';
                portField.style.display = 'block';
            } else if (type === 'ipport') {
                ipField.style.display = 'block';
                portField.style.display = 'block';
            } else if (type === 'icmp') {
                ipField.style.display = 'none';
                portField.style.display = 'none';
            }
        });
        
        // Trigger the change event on page load to set initial state
        document.getElementById('type').dispatchEvent(new Event('change'));
    </script>
</body>
</html>