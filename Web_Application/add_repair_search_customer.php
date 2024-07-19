<?php
session_start();
include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Fetch info from session
$VIN = $_SESSION['vin_from_view_repair'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get customerID from form
    $customerID = mysqli_real_escape_string($db, $_POST['customerID']);
    
    if (isset($_POST['search_customer'])) {
        // Check if labor charge field is filled in
        if (empty($_POST['customerID']) || trim($_POST['customerID']) == '') {
            array_push($error_msg, "Please enter 'Tax Identification Number' or 'Driver License Number' to search.");
        } else {
            // Execute the search for customerID
            $query = "
            WITH customer_i AS(
                SELECT 
                    c.customerID, 
                    i.Driver_License_Number AS identifier 
                FROM 
                    Customer c 
                    INNER JOIN Individual i 
                ON c.customerID = i.customerID
            ),
            customer_b AS(
                SELECT 
                    c.customerID, 
                    b.Tax_Identification_Number AS identifier 
                FROM 
                    Customer c 
                    INNER JOIN Business b
                ON c.customerID = b.customerID
            ),
            customer_u AS(
                (
                    SELECT 
                        CI.customerID, 
                        CI.identifier 
                    FROM customer_i CI
                )
                UNION
                (
                    SELECT 
                        CB.customerID, 
                        CB.identifier 
                    FROM customer_b CB
                )
            )
            SELECT customerID
            FROM customer_u
            WHERE identifier = '$customerID';";
        $customer_search_result = mysqli_query($db, $query);
        $customerID_fetch = mysqli_fetch_array($customer_search_result)['customerID'];
        include('lib/show_queries.php');
        if (mysqli_affected_rows($db) == -1) {
            array_push($error_msg, "SELECT ERROR:Failed to find customer ... <br>" . __FILE__ . " line:" . __LINE__);
        }
        }
    }

} //end of if($_POST)


?>


<?php include("lib/header.php"); ?>
<title>Update Repair</title>

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
            <div class="title_name">
                <?php
                $title = "$VIN - Add Repair Search Customer";
                include("lib/variable_head_title.php");
                ?>
            </div>
            <!-- Customer search and add customer -->
            <div class="features">

                <div class="profile_section">
                    <div class="subtitle">Search Customer</div>
                    <form name="customer_search_form" action="add_repair_search_customer.php" method="post">
                        <table>
                            <tr>
                                Please enter "Tax Identification Number" or "Driver License Number" to search customer before adding repair.
                            </tr>
                            <tr>
                                <td class="item_label">
                                    Driver license Number or Tax Identification Number
                                </td>
                                <td>
                                    <input type="text" name="customerID"/>
                                </td>
                                <td><input type="submit" class="repair_button" name="search_customer" value="Search" /></td>
                            </tr>
                        </table>
                    </form>

                    <form name="add_repair_form" action="add_repair_search_customer.php" method="post">
                        <?php
                        if (isset($_POST['search_customer'])) {
                            if (!is_bool($customer_search_result) && mysqli_num_rows($customer_search_result) > 0) {
                                $customerID_encoded = urlencode($customerID_fetch);
                                $_SESSION['customerID_repair'] = $customerID_encoded;  // Store the encoded customer ID in the session
                                $heading = "Customer exists. Click button to add new repair.";
                                $buttonHref = "add_repair.php";
                            } else {
                                $heading = "Customer not exists. Please add customer.";
                                $buttonHref = "add_customer_for_repair.php";
                            }

                            print "<table><tr>";
                            print "<td class='heading'>$heading</td>";
                            print "<td><a href='$buttonHref' class='fancy_button'>Add</a></td>";
                            print "</tr></table>";
                        }
                        ?>
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
