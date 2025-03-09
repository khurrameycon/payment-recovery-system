<!DOCTYPE html>
<html>
<head>
    <title>Payment Recovery System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Payment Recovery System</h1>
        <p>Welcome to the Payment Recovery System</p>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        Dashboard
                    </div>
                    <div class="card-body">
                        <p>View recovery statistics and reports</p>
                        <a href="?route=dashboard" class="btn btn-primary">Go to Dashboard</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        Failed Transactions
                    </div>
                    <div class="card-body">
                        <p>View and manage failed transactions</p>
                        <a href="?route=failed-transactions" class="btn btn-primary">View Transactions</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        Reports
                    </div>
                    <div class="card-body">
                        <p>Generate detailed reports on recovery performance</p>
                        <a href="?route=reminder-report" class="btn btn-primary">Reminder Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>