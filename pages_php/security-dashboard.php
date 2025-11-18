<?php
// Include authentication and security functions
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/security_functions.php';

// Require admin access
requireLogin();
if (!isAdmin()) {
    header("Location: dashboard.php");
    exit;
}

// Get security statistics
$stats = [];

// Recent login attempts
$stats['recent_attempts'] = fetchAll("
    SELECT email, ip_address, attempt_time, success, failure_reason 
    FROM login_attempts 
    ORDER BY attempt_time DESC 
    LIMIT 20
");

// Failed attempts in last 24 hours
$stats['failed_24h'] = fetchOne("
    SELECT COUNT(*) as count 
    FROM login_attempts 
    WHERE success = FALSE AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
")['count'];

// Successful logins in last 24 hours
$stats['success_24h'] = fetchOne("
    SELECT COUNT(*) as count 
    FROM login_attempts 
    WHERE success = TRUE AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
")['count'];

// Active sessions
$stats['active_sessions'] = fetchOne("
    SELECT COUNT(*) as count 
    FROM user_sessions 
    WHERE is_active = TRUE AND expires_at > NOW()
")['count'];

// Locked accounts
$stats['locked_accounts'] = fetchAll("
    SELECT al.email, al.lockout_reason, al.locked_at, al.unlock_at, u.first_name, u.last_name
    FROM account_lockouts al
    LEFT JOIN users u ON al.user_id = u.user_id
    WHERE al.is_active = TRUE
    ORDER BY al.locked_at DESC
");

// Recent security events
$stats['security_events'] = fetchAll("
    SELECT sl.event_type, sl.description, sl.ip_address, sl.severity, sl.created_at,
           u.first_name, u.last_name, u.email
    FROM security_logs sl
    LEFT JOIN users u ON sl.user_id = u.user_id
    ORDER BY sl.created_at DESC
    LIMIT 50
");

// IP access control
$stats['ip_controls'] = fetchAll("
    SELECT ip_address, access_type, reason, created_at, expires_at, is_active
    FROM ip_access_control
    WHERE is_active = TRUE
    ORDER BY created_at DESC
");

// Include header
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Security Dashboard</h1>
            <p class="text-muted">Monitor system security and user activities</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Security Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?php echo $stats['success_24h']; ?></h4>
                            <p class="mb-0">Successful Logins (24h)</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?php echo $stats['failed_24h']; ?></h4>
                            <p class="mb-0">Failed Attempts (24h)</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?php echo $stats['active_sessions']; ?></h4>
                            <p class="mb-0">Active Sessions</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?php echo count($stats['locked_accounts']); ?></h4>
                            <p class="mb-0">Locked Accounts</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-lock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Login Attempts -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Recent Login Attempts</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>IP Address</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['recent_attempts'] as $attempt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attempt['email']); ?></td>
                                    <td><?php echo htmlspecialchars($attempt['ip_address']); ?></td>
                                    <td><?php echo date('M j, H:i', strtotime($attempt['attempt_time'])); ?></td>
                                    <td>
                                        <?php if ($attempt['success']): ?>
                                            <span class="badge bg-success">Success</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger" title="<?php echo htmlspecialchars($attempt['failure_reason']); ?>">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Locked Accounts -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Locked Accounts</h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="unlockAllAccounts()">
                        <i class="fas fa-unlock me-1"></i>Unlock All
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($stats['locked_accounts'])): ?>
                        <p class="text-muted text-center">No accounts are currently locked</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Reason</th>
                                        <th>Locked At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['locked_accounts'] as $locked): ?>
                                    <tr>
                                        <td>
                                            <?php if ($locked['first_name']): ?>
                                                <?php echo htmlspecialchars($locked['first_name'] . ' ' . $locked['last_name']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($locked['email']); ?></small>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($locked['email']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($locked['lockout_reason']); ?></td>
                                        <td><?php echo date('M j, H:i', strtotime($locked['locked_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success" onclick="unlockAccount('<?php echo htmlspecialchars($locked['email']); ?>')">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Events -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Recent Security Events</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Event</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Severity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['security_events'] as $event): ?>
                                <tr>
                                    <td><?php echo date('M j, H:i', strtotime($event['created_at'])); ?></td>
                                    <td>
                                        <?php if ($event['first_name']): ?>
                                            <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($event['event_type']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['description']); ?></td>
                                    <td><?php echo htmlspecialchars($event['ip_address']); ?></td>
                                    <td>
                                        <?php
                                        $severity_class = [
                                            'low' => 'bg-success',
                                            'medium' => 'bg-warning',
                                            'high' => 'bg-danger',
                                            'critical' => 'bg-dark'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $severity_class[$event['severity']] ?? 'bg-secondary'; ?>">
                                            <?php echo ucfirst($event['severity']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function unlockAccount(email) {
    if (confirm('Are you sure you want to unlock this account?')) {
        fetch('security-actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'unlock_account',
                email: email
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function unlockAllAccounts() {
    if (confirm('Are you sure you want to unlock ALL locked accounts?')) {
        fetch('security-actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'unlock_all_accounts'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
