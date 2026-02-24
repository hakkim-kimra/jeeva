<div class="sidebar">
    <div class="brand"><i class="fa-solid fa-wallet"></i> FinTrack<span>Pro</span></div>
    
    <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>">
        <i class="fa-solid fa-house"></i> Dashboard
    </a>
    
    <a href="simulator.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='simulator.php'?'active':'' ?>">
        <i class="fa-solid fa-wand-magic-sparkles"></i> AI Simulator
    </a>
    
    <a href="runway.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='runway.php'?'active':'' ?>">
        <i class="fa-solid fa-battery-half"></i> Survival Mode
    </a>
    
    <a href="fincity.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='fincity.php'?'active':'' ?>">
        <i class="fa-solid fa-city"></i> FinCity World
    </a>
    
    <a href="reports.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='reports.php'?'active':'' ?>">
        <i class="fa-solid fa-chart-pie"></i> Reports
    </a>
    
    <a href="profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])=='profile.php'?'active':'' ?>">
        <i class="fa-solid fa-user"></i> Profile
    </a>
</div>