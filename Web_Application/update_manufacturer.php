<?php
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
    include("file_with_errors.php");
?>

<?php
include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}




//run the search repair query right away after loading update_repair.php
$query = "
    SELECT distinct manufacturer FROM manufacturer;
";
$result = mysqli_query($db, $query);
include('lib/show_queries.php');
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
$count = mysqli_num_rows($result);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get manufacturer update from form
    $manufacturer = mysqli_real_escape_string($db, $_POST['manufacturer']);

    // Check if manufacturer field is filled in
    if ((!isset($manufacturer) || trim($manufacturer) == '')) {
        array_push($error_msg, "Please enter manufacturer to update.");
    }

    // Update labor charge
    if (isset($_POST['labor_charge_update']) && !empty($labor_charge) && (trim($labor_charge) != '')) {
        // Data validation
        if (!is_numeric($labor_charge)) {
            array_push($error_msg, "Error: Invalid labor charge, not a number!");
        }
        // Business Validation
        if(!labor_charge_validation_passed($labor_charge, $row_repair['labor_charge'], $_SESSION['role_type'])) {
            array_push($error_msg, "Updated labor charge can not be less than previous value.");
        }
        // Update labor charge
        $should_update = is_numeric($labor_charge) && labor_charge_validation_passed($labor_charge, $row_repair['labor_charge'], $_SESSION['role_type']);
        if ($should_update) {
            $query = "UPDATE manufacturerSET manufacturer = '$manufacturer'WHERE manufacturer = '$current_manufacturer';";
        }
        $update_labor_charge = mysqli_query($db, $query);
        if (mysqli_affected_rows($db) == -1) {
            array_push($error_msg, "UPDATE ERROR:Failed to update labor charge ... <br>" . __FILE__ . " line:" . __LINE__);
        }
    }

    // Add parts
    // Check if add parts all fields are filled in
    if (isset($_POST['add_parts']) && (empty($part_number) || empty($vendor_name) || empty($price) || empty($quantity))) {
        array_push($error_msg, "Please enter all fields to add parts.");
    }
    // Data validation
    if (isset($_POST['add_parts']) && !empty($part_number) && !empty($vendor_name) && !empty($price) && !empty($quantity)) {
        // Data conversion
        $part_number = (string) $part_number;
        $vendor_name = (string) $vendor_name;
        $quantity = (int) $quantity;
        $price = (float) $price;

        // Validate if manufacuturer is duplicate
        if (duplicate_part_number($input_part_number, $VIN, $start_date, $db)) {
            array_push($error_msg, "INSERT ERROR: Duplicate manufacturer, please check. <br>" . __FILE__ . " line:" . __LINE__);
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

    // Complete repair on click
    if (isset($_POST['complete'])) {
        $query = "
            UPDATE Repair
            SET completion_date = CURDATE() 
            WHERE VIN = '$VIN' AND completion_date IS NULL;
        ";
    }
    $repair_complete = mysqli_query($db, $query);
    $result = mysqli_query($db, $query);
    include('lib/show_queries.php');
    if (isset($_POST['complete']) && mysqli_affected_rows($db) == -1) {
        array_push($error_msg, "UPDATE ERROR:Failed to complete repair ... <br>" . __FILE__ . " line:" . __LINE__);
    }

    // Display the updated repair in every update and complete
    $query = "
        SELECT VIN, start_date, completion_date, odometer_readout, labor_charge, description
        FROM Repair
        WHERE VIN = '$VIN' AND DATE(start_date) = DATE('$start_date');
    ";
    $repair_record = mysqli_query($db, $query);
    $result = mysqli_query($db, $query);
    include('lib/show_queries.php');
    $row_repair = mysqli_fetch_array($repair_record, MYSQLI_ASSOC);

    // Display added parts
    $query = "
        SELECT VIN, start_date, part_number, vendor_name, price, quantity
        FROM Part
        WHERE VIN = '$VIN' AND DATE(start_date) = DATE('$start_date');
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

?>



<?php include("lib/header.php"); ?>
<title>Update Manufacuturer</title>
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
                        print '<td class="heading">Odometer_readout</td>';
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
                                    <input type="number" min="0" step="any" name="labor_charge"/>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" class="repair_button" name="labor_charge_update" value="Update" />
                    </form>
                </div>

                <div class='profile_section'>
                    <div class='subtitle'>Complete Repair</div>
                    <form name="complete_repair" action="update_repair.php" method="post">
                        <table>
                            <tr>
                                <td>
                                        <input type="submit" class="repair_button" name="complete" value="Complete Repair" />
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
                                    <input type="text" name="part_number"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Vendor Name</td>
                                <td>
                                    <input type="text" name="vendor_name"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Price</td>
                                <td>
                                    <!-- Make sure input is not less than 0 and is float number-->
                                    <input type="number" step="any" min="0" name="price"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Quantity</td>
                                <td>
                                    <!-- Make sure input is not less than 1 and is whole number-->
                                    <input type="number" min="1" step="1" name="quantity"/>
                                </td>
                            </tr>
                        </table>

                        <input type="submit" class="repair_button" name="add_parts" value="Add" />
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