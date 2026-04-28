<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Expired - Delivery Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .trial-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
        }

        .trial-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .trial-title {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .trial-subtitle {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .pricing-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }

        .pricing-card {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s ease;
            position: relative;
        }

        .pricing-card:hover {
            transform: translateY(-4px);
            border-color: #3b82f6;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
        }

        .pricing-card.popular {
            border-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
        }

        .popular-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #10b981;
            color: white;
            padding: 4px 16px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .plan-name {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .plan-price {
            font-size: 32px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 4px;
        }

        .plan-period {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 16px;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 16px 0;
            text-align: left;
        }

        .plan-features li {
            padding: 4px 0;
            color: #4b5563;
            font-size: 14px;
        }

        .plan-features li::before {
            content: "✓";
            color: #10b981;
            font-weight: bold;
            margin-right: 8px;
        }

        .upgrade-button {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            width: 100%;
        }

        .upgrade-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
            color: white;
        }

        .upgrade-button.popular {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .contact-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }

        .trial-stats {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .trial-stats h4 {
            color: #334155;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #3b82f6;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        @media (max-width: 768px) {
            .pricing-cards {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .trial-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="trial-container">
        <div class="trial-icon">⏰</div>
        <h1 class="trial-title">Your Free Trial Has Ended</h1>
        <p class="trial-subtitle">
            Thank you for trying our delivery management system! Your 2-week free trial has concluded. 
            Continue enjoying our powerful features by choosing a plan below.
        </p>

        <?php
        // Get trial usage statistics if client ID is available
        $client_id = $_GET['client_id'] ?? $_SESSION['id'] ?? null;
        $show_stats = false;

        if ($client_id) {
            $idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
            if ($idr) {
                // Get trial usage statistics
                $stats_query = "SELECT 
                    COUNT(DISTINCT da.id) as total_deliveries,
                    COUNT(DISTINCT da.dispatcher_id) as drivers_used,
                    DATEDIFF(trial_end_date, trial_start_date) as trial_duration,
                    trial_start_date,
                    trial_end_date
                    FROM client c
                    LEFT JOIN dispatch_assignments da ON c.id = da.client_id 
                        AND da.created_at BETWEEN c.trial_start_date AND c.trial_end_date
                    WHERE c.id = ?
                    GROUP BY c.id";
                
                $stmt = $idr->prepare($stats_query);
                $stmt->bind_param("i", $client_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($stats = $result->fetch_assoc()) {
                    $show_stats = true;
                }
            }
        }

        if ($show_stats): ?>
        <div class="trial-stats">
            <h4>Your Trial Usage Summary</h4>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['total_deliveries'] ?? 0; ?></div>
                    <div class="stat-label">Deliveries Managed</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['drivers_used'] ?? 0; ?></div>
                    <div class="stat-label">Drivers Utilized</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['trial_duration'] ?? 14; ?></div>
                    <div class="stat-label">Days Trialed</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="pricing-cards">
            <div class="pricing-card">
                <div class="plan-name">Starter Plan</div>
                <div class="plan-price">$49</div>
                <div class="plan-period">per month</div>
                <ul class="plan-features">
                    <li>Up to 500 deliveries/month</li>
                    <li>5 active drivers</li>
                    <li>Basic analytics</li>
                    <li>WhatsApp integration</li>
                    <li>GPS tracking</li>
                    <li>Email support</li>
                </ul>
                <a href="upgrade.php?plan=starter" class="upgrade-button">Choose Starter</a>
            </div>

            <div class="pricing-card popular">
                <div class="popular-badge">Most Popular</div>
                <div class="plan-name">Professional</div>
                <div class="plan-price">$89</div>
                <div class="plan-period">per month</div>
                <ul class="plan-features">
                    <li>Unlimited deliveries</li>
                    <li>Unlimited drivers</li>
                    <li>Advanced analytics</li>
                    <li>WhatsApp integration</li>
                    <li>Real-time GPS tracking</li>
                    <li>Priority phone support</li>
                    <li>Custom reporting</li>
                    <li>API access</li>
                </ul>
                <a href="upgrade.php?plan=professional" class="upgrade-button popular">Choose Professional</a>
            </div>
        </div>

        <div class="contact-info">
            <p><strong>Need help choosing?</strong> Contact our sales team:</p>
            <p>📧 sales@yourdeliveryapp.com | 📞 +1 (555) 123-4567</p>
            <p>We're here to help you find the perfect plan for your business needs.</p>
        </div>
    </div>
</div>

<script>
// Auto-redirect after 60 seconds if no interaction
let redirectTimer = setTimeout(function() {
    if (confirm("Would you like to speak with our sales team about upgrading?")) {
        window.location.href = "contact.php";
    } else {
        window.location.href = "login.php";
    }
}, 60000);

// Clear timer if user interacts
document.addEventListener('click', function() {
    clearTimeout(redirectTimer);
});

document.addEventListener('keydown', function() {
    clearTimeout(redirectTimer);
});
</script>

</body>
</html>