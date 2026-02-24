<?php
session_start();
include 'db_connect.php';

// SECURE FIX: Grab the unique email directly from the session!
if (!isset($_SESSION['user_email'])) { header("Location: index.php"); exit(); }
$email = $_SESSION['user_email'];
$username = $_SESSION['user']; // Kept just for the UI display

// --- 1. FETCH DAILY DATA (Current Month) ---
$daily_labels = []; $daily_data = [];
$q_daily = $conn->query("SELECT DATE_FORMAT(date, '%e %b') as d, SUM(amount) as t FROM expenses WHERE user_email='$email' AND MONTH(date) = MONTH(CURRENT_DATE()) GROUP BY DATE(date) ORDER BY date");
while($r = $q_daily->fetch_assoc()) { 
    $daily_labels[] = $r['d']; 
    $daily_data[] = $r['t']; 
}

// --- 2. FETCH CATEGORY DATA (All Time) ---
$cat_labels = []; $cat_data = [];
$q_cat = $conn->query("SELECT category, SUM(amount) as t FROM expenses WHERE user_email='$email' GROUP BY category ORDER BY t DESC");
$highest_category = "None";
$highest_cat_amount = 0;
while($r = $q_cat->fetch_assoc()) { 
    $cat_labels[] = $r['category']; 
    $cat_data[] = $r['t']; 
    if($highest_category == "None") { $highest_category = $r['category']; $highest_cat_amount = $r['t']; }
}

// --- 3. FETCH MONTHLY DATA (All Time) ---
$month_labels = []; $month_data = [];
$q_month = $conn->query("SELECT DATE_FORMAT(date, '%b %Y') as m, SUM(amount) as t FROM expenses WHERE user_email='$email' GROUP BY MONTH(date), YEAR(date) ORDER BY YEAR(date), MONTH(date)");
$total_all_time = 0;
while($r = $q_month->fetch_assoc()) { 
    $month_labels[] = $r['m']; 
    $month_data[] = $r['t']; 
    $total_all_time += $r['t'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics & Reports - FinTrackPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Montserrat:wght@500;700;800&display=swap');
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0f172a; color: #fff; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }

        /* SIDEBAR (Fixed) */
        .sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100vh; background: #1e293b; padding: 30px 20px; display: flex; flex-direction: column; border-right: 1px solid #334155; z-index: 100; overflow-y: auto; }
        .brand { font-family: 'Montserrat', sans-serif; font-size: 22px; font-weight: 800; margin-bottom: 50px; display: flex; align-items: center; gap: 12px; color: #fff; padding-left: 5px; }
        .brand i { color: #06b6d4; font-size: 24px; } .brand span { color: #06b6d4; }
        .nav-link { display: flex; align-items: center; gap: 15px; padding: 14px; color: #94a3b8; text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 500; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background: linear-gradient(90deg, rgba(6, 182, 212, 0.1), transparent); color: #06b6d4; font-weight: 700; border-left: 4px solid #06b6d4; }
        .nav-link i { width: 20px; text-align: center; }

        /* MAIN CONTENT */
        .main { margin-left: 260px; padding: 40px; min-height: 100vh; display: flex; flex-direction: column; gap: 30px; }
        ::-webkit-scrollbar { width: 8px; } ::-webkit-scrollbar-track { background: #0f172a; } ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        /* HEADER & DOWNLOAD DROPDOWN */
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px;}
        .header h1 { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }
        .header p { color: #94a3b8; font-size: 14px; margin-top: 5px; }
        
        /* Dropdown Styles */
        .export-dropdown { position: relative; display: inline-block; }
        .btn-export { background: linear-gradient(90deg, #06b6d4, #3b82f6); color: #fff; padding: 12px 24px; border: none; border-radius: 12px; cursor: pointer; font-weight: 700; font-family: 'Inter', sans-serif; transition: 0.3s; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3); }
        .btn-export:hover { transform: translateY(-2px); }
        
        .dropdown-content { display: none; position: absolute; right: 0; background: #1e293b; min-width: 180px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); z-index: 10; border-radius: 12px; border: 1px solid #334155; overflow: hidden; margin-top: 10px; }
        .dropdown-content a { color: #cbd5e1; padding: 14px 20px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; transition: 0.2s; border-bottom: 1px solid #334155; }
        .dropdown-content a:last-child { border-bottom: none; }
        .dropdown-content a:hover { background: #0f172a; color: #06b6d4; padding-left: 25px; }
        .export-dropdown:hover .dropdown-content { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* CARDS & CHARTS */
        .insights-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .insight-card { background: linear-gradient(145deg, #1e293b, #0f172a); padding: 25px; border-radius: 20px; border: 1px solid #334155; display: flex; align-items: center; gap: 20px; transition: 0.3s; position: relative; overflow: hidden; }
        .insight-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.4); border-color: #475569; }
        .insight-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #06b6d4; opacity: 0; transition: 0.3s; }
        .insight-card:hover::before { opacity: 1; }
        .insight-icon { width: 55px; height: 55px; border-radius: 14px; display: flex; justify-content: center; align-items: center; font-size: 22px; box-shadow: inset 0 2px 5px rgba(255,255,255,0.1); }
        .insight-info h4 { color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .insight-info div { font-size: 26px; font-weight: 800; font-family: 'Montserrat', sans-serif; }

        .charts-container { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .chart-box { background: #1e293b; padding: 30px; border-radius: 24px; border: 1px solid #334155; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .chart-box.full-width { grid-column: span 2; }
        
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .chart-title { font-size: 16px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px; font-family: 'Montserrat', sans-serif; letter-spacing: 0.5px; }
        
        .dl-icon { color: #64748b; cursor: pointer; transition: 0.3s; font-size: 16px; padding: 5px; }
        .dl-icon:hover { color: #06b6d4; transform: scale(1.1); }
        
        .chart-wrapper { position: relative; height: 320px; width: 100%; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="header">
            <div>
                <h1>Financial Intelligence</h1>
                <p>Advanced data visualization for your spending habits.</p>
            </div>
            
            <div class="export-dropdown">
                <button class="btn-export">
                    <i class="fa-solid fa-cloud-arrow-down"></i> Export Report
                </button>
                <div class="dropdown-content">
                    <a href="#" onclick="window.print()"><i class="fa-solid fa-file-pdf"></i> Save as PDF</a>
                    <a href="#" onclick="downloadCSV()"><i class="fa-solid fa-file-csv"></i> Download CSV Data</a>
                </div>
            </div>
        </div>

        <div class="insights-grid">
            <div class="insight-card">
                <div class="insight-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4; border: 1px solid rgba(6,182,212,0.3);"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div class="insight-info"><h4>Total Lifetime Spent</h4><div>₹<?= number_format($total_all_time) ?></div></div>
            </div>
            <div class="insight-card">
                <div class="insight-icon" style="background: rgba(244, 63, 94, 0.15); color: #f43f5e; border: 1px solid rgba(244,63,94,0.3);"><i class="fa-solid fa-fire-flame-curved"></i></div>
                <div class="insight-info"><h4>Highest Category</h4><div style="color:#f43f5e;"><?= htmlspecialchars($highest_category) ?></div></div>
            </div>
            <div class="insight-card">
                <div class="insight-icon" style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34,197,94,0.3);"><i class="fa-solid fa-chart-line"></i></div>
                <div class="insight-info"><h4>Active Tracking Days</h4><div><?= count($daily_labels) ?> Days</div></div>
            </div>
        </div>

        <div class="charts-container">
            
            <div class="chart-box full-width">
                <div class="chart-header">
                    <div class="chart-title"><i class="fa-solid fa-wave-square" style="color: #06b6d4;"></i> Daily Spending Trend (This Month)</div>
                    <i class="fa-solid fa-download dl-icon" title="Download PNG" onclick="downloadImage('dailyChart', 'Daily_Trend.png')"></i>
                </div>
                <div class="chart-wrapper"><canvas id="dailyChart"></canvas></div>
            </div>

            <div class="chart-box">
                <div class="chart-header">
                    <div class="chart-title"><i class="fa-solid fa-chart-pie" style="color: #ec4899;"></i> Category Breakdown</div>
                    <i class="fa-solid fa-download dl-icon" title="Download PNG" onclick="downloadImage('catChart', 'Category_Pie.png')"></i>
                </div>
                <div class="chart-wrapper"><canvas id="catChart"></canvas></div>
            </div>

            <div class="chart-box">
                <div class="chart-header">
                    <div class="chart-title"><i class="fa-solid fa-chart-column" style="color: #8b5cf6;"></i> Monthly History</div>
                    <i class="fa-solid fa-download dl-icon" title="Download PNG" onclick="downloadImage('monthChart', 'Monthly_Bars.png')"></i>
                </div>
                <div class="chart-wrapper"><canvas id="monthChart"></canvas></div>
            </div>

        </div>
    </div>

    <script>
        // Global Chart Defaults
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.font.family = 'Inter';
        
        const tooltipConfig = {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            titleColor: '#fff', bodyColor: '#cbd5e1', borderColor: '#334155',
            borderWidth: 1, padding: 12, boxPadding: 6, usePointStyle: true
        };

        // 1. Daily Area Chart
        const ctxDaily = document.getElementById('dailyChart').getContext('2d');
        let gradDaily = ctxDaily.createLinearGradient(0, 0, 0, 350);
        gradDaily.addColorStop(0, 'rgba(6, 182, 212, 0.6)'); gradDaily.addColorStop(1, 'rgba(6, 182, 212, 0.0)');
        new Chart(ctxDaily, {
            type: 'line',
            data: { labels: <?= json_encode($daily_labels) ?>, datasets: [{ label: 'Spent (₹)', data: <?= json_encode($daily_data) ?>, borderColor: '#06b6d4', backgroundColor: gradDaily, borderWidth: 3, pointBackgroundColor: '#0f172a', pointBorderColor: '#06b6d4', fill: true, tension: 0.4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: tooltipConfig }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#334155', borderDash: [5, 5] }, beginAtZero: true } } }
        });

        // 2. Category Pie Chart (NEW)
        const ctxCat = document.getElementById('catChart').getContext('2d');
        new Chart(ctxCat, {
            type: 'pie',
            data: {
                labels: <?= json_encode($cat_labels) ?>,
                datasets: [{
                    data: <?= json_encode($cat_data) ?>,
                    backgroundColor: ['#ec4899', '#f59e0b', '#22c55e', '#06b6d4', '#8b5cf6', '#f43f5e'],
                    borderWidth: 2, borderColor: '#1e293b', hoverOffset: 10
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { 
                    legend: { position: 'right', labels: { color: '#cbd5e1', usePointStyle: true, boxWidth: 8, padding: 20 } },
                    tooltip: tooltipConfig
                }
            }
        });

        // 3. Monthly Bar Chart (NEW)
        const ctxMonth = document.getElementById('monthChart').getContext('2d');
        let gradMonth = ctxMonth.createLinearGradient(0, 0, 0, 300);
        gradMonth.addColorStop(0, '#8b5cf6'); gradMonth.addColorStop(1, '#6d28d9');
        new Chart(ctxMonth, {
            type: 'bar',
            data: {
                labels: <?= json_encode($month_labels) ?>,
                datasets: [{
                    label: 'Spent (₹)',
                    data: <?= json_encode($month_data) ?>,
                    backgroundColor: gradMonth,
                    borderRadius: 8, barThickness: 30
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: tooltipConfig },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: '#334155', borderDash: [5, 5] }, beginAtZero: true }
                }
            }
        });

        // --- EXPORT FUNCTIONS ---

        // Download Individual Chart as Image
        function downloadImage(chartId, filename) {
            const canvas = document.getElementById(chartId);
            const link = document.createElement('a');
            link.download = filename;
            // Get background color behind the transparent canvas so the saved image isn't black
            const context = canvas.getContext('2d');
            context.globalCompositeOperation = 'destination-over';
            context.fillStyle = "#1e293b"; 
            context.fillRect(0, 0, canvas.width, canvas.height);
            link.href = canvas.toDataURL('image/png');
            link.click();
        }

        // Export Raw Data to CSV (Excel)
        function downloadCSV() {
            let csv = "Category,Amount Spent (INR)\n";
            <?php
            for ($i = 0; $i < count($cat_labels); $i++) {
                echo "csv += \"" . $cat_labels[$i] . "," . $cat_data[$i] . "\\n\";\n";
            }
            ?>
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", "FinTrackPro_Category_Data.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>