<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$VIN = $_SESSION['vin_from_view_repair'];
$start_date = $_SESSION['start_date_from_view_repair'];

//run the search repair query right away after loading update_repair.php
$query = "
    SELECT VIN, start_date, completion_date, odometer_readout, labor_charge, description
    FROM Repair
    WHERE VIN = '$VIN' AND DATE(start_date) = DATE('$start_date');
";
$repair_record = mysqli_query($db, $query);
$result = mysqli_query($db, $query);
include('lib/show_queries.php');
$row_repair = mysqli_fetch_array($repair_record, MYSQLI_ASSOC);

//run the search parts query right away after loading update_repair.php
$query = "
    SELECT VIN, start_date, part_number, vendor_name, price, quantity
    FROM Part
    WHERE VIN = '$VIN' AND DATE(start_date) = DATE('$start_date');
";
$part_record = mysqli_query($db, $query);
$result = mysqli_query($db, $query);
include('lib/show_queries.php');
if (!is_bool($part_record) && mysqli_num_rows($part_record) > 0) {
    $row_parts = mysqli_fetch_array($part_record, MYSQLI_ASSOC);
}

// Set error variable to empty values first
$labor_charge_err = $part_number_err = $vendor_name_err = $price_err = $quantity_err = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get repair update from form
    $part_number = mysqli_real_escape_string($db, $_POST['part_number']);
    $vendor_name = mysqli_real_escape_string($db, $_POST['vendor_name']);
    $price = mysqli_real_escape_string($db, $_POST['price']);
    $quantity = mysqli_real_escape_string($db, $_POST['quantity']);
    $complete_repair = mysqli_real_escape_string($db, $_POST['complete']);

    // Check if labor charge field is filled in
    if (isset($_POST['labor_charge_update'])) {
        if (!isset($_POST['labor_charge'])) {
            $labor_charge_err = "Labor charge is required";
        } else {
            if (repair_completed($VIN, $start_date, $db)) {
                array_push($error_msg, "Repair completed, labor charge can not be updated.");
            } else {
                $labor_charge = mysqli_real_escape_string($db, $_POST['labor_charge']);
                // Business Validation
                if(!labor_charge_validation_passed($labor_charge, $row_repair['labor_charge'], $_SESSION['role_type'])) {
                    array_push($error_msg, "Updated labor charge can not be less than previous value.");
                } else {
                    $query = "
                        UPDATE Repair
                        SET labor_charge = '$labor_charge' 
                        WHERE VIN = '$VIN' AND DATE(start_date) = DATE('$start_date');
                    ";
                    $update_labor_charge = mysqli_query($db, $query);
                    if (mysqli_affected_rows($db) == -1) {
                        array_push($error_msg, "UPDATE ERROR:Failed to update labor charge ... <br>" . __FILE__ . " line:" . __LINE__);
                    }
                }
            }
        }
    }


    // Add parts
    if (isset($_POST['add_parts'])) {
        
        if (repair_completed($VIN, $start_date, $db)) {
            array_push($error_msg, "Repair completed, parts can not be added.");
        } else {
            if (empty($_POST['part_number'])) {
                $part_number_err = "Part number is required.";
            } else {
                $part_number = mysqli_real_escape_string($db, $_POST['part_number']);
                $part_number = (string) $part_number;
            }

            // validate vendor name input
            if (empty($_POST['vendor_name'])) {
                $vendor_name_err = "Vendor name is required.";
            } else {
                $vendor_name = mysqli_real_escape_string($db, $_POST['vendor_name']);
                $vendor_name = (string) $vendor_name;
            }

            // validate price input
            if (empty($_POST['price'])) {
                $price_err = "Price is required.";
            } else {
                if (!is_numeric($_POST['price'])) {
                    array_push($error_msg, "Price should be a number.<br>" . __FILE__ . " line:" . __LINE__);
                } else {
                    $price = mysqli_real_escape_string($db, $_POST['price']);
                    $price = (float) $price;
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
                    $quantity = (int) $quantity;
                }
            }

            // Validate if part_number is duplicate
            if (duplicate_part_number($part_number, $VIN, $start_date, $db)) {
                array_push($error_msg, "INSERT ERROR: Duplicate part number, please check. <br>" . __FILE__ . " line:" . __LINE__);
            } else{
                // Add part
                $query = "
                    INSERT INTO Part(VIN, start_date, part_number, vendor_name, price, quantity)
                    VALUES('$VIN', '$start_date', '$part_number', '$vendor_name', '$price', '$quantity');
                ";
                $add_parts = mysqli_query($db, $query);
                if (mysqli_affected_rows($db) == -1) {
                    array_push($error_msg, "INSERT ERROR:Failed to add part ... <br>" . __FILE__ . " line:" . __LINE__);
                }
            }
        }
    }


    // Complete repair on click
    if (isset($_POST['complete'])) {
        $query = "
            UPDATE Repair
            SET completion_date = CURDATE() 
            WHERE VIN = '$VIN' AND completion_date IS NULL;
        ";
        $repair_complete = mysqli_query($db, $query);
        if (isset($_POST['complete']) && mysqli_affected_rows($db) == -1) {
            array_push($error_msg, "UPDATE ERROR:Failed to complete repair ... <br>" . __FILE__ . " line:" . __LINE__);
        }else{
            header(REFRESH_TIME . "url=view_repair.php");
        }
    }

    // Display the updated repair in every update and complete
    $query = "
        SELECT VIN, start_date, completion_date, odometer_readout, labor_charge, description
        FROM Repair
        WHERE VIN = '$VIN' AND DATE(start_date) = DATE('$start_date');
    ";
    $repair_record = mysqli_query($db, $query);
    $row_repair = mysqli_fetch_array($repair_record, MYSQLI_ASSOC);

    // Display added parts
    $query = "
        SELECT VIN, start_date, part_number, vendor_name, price, quantity
        FROM Part
        WHERE VIN = '$VIN' AND DATE(start_date) = DATE('$start_date')
    ";
    $part_record = mysqli_query($db, $query);
    if (!is_bool($part_record) && mysqli_num_rows($part_record) > 0) {
        $row_parts = mysqli_fetch_array($part_record, MYSQLI_ASSOC);
    }

}  //end of if($_POST)


function labor_charge_validation_passed($labor_charge, $original_labor_charge, $role_type) {
    if ($role_type == 'Owner') {
        return true;
    } elseif ($labor_charge >= $original_labor_charge) {
        return true;
    } else {
        return false;
    }
}

function duplicate_part_number($input_part_number, $VIN, $start_date, $db){
    $query = "
        SELECT part_number
        FROM Part
        WHERE VIN = '$VIN' AND DATE(start_date) = DATE('$start_date') AND part_number = '$input_part_number';
    ";
    $all_part_num = mysqli_query($db, $query);
    if (!is_bool($all_part_num) && mysqli_num_rows($all_part_num) > 0) {
        return true;
    } else {
        return false;
    }
}

function repair_completed($VIN, $start_date, $db){
    $query = "
        SELECT VIN, start_date
        FROM Repair
        WHERE VIN = '$VIN' AND DATE(start_date) = DATE('$start_date') AND completion_date IS NULL;
    ";
    $repair_count = mysqli_query($db, $query);
    if (!is_bool($repair_count) && mysqli_num_rows($repair_count) > 0) {
        return false;
    } else {
        return true;
    }
}


?>





<?php include("lib/header.php"); ?>
<title>Update Repair</title>
<style>
    span.error {color: #FF0000;}
</style>
</head>

<body>
<div id="main_container">
    <?php if (($_SESSION['role_type']) == "") include("lib/menu/public_menu.php"); ?>
    <?php if (($_SESSION['role_type']) == "InventoryClerk") include("lib/menu/inventory_clerk_menu.php"); ?>
    <?php if (($_SESSION['role_type']) == "Salesperson") include("lib/menu/salesperson_menu.php"); ?>
    <?php if (($_SESSION['role_type']) == "ServiceWriter") include("lib/menu/service_writer_menu.php"); ?>
    <?php if (($_SESSION['role_type']) == "Manager") include("lib/menu/manager_menu.php"); ?>
    <?php if (($_SESSION['role_type']) == "Owner")include("lib/menu/full_menu.php"); ?>

    <div class="center_content">
        <div class="center_left">
            <div class="title_name">
                <?php
                $title = "$VIN-$start_date Repair Update";
                include("lib/variable_head_title.php");
                ?>
            </div>
            <div class="features">
                <div class='profile_section'>
                    <?php
                    if (isset($repair_record)) {
                        print '<div class="subtitle">Repair Record</div>';
                        print '<form name="repair_record_display" action="update_repair.php" method="post">';
                        print '<table>';
                        print '<tr>';
                        print '<td class="heading">VIN</td>';
                        print '<td class="heading">Start Date</td>';
                        print '<td class="heading">Completion Date</td>';
                        print '<td class="heading">Odometer Readout</td>';
                        print '<td class="heading">Labor Charge</td>';
                        print '<td class="heading">Description</td>';
                        print '</tr>';

                        print "<tr>";
                        print "<td>" . $row_repair['VIN'] . "</td>";
                        print "<td>" . $row_repair['start_date'] . "</td>";
                        print "<td>" . $row_repair['completion_date'] . "</td>";
                        print "<td>" . $row_repair['odometer_readout'] . "</td>";
                        print "<td>" . $row_repair['labor_charge'] . "</td>";
                        print "<td>" . $row_repair['description'] . "</td>";
                        print "</tr>";
                    }
                    print '</table>';
                    print '</form>';
                    ?>
                </div>

                <div class='profile_section'>
                    <div class='subtitle'>Update Repair</div>
                    <form name="repair_update" action="update_repair.php" method="post">
                        <table>
                            <tr>
                                <td class="item_label">Update Labor Charge</td>
                                <td>
                                    <span class="error"><input type="number" min="0" step="any" name="labor_charge"/> *<?php echo $labor_charge_err;?></span>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" class="repair_button" name="labor_charge_update" value="Update" />
<!--                        <a href="javascript:repair_update.submit();" class="fancy_button" name="labor_charge_update">Update</a>-->
                    </form>
                </div>

                <div class='profile_section'>
                    <div class='subtitle'>Complete Repair</div>
                    <form name="complete_repair" action="update_repair.php" method="post">
                        <table>
                            <tr>
<!--                                <td class="item_label">Complete</td>-->
                                <td>
                                <input type="submit" class="repair_button" name="complete" value="Complete Repair" />
<!--                                    <a href="javascript:complete_repair.submit();" class="fancy_button">Complete</a>-->
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>


                <div class='profile_section'>
                    <div class='subtitle'>Parts Record</div>
                    <form name="part_record_display" action="update_repair.php" method="post">
                        <table>
                            <tr>
                                <td class="heading">Part Number</td>
                                <td class="heading">Vendor Name</td>
                                <td class="heading">Price</td>
                                <td class="heading">Quantity</td>
                            </tr>
                            <?php
                            if ($row_parts) {

                                while($row_parts) {
                                    print "<tr>";
                                    print "<td>" . $row_parts['part_number'] . "</td>";
                                    print "<td>" . $row_parts['vendor_name'] . "</td>";
                                    print "<td>" . $row_parts['price'] . "</td>";
                                    print "<td>" . $row_parts['quantity'] . "</td>";
                                    print "</tr>";
                                    $row_parts = mysqli_fetch_array($part_record, MYSQLI_ASSOC);
                                }
                            }
                            ?>
                        </table>
                    </form>
                </div>

                <div class='profile_section'>
                    <div class='subtitle'>Add Parts</div>
                    <form name="parts_update_form" action="update_repair.php" method="post">
                        <table>
                            <tr>
                                <td class="item_label">Part Number</td>
                                <td>
                                    <span class="error"><input type="text" name="part_number"/> *<?php echo $part_number_err; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Vendor Name</td>
                                <td>
                                    <span class="error"><input type="text" name="vendor_name"/> *<?php echo $vendor_name_err; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Price</td>
                                <td>
                                    <!-- Make sure input is not less than 0 and is float number-->
                                    <span class="error"><input type="number" step="any" min="0" name="price"/> *<?php echo $price_err; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Quantity</td>
                                <td>
                                    <!-- Make sure input is not less than 1 and is whole number-->
                                    <span class="error"><input type="number" min="1" step="1" name="quantity"/> *<?php echo $quantity_err; ?></span>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" class="repair_button" name="add_parts" value="Add" />
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
