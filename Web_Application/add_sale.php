<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
$VIN = $_SESSION['vin_for_sale'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get Id from form
    $customer_identifier = mysqli_real_escape_string($db, $_POST['customer_identifier']);
    $sold_price = mysqli_real_escape_string($db, $_POST['sold_price']);
    $sold_date = mysqli_real_escape_string($db, $_POST['sold_date']);

    // Check the conditions
    if (empty($customer_identifier)) {
        array_push($error_msg, "Please enter either driver_license_number or tax_identification_number.");
    }

    // Execute the search for customer
    if (!empty($customer_identifier)) {
        $query = "
            WITH customer_i AS(
                SELECT 
                    c.customerID, 
                    i.Driver_License_Number AS identifier, 
                    concat(i.first_name, ' ', i.last_name) AS Name
                FROM 
                    Customer c 
                    INNER JOIN Individual i ON c.customerID = i.customerID
            ),
            customer_b AS(
                SELECT 
                    c.customerID, 
                    b.Tax_Identification_Number AS identifier, 
                    b.business_name AS Name
                FROM 
                    Customer c 
                    INNER JOIN Business b ON c.customerID = b.customerID
            ),
            customer_u AS(
                (
                    SELECT 
                        CI.customerID, 
                        CI.identifier, 
                        CI.Name 
                    FROM customer_i CI
                )
                UNION
                (
                    SELECT 
                        CB.customerID, 
                        CB.identifier, 
                        CB.Name 
                    FROM customer_b CB
                )
            )
            SELECT customerID, identifier, Name
            FROM customer_u
            WHERE identifier = '$customer_identifier';";
    }

    $customer_search_result = mysqli_query($db, $query);
    //for show up in UI durng debug
    $result = mysqli_query($db, $query);
    include('lib/show_queries.php');

    // verify whether customer search is successful
    $result_count = mysqli_num_rows($customer_search_result);
    if ($result_count == 0 && !empty($customer_identifier)) {
        array_push($error_msg, "No such customer, Please add a new customer!" . NEWLINE);
    }

    if (!empty($sold_date) && !empty($sold_price)) {
        // Fetch customerId
        $row = mysqli_fetch_array($customer_search_result, MYSQLI_ASSOC);

        // Fetch username from session
        $username = $_SESSION['username'];
        array_push($query_msg, "username is $username ");

        // Data validation
        if (!is_date($sold_date)) {
            array_push($error_msg, "Error: Invalid sold date, not a date!");
        }

        if (!is_numeric($sold_price)) {
            array_push($error_msg, "Error: Invalid sold price, not a number!");
        }


        // Data conversion
        $sold_price = (float)$sold_price;
        array_push($query_msg, "sold_price is $sold_price ");
        $customerID = (int)$row['customerID'];
        array_push($query_msg, "customerID is $customerID ");

        array_push($query_msg, "userName is $username ");

        array_push($query_msg, "sold_date is $sold_date ");

        // Business validation
        $query = "SELECT invoice_price " .
            "FROM Vehicle " .
            "WHERE Vehicle.VIN = '$VIN' " .
            "LIMIT 1;";
        $result = mysqli_query($db, $query);
        include('lib/show_queries.php');

        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $invoice_price = (float)$row['invoice_price'];

        if (!is_business_validation_passed($sold_price, $invoice_price, ($_SESSION['role_type']))) {
            array_push($error_msg, "Sold price must >= 0.95 * invoice_price, and you are not the owner!");
        }

        // validation conclusion
        $should_insert = is_date($sold_date) && is_numeric($sold_price) && is_business_validation_passed($sold_price, $invoice_price, ($_SESSION['role_type']));

        if ($should_insert) {
            // insert to sales
            $insert_sale_query = "INSERT INTO SalesTransaction " .
                "(VIN, Purchase_Date, sold_price, customerID, userName) VALUES " .
                "('$VIN', '$sold_date', '$sold_price', '$customerID', '$username'); ";
            $result = mysqli_query($db, $insert_sale_query);
            include('lib/show_queries.php');


            if (mysqli_affected_rows($db) == -1) {
                array_push($error_msg, "SalesTransaction insertion failed, please check!" . NEWLINE);
            } else {
                // if successful, redirect to view vehicle detail page
                $_SESSION['vin_for_sale'] = "";
                header(REFRESH_TIME . "url=view_vehicle_detail.php?vin=$VIN");
            }
        }


    }

}  //end of if($_POST)

function is_date($str)
{
    $stamp = strtotime($str);
    if (!is_numeric($stamp)) {
        return false;
    }
    $month = date('m', $stamp);
    $day = date('d', $stamp);
    $year = date('Y', $stamp);

    if (checkdate($month, $day, $year)) {
        return true;
    }
    return false;
}

function is_business_validation_passed($sold_price, $invoice_price, $role_type)
{
    if ($role_type == 'Owner') {
        return true;
    } else if ($sold_price > 0.95 * $invoice_price) {
        return true;
    } else {
        return false;
    }

}

?>

<?php include("lib/header.php"); ?>
<title>Add Sale</title>
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
            <div class="title_name"><?php print "Add Sale Form"; ?></div>
            <div class="features">

                <div class="profile_section">
                    <div class="subtitle">Search Customer (by either Tax Identification or Driver license)</div>

                    <form name="customerform" action="add_sale.php" method="post">
                        <table>
                            <tr>
                                <td class="item_label">Driver License Number / Tax Identification Number</td>
                                <td>
                                    <input type="text" name="customer_identifier"
                                           value="<?php if ($row['customer_identifier']) {
                                               print $row['customer_identifier'];
                                           } ?>"/>
                                </td>
                            </tr>

                        </table>

                        <a href="javascript:customerform.submit();" class="fancy_button">Search</a>
                        <a href='add_customer_for_sale.php' class="fancy_button"> Add </a> </a>

                    </form>
                </div>

                <div class='profile_section'>
                    <div class='subtitle'>Customer Information</div>
                    <table>
                        <tr>
                            <td class='heading'>Identifier (Tax/Driver license)</td>
                            <td class='heading'>Name</td>
                            <td class='heading'>customerID</td>
                        </tr>
                        <?php
                        if (isset($customer_search_result)) {
                            while ($row = mysqli_fetch_array($customer_search_result, MYSQLI_ASSOC)) {
                                print "<tr>";

                                print "<td>{$row['identifier']}</td>";
                                print "<td>{$row['Name']}</td>";
                                print "<td>{$row['customerID']}</td>";

                                print "</tr>";
                            }
                        } ?>
                    </table>
                </div>


                <div class='profile_section'>
                    <div class='subtitle'>Sale Information</div>
                    <form name="salesform" action="add_sale.php" method="post">
                        <table>
                            <tr>
                                <td class="item_label">VIN</td>
                                <td>
                                    <input type="text" name="VIN" readonly="readonly"
                                           value="<?php print $VIN; ?>"/>
                                </td>
                            </tr>

                            <tr>
                                <td class="item_label">Driver License Number / Tax Identification Number</td>
                                <td>
                                    <input type="text" name="customer_identifier" readonly="readonly"
                                           value="<?php print $customer_identifier; ?>"/>
                                </td>
                            </tr>

                            <tr>
                                <td class="item_label">Current UserName</td>
                                <td>
                                    <input type="text" name="username" readonly="readonly"
                                           value="<?php print ($_SESSION['username']); ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Sold Price</td>
                                <td>
                                    <input type="number" name="sold_price"
                                           value="<?php if ($row['sold_price']) {
                                               print $row['sold_price'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Sold Date (YYYY-MM-DD)</td>
                                <td>
                                    <input type="text" name="sold_date"
                                           value="<?php if ($row['sold_date']) {
                                               print $row['sold_date'];
                                           } ?>"/>
                                </td>
                            </tr>

                        </table>

                        <a href="javascript:salesform.submit();" class="fancy_button">Sell</a>

                    </form>

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