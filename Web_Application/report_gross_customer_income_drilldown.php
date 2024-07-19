<?php

    include('lib/common.php');

    if (!isset($_SESSION['username'])) {
        header('Location: login.php');
        exit();
    }

    $customerID = mysqli_real_escape_string($db, $_REQUEST['customerID']);
    $Name = mysqli_real_escape_string($db, $_REQUEST['Name']);

?>

<?php include("lib/header.php"); ?>

<?php
    $title = "Sales & Repair from $Name";
    include("lib/variable_head_title.php");
?>
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

            <div class="features">
                <div class="profile_section">
                    <?php
                    $subtitle = "Sales From $Name";
                    include("lib/variable_subtitle.php");
                    ?>

                    <?php
                    $query = "
                            SELECT 
                                date(s.Purchase_Date) AS sale_date, 
                                s.sold_price, v.VIN, 
                                v.model_year, 
                                v.manufacturer, 
                                v.model_name, 
                                CONCAT (u.first_name, ' ',  u.last_name) AS spName
                            FROM 
                                Vehicle v 
                                INNER JOIN SalesTransaction s 
                                INNER JOIN LoggedInUser u ON v.VIN = s.VIN AND s.userName = u.userName
                            WHERE s.customerID = '$customerID'
                            ORDER BY sale_date DESC, v.VIN ASC
                            ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">Sold Date</td>';
                        print '<td class="heading">Sold Price</td>';
                        print '<td class="heading">VIN';
                        print '<td class="heading">Manufacturer</td>';
                        print '<td class="heading">Model Name</td>';
                        print '<td class="heading">Model Year</td>';
                        print '<td class="heading">Salesperson</td>';
                        print '<td class="heading"> </td>';
                        print '</tr>';

                        while ($row) {


                            print '<tr>';
                            print '<td>' . $row['sale_date'] . '</td>';
                            print '<td>' . $row['sold_price'] . '</td>';
                            print '<td>' . $row['VIN'] . '</td>';
                            print '<td>' . $row['manufacturer'] . '</td>';
                            print '<td>' . $row['model_name'] . '</td>';
                            print '<td>' . $row['model_year'] . '</td>';
                            print '<td>' . $row['spName'] . '</td>';
                            print '</tr>';

                            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

                        }
                        print '</table>';
                    } else {
                        print "<br/>None!";
                    }
                    ?>

                </div>
                <div class="profile_section">
                    <?php
                    $subtitle = "Repair From $Name";
                    include("lib/variable_subtitle.php");
                    ?>

                    <?php
                    $query ="
                        WITH Part_sum AS(
                            SELECT VIN, 
                                start_date, 
                                sum(p.quantity * p.price) AS part_cost
                            FROM Part p
                            GROUP BY VIN, start_date
                        ),
                        Vehicle_repair AS(
                            SELECT 
                                r.VIN, 
                                r.customerID, 
                                r.userName, 
                                r.start_date, 
                                r.completion_date AS end_date, 
                                r.odometer_readout, 
                                IFNULL(r.labor_charge, 0) AS labor_cost, 
                                ps.part_cost AS parts_cost, 
                                IFNULL(r.labor_charge, 0) + ps.part_cost AS total_cost
                            FROM Repair r INNER JOIN Part_sum ps
                            ON ps.VIN = r.VIN and ps.start_date = r.start_date
                        )
                        SELECT 
                            r.VIN,
                            r.start_date, 
                            r.end_date,
                            r.odometer_readout, 
                            r.VIN,
                            r.labor_cost, 
                            r.parts_cost, 
                            r.total_cost,
                            CONCAT (u.first_name, ' ',  u.last_name) AS spName
                        From 
                            Vehicle_repair r 
                            INNER JOIN LoggedInUser u ON r.userName = u.userName
                        WHERE r.customerID = '$customerID'
                        ORDER BY start_date DESC, IF(ISNULL(end_date),0,1) DESC, VIN ASC
                        ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">Start Date</td>';
                        print '<td class="heading">End Date</td>';
                        print '<td class="heading">VIN';
                        print '<td class="heading">Odometer Reading</td>';
                        print '<td class="heading">Labor Cost</td>';
                        print '<td class="heading">Parts Cost</td>';
                        print '<td class="heading">Total Cost</td>';
                        print '<td class="heading">Service Writer</td>';
                        print '<td class="heading"> </td>';
                        print '</tr>';

                        while ($row) {


                            print '<tr>';
                            print '<td>' . $row['start_date'] . '</td>';
                            print '<td>' . $row['end_date'] . '</td>';
                            print '<td>' . $row['VIN'] . '</td>';
                            print '<td>' . $row['odometer_readout'] . '</td>';
                            print '<td>' . $row['labor_cost'] . '</td>';
                            print '<td>' . $row['parts_cost'] . '</td>';
                            print '<td>' . $row['total_cost'] . '</td>';
                            print '<td>' . $row['spName'] . '</td>';
                            print '</tr>';

                            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

                        }
                        print '</table>';
                    } else {
                        print "<br/>None!";
                    }
                    ?>

                </div>
                <tr>
                    <div style=" position:fixed; right:5px; bottom:25px; width:100%; height:50px; background-color:transparent; z-index:9999;">
                        <p><input type="button" name="input" value="Back" Right="15%" ;postion="absolute"
                                  onclick="location.href = 'report_gross_customer_income.php'"/></p></div>
                </tr>
            </div>
        </div>

        <?php include("lib/error.php"); ?>

        <div class="clear"></div>
    </div>

    <?php include("lib/footer.php"); ?>

</div>
</body>
</html>