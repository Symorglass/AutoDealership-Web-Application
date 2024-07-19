<?php

  include('lib/common.php');

  if (!isset($_SESSION['username'])) {
      header('Location: login.php');
      exit();
  }

?>

<?php include("lib/header.php"); ?>

<title>Average Time in Inventory Report</title>
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
                    <div class="subtitle">Average Time in Inventory Report</div>
                    

                    <?php
                      $query = "
                        WITH vehicle_type_options AS(
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
                          SELECT VIN, vehicle_type 
                          FROM vehicle_car
                          UNION
                          SELECT VIN, vehicle_type 
                          FROM vehicle_convertible
                          UNION
                          SELECT VIN, vehicle_type 
                          FROM vehicle_truck
                          UNION
                          SELECT VIN, vehicle_type 
                          FROM vehicle_van_minivan
                          UNION
                          SELECT VIN, vehicle_type 
                          FROM vehicle_suv
                        ),
                        vehicle_info AS(
                          SELECT v.VIN, vu.vehicle_type, v.added_date
                          FROM vehicle_u vu INNER JOIN Vehicle v
                          ON vu.VIN = v.VIN
                        ),
                        report AS(
                          SELECT V.vehicle_type, AVG(DATEDIFF(S.Purchase_Date, V.added_date) + 1) AS avg_days_in_inventory
                          FROM vehicle_info V INNER JOIN SalesTransaction S ON V.VIN = S.VIN
                          GROUP BY vehicle_type
                        )
                        SELECT VO.vehicle_type, IFNULL(R.avg_days_in_inventory, 'N/A') AS avg_days_in_inventory
                        FROM vehicle_type_options VO LEFT JOIN report R
                        ON VO.vehicle_type = R.vehicle_type
                        ORDER BY vehicle_type ASC
                      ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">Vehicle Type</td>';
                        print '<td class="heading">Average Days in Inventory</td>';
                        
                        print '</tr>';

                        while ($row) {


                            print '<tr>';
                            print '<td>' . $row['vehicle_type'] . '</td>';
                            print '<td>' . $row['avg_days_in_inventory'] . '</td>';                                  
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