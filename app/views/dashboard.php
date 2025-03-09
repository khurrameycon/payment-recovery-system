<!DOCTYPE html>
<html>
<head>
    <title>Dashboard | Payment Recovery System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Payment Recovery Dashboard</h1>
        
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Failed Transactions</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $stats['total_failed']; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Recovered Transactions</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $stats['total_recovered']; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-header">Recovery Rate</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $stats['recovery_rate']; ?>%</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-header">Amount Recovered</div>
                    <div class="card-body">
                        <h5 class="card-title">$<?php echo number_format($stats['total_amount_recovered'], 2); ?></h5>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        Recovery Rate by Channel
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Channel</th>
                                    <th>Total</th>
                                    <th>Recovered</th>
                                    <th>Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recoveryRates as $rate): ?>
                                    <tr>
                                        <td><?php echo ucfirst($rate['channel']); ?></td>
                                        <td><?php echo $rate['total_transactions']; ?></td>
                                        <td><?php echo $rate['recovered_transactions']; ?></td>
                                        <td><?php echo $rate['recovery_rate']; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        Recent Reminders
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Channel</th>
                                    <th>Recipient</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reminders as $reminder): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i', strtotime($reminder['sent_at'])); ?></td>
                                        <td><?php echo ucfirst($reminder['channel']); ?></td>
                                        <td>
                                            <?php echo $reminder['channel'] == 'email' ? $reminder['email'] : $reminder['phone']; ?>
                                        </td>
                                        <td><?php echo ucfirst($reminder['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="index.php?route=reminder-report" class="btn btn-primary">Detailed Reminder Report</a>
            <a href="index.php?route=failed-transactions" class="btn btn-secondary">Failed Transactions</a>
        </div>
    </div>
</body>
</html>