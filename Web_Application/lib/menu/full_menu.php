<div id="header">
    <div class="logo"><img src="img/shop_banner.png" style="opacity:0.8;background-color:E9E5E2;" border="0" alt=""
                           title="Shop banner"/></div>
</div>


<div class="nav_bar">
    <ul>
        <li><a href="search_vehicles.php" <?php if ($current_filename == 'search_vehicles.php') echo "class='active'"; ?>>Search for Vehicles</a></li>
        <li><a href="add_vehicle.php" <?php if ($current_filename == 'add_vehicle.php') echo "class='active'"; ?>>Add Vehicle</a></li>
        <li><a href="add_manufacturer.php" <?php if ($current_filename == 'add_manufacturer.php') echo "class='active'"; ?>>Add manufacturer</a></li>
        <li><a href="view_repair.php" <?php if ($current_filename == 'view_repair.php') echo "class='active'"; ?>>View/Add/Update Repair</a></li>
        <li><a href="view_reports.php" <?php if ($current_filename == 'view_reports.php') echo "class='active'"; ?>>View Reports</a></li>
        <li><a href="login.php" <?php if ($current_filename == 'login.php') echo "class='active'"; ?>>Log in</a></li>
        <li><a href="logout.php" <span class='glyphicon glyphicon-log-out'></span> Log Out</a></li>
    </ul>
</div>
