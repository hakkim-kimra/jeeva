<?php
session_start();
include 'db_connect.php';

// 1. SECURE FIX: Fetch data using the unique EMAIL, not the Username
if (!isset($_SESSION['user_email'])) { header("Location: index.php"); exit(); }
$email = $_SESSION['user_email'];

// Ask database for the user with this EXACT email
$user_query = $conn->query("SELECT * FROM users WHERE email='$email'");
$user_data = $user_query->fetch_assoc();
$username = $user_data['username'];
$salary = $user_data['salary'];

// 2. Financial Calculations
$exp_query = $conn->query("SELECT SUM(amount) as t FROM expenses WHERE user_email='$email'");
$total_expenses = $exp_query->fetch_assoc()['t'] ?? 0;

$remaining = $salary - $total_expenses;

$days_in_month = date("t");
$days_passed = max(1, date("j"));
$days_left = $days_in_month - $days_passed;
$daily_limit = ($days_left > 0) ? ($remaining / $days_left) : 0;

// 3. AI Forecast Logic
$avg_daily_spend = $total_expenses / $days_passed;
$projected_spend = $avg_daily_spend * $days_in_month;
$projected_balance = $salary - $projected_spend;

if ($projected_balance > 0) {
    $ai_status = "Excellent";
    $ai_color = "#22c55e"; // Green
    $ai_msg = "You are on track to save <b style='color:#fff'>₹" . number_format($projected_balance) . "</b> this month.";
} else {
    $ai_status = "Critical";
    $ai_color = "#f43f5e"; // Red
    $ai_msg = "Slow down! You are projected to overspend by <b style='color:#fff'>₹" . number_format(abs($projected_balance)) . "</b>.";
}

// 4. Streak Logic
$streak = 0;
for ($i = 1; $i <= 30; $i++) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $day_spent = $conn->query("SELECT SUM(amount) as t FROM expenses WHERE user_email='$email' AND DATE(date)='$d'")->fetch_assoc()['t'] ?? 0;
    if ($day_spent <= ($salary/$days_in_month)) $streak++; else break;
}

// 5. Chart Data (Last 7 Days)
$chart_labels = []; $chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D', strtotime($d));
    $chart_data[] = $conn->query("SELECT SUM(amount) as t FROM expenses WHERE user_email='$email' AND DATE(date)='$d'")->fetch_assoc()['t'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FinTrackPro</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@500;700;800&display=swap');
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        /* 1. LOCK THE BODY */
        body { background: #0f172a; color: #fff; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }

        /* 2. FREEZE THE SIDEBAR */
        .sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100vh; background: #1e293b; padding: 30px 20px; display: flex; flex-direction: column; border-right: 1px solid #334155; z-index: 100; overflow-y: auto; }
        
        .brand { font-family: 'Montserrat', sans-serif; font-size: 22px; font-weight: 800; margin-bottom: 50px; display: flex; align-items: center; gap: 12px; color: #fff; padding-left: 5px; }
        .brand i { color: #06b6d4; font-size: 24px; } .brand span { color: #06b6d4; }
        
        .nav-link { display: flex; align-items: center; gap: 15px; padding: 14px; color: #94a3b8; text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 500; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background: linear-gradient(90deg, rgba(6, 182, 212, 0.1), transparent); color: #06b6d4; font-weight: 700; border-left: 4px solid #06b6d4; }
        .nav-link i { width: 20px; text-align: center; }

        /* 3. PUSH MAIN CONTENT RIGHT */
        .main { margin-left: 260px; padding: 40px; min-height: 100vh; display: flex; flex-direction: column; gap: 30px; }
        
        /* --- HEADER SECTION --- */
        .header { display: flex; justify-content: space-between; align-items: center; }
        .welcome-text h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 700; }
        .welcome-text p { color: #94a3b8; font-size: 14px; margin-top: 5px; }
        .btn-add { background: linear-gradient(90deg, #06b6d4, #3b82f6); color: #fff; padding: 12px 25px; border-radius: 30px; border: none; font-weight: 700; font-family: 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: 0.5px; cursor: pointer; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.4); transition: transform 0.2s; }
        .btn-add:hover { transform: translateY(-2px); }

        /* --- AI INSIGHT CARD --- */
        .ai-card { background: linear-gradient(135deg, #1e293b 0%, rgba(6, 182, 212, 0.1) 100%); border: 1px solid #334155; border-left: 4px solid <?= $ai_color ?>; padding: 25px; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; }
        .ai-content h3 { font-size: 14px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .ai-content p { font-size: 16px; color: #cbd5e1; max-width: 600px; line-height: 1.5; }
        .health-score { text-align: center; background: #0f172a; padding: 10px 20px; border-radius: 12px; border: 1px solid #334155; }
        .health-val { font-size: 20px; font-weight: 800; color: <?= $ai_color ?>; }

        /* --- STATS GRID --- */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .stat-card { background: #1e293b; padding: 25px; border-radius: 20px; border: 1px solid #334155; position: relative; overflow: hidden; }
        .stat-icon { position: absolute; top: 20px; right: 20px; font-size: 24px; opacity: 0.2; }
        .stat-label { font-size: 13px; color: #94a3b8; margin-bottom: 10px; font-weight: 600; text-transform: uppercase;}
        .stat-value { font-size: 28px; font-weight: 800; font-family: 'Montserrat', sans-serif; }
        .stat-sub { font-size: 12px; margin-top: 5px; opacity: 0.8; }
        .card-highlight { background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%); border: none; }
        .card-highlight .stat-label, .card-highlight .stat-value, .card-highlight .stat-sub { color: #fff !important; }
        .card-highlight .stat-icon { color: #fff; opacity: 0.3; }

        /* --- CHART SECTION --- */
        .chart-section { background: #1e293b; padding: 25px; border-radius: 20px; border: 1px solid #334155; height: 350px; }
        .section-title { font-size: 16px; font-weight: 700; margin-bottom: 20px; color: #fff; }

        /* --- TRANSACTIONS TABLE --- */
        .trans-section { background: #1e293b; border-radius: 20px; border: 1px solid #334155; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .trans-header { padding: 25px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        .trans-title { font-size: 16px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px; }
        .trans-table { width: 100%; border-collapse: collapse; }
        .trans-table th { text-align: left; padding: 15px 25px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #334155; background: #182234; }
        .trans-table td { padding: 20px 25px; border-bottom: 1px solid #334155; font-size: 14px; vertical-align: middle; }
        .trans-table tr:last-child td { border-bottom: none; }
        .trans-table tr:hover { background: #263345; transition: 0.2s; }
        .cat-badge { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 16px; }
        .cat-info { display: flex; align-items: center; }

        /* --- MODAL --- */
        #expenseModal { position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; display: none; justify-content: center; align-items: center; backdrop-filter: blur(8px); }
        .modal-content { background: #1e293b; padding: 35px; border-radius: 24px; width: 450px; border: 1px solid #334155; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .form-input { width: 100%; background: #0f172a; border: 1px solid #334155; padding: 15px; border-radius: 12px; color: #fff; margin-bottom: 15px; font-family: 'Inter', sans-serif; outline: none; }
        .form-input:focus { border-color: #06b6d4; }
        .modal-btn { width: 100%; padding: 15px; border-radius: 12px; border: none; background: #06b6d4; color: #fff; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .modal-btn:hover { background: #0891b2; }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        
        <div class="header">
            <div class="welcome-text">
                <h1>Dashboard</h1>
                <p>Welcome back, <span style="color:#06b6d4; font-weight:700;"><?= htmlspecialchars($username) ?></span></p>
            </div>
            
            <div style="display:flex; gap: 15px; align-items: center;">
                <?php if($streak > 0): ?>
                    <div style="background: rgba(234, 179, 8, 0.2); color: #eab308; padding: 8px 15px; border-radius: 20px; font-weight: 700; font-size: 14px; border: 1px solid #eab308;">
                        <i class="fa-solid fa-fire"></i> <?= $streak ?> Day Streak
                    </div>
                <?php endif; ?>
                
                <button class="btn-add" onclick="toggleModal()">
                    <i class="fa-solid fa-plus"></i> Add Transaction
                </button>
            </div>
        </div>

        <div class="ai-card">
            <div class="ai-content">
                <h3><i class="fa-solid fa-robot"></i> AI Financial Analysis</h3>
                <p><?= $ai_msg ?></p>
            </div>
            <div class="health-score">
                <div style="font-size:10px; color:#94a3b8; margin-bottom:2px;">HEALTH SCORE</div>
                <div class="health-val"><?= $ai_status ?></div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fa-solid fa-wallet stat-icon" style="color:#94a3b8"></i>
                <div class="stat-label">TOTAL BUDGET</div>
                <div class="stat-value" style="color: #fff;">₹<?= number_format($salary) ?></div>
                <div class="stat-sub" style="color:#94a3b8">Monthly Income</div>
            </div>

            <div class="stat-card">
                <i class="fa-solid fa-receipt stat-icon" style="color:#f43f5e"></i>
                <div class="stat-label">TOTAL SPENT</div>
                <div class="stat-value" style="color: #f43f5e;">₹<?= number_format($total_expenses) ?></div>
                <div class="stat-sub" style="color:#94a3b8"><?= ($salary > 0) ? round(($total_expenses/$salary)*100) : 0 ?>% of budget</div>
            </div>

            <div class="stat-card card-highlight">
                <i class="fa-solid fa-piggy-bank stat-icon"></i>
                <div class="stat-label">REMAINING</div>
                <div class="stat-value">₹<?= number_format($remaining) ?></div>
                <div class="stat-sub">Safe Daily Spend: ₹<?= number_format($daily_limit) ?></div>
            </div>
        </div>

        <div class="chart-section">
            <div class="section-title">Spending Trends (Last 7 Days)</div>
            <div style="height: 280px; width: 100%;">
                <canvas id="mainChart"></canvas>
            </div>
        </div>

        <div class="trans-section">
            <div class="trans-header">
                <div class="trans-title"><i class="fa-solid fa-list-ul" style="color:#06b6d4;"></i> Recent Transactions</div>
                <a href="reports.php" style="color:#94a3b8; font-size:13px; text-decoration:none; font-weight:600; transition:0.3s;">View All <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            
            <table class="trans-table">
                <thead>
                    <tr>
                        <th width="45%">Transaction Details</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th style="text-align:right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recents = $conn->query("SELECT * FROM expenses WHERE user_email='$email' ORDER BY date DESC LIMIT 5");
                    
                    if ($recents->num_rows > 0) {
                        while($r = $recents->fetch_assoc()) {
                            $icon = "fa-receipt"; $bg = "#334155"; $col = "#94a3b8"; 
                            
                            $cat = strtolower($r['category']);
                            if(strpos($cat, 'food') !== false || strpos($cat, 'cafe') !== false) { $icon="fa-burger"; $bg="rgba(249, 115, 22, 0.15)"; $col="#f97316"; }
                            elseif(strpos($cat, 'transport') !== false || strpos($cat, 'fuel') !== false) { $icon="fa-car"; $bg="rgba(59, 130, 246, 0.15)"; $col="#3b82f6"; }
                            elseif(strpos($cat, 'shopping') !== false || strpos($cat, 'mall') !== false) { $icon="fa-bag-shopping"; $bg="rgba(236, 72, 153, 0.15)"; $col="#ec4899"; }
                            elseif(strpos($cat, 'housing') !== false || strpos($cat, 'bill') !== false) { $icon="fa-house"; $bg="rgba(168, 85, 247, 0.15)"; $col="#a855f7"; }

                            echo '<tr>
                                <td>
                                    <div class="cat-info">
                                        <div class="cat-badge" style="background:'.$bg.'; color:'.$col.';"><i class="fa-solid '.$icon.'"></i></div>
                                        <div>
                                            <div style="font-weight:600; color:#fff;">'.htmlspecialchars($r['category']).'</div>
                                            <div style="font-size:12px; color:#94a3b8;">'.htmlspecialchars(substr($r['notes'], 0, 30)).(strlen($r['notes'])>30?'...':'').'</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="color:#94a3b8; font-weight:500;">'.date('M d, Y', strtotime($r['date'])).'</td>
                                <td style="font-weight:700; color:#fff;">₹'.number_format($r['amount'], 2).'</td>
                                <td style="text-align:right;">
                                    <span style="background:rgba(244, 63, 94, 0.1); color:#f43f5e; padding:6px 14px; border-radius:20px; font-size:11px; font-weight:700;">Expense</span>
                                </td>
                            </tr>';
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center; padding:40px; color:#64748b;"><i class="fa-solid fa-ghost" style="font-size:24px; margin-bottom:10px; display:block;"></i>No transactions yet. Click "Add Transaction" to start.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>

    <div id="expenseModal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-size:20px;">Add Transaction</h2>
                <i class="fa-solid fa-xmark" onclick="toggleModal()" style="cursor:pointer; color:#94a3b8; font-size:20px;"></i>
            </div>
            
            <div style="background:#0f172a; padding:15px; border-radius:12px; text-align:center; border:1px dashed #334155; margin-bottom:20px;">
                <label for="ocrInput" style="cursor:pointer; color:#06b6d4; font-weight:600; font-size:14px;">
                    <i class="fa-solid fa-camera"></i> Scan Receipt with AI
                </label>
                <input type="file" id="ocrInput" style="display:none;" onchange="runOCR()">
                <div id="ocr-msg" style="display:none; color:#22c55e; font-size:12px; margin-top:5px;">Scanning...</div>
            </div>

            <form action="add_transaction.php" method="POST">
                <input type="number" name="amount" id="amt" class="form-input" placeholder="Amount (0.00)" step="0.01" required>
                
                <select name="category" class="form-input" onchange="checkCustom(this)">
                    <option value="" disabled selected>Select Category</option>
                    <option value="Food">Food & Dining</option>
                    <option value="Transport">Transportation</option>
                    <option value="Housing">Housing & Utilities</option>
                    <option value="Shopping">Shopping</option>
                    <option value="Custom" style="color:#06b6d4; font-weight:bold;">+ Create Custom</option>
                </select>
                <input type="text" name="custom_cat" id="custom" class="form-input" placeholder="Enter Category Name" style="display:none;">
                
                <textarea name="notes" class="form-input" placeholder="Add a note (optional)" rows="2"></textarea>
                
                <button type="submit" class="modal-btn">Save Transaction</button>
            </form>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('mainChart').getContext('2d');
    let gradient = ctx.createLinearGradient(0, 0, 0, 400); gradient.addColorStop(0, 'rgba(6, 182, 212, 0.5)'); gradient.addColorStop(1, 'rgba(6, 182, 212, 0)');
    new Chart(ctx, { type: 'line', data: { labels: <?= json_encode($chart_labels) ?>, datasets: [{ label: 'Daily Spending', data: <?= json_encode($chart_data) ?>, borderColor: '#06b6d4', backgroundColor: gradient, borderWidth: 3, fill: true, tension: 0.4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#334155' }, beginAtZero: true } } } });

    function toggleModal() { document.getElementById('expenseModal').style.display = (document.getElementById('expenseModal').style.display === 'flex') ? 'none' : 'flex'; }
    function checkCustom(select) { document.getElementById('custom').style.display = (select.value === 'Custom') ? 'block' : 'none'; }

    function runOCR() {
        const msgDiv = document.getElementById('ocr-msg');
        const fileInput = document.getElementById('ocrInput');
        
        if (fileInput.files.length === 0) return;

        msgDiv.style.display = 'block';
        msgDiv.innerHTML = '<i class="fa-solid fa-bolt fa-spin"></i> Asking AI...';
        msgDiv.style.color = '#06b6d4'; 

        const formData = new FormData();
        formData.append('receipt_image', fileInput.files[0]);

        fetch('scan_receipt.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                msgDiv.innerHTML = 'Error: ' + data.error;
                msgDiv.style.color = '#f43f5e';
            } else {
                document.getElementById('amt').value = parseFloat(data.amount).toFixed(2);
                const catSelect = document.querySelector('select[name="category"]');
                catSelect.value = data.category; 
                if (!catSelect.value) catSelect.value = "Uncategorized";
                checkCustom(catSelect);
                document.querySelector('textarea[name="notes"]').value = "Date: " + (data.date || 'Unknown') + "\n\nItems:\n" + data.items;
                msgDiv.innerHTML = '<i class="fa-solid fa-check"></i> Analysis Complete!';
                msgDiv.style.color = '#22c55e';
            }
        })
        .catch(error => { msgDiv.innerHTML = 'Connection Failed'; msgDiv.style.color = '#f43f5e'; });
    }

    <?php if($streak > 2): ?> 
    if (!sessionStorage.getItem('confettiShown')) { 
        window.onload = function() { 
            confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 } }); 
            sessionStorage.setItem('confettiShown', 'true'); 
        }; 
    } 
    <?php endif; ?>
    </script>
</body>
</html>