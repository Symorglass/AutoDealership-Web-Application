<?php

include('lib/common.php');

$user_role = $_SESSION['role_type'];
$VIN = $_GET['vin'];

$query = "
    WITH vehicle_car AS(
        SELECT VIN, 'Car' AS vehicle_type, number_of_doors AS property1, '' AS property2, '' AS property3
        FROM Car
    ),
    vehicle_convertible AS(
        SELECT VIN, 'Convertible' AS vehicle_type, back_seat_count AS property1, 0 AS property2, roof_type AS property3 
        FROM Convertible
    ),
    vehicle_truck AS(
        SELECT VIN, 'Truck' AS vehicle_type, number_of_rear_axles AS property1, cargo_capacity AS property2, cargo_cover_type AS property3
        FROM Truck
    ),
    vehicle_van_minivan AS(
        SELECT VIN, 'VAN_MiniVAN' AS vehicle_type, driver_side_backdoor AS property1, 0 AS property2, '' AS property3 
        FROM VAN_MiniVAN
    ),
    vehicle_suv AS(
        SELECT VIN, 'SUV' AS vehicle_type, number_of_cupholders AS property1, 0 AS property2, drivetrain_type AS property3
        FROM SUV
    ),
    vehicle_u AS (
        SELECT VIN, vehicle_type, property1, property2, property3 FROM vehicle_car
        UNION
        SELECT VIN, vehicle_type, property1, property2, property3 FROM vehicle_convertible 
        UNION
        SELECT VIN, vehicle_type, property1, property2, property3 FROM vehicle_truck
        UNION
        SELECT VIN, vehicle_type, property1, property2, property3 FROM vehicle_van_minivan 
        UNION
        SELECT VIN, vehicle_type, property1, property2, property3 FROM vehicle_suv
    ),
    vehicle_raw_info AS(
        SELECT v.VIN, vu.vehicle_type, v.model_year, v.model_name, v.manufacturer, GROUP_CONCAT(vc.color) AS color, v.invoice_price * 1.25 AS list_price, v.description, vu.property1, vu.property2, vu.property3, v.invoice_price, v.added_date, v.userName
        FROM vehicle_u vu INNER JOIN Vehicle v INNER JOIN Vehicle_Color vc ON vu.VIN = v.VIN AND vu.VIN = vc.VIN
        GROUP BY v.VIN
    ),
    vehicle_formatted_info AS(
        SELECT v.VIN, v.vehicle_type, v.model_year, v.model_name, v.manufacturer, v.color, v.list_price, v.description, v.property1, v.property2, v.property3, v.invoice_price, v.added_date, 
        IF(vehicle_type = 'Car', v.property1,NULL) AS number_of_doors, 
        IF(vehicle_type = 'Convertible', v.property1, NULL) AS back_seat_count, 
        IF(vehicle_type = 'Convertible', v.property3, NULL) AS roof_type, 
        IF(vehicle_type = 'Truck', v.property1, NULL) AS number_of_rear_axles, 
        IF(vehicle_type = 'Truck' , v.property2, NULL) AS cargo_capacity,
        IF(vehicle_type = 'Truck' , v.property3, NULL) AS cargo_cover_type, 
        IF(vehicle_type = 'VAN_MiniVAN' , v.property1, NULL) AS driver_side_backdoor, 
        IF(vehicle_type = 'SUV' , v.property1, NULL) AS number_of_cupholders, 
        IF(vehicle_type = 'SUV' , v.property1, NULL) AS drivetrain_type
        FROM vehicle_raw_info v
    )
";

if ($user_role == "Manager" || $user_role == "Owner") {
    $query = $query . "
    ,
    result AS(
    SELECT v.VIN, v.vehicle_type, v.model_year, v.model_name, v.manufacturer, v.color, v.list_price, v.invoice_price, v.added_date, 
    IFNULL(v.description,'') AS description, 
    IFNULL(v.number_of_doors, 'N/A') AS number_of_doors, 
    IFNULL(v.back_seat_count, 'N/A') AS back_seat_count, 
    IFNULL(v.roof_type, 'N/A') AS roof_type, 
    IFNULL(v.number_of_rear_axles, 'N/A') AS number_of_rear_axles, 
    IFNULL(v.cargo_capacity, 'N/A') AS cargo_capacity, 
    IFNULL(v.cargo_cover_type, 'N/A') AS cargo_cover_type, 
    IFNULL(v.driver_side_backdoor, 'N/A') AS driver_side_backdoor, 
    IFNULL(v.number_of_cupholders, 'N/A') AS number_of_cupholders, 
    IFNULL(v. drivetrain_type, 'N/A') AS drivetrain_type
    FROM vehicle_formatted_info v
    WHERE v.VIN = '{$VIN}'
    ),

    inventory_clerk_info AS(
    SELECT v.VIN, u.first_name, u.last_name
    FROM vehicle_raw_info v INNER JOIN LoggedInUser u 
    WHERE v.userName = u.userName
    ),

    result_inventory_clerk AS( 
    SELECT i.first_name AS inventory_clerk_first_name, i.last_name AS inventory_clerk_last_name, r1.VIN, r1.vehicle_type, r1.model_year, r1.model_name, r1.manufacturer, r1.color, r1.list_price, r1.invoice_price, r1.added_date, r1.description, r1.number_of_doors, r1.back_seat_count, r1.roof_type, r1.number_of_rear_axles, r1.cargo_capacity, r1.cargo_cover_type, r1.driver_side_backdoor, r1.number_of_cupholders, r1.drivetrain_type
    FROM result r1 INNER JOIN inventory_clerk_info i ON r1.VIN = i.VIN
    ),

    sales_info AS(
    SELECT s.VIN, u.first_name, u.last_name, s.Purchase_Date as sale_date, s.sold_price, s.customerID
    FROM SalesTransaction s INNER JOIN vehicle_formatted_info v INNER JOIN LoggedInUser u
    ON s.VIN = v.VIN AND s.userName = u.userName
    ),

    result_inventory_clerk_sales AS(
    SELECT s.first_name AS sales_person_first_name, s.last_name AS sales_person_last_name, s.sale_date, s.sold_price, s.customerID, r2.inventory_clerk_first_name, r2.inventory_clerk_last_name, r2.VIN, r2.vehicle_type, r2.model_year, r2.model_name, r2.manufacturer, r2.color, r2.list_price, r2.invoice_price, r2.added_date, r2.description, r2.number_of_doors, r2.back_seat_count, r2.roof_type, r2.number_of_rear_axles, r2.cargo_capacity, r2.cargo_cover_type, r2.driver_side_backdoor, r2.number_of_cupholders, r2.drivetrain_type
    FROM result_inventory_clerk r2 LEFT JOIN sales_info s
    ON r2.VIN = s.VIN
    ),

    customer_i AS(
    SELECT c.customerID, concat(i.first_name, ' ', i.last_name) AS Name, '' AS primary_contact_name, '' AS primary_contact_title, IFNULL(c.email, '') AS email, c.phone_number, c.street_address, c.city, c.state, c.postal_code
    FROM Customer c INNER JOIN Individual i ON c.customerID = i.customerID
    ),

    customer_b AS(
    SELECT c.customerID, b.business_name AS Name, b.primary_contact_name, b.primary_contact_title, IFNULL(c.email, '') AS email, c.phone_number, c.street_address, c.city, c.state, c.postal_code, b.tax_identification_number
    FROM Customer c INNER JOIN Business b
    ON c.customerID = b.customerID
    ),

    customer_u AS(
    SELECT CI.customerID, CI.Name, CI.primary_contact_name, CI.primary_contact_title, CI.email, CI.phone_number, CI.street_address, CI.city, CI.state, CI.postal_code, '' AS tax_identification_number
    FROM customer_i CI
    UNION
    SELECT CB.customerID, CB.Name, CB.primary_contact_name, CB.primary_contact_title, CB.email, CB.phone_number, CB.street_address, CB.city, CB.state, CB.postal_code, CB.tax_identification_number
    FROM customer_b CB
    )

    ";

    $query = $query . "
    SELECT r3.sales_person_first_name, r3.sales_person_last_name, r3.sale_date, r3.sold_price, r3.customerID, r3.inventory_clerk_first_name, r3.inventory_clerk_last_name, r3.VIN, r3.vehicle_type, r3.model_year, r3.model_name, r3.manufacturer, r3.color, r3.list_price, r3.invoice_price, r3.added_date, c.Name, c.primary_contact_name, c.primary_contact_title, c.email, c.phone_number, c.street_address, c.city, c.state, c.postal_code, c.tax_identification_number,
    IFNULL(r3.description,'') AS description,
    IFNULL(r3.number_of_doors, 'N/A') AS number_of_doors, 
    IFNULL(r3.back_seat_count, 'N/A') AS back_seat_count,
    IFNULL(r3.roof_type, 'N/A') AS roof_type, 
    IFNULL(r3.number_of_rear_axles, 'N/A') AS number_of_rear_axles, 
    IFNULL(r3.cargo_capacity, 'N/A') AS cargo_capacity, 
    IFNULL(r3.cargo_cover_type, 'N/A') AS cargo_cover_type, 
    IFNULL(r3.driver_side_backdoor, 'N/A') AS driver_side_backdoor, 
    IFNULL(r3.number_of_cupholders, 'N/A') AS number_of_cupholders, 
    IFNULL(r3.drivetrain_type, 'N/A') AS drivetrain_type
    FROM result_inventory_clerk_sales r3 LEFT JOIN customer_u c 
    ON r3.customerID = c.customerID
    ";
} else {
    $query = $query . "
    SELECT v.VIN, v.vehicle_type, v.model_year, v.model_name, v.manufacturer, v.color, v.list_price, v.invoice_price, v.added_date,
    IFNULL(v.description,'') AS description,
    IFNULL(v.number_of_doors, 'N/A') AS number_of_doors, 
    IFNULL(v.back_seat_count, 'N/A') AS back_seat_count,
    IFNULL(v.roof_type, 'N/A') AS roof_type, 
    IFNULL(v.number_of_rear_axles, 'N/A') AS number_of_rear_axles, 
    IFNULL(v.cargo_capacity, 'N/A') AS cargo_capacity, 
    IFNULL(v.cargo_cover_type, 'N/A') AS cargo_cover_type, 
    IFNULL(v.driver_side_backdoor, 'N/A') AS driver_side_backdoor, 
    IFNULL(v.number_of_cupholders, 'N/A') AS number_of_cupholders, 
    IFNULL(v.drivetrain_type, 'N/A') AS drivetrain_type
    FROM vehicle_formatted_info v WHERE VIN = '{$VIN}' ";
}

$result = mysqli_query($db, $query);
include('lib/show_queries.php');

$result_count = mysqli_num_rows($result);
if ($result_count == 0) {
    array_push($error_msg, "Sorry, it looks like we don’t have that in stock!" . NEWLINE);
}

$reqair_query = "
    WITH repair_info AS(
        SELECT
            r.VIN,
            r.start_date,
            r.completion_date,
            r.labor_charge,
            r.userName,
            IF(
                p.price = NULL OR p.quantity = NULL,
                0,
                SUM(p.price * p.quantity)
            ) AS part_cost
        FROM
    REPAIR
        r
    LEFT JOIN Part p ON
        r.VIN = p.VIN AND r.start_date = p.start_date
    GROUP BY
        r.VIN,
        r.start_date
    ),
    repair_report AS(
        SELECT
            r2.VIN,
            r2.start_date,
            r2.userName,
            IFNULL(r2.completion_date, 'In progress') AS end_date,
            IFNULL(r2.labor_charge, 0) AS labor_charges,
            IFNULL(r2.part_cost, 0) AS parts_cost,
            (
                IFNULL(r2.labor_charge, 0) + IFNULL(r2.part_cost, 0)
            ) AS total_cost
        FROM
            repair_info r2
        WHERE
            VIN = '{$VIN}'
    ),
    service_writer_info AS(
        SELECT
            r.VIN,
            r.start_date,
            u.first_name,
            u.last_name,
            u.userName,
            r.customerID
        FROM
            LoggedInUser u
        INNER JOIN REPAIR r ON
        u.userName = r.userName
    ),
    repair_report_with_service_writer AS(
        SELECT
            r3.VIN,
            r3.start_date,
            r3.userName,
            r3.end_date,
            r3.labor_charges,
            r3.parts_cost,
            r3.total_cost,
            s.first_name,
            s.last_name,
            s.customerID
        FROM
            repair_report r3
        LEFT JOIN service_writer_info s ON
            r3.userName = s.userName AND r3.VIN = s.VIN AND s.start_date = r3.start_date
    ),
    customer_i AS(
        SELECT
            c.customerID,
            CONCAT(i.first_name,' ', i.last_name) AS NAME
        FROM
            Customer c
        INNER JOIN Individual i ON
            c.customerID = i.customerID
    ),
    customer_b AS(
        SELECT
            c.customerID,
            b.business_name AS NAME
        FROM
            Customer c
        INNER JOIN Business b ON
            c.customerID = b.customerID
    ),
    customer_u AS(
        SELECT
            CI.customerID,
            CI.Name
        FROM
            customer_i CI
        UNION
    SELECT
        CB.customerID,
        CB.Name
    FROM
        customer_b CB
    )

    SELECT
        r4.VIN,
        r4.start_date,
        r4.end_date,
        r4.labor_charges,
        r4.parts_cost,
        r4.total_cost,
        r4.first_name AS service_writer_first_name,
        r4.last_name AS service_writer_last_name,
        cu.Name AS customer_name
    FROM
        repair_report_with_service_writer r4
    INNER JOIN customer_u cu ON
        r4.customerID = cu.customerID
";

$repair_result = mysqli_query($db, $reqair_query);
include('lib/show_queries.php');

?>

<?php include("lib/header.php"); ?>
<title>Vehicle Details</title>
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
                    <div class="subtitle">View Vehicle Details (General)</div>
                    <table>
                        <tr>
                            <td class='heading'>VIN</td>
                            <td class='heading'>Vehicle Type</td>
                            <td class='heading'>Model Year</td>
                            <td class='heading'>Model Name</td>
                            <td class='heading'>Manufacturer</td>
                            <td class='heading'>Color(s)</td>
                            <td class='heading'>Description</td>
                            <td class='heading'>List Price</td>

                            <?php
                            if ($user_role == "InventoryClerk") {
                                print "<td class='heading'>Invoice Price</td>";
                            }

                            if ($user_role == "Manager" || $user_role == "Owner") {
                                print "<td class='heading'>Invoice Price</td>";
                                print "<td class='heading'>Added Date</td>";
                                print "<td class='heading'>Inventory Clerk's First Name</td>";
                                print "<td class='heading'>Inventory Clerk's Last Name</td>";
                            }
                            ?>
                        </tr>

                        <?php
                        if (isset($result)) {
                            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                print "<tr>";
                                print "<td>{$row['VIN']}</td>";
                                print "<td>{$row['vehicle_type']}</td>";
                                print "<td>{$row['model_year']}</td>";
                                print "<td>{$row['model_name']}</td>";
                                print "<td>{$row['manufacturer']}</td>";
                                print "<td>{$row['color']}</td>";
                                print "<td>{$row['description']}</td>";
                                print "<td>{$row['list_price']}</td>";
                                if ($user_role == "InventoryClerk") {
                                    print "<td>{$row['invoice_price']}</td>";
                                }
                                if ($user_role == "Manager" || $user_role == "Owner") {
                                    print "<td>{$row['invoice_price']}</td>";
                                    print "<td>{$row['added_date']}</td>";
                                    print "<td>{$row['inventory_clerk_first_name']}</td>";
                                    print "<td>{$row['inventory_clerk_last_name']}</td>";
                                }
                                print "</tr>";
                            }
                        } ?>
                    </table>

                    <?php
                    if ($user_role == "Salesperson" || $user_role == "Owner") {
                        print "<td><a href='add_sale.php?vin=$VIN' class='fancy_button'>Sell</a></td>";
                    }
                    $_SESSION['vin_for_sale'] = $VIN;

                    ?>
                </div>

                <div class="profile_section">
                    <div class="subtitle">View Vehicle Details (Type Specific)</div>
                    <table>
                        <tr>
                            <td class='heading'>Number of Doors</td>
                            <td class='heading'>Roof Type</td>
                            <td class='heading'>Back Seat Count</td>
                            <td class='heading'>Cargo Capacity</td>
                            <td class='heading'>Cargo Cover Type</td>
                            <td class='heading'>Number of Rear Axis</td>
                            <td class='heading'>Driver Side Door</td>
                            <td class='heading'>Drivetrain Type</td>
                            <td class='heading'>Number of Cupholders</td>
                        </tr>

                        <?php

                        $specific_details_result = mysqli_query($db, $query);

                        if (isset($specific_details_result)) {
                            while ($row = mysqli_fetch_array($specific_details_result, MYSQLI_ASSOC)) {
                                $driver_side_backdoor_for_display = $row['driver_side_backdoor'];
                                if (isset($driver_side_backdoor_for_display)) {
                                    if ($driver_side_backdoor_for_display == 1) {
                                        $driver_side_backdoor_for_display = "True";
                                    } else if ($driver_side_backdoor_for_display == 0) {
                                        $driver_side_backdoor_for_display = "False";
                                    } else {
                                        $driver_side_backdoor_for_display = "N/A";
                                    }
                                }


                                print "<tr>";
                                print "<td>{$row['number_of_doors']}</td>";
                                print "<td>{$row['roof_type']}</td>";
                                print "<td>{$row['back_seat_count']}</td>";
                                print "<td>{$row['cargo_capacity']}</td>";
                                print "<td>{$row['cargo_cover_type']}</td>";
                                print "<td>{$row['number_of_rear_axles']}</td>";
                                print "<td>$driver_side_backdoor_for_display</td>";
                                print "<td>{$row['drivetrain_type']}</td>";
                                print "<td>{$row['number_of_cupholders']}</td>";
                                print "</tr>";
                            }
                        } ?>
                    </table>
                </div>

                <div class='profile_section' <?php if ($user_role != "Manager" && $user_role != "Owner") print "hidden"; ?>>
                    <div class='subtitle'>Sale Transaction Information</div>
                    <table>
                        <tr>

                            <td class='heading'>Sold Price</td>
                            <td class='heading'>Purchase Date</td>
                            <td class='heading'>Salesperson's First Name</td>
                            <td class='heading'>Salesperson's Last Name</td>
                            <td class='heading'>Customer Name</td>
                            <td class='heading'>Primary Contact Name</td>
                            <td class='heading'>Primary Contact Title</td>
                            <td class='heading'>Phone Number</td>
                            <td class='heading'>Email</td>
                            <td class='heading'>Address</td>
                        </tr>

                        <?php
                        $transaction_query = "SELECT count(*) as count FROM Salestransaction WHERE VIN='$VIN'";

                        $transaction_result = mysqli_query($db, $transaction_query);
                        include('lib/show_queries.php');

                        if (isset($transaction_result)) {
                            $count = mysqli_fetch_array($transaction_result, MYSQLI_ASSOC);
                            if ($count['count'] != '0') {
                                $result = mysqli_query($db, $query);
                                include('lib/show_queries.php');

                                if (isset($result)) {
                                    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                        print "<tr>";
                                        print "<td>{$row['sold_price']}</td>";
                                        print "<td>{$row['sale_date']}</td>";
                                        print "<td>{$row['sales_person_first_name']}</td>";
                                        print "<td>{$row['sales_person_last_name']}</td>";
                                        print "<td>{$row['Name']}</td>";
                                        print "<td>{$row['primary_contact_name']}</td>";
                                        print "<td>{$row['primary_contact_title']}</td>";
                                        print "<td>{$row['phone_number']}</td>";
                                        print "<td>{$row['email']}</td>";
                                        if (!empty($row['street_address']) || !empty($row['city']) || !empty($row['state']) || !empty($row['postal_code'])) {
                                            print "<td>{$row['street_address']}, {$row['city']}, {$row['state']}, {$row['postal_code']}</td>";
                                        }
                                        print "</tr>";
                                    }
                                }
                            }
                        }
                        ?>
                    </table>
                </div>

                <div class='profile_section' <?php if ($user_role != "Manager" && $user_role != "Owner") print "hidden"; ?>>
                    <div class='subtitle'>Repairs</div>
                    <table>
                        <tr>
                            <td class='heading'>Start Date</td>
                            <td class='heading'>End Date</td>
                            <td class='heading'>Labor Charges</td>
                            <td class='heading'>Part Cost</td>
                            <td class='heading'>Total Cost</td>
                            <td class='heading'>Service Writer First Name</td>
                            <td class='heading'>Service Writer Last Name</td>
                            <td class='heading'>Customer Name</td>
                        </tr>
                        <?php
                        if (isset($repair_result)) {
                            while ($row = mysqli_fetch_array($repair_result, MYSQLI_ASSOC)) {
                                print "<tr>";

                                print "<td>{$row['start_date']}</td>";
                                print "<td>{$row['end_date']}</td>";
                                print "<td>{$row['labor_charges']}</td>";
                                print "<td>{$row['parts_cost']}</td>";
                                print "<td>{$row['total_cost']}</td>";
                                print "<td>{$row['service_writer_first_name']}</td>";
                                print "<td>{$row['service_writer_last_name']}</td>";
                                print "<td>{$row['customer_name']}</td>";

                                print "</tr>";
                            }
                        } ?>
                    </table>
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