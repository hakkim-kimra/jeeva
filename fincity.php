<?php
session_start();
include 'db_connect.php';

// SECURE FIX: Strict Email Targeting
if (!isset($_SESSION['user_email'])) { header("Location: index.php"); exit(); }
$email = $_SESSION['user_email'];

// 1. Fetch User Data
$user_query = $conn->query("SELECT * FROM users WHERE email='$email'");
$u = $user_query->fetch_assoc();
$username = $u['username'];
$salary = $u['salary'];

// 2. Financial Calculations
$exp_query = $conn->query("SELECT SUM(amount) as t FROM expenses WHERE user_email='$email'");
$total_spent = $exp_query->fetch_assoc()['t'] ?? 0;
$balance = $salary - $total_spent;

// Calculate Survival Runway (For the Shield)
$days_passed = max(1, date("j"));
$monthly_spend = $conn->query("SELECT SUM(amount) as t FROM expenses WHERE user_email='$email' AND MONTH(date) = MONTH(CURRENT_DATE())")->fetch_assoc()['t'] ?? 0;
$daily_burn = max(1, ($monthly_spend / $days_passed));
$survival_days = floor($balance / $daily_burn);

// 3. CLASH OF CLANS GAMIFICATION LOGIC
$town_hall_level = 1;
$next_upgrade_cost = 10000;
$th_icon = "fa-campground"; 
$th_color = "#94a3b8";
$th_name = "Level 1: Survivor Camp";

if ($balance < 0) {
    $town_hall_level = 0;
    $next_upgrade_cost = 0;
    $th_icon = "fa-house-fire"; 
    $th_color = "#f43f5e";
    $th_name = "RUINED: Debt Attack!";
} elseif ($balance >= 100000) {
    $town_hall_level = 4;
    $next_upgrade_cost = 500000;
    $th_icon = "fa-city"; 
    $th_color = "#8b5cf6";
    $th_name = "Level 4: Wealth Metropolis";
} elseif ($balance >= 50000) {
    $town_hall_level = 3;
    $next_upgrade_cost = 100000;
    $th_icon = "fa-building"; 
    $th_color = "#06b6d4";
    $th_name = "Level 3: Financial Hub";
} elseif ($balance >= 10000) {
    $town_hall_level = 2;
    $next_upgrade_cost = 50000;
    $th_icon = "fa-house-chimney"; 
    $th_color = "#22c55e";
    $th_name = "Level 2: Stable Village";
}

// Progress Bar Math
$progress_percent = 0;
if ($town_hall_level > 0 && $town_hall_level < 4) {
    $progress_percent = min(100, ($balance / $next_upgrade_cost) * 100);
} elseif ($town_hall_level == 4) {
    $progress_percent = 100; // Max level
}

// Shield Logic
$shield_active = ($survival_days >= 30 && $balance >= 0) ? true : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FinCity Base - FinTrackPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Montserrat:wght@500;700;800;900&display=swap');
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0f172a; color: #fff; font-family: 'Inter', sans-serif; overflow: hidden; }
        
        /* SIDEBAR */
        .sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100vh; background: #1e293b; padding: 30px 20px; display: flex; flex-direction: column; border-right: 1px solid #334155; z-index: 100; }
        .brand { font-family: 'Montserrat', sans-serif; font-size: 22px; font-weight: 800; margin-bottom: 50px; display: flex; align-items: center; gap: 12px; color: #fff; padding-left: 5px; }
        .brand i { color: #06b6d4; font-size: 24px; } .brand span { color: #06b6d4; }
        .nav-link { display: flex; align-items: center; gap: 15px; padding: 14px; color: #94a3b8; text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 500; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background: linear-gradient(90deg, rgba(6, 182, 212, 0.1), transparent); color: #06b6d4; font-weight: 700; border-left: 4px solid #06b6d4; }
        .nav-link i { width: 20px; text-align: center; }

        /* MAIN GAME AREA */
        .main { margin-left: 260px; height: 100vh; display: flex; flex-direction: column; position: relative; <?= ($town_hall_level == 0) ? 'background: radial-gradient(circle, #450a0a 0%, #0f172a 100%);' : 'background: radial-gradient(circle, #1e293b 0%, #0f172a 100%);' ?> }
        
        /* GAME HUD (Heads Up Display) */
        .game-hud {
            position: absolute; top: 0; left: 0; width: 100%; padding: 25px 40px;
            display: flex; justify-content: space-between; align-items: flex-start; z-index: 50;
        }

        .profile-badge {
            background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); border: 1px solid #334155;
            padding: 10px 20px; border-radius: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .level-circle { width: 40px; height: 40px; background: #06b6d4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 20px; font-family: 'Montserrat', sans-serif; color: #0f172a; box-shadow: 0 0 15px rgba(6,182,212,0.5); }
        
        .resource-bar {
            background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); border: 1px solid #334155;
            padding: 15px 25px; border-radius: 20px; min-width: 300px; box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .res-title { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 1px; margin-bottom: 8px; display: flex; justify-content: space-between; }
        .xp-bg { width: 100%; height: 8px; background: #334155; border-radius: 10px; overflow: hidden; }
        .xp-fill { height: 100%; width: <?= $progress_percent ?>%; background: linear-gradient(90deg, #22c55e, #10b981); transition: width 1s ease-in-out; }
        
        /* --- 3D ISOMETRIC ENGINE --- */
        .isometric-stage {
            flex: 1; display: flex; justify-content: center; align-items: center; perspective: 1500px;
            transform-style: preserve-3d;
        }

        .base-plate {
            width: 400px; height: 400px; background: #1e293b;
            border: 4px solid #334155; border-radius: 20px;
            /* THIS CREATES THE CLASH OF CLANS CAMERA ANGLE */
            transform: rotateX(60deg) rotateZ(-45deg);
            transform-style: preserve-3d;
            box-shadow: -20px 20px 50px rgba(0,0,0,0.8), inset 0 0 50px rgba(0,0,0,0.5);
            position: relative;
            background-image: linear-gradient(rgba(255,255,255,0.05) 2px, transparent 2px), linear-gradient(90deg, rgba(255,255,255,0.05) 2px, transparent 2px);
            background-size: 50px 50px;
            <?= ($town_hall_level == 0) ? 'border-color: #f43f5e; box-shadow: -20px 20px 50px rgba(244,63,94,0.3);' : '' ?>
        }

        /* The Main Town Hall Building */
        .town-hall {
            position: absolute; top: 50%; left: 50%;
            /* Counter-rotate to make it stand up straight */
            transform: translate(-50%, -50%) rotateZ(45deg) rotateX(-60deg);
            display: flex; flex-direction: column; align-items: center;
            transition: 0.5s; z-index: 10;
        }

        .th-icon {
            font-size: 120px; color: <?= $th_color ?>;
            filter: drop-shadow(0 20px 15px rgba(0,0,0,0.6));
            <?= ($town_hall_level == 0) ? 'animation: shake 0.5s infinite;' : '' ?>
            <?= ($town_hall_level == 4) ? 'animation: float 3s infinite alternate;' : '' ?>
        }

        /* 3D Label */
        .th-label {
            background: rgba(15, 23, 42, 0.9); border: 1px solid <?= $th_color ?>; color: #fff;
            padding: 8px 16px; border-radius: 12px; font-size: 13px; font-weight: 700;
            margin-top: 15px; white-space: nowrap; box-shadow: 0 10px 20px rgba(0,0,0,0.5);
        }

        /* --- THE SHIELD DOME (Emergency Fund) --- */
        .energy-shield {
            position: absolute; top: 50%; left: 50%;
            width: 350px; height: 350px;
            transform: translate(-50%, -50%) rotateZ(45deg) rotateX(-60deg);
            border-radius: 50%;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.1) 0%, rgba(6, 182, 212, 0.4) 90%, rgba(6, 182, 212, 0.8) 100%);
            box-shadow: 0 0 30px rgba(6, 182, 212, 0.5), inset 0 0 30px rgba(6, 182, 212, 0.8);
            backdrop-filter: blur(2px);
            z-index: 20;
            animation: pulseShield 2s infinite alternate;
            pointer-events: none;
            <?= ($shield_active) ? 'display: block;' : 'display: none;' ?>
        }

        @keyframes pulseShield { from { transform: translate(-50%, -50%) rotateZ(45deg) rotateX(-60deg) scale(1); opacity: 0.8; } to { transform: translate(-50%, -50%) rotateZ(45deg) rotateX(-60deg) scale(1.02); opacity: 1; } }
        @keyframes shake { 0% { transform: translateX(0); } 25% { transform: translateX(-5px) rotate(-2deg); } 75% { transform: translateX(5px) rotate(2deg); } 100% { transform: translateX(0); } }
        @keyframes float { from { transform: translateY(0); } to { transform: translateY(-15px); filter: drop-shadow(0 35px 25px rgba(0,0,0,0.4)); } }

        /* Status Panels at the bottom */
        .bottom-hud { position: absolute; bottom: 40px; left: 0; width: 100%; display: flex; justify-content: center; gap: 20px; z-index: 50; }
        .hud-card { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); border: 1px solid #334155; padding: 15px 25px; border-radius: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        
        <div class="game-hud">
            <div class="profile-badge">
                <div class="level-circle"><?= $town_hall_level ?></div>
                <div>
                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Town Hall Level</div>
                    <div style="font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 18px; color: #fff;"><?= htmlspecialchars($username) ?>'s Base</div>
                </div>
            </div>

            <div class="resource-bar">
                <div class="res-title">
                    <span><i class="fa-solid fa-coins" style="color: #facc15;"></i> Wealth Elixir (Savings)</span>
                    <span style="color: #fff;">₹<?= number_format($balance) ?></span>
                </div>
                <div class="xp-bg"><div class="xp-fill"></div></div>
                <div style="font-size: 10px; color: #64748b; margin-top: 8px; text-align: right;">
                    <?= ($town_hall_level < 4 && $town_hall_level > 0) ? 'Next Upgrade at ₹'.number_format($next_upgrade_cost) : (($town_hall_level == 0) ? 'Pay debts to rebuild!' : 'Max Level Reached!') ?>
                </div>
            </div>
        </div>

        <div class="isometric-stage">
            <div class="base-plate">
                
                <div class="town-hall">
                    <i class="fa-solid <?= $th_icon ?> th-icon"></i>
                    <div class="th-label"><?= $th_name ?></div>
                </div>

                <div class="energy-shield"></div>
            </div>
        </div>

        <div class="bottom-hud">
            <div class="hud-card">
                <div style="font-size: 24px; color: <?= ($shield_active) ? '#06b6d4' : '#64748b' ?>;">
                    <i class="fa-solid <?= ($shield_active) ? 'fa-shield-halved' : 'fa-shield-slash' ?>"></i>
                </div>
                <div>
                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Defense Status</div>
                    <div style="font-weight: 700; color: <?= ($shield_active) ? '#06b6d4' : '#fff' ?>;">
                        <?= ($shield_active) ? 'Shield Active (30+ Days)' : 'Vulnerable (Need 30 Days)' ?>
                    </div>
                </div>
            </div>
            
            <?php if($town_hall_level == 0): ?>
            <div class="hud-card" style="border-color: #f43f5e; background: rgba(244, 63, 94, 0.1);">
                <div style="color: #f43f5e; font-size: 24px;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <div style="font-size: 11px; color: #f43f5e; text-transform: uppercase; font-weight: 700;">WARNING</div>
                    <div style="font-weight: 700; color: #fff;">Debt Goblins attacking! Add funds!</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>