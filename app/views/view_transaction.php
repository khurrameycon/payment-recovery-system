<!DOCTYPE html>
<html>
<head>
    <title>View Transaction | Payment Recovery System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Transaction Details</h1>
            <a href="index.php?route=failed-transactions" class="btn btn-secondary">Back to Transactions</a>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Transaction Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th>Transaction ID:</th>
                                <td><?php echo htmlspecialchars($transaction['transaction_reference']); ?></td>
                            </tr>
                            <tr>
                                <th>Amount:</th>
                                <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Failure Reason:</th>
                                <td><?php echo htmlspecialchars($transaction['failure_reason']); ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-<?php echo $transaction['recovery_status'] == 'recovered' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($transaction['recovery_status']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo $customer['phone'] ? htmlspecialchars($customer['phone']) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Timezone:</th>
                                <td><?php echo htmlspecialchars($customer['timezone']); ?></td>
                            </tr>
                            <tr>
                                <th>Segment:</th>
                                <td><?php echo ucfirst(htmlspecialchars($customer['segment'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Recovery Link</h5>
            </div>
            <div class="card-body">
                <?php if ($recoveryLink): ?>
                    <div class="mb-3">
                        <label class="form-label">Recovery Link:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($recoveryLink['recovery_link']); ?>" readonly>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p>Status: <span class="badge bg-<?php echo $recoveryLink['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($recoveryLink['status']); ?>
                            </span></p>
                        </div>
                        <div class="col-md-6">
                            <p>Expires: <?php echo date('Y-m-d H:i', strtotime($recoveryLink['expiry_date'])); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <p>No recovery link has been generated for this transaction.</p>
                    <a href="index.php?route=create-recovery-link&id=<?php echo $transaction['id']; ?>" class="btn btn-primary">Generate Recovery Link</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Communication History</h5>
                <div>
                    <a href="index.php?route=send-reminder&id=<?php echo $transaction['id']; ?>&channel=email" class="btn btn-primary btn-sm">Send Email</a>
                    <a href="index.php?route=send-reminder&id=<?php echo $transaction['id']; ?>&channel=sms" class="btn btn-info btn-sm">Send SMS</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($communications)): ?>
                    <p>No communication attempts have been made for this transaction.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Channel</th>
                                <th>Status</th>
                                <th>Template</th>
                                <th>Opened</th>
                                <th>Clicked</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($communications as $comm): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', strtotime($comm['sent_at'] ?? $comm['scheduled_at'])); ?></td>
                                    <td><?php echo ucfirst($comm['channel']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $comm['status'] == 'sent' ? 'success' : ($comm['status'] == 'opened' || $comm['status'] == 'clicked' ? 'info' : 'secondary'); ?>">
                                            <?php echo ucfirst($comm['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo str_replace('_', ' ', ucfirst($comm['message_template'])); ?></td>
                                    <td><?php echo $comm['opened_at'] ? date('Y-m-d H:i', strtotime($comm['opened_at'])) : '-'; ?></td>
                                    <td><?php echo $comm['clicked_at'] ? date('Y-m-d H:i', strtotime($comm['clicked_at'])) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>