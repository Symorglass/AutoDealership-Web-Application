<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}


?>

<?php include("lib/header.php"); ?>

<title>Sales by Type</title>
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
                    <div class="subtitle">Sales by Type Report</div>
                    

                      <?php
                        $query = "                   
                          WITH sales_with_cutoff AS(
                            SELECT 
                              S.VIN, 
                              S.Purchase_Date, 
                              (SELECT MAX(SS.Purchase_Date) FROM SalesTransaction SS) AS last_avaliable_sale_date, 
                              DATE_ADD((SELECT MAX(SSS.Purchase_Date) FROM SalesTransaction SSS),INTERVAL -30 DAY) AS month_cutoff, 
                              DATE_ADD((SELECT MAX(SSSS.Purchase_Date) FROM SalesTransaction SSSS),INTERVAL -1 YEAR) AS year_cutoff
                            FROM SalesTransaction S
                          ), 
                          vehicle_type AS(
                            SELECT 'Car' AS vehicle_type
                            UNION
                            SELECT 'Convertible' AS vehicle_type
                            UNION
                            SELECT 'Truck' AS vehicle_type
                            UNION
                            SELECT 'VAN_MiniVAN' AS vehicle_type
                            UNION
                            SELECT 'SUV' AS vehicle_type
                          ),
                          vehicle_car AS(
                            SELECT VIN, 'Car' AS vehicle_type
                            FROM Car
                          ),
                          vehicle_convertible AS(
                            SELECT VIN, 'Convertible' AS vehicle_type
                            FROM Convertible
                          ), 
                          vehicle_truck AS(
                            SELECT VIN, 'Truck' AS vehicle_type
                            FROM Truck
                          ), 
                          vehicle_van_minivan AS(
                            SELECT VIN, 'VAN_MiniVAN' AS vehicle_type
                            FROM VAN_MiniVAN
                          ), 
                          vehicle_suv AS(
                            SELECT VIN, 'SUV' AS vehicle_type
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
                          one_month_report AS (
                            SELECT 
                              V.vehicle_type, 
                              COUNT(V.VIN) AS one_month_sales_count
                            FROM sales_with_cutoff S NATURAL JOIN vehicle_u V 
                            WHERE S.Purchase_Date > S.month_cutoff
                            GROUP BY vehicle_type
                          ),
                          one_year_report AS (
                            SELECT V.vehicle_type, COUNT(V.VIN) AS one_year_sales_count
                            FROM sales_with_cutoff S NATURAL JOIN vehicle_u V 
                            WHERE S.Purchase_Date > S.year_cutoff
                            GROUP BY vehicle_type
                          ),
                          all_time_report AS (
                            SELECT V.vehicle_type, COUNT(V.VIN) AS alltime_sales_count
                            FROM sales_with_cutoff S NATURAL JOIN vehicle_u V 
                            GROUP BY vehicle_type
                          ),
                          report AS(
                            SELECT 
                              all_time_report.vehicle_type AS vehicle_type, 
                              one_month_report.one_month_sales_count, 
                              one_year_report.one_year_sales_count, 
                              all_time_report.alltime_sales_count
                            FROM 
                              one_month_report 
                              RIGHT JOIN one_year_report ON one_month_report.vehicle_type = one_year_report.vehicle_type
                              RIGHT JOIN all_time_report ON one_year_report.vehicle_type = all_time_report.vehicle_type
                          )
                          SELECT 
                            VT.vehicle_type, 
                            IFNULL(R.one_month_sales_count, 0) AS one_month_sales_count, 
                            IFNULL(R.one_year_sales_count, 0) AS one_year_sales_count, 
                            IFNULL(R.alltime_sales_count,0) AS alltime_sales_count
                          FROM 
                            report R RIGHT JOIN vehicle_type VT ON R.vehicle_type = VT.vehicle_type
                          ORDER BY R.vehicle_type ASC
                        ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">Vehicle Type</td>';
                        print '<td class="heading">One Month Sales Count</td>';
                        print '<td class="heading">One Year Sales Count</td>';
                        print '<td class="heading">All-time Sales Count</td>';
                        print '</tr>';

                        while ($row) {


                            print '<tr>';
                            print '<td>' . $row['vehicle_type'] . '</td>';
                            print '<td>' . $row['one_month_sales_count'] . '</td>';
                            print '<td>' . $row['one_year_sales_count'] . '</td>';
                            print '<td>' . $row['alltime_sales_count'] . '</td>';               
                            print '</tr>';

                           $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                        }
                        print '</table>';
                    } else {
                        print "<br/>None!";
                    }
                    ?>

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