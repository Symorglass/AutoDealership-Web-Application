<?php

    include('lib/common.php');

    if (!isset($_SESSION['username'])) {
        header('Location: login.php');
        exit();
    }

    $Year = mysqli_real_escape_string($db, $_REQUEST['year']);
    $Month = mysqli_real_escape_string($db, $_REQUEST['month']);

?>

<?php include("lib/header.php"); ?>

<?php
    $title = "$Year-$Month Sales Report";
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
                    $subtitle = "$Year-$Month Sales Report";
                    include("lib/variable_subtitle.php");
                    ?>


                    <?php $query = "
                        SELECT 
                            SP.first_name, SP.last_name, 
                            COUNT(S.VIN) AS total_number_sold, 
                            SUM(S.sold_price) AS total_sales
                        FROM 
                            SalesTransaction S 
                            INNER JOIN Vehicle V 
                            INNER JOIN LoggedInUser SP ON S.VIN = V.VIN AND S.userName = SP.userName
                        WHERE YEAR(S.Purchase_Date) = '$Year' AND MONTH(S.Purchase_Date) = '$Month' GROUP BY S.userName
                        ORDER BY total_number_sold DESC, total_sales DESC, last_name, first_name
                    ";

                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');

                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $count = mysqli_num_rows($result);
                    if ($row) {

                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">First Name</td>';
                        print '<td class="heading">Last Name</td>';
                        print '<td class="heading">Total Sold Count</td>';
                        print '<td class="heading">Total Sales</td>';

                        print '</tr>';

                        while ($row) {

                            print '<tr>';
                            print '<td>' . $row['first_name'] . '</td>';
                            print '<td>' . $row['last_name'] . '</td>';
                            print '<td>' . $row['total_number_sold'] . '</td>';
                            print '<td>' . $row['total_sales'] . '</td>';


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