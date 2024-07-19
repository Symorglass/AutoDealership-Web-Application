<?php

    include('lib/common.php');

    if (!isset($_SESSION['username'])) {
        header('Location: login.php');
        exit();
    }


?>

<?php include("lib/header.php"); ?>

<title>Monthly Sales Report</title>
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
                    <div class="subtitle">Monthly Sales Report Summary</div>

                    <?php
                    $query = "
                        SELECT 
                            YEAR(S.Purchase_Date) AS Year, 
                            MONTH(S.Purchase_Date) AS Month, 
                            COUNT(S.VIN) AS total_number_sold, 
                            SUM(S.sold_price) AS income, 
                            (SUM(S.sold_price) - SUM(V.invoice_price)) AS net_income, 
                            CONCAT((SUM(S.sold_price) / SUM(V.invoice_price)*100),'%') AS ratio
                        FROM SalesTransaction S INNER JOIN Vehicle V ON S.VIN = V.VIN
                        GROUP BY YEAR(S.Purchase_Date), MONTH(S.Purchase_Date)
                        ORDER BY YEAR(S.Purchase_Date) DESC, MONTH(S.Purchase_Date) DESC
                    ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">Year</td>';
                        print '<td class="heading">Month</td>';
                        print '<td class="heading">Total Sold Count</td>';
                        print '<td class="heading">Income</td>';
                        print '<td class="heading">Net Income</td>';
                        print '<td class="heading">Ratio</td>';
                        print '<td class="heading">Drill-down report</td>';
                        print '</tr>';

                        while ($row) {
                            $year = urlencode($row['Year']);
                            $month = urlencode($row['Month']);
                            $ratio_string = urlencode($row['ratio']);
                            $ratio_decimal = str_replace('%', '', $ratio_string) / 100.00;;

                            if ($ratio_decimal >= 1.25) {
                                print '<tr style="background-color:green">';
                            } else if ($ratio_decimal <= 1.1) {
                                print '<tr style="background-color:yellow">';

                            } else {
                                print '<tr>';
                            }
                            print '<td>' . $row['Year'] . '</td>';
                            print '<td>' . $row['Month'] . '</td>';
                            print '<td>' . $row['total_number_sold'] . '</td>';
                            print '<td>' . $row['income'] . '</td>';
                            print '<td>' . $row['net_income'] . '</td>';
                            print '<td>' . $row['ratio'] . '</td>';


                            print "<td><a href='report_monthly_sales_drill_down.php?year=$year&month=$month'>Drill-Down report</a></td>";

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