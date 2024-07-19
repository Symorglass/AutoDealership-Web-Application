<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // When VIN_search button is clicked
    if (isset($_POST['VIN_search'])) {
        // validate if VIN field is empty
        if (empty($_POST['VIN_search'])) {
            array_push($error_msg, "Please enter VIN to search.");
        } else {

            // Fetch VIN value
            $VIN = mysqli_real_escape_string($db, $_POST['VIN']);

            // Execute the VIN search
            $query = "
                SELECT VIN
                FROM Vehicle
                WHERE VIN = '$VIN';
            ";

            $VIN_search_result = mysqli_query($db, $query);
            //for show up in UI durng debug
            $result = mysqli_query($db, $query);
            include('lib/show_queries.php');
            if (mysqli_affected_rows($db) == -1) {
                array_push($error_msg, "SELECT ERROR:Failed to find vehicles ... <br>" . __FILE__ . " line:" . __LINE__);
            }

            // If VIN exists, search if VIN has been sold
            if (is_bool($VIN_search_result) || mysqli_num_rows($VIN_search_result) == 0) {
                array_push($error_msg, "Vehicle with this VIN not found, please check." . NEWLINE);
            } elseif (!is_bool($VIN_search_result) && (mysqli_num_rows($VIN_search_result) > 0)) {

                // validate if vehicle is sold (no repair should exist for unsold vehicle)
                $query = "
                    WITH sold_vehicle AS (
                        SELECT V.VIN, V.model_year, V.model_name, V.manufacturer 
                        FROM Vehicle V INNER JOIN SalesTransaction AS ST ON V.VIN = ST.VIN
                    )
                    SELECT S.VIN FROM sold_vehicle S
                    WHERE S.VIN = '$VIN';
                ";

                $VIN_sold = mysqli_query($db, $query);
                //for show up in UI durng debug
                $result = mysqli_query($db, $query);
                include('lib/show_queries.php');
                if (mysqli_affected_rows($db) == -1) {
                    array_push($error_msg, "SELECT ERROR:Failed to find vehicles ... <br>" . __FILE__ . " line:" . __LINE__);
                }

                // Is VIN is sold, display detail of VIN
                if (is_bool($VIN_sold) || mysqli_num_rows($VIN_sold) == 0) {
                    array_push($error_msg, " This vehicle is not sold, should not have repair record." . NEWLINE);
                } elseif (!is_bool($VIN_sold) && mysqli_num_rows($VIN_sold) > 0) {
                    // if VIN exist and vehicle is sold, display details for this vehicle
                    $query = "
                    WITH vehicle_car AS (
                        SELECT VIN, 'Car' AS vehicle_type 
                        FROM Car 
                    ),
                    vehicle_convertible AS (
                        SELECT VIN, 'Convertible' AS vehicle_type 
                        FROM Convertible
                    ),
                    vehicle_truck AS (
                        SELECT VIN, 'Truck' AS vehicle_type 
                        FROM Truck
                    ),
                    vehicle_van_minivan AS (
                        SELECT VIN, 'VAN_MiniVAN' AS vehicle_type 
                        FROM VAN_MiniVAN 
                    ),
                    vehicle_suv AS (
                        SELECT VIN, 'VAN_MiSUVniVAN' AS vehicle_type 
                        FROM SUV 
                    ),
                    vehicle_u AS (
                        SELECT VIN, vehicle_type FROM vehicle_car
                        UNION
                        SELECT VIN, vehicle_type FROM vehicle_convertible 
                        UNION
                        SELECT VIN, vehicle_type FROM vehicle_truck
                        UNION
                        SELECT VIN, vehicle_type FROM vehicle_van_minivan 
                        UNION
                        SELECT VIN, vehicle_type FROM vehicle_suv
                    ),
                    vehicle_info AS (
                        SELECT v.VIN, vu.vehicle_type, v.manufacturer, v.model_year, v.model_name, GROUP_CONCAT(vc.color) AS color
                        FROM vehicle_u vu 
                        INNER JOIN Vehicle v 
                        INNER JOIN Vehicle_Color vc
                        ON vu.VIN = v.VIN AND vu.VIN=vc.VIN
                        GROUP BY v.VIN
                    )
                    SELECT v.VIN, v.vehicle_type, v.manufacturer, v.model_year, v.model_name, v.color
                    FROM vehicle_info v
                    WHERE v.VIN = '$VIN';
                    ";

                    $display_vehicle = mysqli_query($db, $query);
                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');
                    if (mysqli_affected_rows($db) == -1) {
                        array_push($error_msg, "SELECT ERROR:Failed to find vehicles ... <br>" . __FILE__ . " line:" . __LINE__);
                    }
                }
            }
        }
    }

    // If this VIN exist and is sold, validate if it has repair
    if (isset($display_vehicle)) {
        if (is_bool($display_vehicle) || mysqli_num_rows($display_vehicle) == 0) {
            array_push($error_msg, "Error in vehicle search, please check." . NEWLINE);
        } else {
            // Fetch search result
            $row = mysqli_fetch_array($display_vehicle, MYSQLI_ASSOC);
            //Validate if this vehicle has any repairs
            $query = "
                SELECT VIN, start_date, completion_date, odometer_readout, labor_charge, description
                FROM Repair
                WHERE VIN = '$VIN'
                ORDER BY start_date ASC;
            ";
            $repair_record = mysqli_query($db, $query);
            $result = mysqli_query($db, $query);
            include('lib/show_queries.php');

        // If this VIN has repair, validate if it has any open repair (repair without completion date)
        if (!is_bool($repair_record) && mysqli_num_rows($repair_record) != 0) {
            $query = "
                SELECT VIN, completion_date
                FROM Repair
                WHERE VIN = '$VIN' AND completion_date IS NULL;
            ";
            $repair_completion_date_empty = mysqli_query($db, $query);
            $result = mysqli_query($db, $query);
            include('lib/show_queries.php');
        }

        // Validate if any repair has start date as today
        if (!is_bool($repair_record) && mysqli_num_rows($repair_record) != 0) {
            $query = "
                SELECT VIN, start_date
                FROM Repair
                WHERE VIN = '$VIN' AND DATE(start_date) = CURDATE();
            ";
        }
        $has_repair_start_today = mysqli_query($db, $query);
        $result = mysqli_query($db, $query);
        include('lib/show_queries.php');

        }
    }


}  //end of if($_POST)

?>

<?php include("lib/header.php"); ?>
<head>
<title>View Repair</title>
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
            <div class="title_name"><?php print "View Repair Form"; ?></div>
            <div class="features">

                <!-- VIN search -->
                <div class="profile_section">
                    <div class="subtitle">Search Repair Record (by VIN)</div>
                    <form name="VIN_search_form" action="view_repair.php" method="post">
                        <table>
                            <tr>
                                <td class="item_label">VIN</td>
                                <td>
                                    <input type="text" name="VIN"/>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" class="repair_button" name="VIN_search" value="Search" />
<!--                            <a href="javascript:VIN_search_form.submit();" class="fancy_button">Search</a>-->
<!--                        <a href='add_customer_for_sale.php' class="fancy_button"> Add </a> </a>-->
                    </form>
                </div>

                <!-- Display vehicle details -->
                <div class='profile_section'>
                    <div class='subtitle'>Vehicle Information</div>
                    <table>
                        <tr>
                            <td class='heading'>VIN</td>
                            <td class='heading'>Vehicle Type</td>
                            <td class='heading'>Model Year</td>
                            <td class='heading'>Model Name</td>
                            <td class='heading'>Manufacturer</td>
                            <td class='heading'>Color(s)</td>
                        </tr>
                        <?php
                        if (isset($display_vehicle)){
                                print "<tr>";
                                print "<td>{$row['VIN']}</td>";
                                print "<td>{$row['vehicle_type']}</td>";
                                print "<td>{$row['model_year']}</td>";
                                print "<td>{$row['model_name']}</td>";
                                print "<td>{$row['manufacturer']}</td>";
                                print "<td>{$row['color']}</td>";
                                print "</tr>";
                        } ?>
                    </table>
                </div>

                <!-- display repair info -->
                <div class='profile_section'>
                    <div class='subtitle'>Repair Information</div>
                    <form name="repair_form" action="view_repair.php" method="post">

                        <table>
                            <?php
                            if (isset($repair_record)){
                            $row = mysqli_fetch_array($repair_record, MYSQLI_ASSOC);
                            }
                            if ($row) {
                                print '<table>';
                                print '<tr>';
                                print '<td class="heading">VIN</td>';
                                print '<td class="heading">Start Date</td>';
                                print '<td class="heading">Odometer Readout</td>';
                                print '<td class="heading">Labor Charge</td>';
                                print '<td class="heading">Description</td>';
                                print '<td class="heading">Completion Date</td>';
                                print '</tr>';

                                while($row) {
                                    print "<tr>";
                                    print "<td>" . $row['VIN'] . "</td>";
                                    print "<td>" . $row['start_date'] . "</td>";
                                    print "<td>" . $row['odometer_readout'] . "</td>";
                                    print "<td>" . $row['labor_charge'] . "</td>";
                                    print "<td>" . $row['description'] . "</td>";
                                    if (empty($row['completion_date'])) {
                                        $VIN = urlencode($row['VIN']);
                                        // Convert date to parse
                                        $createDate = new DateTime($row['start_date']);
                                        $strip_date = $createDate->format('Y-m-d'); // strip time from datetime
                                        $start_date = urlencode($strip_date);
//                                        $start_date = urlencode($row['start_date']);
                                        print "<td><a href='update_repair.php?vin=$VIN&strat_date=$start_date'>Update</a></td>";
                                        $_SESSION['vin_from_view_repair'] = $VIN;
                                        $_SESSION['start_date_from_view_repair'] = $start_date;
                                    } else {
                                        print "<td>" . $row['completion_date'] . "</td>";
                                    }
                                    print "</tr>";
                                    $row = mysqli_fetch_array($repair_record, MYSQLI_ASSOC);
                                    }
                                }
                             ?>
                        </table>

                        <table>
                        <?php

                        // Check if any repair
                        if (empty($_POST['VIN_search']) || !isset($repair_record) || !isset($repair_completion_date_empty) || !isset($has_repair_start_today)){
                            print '<tr>';
                            print '</tr>';
                        } elseif (!is_bool($repair_record) && mysqli_num_rows($repair_record) == 0) {
                            $VIN = urlencode($VIN);
                            print '<tr>';
                            print '<td class="heading">Reminder: There is 0 repair record for this vehicle, please add new repair.</td>';
                            print "<td><a class='fancy_button' href='add_repair_search_customer.php?vin=$VIN'>Add</a></td>";
                            $_SESSION['vin_from_view_repair'];
                            print '</tr>';
                        } elseif (!is_bool($repair_completion_date_empty) && mysqli_num_rows($repair_completion_date_empty) != 0){
                            print '<tr>';
                            print '<td class="heading">Reminder: There is 1 open repair record for this vehicle, please update and complete the repair before creating new repair.</td>';
//                            print '<td><a class="fancy_button" href="update_repair.php;">Update</a></td>';
                            print '</tr>';
                        } elseif (!is_bool($has_repair_start_today) && mysqli_num_rows($has_repair_start_today) != 0){
                            print '<tr>';
                            print '<td class="heading">Reminder: There exist a completed repair record started today. A vehicle can not have more than one repair starting on the same date. Please check!</td>';
                            print '</tr>';
                        } else {
                            $VIN = urlencode($VIN);
                            print '<tr>';
                            print '<td class="heading">Reminder: There is no open repair record for this vehicle, please add.</td>';
                            print "<td><a class='fancy_button' href='add_repair_search_customer.php?vin=$VIN'>Add</a></td>";
                            $_SESSION['vin_from_view_repair'] = $VIN;
                            print '</tr>';
                        }
                        ?>
<!--                        <a href="javascript:salesform.submit();" class="fancy_button">Sell</a>-->
                        </table>
                    </form>

                </div>

            </div>
        </div>

        <?php include("lib/error.php"); ?>

        <div class="clear"></div>
    </div>

    <?php include("lib/footer.php"); ?>

</div>
</body>
</html>