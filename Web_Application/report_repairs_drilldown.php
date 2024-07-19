<?php

  include('lib/common.php');

  if (!isset($_SESSION['username'])) {
      header('Location: login.php');
      exit();
  }

  $manufacturer = mysqli_real_escape_string($db, $_REQUEST['manufacturer']);

?>

<?php include("lib/header.php"); ?>

<?php
  $title = "Repairs from $manufacturer";
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
                      vehicle_info AS (
                        SELECT v.VIN, vu.vehicle_type, v.manufacturer, v.model_name
                        FROM vehicle_u vu INNER JOIN Vehicle v ON vu.VIN = v.VIN
                      ),
                      Part_sum AS (
                        SELECT VIN, start_date, sum(p.quantity * p.price) AS part_cost
                        FROM Part p
                        GROUP BY VIN, start_date
                      ),
                      repair_records AS (
                        SELECT 
                          r.VIN, r.start_date,
                          IFNULL(r.labor_charge,0) AS labor_charge,
                          IFNULL(p.part_cost,0) parts_cost,
                          IFNULL(r.labor_charge,0) + IFNULL(p.part_cost,0) AS total_cost
                        FROM Repair r LEFT OUTER JOIN Part_sum p ON p.VIN = r.VIN and p.start_date = r.start_date
                      ),
                      manufacturer_vehicle_repair_records AS (
                        SELECT 
                          r.VIN, v.vehicle_type, v.manufacturer, v.model_name,
                          r.start_date, r.labor_charge, r.parts_cost, r.total_cost
                        FROM repair_records r LEFT OUTER JOIN vehicle_info v ON r.VIN = v.VIN
                        WHERE v.manufacturer = '$manufacturer'
                      ),
                      repair_records_by_vehicle_type AS (
                        SELECT 
                          NULL AS category,
                          rr.vehicle_type AS sub_name_for_ordering,
                          COUNT(VIN) AS repair_count_for_vehicle_type,
                          NULL AS repair_count_for_model_name,
                          SUM(rr.labor_charge) AS total_labor_costs,
                          SUM(rr.parts_cost) AS total_parts_costs,
                          SUM(rr.total_cost) AS total_costs
                        FROM manufacturer_vehicle_repair_records rr
                        GROUP BY rr.vehicle_type
                        ORDER BY rr.vehicle_type ASC
                      ),
                      repair_records_by_model_name AS (
                        SELECT 
                          rr.model_name AS category,
                          rr.vehicle_type AS sub_name_for_ordering,
                          NULL AS repair_count_for_vehicle_type,
                          COUNT(VIN) AS repair_count_for_model_name,
                          SUM(rr.labor_charge) AS total_labor_costs,
                          SUM(rr.parts_cost) AS total_parts_costs,
                          SUM(rr.total_cost) AS total_costs
                        FROM manufacturer_vehicle_repair_records rr
                        GROUP BY CONCAT(rr.vehicle_type, rr.model_name)
                        ORDER BY COUNT(rr.VIN) DESC
                      ),
                      repair_records_by_model_name_with_type_count AS (
                        SELECT 
                          rrmn.category, 
                          rrmn.sub_name_for_ordering, 
                          rrvt.repair_count_for_vehicle_type, 
                          rrmn.repair_count_for_model_name,
                          rrmn.total_labor_costs, 
                          rrmn.total_parts_costs, 
                          rrmn.total_costs
                        FROM 
                          repair_records_by_model_name rrmn 
                          INNER JOIN repair_records_by_vehicle_type rrvt ON rrmn.sub_name_for_ordering = rrvt.sub_name_for_ordering
                      ),
                      repair_union AS (
                        SELECT 
                          category,
                          sub_name_for_ordering, 
                          repair_count_for_vehicle_type, 
                          repair_count_for_model_name, 
                          total_labor_costs,
                          total_parts_costs,
                          total_costs
                        FROM repair_records_by_vehicle_type
                        UNION
                        SELECT 
                          category,
                          sub_name_for_ordering, 
                          repair_count_for_vehicle_type, 
                          repair_count_for_model_name, 
                          total_labor_costs,
                          total_parts_costs,
                          total_costs
                        FROM repair_records_by_model_name_with_type_count
                      )
                      SELECT 
                        sub_name_for_ordering, 
                        repair_count_for_vehicle_type, 
                        repair_count_for_model_name,
                        category, 
                        COALESCE(repair_count_for_model_name, 
                        repair_count_for_vehicle_type) AS repair_count, 
                        total_labor_costs,
                        total_parts_costs,
                        total_costs
                      FROM repair_union
                      ORDER BY repair_count_for_vehicle_type DESC, sub_name_for_ordering ASC, repair_count DESC, category ASC
                      ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">Vehicle Type</td>';
                        print '<td class="heading">Vehicle Model</td>';
                        print '<td class="heading">Repair Count</td>';
                        print '<td class="heading">Total Labor Cost</td>';
                        print '<td class="heading">Total Part Cost</td>';
                        print '<td class="heading">Total Repair Cost</td>';
                        print '</tr>';

                        while ($row) {


                            print '<tr>';
                            print '<td>' . $row['sub_name_for_ordering'] . '</td>';
                            print '<td>' . $row['category'] . '</td>';
                            print '<td>' . $row['repair_count'] . '</td>';
                            print '<td>' . $row['total_labor_costs'] . '</td>';
                            print '<td>' . $row['total_parts_costs'] . '</td>';
                            print '<td>' . $row['total_costs'] . '</td>';
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
                                  onclick="location.href = 'report_repairs.php'"/></p></div>
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