<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$get_current_query = "SELECT * from `manufacturer`;";
$get_current_result = mysqli_query($db, $get_current_query) or die(mysqli_error($db));

if ($get_current_result->num_rows > 0) {
    array_push($error_msg, "current manufacturers retrieved.");
}
else {
    array_push($error_msg, "No manufacturers available.");
}

// echo "<table>"; // start a table tag in the HTML

// while($row = mysql_fetch_array($result)){   //Creates a loop to loop through results
// echo "<tr><td>" . $row['name'] . "</td><td>" . $row['age'] . "</td></tr>";  //$row['index'] the index here is a field name
// }

// echo "</table>"; //Close the table in HTML



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get manufacturer from form
    $new_manufacturer = mysqli_real_escape_string($db, $_POST['new_manufacturer']);
    $current_manufacturer = mysqli_real_escape_string($db, $_POST['current_manufacturer']);

    // Check update 
    if (empty($current_manufacturer) && empty($new_manufacturer)) {
        array_push($error_msg, "Please enter either current or new manufacturer.");
        // echo '<script>alert("current manufacturer and new manufacturer empty")</script>';
    }
    if (!empty($current_manufacturer) && empty($new_manufacturer)) {
        array_push($error_msg, "Please enter new manufacturer to update.");
        // echo '<script>alert("new manufacturer empty")</script>';
    }


    if (!empty($current_manufacturer) && !empty($new_manufacturer)) {
        $check_exist_query = "SELECT `manufacturer` from `manufacturer` where `manufacturer` = '$current_manufacturer';";
        $exist_result = mysqli_query($db, $check_exist_query) or die(mysqli_error($db));
        if ($exist_result->num_rows > 0) {
            $update_manufacturer_query = "UPDATE `manufacturer` SET `manufacturer` = '$new_manufacturer' WHERE `manufacturer`.`manufacturer` = '$current_manufacturer';";
            $result = mysqli_query($db, $update_manufacturer_query) or die(mysqli_error($db));
            if (mysqli_affected_rows($db) == -1) {
                array_push($error_msg, "Manufacturer update failed, please check!" . NEWLINE);
                // echo '<script>alert("'.$new_manufacturer.'")</script>';
            }
            else {
                // echo '<script>alert("Update manufacturer successfully!")</script>';
                array_push($error_msg, "Manufacturer updated successfully!" . NEWLINE);
                header("Refresh:0");
            }
        }
        else {
            array_push($error_msg, "No current manufacturer named " . $current_manufacturer . " found in database" . NEWLINE);
        }
    }


    if (empty($current_manufacturer) && !empty($new_manufacturer)) {
        $insert_manufacturer_query = "INSERT INTO manufacturer " .
        "(manufacturer) VALUES" .
        "('$new_manufacturer') ;";
        $result = mysqli_query($db, $insert_manufacturer_query);
        if (mysqli_affected_rows($db) == -1) {
            array_push($error_msg, "Manufacturer insertion failed, please check!" . NEWLINE);
        }
        else {
            // echo '<script>alert("Add new manufacturer successfully!")</script>';
            array_push($error_msg, "Add new manufacturer successfully!" . NEWLINE);
            header("Refresh:0");
        }
    }

}  //end of if($_POST)



?>

<?php include("lib/header.php"); ?>
<title>Add Manufacturer</title>
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
            <div class="title_name"><?php print "Add/Update Manufacturer Form"; ?></div>
            <div class="features">

                <div class='profile_section'>
                    <div class='subtitle'>Current Manufacturer</div>
                    <table>
                        <tr>
                            <td class='heading'>Manufacturer</td>
                        </tr>
                        <?php
                        if (isset($get_current_result)){
                            while($row = $get_current_result->fetch_assoc()) {
                                print "<tr>";
                                print "<td>{$row['manufacturer']}</td>";
                                print "</tr>";
                            }
                        } ?>
                    </table>
                </div>

                <div class="profile_section">
                    <div class="subtitle">Update Manufacturer</div>
                    
                    <form name="updatemanufacturerform" = "add_manufacturer.php" method="post">
                        <table>
                            <tr>
                                <td class="item_label">Current Manufacturer</td>
                                <td>
                                    <input type="text" name="current_manufacturer"
                                           value="<?php if ($row['current_manufacturer']) {
                                               print $row['current_manufacturer'];
                                           } ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="item_label">New Manufacturer</td>
                                <td>
                                    <input type="text" name="new_manufacturer"
                                           value="<?php if ($row['new_manufacturer']) {
                                               print $row['new_manufacturer'];
                                           } ?>"/>
                                </td>
                            </tr>

                        </table>

                        <a href="javascript:updatemanufacturerform.submit();" class="fancy_button">Update</a>
                        <a href="javascript:updatemanufacturerform.submit();" class="fancy_button">Add</a> </a>

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