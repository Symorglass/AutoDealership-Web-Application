<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}


?>

<?php include("lib/header.php"); ?>

<title>Sales by Color</title>
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
                    <div class="subtitle">Sales by Color Report</div>
                    

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
                            single_color_vehicle AS (
                                SELECT V.VIN, VC.color AS color
                                FROM Vehicle V NATURAL JOIN Vehicle_Color VC
                                GROUP BY V.VIN
                                HAVING COUNT(VC.VIN) = 1
                            ),
                            multi_color_vehicle AS (
                                SELECT V.VIN, VC.color AS color
                                FROM Vehicle V NATURAL JOIN Vehicle_Color VC
                                GROUP BY V.VIN
                                HAVING NOT COUNT(V.VIN) = 1
                            ),
                            vehicles_by_color AS (
                                SELECT MV.VIN, 'multiple' as color
                                FROM multi_color_vehicle MV
                                UNION
                                SELECT SV.VIN, SV.color
                                FROM single_color_vehicle SV
                            ),
                            one_month_report AS (
                                SELECT VBC.color, IFNULL(COUNT(VBC.VIN), 0) AS one_month_sales_count
                                FROM vehicles_by_color VBC NATURAL JOIN sales_with_cutoff S
                                WHERE S.Purchase_Date > S.month_cutoff
                                GROUP BY VBC.color
                            ),
                            one_year_report AS (
                                SELECT VBC.color, COUNT(VBC.VIN) AS one_year_sales_count
                                FROM vehicles_by_color VBC NATURAL JOIN sales_with_cutoff S
                                WHERE S.Purchase_Date > S.year_cutoff
                                GROUP BY VBC.color
                            ),
                            all_time_report AS (
                                SELECT VBC.color, COUNT(VBC.VIN) AS alltime_sales_count
                                FROM vehicles_by_color VBC NATURAL JOIN sales_with_cutoff S
                                GROUP BY VBC.color
                            ),
                            report AS (
                                SELECT 
                                    all_time_report.color AS Color, 
                                    IFNULL(one_month_report.one_month_sales_count, 0) AS one_month_sales_count, 
                                    IFNULL(one_year_report.one_year_sales_count, 0) AS one_year_sales_count, 
                                    IFNULL(all_time_report.alltime_sales_count, 0) AS alltime_sales_count
                                FROM 
                                    one_month_report
                                    RIGHT JOIN one_year_report ON one_month_report.color = one_year_report.color
                                    RIGHT JOIN all_time_report ON one_year_report.color = all_time_report.color
                            ),
                            all_color_options AS(
                                SELECT Color
                                FROM report
                                UNION
                                SELECT color AS Color
                                FROM Color
                            )
                            SELECT 
                                A.Color, 
                                IFNULL(one_month_sales_count, 0) AS one_month_sales_count , 
                                IFNULL(one_year_sales_count, 0) AS one_year_sales_count, 
                                IFNULL(alltime_sales_count, 0) AS alltime_sales_count
                            FROM 
                                report R
                                RIGHT JOIN all_color_options A ON R.Color = A.Color
                            ORDER BY Color ASC
                            ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">Color</td>';
                        print '<td class="heading">One Month Sales Count</td>';
                        print '<td class="heading">One Year Sales Count</td>';
                        print '<td class="heading">All-time Sales Count</td>';
                        print '</tr>';

                        while ($row) {


                            print '<tr>';
                            print '<td>' . $row['Color'] . '</td>';
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