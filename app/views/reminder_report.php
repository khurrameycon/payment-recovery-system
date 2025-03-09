<!DOCTYPE html>
<html>
<head>
    <title>Reminder Report | Payment Recovery System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Reminder Report</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                Date Range
            </div>
            <div class="card-body">
                <form action="index.php" method="get" class="row">
                    <input type="hidden" name="route" value="reminder-report">
                    
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mb-3">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Channel</th>
                    <th>Sent</th>
                    <th>Opened</th>
                    <th>Open Rate</th>
                    <th>Clicked</th>
                    <th>Click Rate</th>
                    <th>Recovered</th>
                    <th>Recovery Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reminderStats)): ?>
                    <tr>
                        <td colspan="9" class="text-center">No data available for the selected date range</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reminderStats as $stat): ?>
                        <tr>
                            <td><?php echo $stat['date']; ?></td>
                            <td><?php echo ucfirst($stat['channel']); ?></td>
                            <td><?php echo $stat['total_sent']; ?></td>
                            <td><?php echo $stat['opened']; ?></td>
                            <td><?php echo $stat['open_rate']; ?>%</td>
                            <td><?php echo $stat['clicked']; ?></td>
                            <td><?php echo $stat['click_rate']; ?>%</td>
                            <td><?php echo $stat['recovered']; ?></td>
                            <td><?php echo $stat['recovery_rate']; ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="mt-4">
            <a href="index.php?route=dashboard" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>