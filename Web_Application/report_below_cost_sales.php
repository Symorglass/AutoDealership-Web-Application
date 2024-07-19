<?php

    include('lib/common.php');

    if (!isset($_SESSION['username'])) {
        header('Location: login.php');
        exit();
    }

?>

<?php include("lib/header.php"); ?>

<title>Below Cost Sales Report</title>
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
                    <div class="subtitle">Below Cost Sales Report</div>

                    <?php
                    $query = "
                    WITH report AS(  
                        SELECT 
                            S.Purchase_Date, 
                            V.invoice_price, 
                            S.sold_price, 
                            CONCAT(S.sold_price/V.invoice_price * 100,'%') AS ratio, 
                            SP.first_name, 
                            SP.last_name, 
                            S.customerID  
                        FROM 
                            SalesTransaction S 
                            INNER JOIN Vehicle V 
                            INNER JOIN LoggedInUser SP ON S.VIN = V.VIN AND S.userName = SP.userName  
                        WHERE S.sold_price < V.invoice_price
                    ), 
                    customer_i AS( 
                        SELECT 
                            C.customerID, 
                            concat(I.first_name,' ', I.last_name) AS Name   
                        FROM Customer C INNER JOIN Individual I  ON I.customerID = C.customerID
                    ), 
                    customer_b AS(  
                        SELECT 
                            C.customerID, 
                            B.business_name AS Name  
                        FROM 
                            Customer C 
                            INNER JOIN Business B ON B.customerID = C.customerID
                    ), 
                    customer_u AS(  
                        SELECT 
                            CI.customerID, 
                            CI.Name  
                        FROM customer_i CI  
                        UNION  
                        SELECT 
                            CB.customerID, 
                            CB.Name 
                        FROM customer_b CB
                    ) 
                    SELECT 
                        R.Purchase_Date AS sale_date, 
                        R.invoice_price, 
                        R.sold_price, 
                        R.ratio, 
                        C.Name AS customer_name, 
                        R.first_name AS salesperson_first_name, 
                        R.last_name AS salesperson_last_name 
                    FROM 
                        customer_u C 
                        INNER JOIN report R ON C.customerID = R.customerID 
                    ORDER BY R.Purchase_Date DESC, ratio DESC
                    ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">Sale Date</td>';
                        print '<td class="heading">Invoice Price</td>';
                        print '<td class="heading">Sold Price</td>';
                        print '<td class="heading">Ratio</td>';
                        print '<td class="heading">Customer Name</td>';
                        print '<td class="heading">Salesperson First Name</td>';
                        print '<td class="heading">Salesperson Last Name</td>';
                        print '</tr>';

                        while ($row) {

                            $ratio_string = urlencode($row['ratio']);
                            $ratio_decimal = str_replace('%', '', $ratio_string) / 100.00;


                            if ($ratio_decimal <= 0.95) {
                                print '<tr style="background-color:red">';
                            } else {
                                print '<tr>';
                            }
                            print '<td>' . $row['sale_date'] . '</td>';
                            print '<td>' . $row['invoice_price'] . '</td>';
                            print '<td>' . $row['sold_price'] . '</td>';
                            print '<td>' . $row['ratio'] . '</td>';
                            print '<td>' . $row['customer_name'] . '</td>';
                            print '<td>' . $row['salesperson_first_name'] . '</td>';
                            print '<td>' . $row['salesperson_last_name'] . '</td>';

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
