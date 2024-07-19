<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

?>

<?php include("lib/header.php"); ?>

<title>View Reports</title>
</head>

<body>
<div id="main_container">
    <?php if (($_SESSION['role_type']) == "") include("lib/menu/public_menu.php"); ?>
    <?php if (($_SESSION['role_type']) == "InventoryClerk") include("lib/menu/inventory_clerk_menu.php"); ?>
    <?php if (($_SESSION['role_type']) == "Salesperson") include("lib/menu/salesperson_menu.php"); ?>
    <?php if (($_SESSION['role_type']) == "ServiceWriter") include("lib/menu/service_writer_menu.php"); ?>
    <?php if (($_SESSION['role_type']) == "Manager") include("lib/menu/manager_menu.php"); ?>
    <?php if (($_SESSION['role_type']) == "Owner") include("lib/menu/full_menu.php"); ?>

    <div class="center_content">
        <div class="center_left">
            <div class='profile_section'>
                <div class='subtitle'></div>
                <table>
                    <tr>
                        <p="center_left"><input type="button" name="input" value="Sales by Color Report" onclick="location.href = 'report_sales_by_color.php'" /></p>                    </tr>
                    </tr>
                    <br/>
                    <tr>
                        <p="center_left"><input type="button" name="input" value="Sales by Type Report" onclick="location.href = 'report_sales_by_type.php'" /></p>
                    </tr>
                    <br/>
                    <tr>
                    <p="center_left"><input type="button" name="input" value="Sales by Manufacturer" onclick="location.href = 'report_sales_by_manufacturer.php'" /></p>
                    </tr>
                    <br/>
                    <tr>
                    <p="center_left"><input type="button" name="input" value="Gross Customer Income Report" onclick="location.href = 'report_gross_customer_income.php'" /></p>
                    </tr>
                    <br/>
                    <tr>
                    <p="center_left"><input type="button" name="input" value="Repair by Manufacturer/Type/Model Report" onclick="location.href = 'report_repairs.php'" /></p>
                    </tr>
                    <br/>
                    <tr>
                    <p="center_left"><input type="button" name="input" value="Below Cost Report" onclick="location.href = 'report_below_cost_sales.php'" /></p>
                    </tr>
                    <br/>
                    <tr>
                    <p="center_left"><input type="button" name="input" value="Average Time in Inventory Report" onclick="location.href = 'report_average_time_in_inventory.php'" /></p>
                    </tr>
                    <br/>
                    <tr>
                    <p="center_left"><input type="button" name="input" value="Parts Statistics Report" onclick="location.href = 'report_parts_statistics.php'" /></p>
                    </tr>
                    <br/>
                    <tr>
                    <p="center_left"><input type="button" name="input" value="Monthly Sales Report" onclick="location.href = 'report_monthly_sales.php'" /></p>
                    </tr>
                </table>

            </div>
        </div>

        <?php include("lib/error.php"); ?>

        <div class="clear"></div>
    </div>

    <?php include("lib/footer.php"); ?>

</div>
</body>
</html>