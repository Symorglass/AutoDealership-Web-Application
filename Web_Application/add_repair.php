<?php

session_start();
include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Fetch info from session
$VIN = $_SESSION['vin_from_view_repair'];
$start_date = date("Y-m-d");
$customerID = $_SESSION['customerID_repair'];
$username = $_SESSION['username'];

// Set error variable to empty values first
$odometer_readout_err = $part_number_err = $vendor_name_err = $price_err = $quantity_err = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Once add repair button is clicked
    // validate if repair has been added (can't add twice)
    if (isset($_POST['add_repair'])) {
        if (has_duplicate_repair($VIN, $db)) {
            array_push($error_msg, "ERROR: can't add duplicate repairs, if need to update, please search this repair and update.<br>" . __FILE__ . " line:" . __LINE__);
        } else {
            // Data validation
            // validate odometer_readout input (required field)
            if (!isset($_POST['odometer_readout'])) {
                $odometer_readout_err = "Odometer readout is required.";
            } else {
                $odometer_readout = mysqli_real_escape_string($db, $_POST['odometer_readout']);
                // Add repair
                $can_insert = isset($VIN) && isset($odometer_readout) && isset($username) && isset($customerID);
                $customerID = (string)$customerID;
                if ($can_insert) {
                    $query = "INSERT INTO Repair(VIN, start_date, odometer_readout, userName, customerID)
                            VALUES('$VIN', CURDATE(), '$odometer_readout', '$username', '$customerID');";
                    $success_add_repair = mysqli_query($db, $query);
                    if (mysqli_affected_rows($db) == -1) {
                        array_push($error_msg, "INSERT ERROR:Failed to add repair... <br>" . __FILE__ . " line:" . __LINE__);
                    }
//                    else {
//                        array_push($success, "Repair successfully added. <br>" . __FILE__ . " line:" . __LINE__);
//                    }
                }
            }
        }
    }


    // Once add labor charge button is clicked, add labor charge separately
    if (isset($_POST['add_labor_charge'])) {
        if (!repair_exist($VIN, $db)) {
            array_push($error_msg, "ERROR: Corresponding repair not exist. Please add repair before adding parts.<br>" . __FILE__ . " line:" . __LINE__);
        } else {
            // Validate labor_charge input (NULL allowed)
            if (!isset($_POST['labor_charge'])) {
                $labor_charge = null;
            } else {
                if (!is_numeric($_POST['labor_charge'])) {
                    array_push($error_msg, "Labor charge should be a number.<br>" . __FILE__ . " line:" . __LINE__);
                } else {
                    $labor_charge = mysqli_real_escape_string($db, $_POST['labor_charge']);
                    // Add labor charge
                    $query = "UPDATE Repair
                            SET labor_charge = '$labor_charge' 
                            WHERE VIN = '$VIN' AND start_date = CURDATE() AND completion_date IS NULL;";
                    $success_add_labor_charge = mysqli_query($db, $query);
                    $result = mysqli_query($db, $query);
                    include('lib/show_queries.php');
                    if (mysqli_affected_rows($db) == -1) {
                        array_push($error_msg, "INSERT ERROR: Failed to add labor charge... <br>" . __FILE__ . " line:" . __LINE__);
                    }
                }
            }
        }
    }


    // Once add description button is clicked, add description separately
    if (isset($_POST['add_description'])) {
        if (!repair_exist($VIN, $db)) {
            array_push($error_msg, "ERROR: Corresponding repair not exist. Please add repair before adding parts.<br>" . __FILE__ . " line:" . __LINE__);
        } else {
            // Validate description input (NULL allowed)
            if (!isset($_POST['description'])) {
                $description = "";
            } else {
                $description = mysqli_real_escape_string($db, $_POST['description']);
                // Add description
                $query = "UPDATE Repair
                        SET description = '$description' 
                        WHERE VIN = '$VIN' AND start_date = CURDATE() AND completion_date IS NULL;";
                $success_add_description = mysqli_query($db, $query);
                $result = mysqli_query($db, $query);
                include('lib/show_queries.php');
                if (mysqli_affected_rows($db) == -1) {
                    array_push($error_msg, "INSERT ERROR: Failed to add description... <br>" . __FILE__ . " line:" . __LINE__);
                }
            }
        }
    }


    // Once add parts button is clicked
    if (isset($_POST['add_parts'])) {
        if (!repair_exist($VIN, $db)) {
            array_push($error_msg, "INSERT ERROR:Corresponding repair not exist. Please add repair before adding parts. <br>" . __FILE__ . " line:" . __LINE__);
        } else {
            // Data validation
            // validate part number input
            if (empty($_POST['part_number'])) {
                $part_number_err = "Part number is required.";
            } else {
                $part_number = mysqli_real_escape_string($db, $_POST['part_number']);
            }

            // validate vendor name input
            if (empty($_POST['vendor_name'])) {
                $vendor_name_err = "Vendor name is required.";
            } else {
                $vendor_name = mysqli_real_escape_string($db, $_POST['vendor_name']);
            }

            // validate price input
            if (empty($_POST['price'])) {
                $price_err = "Price is required.";
            } else {
                if (!is_numeric($_POST['price'])) {
                    array_push($error_msg, "Price should be a number.<br>" . __FILE__ . " line:" . __LINE__);
                } else {
                    $price = mysqli_real_escape_string($db, $_POST['price']);
                }
            }

            // validate quantity input
            if (empty($_POST['quantity'])) {
                $quantity_err = "Quantity is required.";
            } else {
                if (!is_numeric($_POST['quantity'])) {
                    array_push($error_msg, "Quantity should be a number.<br>" . __FILE__ . " line:" . __LINE__);
                } else {
                    $quantity = mysqli_real_escape_string($db, $_POST['quantity']);
                }
            }

            $can_insert = isset($part_number) && isset($vendor_name) && isset($price) && isset($quantity);
            if ($can_insert) {
                // Data conversion
                $part_number = (string)$part_number;
                $vendor_name = (string)$vendor_name;
                $quantity = (int)$quantity;
                $price = (float)$price;

                // Validate if part_number is duplicate
                if (duplicate_part_number($part_number, $VIN, $db)) {
                    array_push($error_msg, "INSERT ERROR: Duplicate part number, please check. <br>" . __FILE__ . " line:" . __LINE__);
                } else {
                    // Add part
                    $query = "INSERT INTO Part(VIN, start_date, part_number, vendor_name, price, quantity)
                    VALUES('$VIN', CURDATE(), '$part_number', '$vendor_name', '$price', '$quantity');";
                    $add_parts = mysqli_query($db, $query);
//                $result = mysqli_query($db, $query);
//                include('lib/show_queries.php');
                    if (mysqli_affected_rows($db) == -1) {
                        array_push($error_msg, "INSERT ERROR:Failed to add part ... <br>" . __FILE__ . " line:" . __LINE__);
                    }
                }
            }
        }
    }


} //end of if($_POST)


function repair_exist($VIN, $db)
{
    $query = "SELECT VIN, start_date
    FROM Repair
    WHERE VIN = '$VIN' AND date(start_date) = date(CURDATE()) AND completion_date IS NULL;";
    $repair_count = mysqli_query($db, $query);
    if (!is_bool($repair_count) && mysqli_num_rows($repair_count) > 0) {
        return true;
    } else {
        return false;
    }
}


function duplicate_part_number($part_number_input, $VIN, $db)
{
    $query = "SELECT part_number
        FROM Part
        WHERE VIN = '$VIN' AND date(start_date) = date(CURDATE()) AND part_number = '$part_number_input';";
    $part_number_count = mysqli_query($db, $query);
    if (!is_bool($part_number_count) && mysqli_num_rows($part_number_count) > 0) {
        return true;
    } else {
        return false;
    }
}

function has_duplicate_repair($VIN, $db)
{
    $query = "SELECT VIN, start_date
    FROM Repair
    WHERE VIN='$VIN' AND date(start_date) = date(CURDATE());";
    $repair_count = mysqli_query($db, $query);
    if (!is_bool($repair_count) && mysqli_num_rows($repair_count) > 0) {
        return true;
    } else {
        return false;
    }
}


?>



<?php include("lib/header.php"); ?>
<title>Add Repair</title>
<!--<link rel="stylesheet" href="css/gtonline_style.css">-->
<style>
    span.error {
        color: #FF0000;
    }

</style>
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
                <?php print "Add Repair Form for Vehicle $VIN "; ?>
            </div>
            <!-- Customer search and add customer -->
            <div class="features">

                <div class="profile_section">
                    <div class="subtitle">Start New Repair Form</div>
                    <form name="add_repair_form" action="add_repair.php" method="post">
                        <table>

                            <tr>
                                <td class="heading">CustomerID</td>
                                <td>Customer exists. CustomerID <?php echo $customerID ?> will be added to the repair.
                                </td>
                            </tr>
                            <tr>
                                <td class="heading">VIN</td>
                                <td><?php echo $VIN ?></td>
                            </tr>
                            <tr>
                                <td class="heading">Start Date</td>
                                <td><?php echo $start_date ?></td>
                            </tr>
                            <tr>
                                <td class="heading">User Name</td>
                                <td><?php echo $username ?></td>
                            </tr>
                            <tr>
                                <td class="heading">Odometer Readout</td>
                                <td>
                                    <span class="error"><input type="number" step="any" min="0"
                                                               name="odometer_readout"/> *<?php echo $odometer_readout_err; ?></span>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" class="repair_button" name="add_repair" value="Start"/>
                    </form>
                </div>

                <br>
                <br>
                <br>

                <div class="profile_section">
                    <div class="subtitle"></div>
                    <form name="add_labor_charge_form" action="add_repair.php" method="post">
                        <table>
                            <tr class="heading">Reminder: Please start new repair before adding labor charge or
                                description.
                            </tr>
                            <tr>
                                <td class="heading">Labor Charge</td>
                                <td>
                                    <input type="number" step="any" min="0" name="labor_charge"/>
                                </td>
                                <td>
                                    <input type="submit" class="repair_button" name="add_labor_charge" value="Add"/>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>


                <div class="profile_section">
                    <div class="subtitle"></div>
                    <form name="add_description_form" action="add_repair.php" method="post">
                        <table>
                            <tr>
                                <td class="heading">Description</td>
                                <td>
                                    <textarea name="description" maxlength="250" rows="5" cols="40"
                                              placeholder="250 characters." autofocus/></textarea>
                                </td>
                                <td>
                                    <input type="submit" class="repair_button" name="add_description" value="Add"/>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>

                <div class='profile_section'>
                    <div class='subtitle'>Add Parts</div>
                    <form name="parts_update_form" action="add_repair.php" method="post">
                        <table>
                            <tr>
                                <td class="item_label">Part Number</td>
                                <td>
                                    <span class="error"><input type="text"
                                                               name="part_number"/> *<?php echo $part_number_err; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Vendor Name</td>
                                <td>
                                    <span class="error"><input type="text"
                                                               name="vendor_name"/> *<?php echo $vendor_name_err; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Price</td>
                                <td>
                                    <!-- Make sure input is not less than 0 and is float number-->
                                    <span class="error"><input type="number" step="any" min="0"
                                                               name="price"/> *<?php echo $price_err; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Quantity</td>
                                <td>
                                    <!-- Make sure input is not less than 1 and is whole number-->
                                    <span class="error"><input type="number" min="1" step="1" name="quantity"
                                                               class="features"/> *<?php echo $quantity_err; ?></span>
                                </td>
                            </tr>
                        </table>

                        <input type="submit" class="repair_button" name="add_parts" value="Add"/>
                        <!--                        <a href="javascript:parts_update_form.submit();" class="fancy_button">Add</a>-->
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
