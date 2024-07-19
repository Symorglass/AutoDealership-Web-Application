<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Initialize, especially initialize after refresh
    // $_POST = array();

    // Get attributes insert into Vehicle
    $userName = mysqli_real_escape_string($db, $_SESSION['username']);
    $vehicle_type = mysqli_real_escape_string($db, $_POST['vehicle_type']);
    $VIN = mysqli_real_escape_string($db, $_POST['VIN']);
    $model_name = mysqli_real_escape_string($db, $_POST['model_name']);
    $model_year = mysqli_real_escape_string($db, $_POST['model_year']);
    $invoice_price = mysqli_real_escape_string($db, $_POST['invoice_price']);
    $description = mysqli_real_escape_string($db, $_POST['description']);
    $manufacturer = mysqli_real_escape_string($db, $_POST['manufacturer']);
    $selected_colors = mysqli_real_escape_string($db, $_POST['selected_colors']);

    date_default_timezone_set("UTC");
    $added_date = date("Y-m-d H:i:s");

    //Get attributes insert into car
    $number_of_doors = mysqli_real_escape_string($db, $_POST['number_of_doors']);

    //Get attributes insert into Convertible
    $roof_type = mysqli_real_escape_string($db, $_POST['roof_type']);
    $back_seat_count = mysqli_real_escape_string($db, $_POST['back_seat_count']);

    //Get attributes insert into truck
    $cargo_cover_type = mysqli_real_escape_string($db, $_POST['cargo_cover_type']);
    $cargo_capacity = mysqli_real_escape_string($db, $_POST['cargo_capacity']);
    $number_of_rear_axles = mysqli_real_escape_string($db, $_POST['number_of_rear_axles']);

    //Get attributes insert into VAN_MiniVan
    $driver_side_door = mysqli_real_escape_string($db, $_POST['driver_side_door']);

    //Get attributes insert into SUV
    $drivetrain_type = mysqli_real_escape_string($db, $_POST['drivetrain_type']);
    $number_of_cupholders = mysqli_real_escape_string($db, $_POST['number_of_cupholders']);

    $continue = true;

    // Check the conditions
    if (!isset($vehicle_type)) {
        array_push($error_msg, "Please enter vehicle type.");
        $continue = false;
    }

    if (!isset($VIN)) {
        array_push($error_msg, "Please enter VIN.");
        $continue = false;
    }

    if (!isset($model_name)) {
        array_push($error_msg, "Please enter model name.");
        $continue = false;
    }

    if (!isset($model_year)) {
        array_push($error_msg, "Please enter model year.");
        $continue = false;
    }

    if (!isset($invoice_price)) {
        array_push($error_msg, "Please enter invoice price.");
        $continue = false;
    } else {
        if (!is_numeric($invoice_price)) {
            array_push($error_msg, "Invoices must be a float or integer.");
            $continue = false;
        }
    }

    if (!isset($manufacturer)) {
        array_push($error_msg, "Please enter manufacturer.");
        $continue = false;
    }

    if ($vehicle_type == "car" && !isset($number_of_doors)) {
        array_push($error_msg, "Please enter number of doors.");
        $continue = false;
    } else if ($vehicle_type == "car" && isset($number_of_doors)) {
        if (is_numeric($number_of_doors) and (int)$number_of_doors == $number_of_doors) {
            array_push($query_msg, "number_of_doors is $number_of_doors ");
        } else {
            array_push($error_msg, "Number of doors must be an integer.");
            $continue = false;
        }
    }

    if ($vehicle_type == "convertible" && !isset($roof_type)) {
        array_push($error_msg, "Please enter roof type.");
        $continue = false;
    }

    if ($vehicle_type == "convertible" && !isset($back_seat_count)) {
        array_push($error_msg, "Please enter back seat count.");
        $continue = false;
    } else if ($vehicle_type == "convertible" && isset($back_seat_count)) {
        if (is_numeric($back_seat_count) and (int)$back_seat_count == $back_seat_count) {
            array_push($query_msg, "back_seat_count is $back_seat_count ");
        } else {
            array_push($error_msg, "Back seat count must be an integer.");
            $continue = false;
        }

    }

    if ($vehicle_type == "truck" && !isset($cargo_capacity)) {
        array_push($error_msg, "Please enter cargo capacity.");
        $continue = false;
    } else if ($vehicle_type == "truck" && isset($cargo_capacity)) {
        if (!is_numeric($cargo_capacity)) {
            array_push($error_msg, "Cargo capacity must be a float or integer.");
            $continue = false;
        }
    }

    if ($vehicle_type == "truck" && !isset($number_of_rear_axles)) {
        array_push($error_msg, "Please enter number of rear axles.");
        $continue = false;
    } else if ($vehicle_type == "truck" && isset($number_of_rear_axles)) {
        if (is_numeric($number_of_rear_axles) and (int)$number_of_rear_axles == $number_of_rear_axles) {
            array_push($query_msg, "number_of_rear_axles is $number_of_rear_axles ");
        } else {
            array_push($error_msg, "Number of rear axles must be an integer.");
            $continue = false;
        }
    }

    if ($vehicle_type == "van_minivan" && !isset($driver_side_door)) {
        array_push($error_msg, "Please enter driver side door.");
        $continue = false;
    }

    if ($vehicle_type == "SUV" && !isset($drivetrain_type)) {
        array_push($error_msg, "Please enter drivetrain type.");
        $continue = false;
    }

    if ($vehicle_type == "SUV" && !isset($number_of_cupholders)) {
        array_push($error_msg, "Please enter number of cupholders.");
        $continue = false;
    } else if ($vehicle_type == "SUV" && isset($number_of_cupholders)) {
        if (is_numeric($number_of_cupholders) and (int)$number_of_cupholders == $number_of_cupholders) {
            array_push($query_msg, "number_of_cupholders is $number_of_cupholders ");
        } else {
            array_push($error_msg, "Number of cupholders must be an integer.");
            $continue = false;
        }
    }

    //check the conditions for model year
    $date_input = (int)$model_year;
    $max_year = (int)date("Y") + 1;

    if (($date_input > $max_year) || strlen($model_year) != 4) {
        array_push($error_msg, "Model year must be less than current year +1, and it must be a 4-digit integer.");
        $continue = false;
    }

    if ($continue) {
        // execute the insert
        $invoice_price_float = (float)$invoice_price;
        $insert_vehicle_query = "INSERT INTO VEHICLE " .
            "(VIN, model_name, model_year, invoice_price, added_date, description, manufacturer, userName) VALUES " .
            "('$VIN', '$model_name', '$model_year', '$invoice_price_float', '$added_date', '$description', '$manufacturer', '$userName'); ";

        $result = mysqli_query($db, $insert_vehicle_query);
        include('lib/show_queries.php');


        if (mysqli_affected_rows($db) == -1) {
            array_push($error_msg, "Vehicle insertion failed, please check!" . NEWLINE);
            $continue = false;
        }
    }

    if ($continue) {
        // insert vehicle_color
        array_push($query_msg, "selected_colors", $selected_colors);
        $colors = explode(",", $selected_colors);

        foreach ($colors as $key => $value) {
            $insert_vehicle_color_query = "INSERT INTO VEHICLE_COLOR " .
                "(VIN, color) VALUES " .
                "('$VIN', '$value'); ";

            $result = mysqli_query($db, $insert_vehicle_color_query);
            include('lib/show_queries.php');


            if (mysqli_affected_rows($db) == -1) {
                array_push($error_msg, "Vehicle_Color insertion for color '$value' failed, please check!" . NEWLINE);
                $continue = false;
            }
        }
    }

    if ($continue) {
        // depends on the vehicle type, insert to respective tables
        // insert into car
        $insert_type_query = '';
        if ($vehicle_type == "car") {
            $insert_type_query = $insert_type_query . "INSERT INTO CAR " .
                "(VIN, number_of_doors) VALUES " .
                "('$VIN', '$number_of_doors'); ";
        }

        //insert into convertible
        if ($vehicle_type == "convertible") {
            $insert_type_query = "INSERT INTO CONVERTIBLE " .
                "(VIN, roof_type, back_seat_count) VALUES " .
                "('$VIN', '$roof_type', '$back_seat_count'); ";
        }

        //insert into truck
        if ($vehicle_type == "truck") {
            if (isset($cargo_cover_type)) {
                $insert_type_query = "INSERT INTO TRUCK " .
                    "(VIN, cargo_capacity, cargo_cover_type, number_of_rear_axles) VALUES " .
                    "('$VIN', '$cargo_capacity', '$cargo_cover_type', '$number_of_rear_axles'); ";
            } else {
                $insert_type_query = "INSERT INTO TRUCK " .
                    "(VIN, cargo_capacity, number_of_rear_axles) VALUES " .
                    "('$VIN', '$cargo_capacity', '$number_of_rear_axles'); ";
            }
        }

        //insert into van minivan
        if ($vehicle_type == "van_minivan") {
            $driver_side_door_int = (int)$driver_side_door;
            $insert_type_query = "INSERT INTO VAN_MINIVAN " .
                "(VIN, driver_side_backdoor) VALUES " .
                "('$VIN', '$driver_side_door_int'); ";
        }

        //insert into SUV
        if ($vehicle_type == "SUV") {
            $insert_type_query = "INSERT INTO SUV " .
                "(VIN, drivetrain_type, number_of_cupholders) VALUES " .
                "('$VIN', '$drivetrain_type', '$number_of_cupholders'); ";
        }

        if (!empty($insert_type_query)) {
            $result = mysqli_query($db, $insert_type_query);
            include('lib/show_queries.php');


            if (mysqli_affected_rows($db) == -1) {
                array_push($error_msg, "$vehicle_type insertion failed, please check!" . NEWLINE);
                $continue = false;
            }
        } else {
            $continue = false;
        }
    }

    if ($continue) {
        header(REFRESH_TIME . "url=view_vehicle_detail.php?vin=$VIN");
    }
} //end of if($_POST)
?>

<?php include("lib/header.php"); ?>
<title>Add Vehicle</title>
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
            <div class="title_name"><?php print "Add vehicle Form"; ?></div>
            <div class="features">

                <div class="profile_section">
                    <div class="subtitle">Add New Vehicle</div>

                    <form name="vehicleform" action="add_vehicle.php" method="post">
                        <table>
                            <!-- general attributes -->
                            <tr>
                                <td class="item_label">VIN</td>
                                <td>
                                    <input type="text" name="VIN"
                                           value="<?php
                                           echo $VIN;
                                           if ($VIN) {
                                               print $row['VIN'];
                                           } ?>"/>
                                </td>
                            </tr>

                            <tr>

                                <?php include('lib/selections/all_color_multiple_selection_dropdown.php'); ?>

                            </tr>

                            <tr>
                                <?php include('lib/selections/vehicle_type_dropdown.php'); ?>
                            </tr>
                            <tr>
                                <td class="item_label">Manufacturer</td>
                                <td>
                                    <select name="manufacturer">
                                        <option disabled selected>-- Select Manufacturer --</option>
                                        <?php
                                        echo $manufacturer;
                                        include('lib/db_connection.php');
                                        $records = mysqli_query($db, "SELECT DISTINCT manufacturer From Manufacturer");  // Use select query here

                                        while ($data = mysqli_fetch_array($records)) {
                                            echo "<option value='" . $data['manufacturer'] . "'>" . $data['manufacturer'] . "</option>";  // displaying data in option menu
                                        } ?>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <td class="item_label">Model Name</td>
                                <td>
                                    <input type="text" name="model_name"
                                           value="<?php
                                           echo $model_name;
                                           if ($row['model_name']) {
                                               print $row['model_name'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Model Year</td>
                                <td>
                                    <input type="text" name="model_year"
                                           value="<?php
                                           echo $model_year;
                                           if ($row['model_year']) {
                                               print $row['model_year'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Invoice Price (in dollars and cents)</td>
                                <td>
                                    <input type="number" name="invoice_price"
                                           value="<?php
                                           echo $invoice_price;
                                           if ($row['invoice_price']) {
                                               print $row['invoice_price'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Description</td>
                                <td>
                                    <input type="text" name="description"
                                           value="<?php
                                           echo $description;
                                           if ($row['description']) {
                                               print $row['description'];
                                           } ?>"/>
                                </td>
                            </tr>

                            <!-- type specific attributes -->
                            <tr></tr>
                            <tr></tr>
                            <tr></tr>
                            <tr>
                                <td style="white-space:nowrap">
                                    <b>------For vehicle type Car------</b>
                                </td>
                            </tr>


                            <tr>
                                <td class="item_label">Number of doors (Integer only)</td>
                                <td>
                                    <input type="number" step="1"
                                           name="number_of_doors"
                                           value="<?php

                                           echo $number_of_doors;
                                           if ($row['number_of_doors']) {
                                               print $row['number_of_doors'];
                                           } ?>"/>
                                </td>
                            </tr>

                            <tr></tr>
                            <tr></tr>
                            <tr></tr>
                            <tr>
                                <td style="white-space:nowrap">
                                    <b>------For vehicle type Convertible------</b>
                                </td>
                                <td></td>
                            </tr>

                            <tr>
                                <td class="item_label">Roof Type</td>
                                <td>
                                    <input type="text" name="roof_type"
                                           value="<?php if ($row['roof_type']) {
                                               print $row['roof_type'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Back Seat Count (Integer only)</td>
                                <td>
                                    <input type="number" step="1"
                                           name="back_seat_count"
                                           value="<?php if ($row['back_seat_count']) {
                                               print $row['back_seat_count'];
                                           } ?>"/>
                                </td>
                            </tr>

                            <tr></tr>
                            <tr></tr>
                            <tr></tr>
                            <tr>
                                <td style="white-space:nowrap">
                                    <b>------For vehicle type Truck------</b>
                                </td>
                                <td></td>
                            </tr>


                            <tr>
                                <td class="item_label">Cargo Capacity (In Tons, can have decimal)</td>
                                <td>
                                    <input type="number"
                                           name="cargo_capacity"
                                           value="<?php if ($row['cargo_capacity']) {
                                               print $row['cargo_capacity'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Cargo Cover Type</td>
                                <td>
                                    <input type="text" name="cargo_cover_type"
                                           value="<?php if ($row['cargo_cover_type']) {
                                               print $row['cargo_cover_type'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Number of Rear Axis (Integer only)</td>
                                <td>
                                    <input type="number" step="1"
                                           name="number_of_rear_axles"
                                           value="<?php if ($row['number_of_rear_axles']) {
                                               print $row['number_of_rear_axles'];
                                           } ?>"/>
                                </td>
                            </tr>

                            <tr></tr>
                            <tr></tr>
                            <tr></tr>
                            <tr>
                                <td style="white-space:nowrap">
                                    <b>------For vehicle type Van/Minivan------</b>
                                </td>
                                <td></td>
                            </tr>

                            <tr>
                                <td class="item_label">Has Driver Side Door (1 for True, 0 for False)</td>
                                <td>
                                    <input type="number" step="1" min="0" max="1"
                                           name="driver_side_door"
                                           value="<?php if ($row['driver_side_door']) {
                                               print $row['driver_side_door'];
                                           } ?>"/>
                                </td>
                            </tr>

                            <tr></tr>
                            <tr></tr>
                            <tr></tr>
                            <tr>
                                <td style="white-space:nowrap">
                                    <b>------For vehicle type SUV------</b>
                                </td>
                                <td></td>
                            </tr>

                            <tr>
                                <td class="item_label">Drivetrain Type</td>
                                <td>
                                    <input type="text" name="drivetrain_type"
                                           value="<?php if ($row['drivetrain_type']) {
                                               print $row['drivetrain_type'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">Number of Cupholders (Integer only)</td>
                                <td>
                                    <input type="number" step="1"
                                           name="number_of_cupholders"
                                           value="<?php if ($row['number_of_cupholders']) {
                                               print $row['number_of_cupholders'];
                                           } ?>"/>
                                </td>
                            </tr>

                        </table>
                        <td><a href='add_vehicle.php' class='fancy_button'>Reset</a></td>
                        <a href="javascript:vehicleform.submit();" class="fancy_button">Add</a>

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