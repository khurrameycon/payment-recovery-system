<!DOCTYPE html>
<html>
<head>
    <title>Complete Your Payment | Payment Recovery System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Complete Your Payment</h3>
                    </div>
                    <div class="card-body">
                        <p>Please enter your payment details to complete your transaction of <strong>$<?php echo number_format($recovery['amount'], 2); ?></strong>.</p>
                        
                        <form action="index.php?route=process-payment" method="post">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            
                            <div class="mb-3">
                                <label for="card_number" class="form-label">Card Number</label>
                                <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="expiry_month" class="form-label">Expiry Month</label>
                                    <select class="form-select" id="expiry_month" name="expiry_month" required>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col">
                                    <label for="expiry_year" class="form-label">Expiry Year</label>
                                    <select class="form-select" id="expiry_year" name="expiry_year" required>
                                        <?php 
                                        $currentYear = date('Y');
                                        for ($i = $currentYear; $i <= $currentYear + 10; $i++): 
                                        ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cvv" name="cvv" placeholder="123" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Complete Payment</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>