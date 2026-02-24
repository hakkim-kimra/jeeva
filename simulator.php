<?php
session_start();
include 'db_connect.php';

// Security: Check unique email
if (!isset($_SESSION['user_email'])) { header("Location: index.php"); exit(); }
$email = $_SESSION['user_email'];

// 1. Fetch User Data
$user_query = $conn->query("SELECT * FROM users WHERE email='$email'");
$u = $user_query->fetch_assoc();
$username = $u['username'];
$salary = $u['salary'];

// 2. Fetch Total Spent
$exp_query = $conn->query("SELECT SUM(amount) as t FROM expenses WHERE user_email='$email'");
$total_spent = $exp_query->fetch_assoc()['t'] ?? 0;

// 3. THE FIX: Current Balance MUST be Salary minus Total Spent
$current_balance = $salary - $total_spent;

// 4. Days logic for limits
$days_in_month = date("t");
$days_passed = max(1, date("j"));
$days_left = $days_in_month - $days_passed;
$current_daily_limit = ($days_left > 0) ? ($current_balance / $days_left) : 0;

// HANDLE SIMULATION
$sim_result = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item = $conn->real_escape_string($_POST['item']);
    $cost = floatval($_POST['cost']);

    // Calculations based on the CORRECTED balance
    $future_balance = $current_balance - $cost;
    $new_daily_limit = ($days_left > 0) ? ($future_balance / $days_left) : 0;
    $impact_percent = ($current_balance > 0) ? ($cost / $current_balance) * 100 : 100;

    // AI Verdict Logic
    if ($future_balance < 0) {
        $verdict = "DENIED"; $color = "#f43f5e"; 
        $advice = "You cannot afford this. It will put you in debt by ₹" . number_format(abs($future_balance)) . ".";
        $risk_width = "100%";
    } elseif ($impact_percent > 50) {
        $verdict = "HIGH RISK"; $color = "#f59e0b"; 
        $advice = "This purchase eats " . round($impact_percent) . "% of your remaining money. Only buy if urgent.";
        $risk_width = "75%";
    } elseif ($impact_percent > 20) {
        $verdict = "MODERATE"; $color = "#eab308"; 
        $advice = "Affordable, but your daily spending limit will drop to ₹" . number_format($new_daily_limit) . ".";
        $risk_width = "40%";
    } else {
        $verdict = "APPROVED"; $color = "#22c55e"; 
        $advice = "Safe to buy. You will still have a healthy balance of ₹" . number_format($future_balance) . ".";
        $risk_width = "15%";
    }
    $sim_result = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Simulator - FinTrackPro</title>
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
        
        /* MAIN CONTENT AREA */
        .main { margin-left: 260px; padding: 40px; min-height: 100vh; display: flex; flex-direction: column; align-items: center; }
        ::-webkit-scrollbar { width: 8px; } ::-webkit-scrollbar-track { background: #0f172a; } ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        /* SIMULATOR STYLES */
        .sim-card { background: #1e293b; width: 100%; max-width: 600px; padding: 40px; border-radius: 24px; border: 1px solid #334155; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .header-section { text-align: center; margin-bottom: 30px; }
        .header-section h1 { font-family: 'Montserrat', sans-serif; font-size: 24px; }
        .header-section p { color: #94a3b8; font-size: 14px; margin-top: 5px; }
        
        .input-group { margin-bottom: 20px; width: 100%; }
        label { display: block; color: #94a3b8; font-size: 12px; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; }
        input { width: 100%; background: #0f172a; border: 1px solid #334155; padding: 15px; border-radius: 12px; color: #fff; font-size: 16px; outline: none; transition: 0.3s; font-family: 'Inter', sans-serif; }
        input:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1); }
        
        .btn-sim { width: 100%; background: linear-gradient(90deg, #06b6d4, #3b82f6); color: #fff; padding: 15px; border: none; border-radius: 12px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.2s; font-family: 'Montserrat', sans-serif; }
        .btn-sim:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(6, 182, 212, 0.3); }

        /* RESULT BOX */
        .result-box { margin-top: 30px; background: #0f172a; border-radius: 16px; padding: 25px; border: 1px solid #334155; position: relative; overflow: hidden; animation: slideUp 0.5s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .verdict-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 800; background: <?= $color ?? '#000' ?>20; color: <?= $color ?? '#000' ?>; border: 1px solid <?= $color ?? '#000' ?>; margin-bottom: 15px; }
        
        .comparison-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155; }
        .comp-item h4 { font-size: 12px; color: #94a3b8; margin-bottom: 5px; }
        .comp-item div { font-size: 18px; font-weight: 700; font-family: 'Montserrat', sans-serif; }
        
        .risk-bar-bg { width: 100%; height: 6px; background: #334155; border-radius: 10px; margin-top: 15px; overflow: hidden; }
        .risk-bar-fill { height: 100%; width: <?= $risk_width ?? '0%' ?>; background: <?= $color ?? '#000' ?>; border-radius: 10px; transition: width 1s ease; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="sim-card">
            <div class="header-section">
                <div style="width: 60px; height: 60px; background: rgba(6, 182, 212, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: #06b6d4; font-size: 24px;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <h1>Purchase Simulator</h1>
                <p>Test a big purchase before you buy to see the impact.</p>
            </div>

            <form method="POST" style="width: 100%;">
                <div class="input-group">
                    <label>What do you want to buy?</label>
                    <input type="text" name="item" placeholder="e.g. New iPhone" required value="<?= isset($_POST['item']) ? htmlspecialchars($_POST['item']) : '' ?>">
                </div>
                <div class="input-group">
                    <label>Estimated Cost (₹)</label>
                    <input type="number" name="cost" placeholder="0.00" required value="<?= isset($_POST['cost']) ? htmlspecialchars($_POST['cost']) : '' ?>">
                </div>
                <button type="submit" class="btn-sim">Simulate Impact</button>
            </form>

            <?php if($sim_result): ?>
                <div class="result-box" style="width: 100%;">
                    <div class="verdict-badge"><?= $verdict ?></div>
                    <h3 style="margin-bottom: 5px; font-family: 'Montserrat', sans-serif;">Analysis Result</h3>
                    <p style="color: #cbd5e1; font-size: 14px; line-height: 1.5;"><?= $advice ?></p>
                    
                    <div class="risk-bar-bg"><div class="risk-bar-fill"></div></div>
                    <div style="font-size: 10px; color: #94a3b8; margin-top: 5px; text-align: right; text-transform: uppercase; font-weight: bold;">Risk Level</div>

                    <div class="comparison-grid">
                        <div class="comp-item">
                            <h4>Current Balance</h4>
                            <div style="color: #94a3b8;">₹<?= number_format($current_balance) ?></div>
                        </div>
                        <div class="comp-item">
                            <h4>After Purchase</h4>
                            <div style="color: <?= $color ?>;">₹<?= number_format($future_balance) ?></div>
                        </div>
                        <div class="comp-item">
                            <h4>Current Daily Limit</h4>
                            <div style="color: #94a3b8;">₹<?= number_format($current_daily_limit) ?></div>
                        </div>
                        <div class="comp-item">
                            <h4>New Daily Limit</h4>
                            <div style="color: <?= $color ?>;">₹<?= number_format($new_daily_limit) ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>