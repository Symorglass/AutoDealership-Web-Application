<?php

    include('lib/common.php');


    if (!isset($_SESSION['username'])) {
        header('Location: login.php');
        exit();
    }

?>

<?php include("lib/header.php"); ?>

<title>Parts Statistics Report</title>
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
                    <div class="subtitle">Parts Statistics Report</div>

                    <?php
                    $query = " 
                        SELECT 
                            vendor_name, 
                            sum(quantity) AS total_supply_quantity, 
                            sum(price * quantity) AS total_spent_on_parts 
                        FROM Part 
                        GROUP BY vendor_name 
                        ORDER BY sum(price * quantity) DESC 
                    ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">Vendor Name</td>';
                        print '<td class="heading">Total Supply Quantity</td>';
                        print '<td class="heading">Total Spent On Parts</td>';
                        print '</tr>';

                        while ($row) {
                            

                            print '<tr>';
                            print '<td>' . $row['vendor_name'] . '</td>';
                            print '<td>' . $row['total_supply_quantity'] . '</td>';
                            print '<td>' . $row['total_spent_on_parts'] . '</td>';
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