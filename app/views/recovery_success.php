<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful | Payment Recovery System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3>Payment Successful</h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                            </svg>
                        </div>
                        
                        <p class="text-center">Your payment of <strong>$<?php echo number_format($recovery['amount'], 2); ?></strong> has been successfully processed.</p>
                        
                        <p class="text-center">Transaction Reference: <strong><?php echo htmlspecialchars($recovery['transaction_reference']); ?></strong></p>
                        
                        <p class="text-center">Thank you for your payment!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>