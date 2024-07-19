<?php

  include('lib/common.php');

  if (!isset($_SESSION['username'])) {
      header('Location: login.php');
      exit();
  }

?>

<?php include("lib/header.php"); ?>

<title>Gross Cumstomer Income Report</title>
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
                    <div class="subtitle">Gross Customer Income Report</div>

                    <?php
                    $query = "
                      WITH customer_i AS (
                        SELECT c.customerID, concat(i.first_name, ' ', i.last_name) AS Name
                        FROM Customer c LEFT OUTER JOIN Individual i ON c.customerID = i.customerID
                      ),
                      customer_b AS (
                        SELECT c.customerID, b.business_name AS Name
                        FROM Customer c LEFT OUTER JOIN Business b ON c.customerID = b.customerID
                      ),
                      customer_u AS (
                        SELECT COALESCE (CI.Name, CB.Name) AS Name, CI.customerID 
                        FROM customer_i CI LEFT OUTER JOIN customer_b CB ON CI.customerID = CB.customerID
                      ),
                      Vehicle_sale AS (
                        SELECT v.VIN, s.customerID, s.Purchase_Date, s.sold_price AS single_sale_income
                        From Vehicle v INNER JOIN SalesTransaction s ON v.VIN = s.VIN
                      ),
                      Part_sum AS (
                        SELECT VIN, start_date, sum(p.quantity * p.price) AS part_cost
                        FROM Part p
                        GROUP BY VIN, start_date
                      ),
                      Vehicle_repair AS (
                        SELECT 
                          r.VIN, r.customerID, r.start_date,
                          IFNULL(r.labor_charge, 0) AS labor_charge,
                          IFNULL(r.labor_charge, 0) + ps.part_cost AS single_repair_income
                        FROM Repair r INNER JOIN Part_sum ps ON ps.VIN = r.VIN and ps.start_date = r.start_date
                      ),
                      Customer_sale AS ( 
                        SELECT 
                          customerID AS customerID_sale,
                          MIN(Purchase_Date) AS first_sale_date,
                          MAX(Purchase_Date) AS most_recent_sale_date,
                          SUM(single_sale_income) AS total_sale_income,
                          COUNT(VIN) AS sales_count
                        FROM Vehicle_sale
                        GROUP BY customerID_sale
                      ),
                      Customer_repair AS ( 
                        SELECT 
                          customerID AS customerID_repair,
                          MIN(start_date) AS first_repair_date,
                          MAX(start_date) AS most_recent_repair_date,
                          SUM(single_repair_income) AS total_repair_income,
                          COUNT(VIN) AS repair_count
                        FROM Vehicle_repair
                        GROUP BY customerID_repair
                      ),
                      Customer_sale_repair_u1 AS (
                        SELECT customerID_sale, customerID_repair, first_sale_date, most_recent_sale_date, total_sale_income, sales_count, first_repair_date, most_recent_repair_date, total_repair_income, repair_count FROM Customer_sale cs
                        LEFT JOIN Customer_repair cr ON cs.customerID_sale = cr.customerID_repair
                        UNION  
                        SELECT customerID_sale, customerID_repair, first_sale_date, most_recent_sale_date, total_sale_income, sales_count, first_repair_date, most_recent_repair_date, total_repair_income, repair_count FROM Customer_sale cs
                        RIGHT JOIN Customer_repair cr ON cs.customerID_sale = cr.customerID_repair 
                      ),
                      Customer_sale_repair_u2 AS (
                        SELECT COALESCE (customerID_sale, customerID_repair) AS customerID, first_sale_date, most_recent_sale_date, total_sale_income, sales_count, first_repair_date, most_recent_repair_date, total_repair_income, repair_count
                        FROM Customer_sale_repair_u1

                      )
                      SELECT 
                        customer_u.customerID AS customerID, 
                        date(LEAST( IFNULL(first_sale_date, '9999-12-31'), 
                        IFNULL(first_repair_date, '9999-12-31'))) AS first_date, 
                        date(GREATEST(IFNULL(most_recent_sale_date, '1000-01-01'), IFNULL(most_recent_repair_date, '1000-01-01')))  AS most_recent_date, 
                        IFNULL(total_sale_income, 0) + IFNULL(total_repair_income, 0)  AS total_income, 
                        IFNULL(sales_count,0) AS sales_count, IFNULL(repair_count,0) AS repair_count, 
                        customer_u.Name
                      FROM Customer_sale_repair_u2 INNER JOIN customer_u ON Customer_sale_repair_u2.customerID = customer_u.customerID
                      ORDER BY total_income DESC, most_recent_date DESC
                      LIMIT 15
                      ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">Customer Name</td>';
                        print '<td class="heading">First Date of Activity</td>';
                        print '<td class="heading">Most Recent Date of Activity';
                        print '<td class="heading">Number of Sales</td>';
                        print '<td class="heading">Number of Repairs</td>';
                        print '<td class="heading">Total Income</td>';
                        print '<td class="heading"> </td>';
                        print '</tr>';

                        while ($row) {
                            $customerID = urlencode($row['customerID']);
                            $Name = urlencode($row['Name']);

                            print '<tr>';
                            print '<td>' . $row['Name'] . '</td>';
                            print '<td>' . $row['first_date'] . '</td>';
                            print '<td>' . $row['most_recent_date'] . '</td>';
                            print '<td>' . $row['sales_count'] . '</td>';
                            print '<td>' . $row['repair_count'] . '</td>';
                            print '<td>' . $row['total_income'] . '</td>';
                            print "<td><input type='button' name='detail$row[customerID]' value='More Details' onclick= \"location.href = 'report_gross_customer_income_drilldown.php?customerID=$customerID&Name=$Name'\"/></td>";

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