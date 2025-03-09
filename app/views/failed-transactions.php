<!DOCTYPE html>
<html>
<head>
    <title>Failed Transactions | Payment Recovery System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Failed Transactions</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Import Transactions from NMI
                    </div>
                    <div class="card-body">
                        <form action="index.php?route=fetch-from-nmi" method="post">
                            <div class="row">
                                <div class="col">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                                </div>
                                <div class="col">
                                    <label>End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Import Transactions</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Generate Recovery Links
                    </div>
                    <div class="card-body">
                        <p>Create recovery links for transactions that don't have them yet.</p>
                        <a href="index.php?route=create-recovery-links" class="btn btn-success">Generate Recovery Links</a>
                    </div>
                </div>
            </div>
        </div>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No failed transactions found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo $transaction['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($transaction['email']); ?><br>
                                <?php echo $transaction['phone'] ? htmlspecialchars($transaction['phone']) : 'No phone'; ?>
                            </td>
                            <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['failure_reason']); ?></td>
                            <td><?php echo ucfirst($transaction['recovery_status']); ?></td>
                            <td>
                                <a href="index.php?route=view-transaction&id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-info">View</a>
                                <a href="index.php?route=send-reminder&id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-warning">Send Reminder</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>