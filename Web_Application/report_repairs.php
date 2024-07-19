<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}


?>

<?php include("lib/header.php"); ?>

<title>Repairs by Manufacturer/Type/Model Reports</title>
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
                    <div class="subtitle">Repairs by Manufacturer/Type/Model Report</div>
                    

                    <?php
                        $query = "
                        WITH repair_records AS (
                            SELECT R.VIN AS VIN, R.start_date AS start_date, labor_cost, part_cost, COALESCE(labor_cost + part_cost, labor_cost, part_cost, 0) AS total_cost
                            FROM (
                                (
                                    SELECT VIN, start_date, labor_charge AS labor_cost 
                                    FROM Repair
                                ) AS R
                                LEFT JOIN 
                                (
                                    SELECT VIN, start_date, sum(price * quantity) AS part_cost
                                    FROM Part GROUP BY VIN, start_date
                                ) AS P ON R.VIN = P.VIN AND R.start_date = P.start_date
                            )
                        ),  
                        report AS (
                            SELECT
                                V.manufacturer as manufacturer,
                                COUNT( DISTINCT R.VIN, R.start_date) AS repair_count,
                                SUM(R.labor_cost) AS total_labor_cost,
                                SUM(R.total_cost) AS total_repair_cost,
                                SUM(R.part_cost) AS total_part_cost
                            FROM (Vehicle AS V LEFT JOIN repair_records AS R ON V.VIN = R.VIN)
                            GROUP BY V.manufacturer
                        )
                        SELECT 
                            m.manufacturer,
                            IFNULL(r.repair_count,0) AS repair_count,
                            IFNULL(r.total_labor_cost,0) AS total_labor_cost,
                            IFNULL(r.total_part_cost,0) AS total_part_cost,
                            IFNULL(r.total_repair_cost,0) AS total_repair_cost
                        FROM report r RIGHT JOIN Manufacturer m ON r.manufacturer = m.manufacturer
                        ORDER BY m.manufacturer ASC
                        ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">Manufacturer</td>';
                        print '<td class="heading">Repair Count</td>';
                        print '<td class="heading">Total Labor Cost</td>';
                        print '<td class="heading">Total Part Cost</td>';
                        print '<td class="heading">Total Repair Cost</td>';
                        print '<td class="heading"></td>';
                        print '</tr>';

                        while ($row) {
                            $manufacturer = urlencode($row['manufacturer']);

                            print '<tr>';
                            print '<td>' . $row['manufacturer'] . '</td>';
                            print '<td>' . $row['repair_count'] . '</td>';
                            print '<td>' . $row['total_labor_cost'] . '</td>';
                            print '<td>' . $row['total_part_cost'] . '</td>'; 
                            print '<td>' . $row['total_repair_cost'] . '</td>';    
                            print "<td><input type='button' name='detail$row[manufacturer]' value='More Details' onclick= \"location.href = 'report_repairs_drilldown.php?manufacturer=$manufacturer'\"/></td>";

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