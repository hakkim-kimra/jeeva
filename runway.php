<?php
session_start();
include 'db_connect.php';

// SECURE FIX: Strict Email Targeting
if (!isset($_SESSION['user_email'])) { header("Location: index.php"); exit(); }
$email = $_SESSION['user_email'];

// Fetch User Data safely
$user_query = $conn->query("SELECT * FROM users WHERE email='$email'");
$u = $user_query->fetch_assoc();
$username = $u['username'];
$salary = $u['salary'];

// Calculate Total Balance securely
$exp_query = $conn->query("SELECT SUM(amount) as t FROM expenses WHERE user_email='$email'");
$total_spent = $exp_query->fetch_assoc()['t'] ?? 0;
$current_balance = $salary - $total_spent; 

// Calculate Daily Burn Rate
$days_passed = max(1, date("j")); 
$monthly_spend = $conn->query("SELECT SUM(amount) as t FROM expenses WHERE user_email='$email' AND MONTH(date) = MONTH(CURRENT_DATE())")->fetch_assoc()['t'] ?? 0;
$daily_burn = ($monthly_spend / $days_passed); 
if($daily_burn == 0) $daily_burn = 1; 

// THE SURVIVAL CALCULATION
$survival_days = floor($current_balance / $daily_burn);

// Battery UI Logic
$battery_level = 0; $battery_color = "#f43f5e"; $status = "CRITICAL";
if ($survival_days > 180) { $battery_level = 100; $battery_color = "#22c55e"; $status = "SECURE"; } 
elseif ($survival_days > 90) { $battery_level = 75; $battery_color = "#06b6d4"; $status = "STABLE"; } 
elseif ($survival_days > 30) { $battery_level = 50; $battery_color = "#eab308"; $status = "WARNING"; } 
else { $battery_level = 20; $battery_color = "#f43f5e"; $status = "DANGER"; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survival Mode - FinTrackPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Montserrat:wght@500;700;800&display=swap');
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        /* FIXED SIDEBAR LAYOUT */
        body { background: #0f172a; color: #fff; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100vh; background: #1e293b; padding: 30px 20px; display: flex; flex-direction: column; border-right: 1px solid #334155; z-index: 100; overflow-y: auto; }
        .brand { font-family: 'Montserrat', sans-serif; font-size: 22px; font-weight: 800; margin-bottom: 50px; display: flex; align-items: center; gap: 12px; color: #fff; padding-left: 5px; }
        .brand i { color: #06b6d4; font-size: 24px; } .brand span { color: #06b6d4; }
        .nav-link { display: flex; align-items: center; gap: 15px; padding: 14px; color: #94a3b8; text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 500; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background: linear-gradient(90deg, rgba(6, 182, 212, 0.1), transparent); color: #06b6d4; font-weight: 700; border-left: 4px solid #06b6d4; }
        .nav-link i { width: 20px; text-align: center; }
        .main { margin-left: 260px; padding: 40px; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        ::-webkit-scrollbar { width: 8px; } ::-webkit-scrollbar-track { background: #0f172a; } ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        /* SURVIVAL STYLES */
        .survival-card { background: #1e293b; width: 100%; max-width: 700px; padding: 50px; border-radius: 30px; border: 1px solid #334155; text-align: center; position: relative; box-shadow: 0 0 50px rgba(0,0,0,0.5); }
        .days-count { font-family: 'Montserrat', sans-serif; font-size: 80px; font-weight: 800; background: linear-gradient(to bottom, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; margin-top: 10px; }
        .days-label { font-size: 18px; color: #94a3b8; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 40px; }
        .battery-container { width: 100%; height: 60px; background: #0f172a; border-radius: 15px; padding: 5px; border: 2px solid #334155; position: relative; margin-bottom: 30px; }
        .battery-fill { height: 100%; width: <?= $battery_level ?>%; background: <?= $battery_color ?>; border-radius: 10px; transition: width 1.5s ease-in-out; box-shadow: 0 0 20px <?= $battery_color ?>80; display: flex; align-items: center; justify-content: flex-end; padding-right: 15px; font-weight: bold; color: #000; }
        .battery-nub { position: absolute; right: -12px; top: 15px; width: 10px; height: 20px; background: #334155; border-radius: 0 5px 5px 0; }
        .stats-row { display: flex; justify-content: space-between; border-top: 1px solid #334155; padding-top: 30px; }
        .stat-box h4 { font-size: 12px; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        .stat-box div { font-size: 20px; font-weight: 700; color: #fff; }

        <?php if($status == "DANGER"): ?>
        .survival-card { animation: pulseRed 2s infinite; }
        @keyframes pulseRed { 0% { box-shadow: 0 0 0 0 rgba(244, 63, 94, 0.4); } 70% { box-shadow: 0 0 0 20px rgba(244, 63, 94, 0); } 100% { box-shadow: 0 0 0 0 rgba(244, 63, 94, 0); } }
        <?php endif; ?>
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main">
        <div class="survival-card">
            <div style="font-size:14px; color:<?= $battery_color ?>; font-weight:700; border:1px solid <?= $battery_color ?>; display:inline-block; padding:5px 15px; border-radius:20px; margin-bottom:20px;">
                <i class="fa-solid fa-heart-pulse"></i> STATUS: <?= $status ?>
            </div>
            <div class="days-count"><?= $survival_days ?></div>
            <div class="days-label">Days of Runway</div>

            <div class="battery-container">
                <div class="battery-fill"><?= $battery_level ?>%</div>
                <div class="battery-nub"></div>
            </div>

            <div style="color: #94a3b8; font-size: 14px; margin-bottom: 30px; line-height:1.6;">
                Based on your current balance and daily spending habits,<br> 
                if you stopped earning today, you would run out of money on 
                <strong style="color:#fff"><?= date('F j, Y', strtotime("+$survival_days days")) ?></strong>.
            </div>

            <div class="stats-row">
                <div class="stat-box"><h4>Current Savings</h4><div>₹<?= number_format($current_balance) ?></div></div>
                <div class="stat-box"><h4>Daily Burn Rate</h4><div style="color: #f43f5e;">₹<?= number_format($daily_burn) ?>/day</div></div>
                <div class="stat-box"><h4>Emergency Goal</h4><div style="color: #06b6d4;">180 Days</div></div>
            </div>
        </div>
    </div>
</body>
</html>