<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Initialize, especially initialize after refresh
    // $_POST = array();

    // Get Individual attributes
    $Driver_License_Number = mysqli_real_escape_string($db, $_POST['Driver_License_Number']);
    $first_name = mysqli_real_escape_string($db, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($db, $_POST['last_name']);

    // Get Business attributes
    $Tax_Identification_Number = mysqli_real_escape_string($db, $_POST['Tax_Identification_Number']);
    $business_name = mysqli_real_escape_string($db, $_POST['business_name']);
    $primary_contact_name = mysqli_real_escape_string($db, $_POST['primary_contact_name']);
    $primary_contact_title = mysqli_real_escape_string($db, $_POST['primary_contact_title']);

    // Get common attributes
    $phone_number = mysqli_real_escape_string($db, $_POST['phone_number']);
    $city = mysqli_real_escape_string($db, $_POST['city']);
    $street_address = mysqli_real_escape_string($db, $_POST['street_address']);
    $postal_code = mysqli_real_escape_string($db, $_POST['postal_code']);
    $state = mysqli_real_escape_string($db, $_POST['state']);
    $email = mysqli_real_escape_string($db, $_POST['email']);


    // Check the conditions
    if (empty($Driver_License_Number) && empty($Tax_Identification_Number)) {
        array_push($error_msg, "Please enter details either for individual or business.");
    }


    //Validations
    if (!empty($Driver_License_Number) && (empty($first_name) || empty($last_name))) {
        array_push($error_msg, "You have to enter all fields for Driver_License_Number, only email is optional.");
    }


    if (!empty($Tax_Identification_Number) && (empty($business_name) || empty($primary_contact_name) || empty($primary_contact_title))) {
        array_push($error_msg, "You have to enter all fields for Business, only email is optional.");
    }

    if (empty($phone_number) || empty($city) || empty($street_address) || empty($postal_code) || empty($state)) {
        array_push($error_msg, "You have to enter all common fields, only email is optional.");
    }

    // Execute the insert
    if (empty($email)) {
        $insert_customer_query = "INSERT INTO CUSTOMER " .
            "(phone_number, city, street_address,postal_code,state) VALUES" .
            "('$phone_number', '$city', '$street_address','$postal_code','$state'); ";


    } else {
        $insert_customer_query = "INSERT INTO CUSTOMER " .
            "(phone_number, city, street_address,postal_code,state,email) VALUES" .
            "('$phone_number', '$city', '$street_address','$postal_code','$state','$email') ;";
    }


    if (!empty($phone_number) || !empty($city) || !empty($street_address) || !empty($postal_code) || !empty($state)) {
        $result = mysqli_query($db, $insert_customer_query);
        include('lib/show_queries.php');
        if (mysqli_affected_rows($db) == -1) {
            array_push($error_msg, "Customer insertion failed, please check!" . NEWLINE);
        }
    }

    // Get latest customerId
    $customer_query = "SELECT customerID " .
        "FROM customer " .
        "ORDER BY customerID DESC " .
        "LIMIT 1;";
    $result = mysqli_query($db, $customer_query);
    include('lib/show_queries.php');

    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $customerID = $row['customerID'];

    // Insert individual/business
    if (!empty($Tax_Identification_Number) && !empty($business_name) && !empty($primary_contact_name) && !empty($primary_contact_title)) {
        $query = "INSERT INTO Business " .
            "(customerID, Tax_Identification_Number, business_name, primary_contact_name, primary_contact_title) " .
            "VALUES ('$customerID','$Tax_Identification_Number','$business_name', '$primary_contact_name', '$primary_contact_title'); ";
        $result = mysqli_query($db, $query);
        include('lib/show_queries.php');

        if (mysqli_affected_rows($db) == -1) {
            // failed, show error, no redirect.
            array_push($error_msg, "Business Customer specifics insertion failed, please check!" . NEWLINE);
        } else {
            // if successful, redirect to add_sale page
            header(REFRESH_TIME . 'url=add_sale.php');
        }
    }

    if (!empty($Driver_License_Number) && !empty($first_name) && !empty($last_name)) {
        $query = "INSERT INTO Individual " .
            "(customerID, Driver_License_Number, first_name, last_name) " .
            "VALUES ('$customerID','$Driver_License_Number','$first_name', '$last_name'); ";
        $result = mysqli_query($db, $query);
        include('lib/show_queries.php');

        if (mysqli_affected_rows($db) == -1) {
            // failed, show error, no redirect.
            array_push($error_msg, "Individual Customer specifics insertion failed, please check!" . NEWLINE);
        } else {
            // if successful, redirect to add_sale page
            header(REFRESH_TIME . 'url=add_sale.php');
        }
    }


}  //end of if($_POST)


?>

<?php include("lib/header.php"); ?>
<title>Add Customer</title>
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
            <div class="title_name"><?php print "Add Customer Form"; ?></div>
            <div class="features">

                <div class="profile_section">
                    <div class="subtitle">Add Individual Customer</div>

                    <form name="individualform" action="add_customer_for_sale.php" method="post">
                        <table>
                            <tr>
                                <td class="item_label">Driver license Number</td>
                                <td>
                                    <input type="text" name="Driver_License_Number"
                                           value="<?php if ($row['Driver_License_Number']) {
                                               print $row['Driver_License_Number'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">First Name</td>
                                <td>
                                    <input type="text" name="first_name"
                                           value="<?php if ($row['first_name']) {
                                               print $row['first_name'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Last Name</td>
                                <td>
                                    <input type="text" name="last_name"
                                           value="<?php if ($row['last_name']) {
                                               print $row['last_name'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Phone Number</td>
                                <td>
                                    <input type="text" name="phone_number"
                                           value="<?php if ($row['phone_number']) {
                                               print $row['phone_number'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">City</td>
                                <td>
                                    <input type="text" name="city"
                                           value="<?php if ($row['city']) {
                                               print $row['city'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Street Address</td>
                                <td>
                                    <input type="text" name="street_address"
                                           value="<?php if ($row['street_address']) {
                                               print $row['street_address'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Postal Code</td>
                                <td>
                                    <input type="text" name="postal_code"
                                           value="<?php if ($row['postal_code']) {
                                               print $row['postal_code'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">State</td>
                                <td>
                                    <input type="text" name="state"
                                           value="<?php if ($row['state']) {
                                               print $row['state'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Email</td>
                                <td>
                                    <input type="text" name="email"
                                           value="<?php if ($row['email']) {
                                               print $row['email'];
                                           } ?>"/>
                                </td>
                            </tr>


                        </table>

                        <a href="javascript:individualform.submit();" class="fancy_button">Add</a>

                    </form>

                    <div class="subtitle">Add Business Customer</div>
                    <form name="businessform" action="add_customer_for_sale.php" method="post">
                        <table>
                            <tr>
                                <td class="item_label">Tax Identification Number</td>
                                <td>
                                    <input type="text" name="Tax_Identification_Number"
                                           value="<?php if ($row['Tax_Identification_Number']) {
                                               print $row['Tax_Identification_Number'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Business Name</td>
                                <td>
                                    <input type="text" name="business_name"
                                           value="<?php if ($row['business_name']) {
                                               print $row['business_name'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Primary Contact Name</td>
                                <td>
                                    <input type="text" name="primary_contact_name"
                                           value="<?php if ($row['primary_contact_name']) {
                                               print $row['primary_contact_name'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Primary Contact Title</td>
                                <td>
                                    <input type="text" name="primary_contact_title"
                                           value="<?php if ($row['primary_contact_title']) {
                                               print $row['primary_contact_title'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Phone Number</td>
                                <td>
                                    <input type="text" name="phone_number"
                                           value="<?php if ($row['phone_number']) {
                                               print $row['phone_number'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">City</td>
                                <td>
                                    <input type="text" name="city"
                                           value="<?php if ($row['city']) {
                                               print $row['city'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Street Address</td>
                                <td>
                                    <input type="text" name="street_address"
                                           value="<?php if ($row['street_address']) {
                                               print $row['street_address'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Postal Code</td>
                                <td>
                                    <input type="text" name="postal_code"
                                           value="<?php if ($row['postal_code']) {
                                               print $row['postal_code'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">State</td>
                                <td>
                                    <input type="text" name="state"
                                           value="<?php if ($row['state']) {
                                               print $row['state'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Email</td>
                                <td>
                                    <input type="text" name="email"
                                           value="<?php if ($row['email']) {
                                               print $row['email'];
                                           } ?>"/>
                                </td>
                            </tr>


                        </table>

                        <a href="javascript:businessform.submit();" class="fancy_button">Add</a>

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