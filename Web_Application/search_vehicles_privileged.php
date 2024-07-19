<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
    header('Location: search_vehicles.php');
    exit();
}

$query = "
    SELECT first_name, last_name " .
    "FROM loggedinuser " .
    "WHERE loggedinuser.userName = '{$_SESSION['username']}'
";

$result = mysqli_query($db, $query);
include('lib/show_queries.php');

if (!is_bool($result) && (mysqli_num_rows($result) > 0)) {
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $user_name = $row['first_name'] . " " . $row['last_name'];
} else {
    $user_name = ' Visitor';
}

//run the search query right away after loading search_vehicles_privileged.php
$query = "
    SELECT COUNT(v.VIN) AS count
    FROM Vehicle v
    WHERE v.VIN NOT IN (
        SELECT s.VIN
        FROM SalesTransaction s
    )
";


$result = mysqli_query($db, $query);
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
$for_sale_count = $row['count'];
include('lib/show_queries.php');

if (mysqli_affected_rows($db) == -1) {
    array_push($error_msg, "SELECT ERROR:Failed to find vehicles ... <br>" . __FILE__ . " line:" . __LINE__);
}

/* if form was submitted, then execute query to search for vehicles */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $sold_status = mysqli_real_escape_string($db, $_POST['sold_status']);
    $VIN = mysqli_real_escape_string($db, $_POST['VIN']);
    $vehicle_type = mysqli_real_escape_string($db, $_POST['vehicle_type']);
    $manufacturer = mysqli_real_escape_string($db, $_POST['manufacturer']);
    $model_year = mysqli_real_escape_string($db, $_POST['model_year']);
    $model_name = mysqli_real_escape_string($db, $_POST['model_name']);
    $color = mysqli_real_escape_string($db, $_POST['color']);
    $min_list_price = mysqli_real_escape_string($db, $_POST['min_list_price']);
    $max_list_price = mysqli_real_escape_string($db, $_POST['max_list_price']);
    $keyword = mysqli_real_escape_string($db, $_POST['keyword']);
    $keyword_is_case_sensitive = mysqli_real_escape_string($db, $_POST['keyword_is_case_sensitive']);

    $query = "
        WITH vehicle_car AS(
            SELECT VIN, 'Car' AS vehicle_type
            FROM Car
        ),
        vehicle_convertible AS(
            SELECT VIN, 'Convertible' AS vehicle_type
            FROM Convertible
        ), 
        vehicle_truck AS(
            SELECT VIN, 'Truck' AS vehicle_type
            FROM Truck
        ), 
        vehicle_van_minivan AS(
            SELECT VIN, 'VAN_MiniVAN' AS vehicle_type
            FROM VAN_MiniVAN
        ), 
        vehicle_suv AS(
            SELECT VIN, 'SUV' AS vehicle_type
            FROM SUV
        ), 
        vehicle_u AS (
            SELECT VIN, vehicle_type FROM vehicle_car
            UNION
            SELECT VIN, vehicle_type FROM vehicle_convertible
            UNION
            SELECT VIN, vehicle_type FROM vehicle_truck
            UNION
            SELECT VIN, vehicle_type FROM vehicle_van_minivan
            UNION
            SELECT VIN, vehicle_type FROM vehicle_suv
        ),
        ";
    if ($sold_status == 'both') {
        $query = $query . "
            vins AS (
                SELECT v.VIN
                FROM vehicle_u v
            ),
        ";
    } else if ($sold_status == 'sold') {
        $query = $query . "
            vins AS (
                SELECT v.VIN
                FROM vehicle_u v
                WHERE v.VIN IN(
                    SELECT s.VIN
                    FROM SalesTransaction s
                )
            ),
        ";
    } else {
        $query = $query . "
            vins AS (
                SELECT v.VIN
                FROM vehicle_u v
                WHERE v.VIN NOT IN (
                    SELECT s.VIN
                    FROM SalesTransaction s
                )
            ),
        ";
    }


    $query = $query . "
        vehicle_info AS (
            SELECT v.VIN, vu.vehicle_type, v.manufacturer, v.model_year, v.invoice_price * 1.25 AS list_price, v.description, v.model_name, GROUP_CONCAT(vc.color) AS color
            FROM vehicle_u vu INNER JOIN Vehicle v INNER JOIN Vehicle_Color vc INNER JOIN vins uv
            ON vu.VIN = v.VIN AND vu.VIN=vc.VIN AND vu.VIN = uv.VIN
            GROUP BY v.VIN
        ),
        result AS(
        ";

        if (!empty($keyword) && $keyword_is_case_sensitive == 'sensitive') {
            $query = $query . "
                SELECT vi.VIN, vi.vehicle_type, vi.model_year, vi.manufacturer, vi.model_name, vi.list_price, vi.color, vi.description, vi.description LIKE BINARY '%$keyword%' AS description_match_indicator
            ";
        } else if (!empty($keyword) && $keyword_is_case_sensitive == 'insensitive') {
            $query = $query . "
                SELECT vi.VIN, vi.vehicle_type, vi.model_year, vi.manufacturer, vi.model_name, vi.list_price, vi.color, vi.description, vi.description LIKE '%$keyword%' AS description_match_indicator
            ";
        } else {
            $query = $query . "
                SELECT vi.VIN, vi.vehicle_type, vi.model_year, vi.manufacturer, vi.model_name, vi.list_price, vi.color, vi.description, 0 AS description_match_indicator
            ";
        }

    $query = $query . "
        FROM vehicle_info vi
        WHERE (0=0";

//Conditions

    if (!empty($VIN) or !empty($vehicle_type) or !empty($manufacturer) or !empty($model_year) or !empty($model_name) or !empty($color) or !empty($min_list_price) or !empty($max_list_price) or ((int)$max_list_price == 0) or !empty($keyword)) {
        if (!empty($VIN)) {
            $query = $query . " 
                AND ( vi.VIN = '$VIN')
            ";
        }
        if (!empty($vehicle_type)) {
            $query = $query . "  
                AND (vi.vehicle_type = '$vehicle_type'  )
            ";
        }
        if (!empty($manufacturer)) {
            $query = $query . "  
                AND ( vi.manufacturer = '$manufacturer' )
            ";
        }
        if (!empty($model_year)) {
            $query = $query . "  
                AND (vi.model_year = '$model_year' )
            ";
        }
        if (!empty($model_name)) {
            $query = $query . "  
                AND (vi.model_name = '$model_name' )
            ";
        }
        if (!empty($color)) {
            $query = $query . "  
                AND (vi.color LIKE '%$color%')
            ";
        }
        if (!empty($min_list_price)) {
            $query = $query . "  
                AND (vi.list_price > '$min_list_price')
            ";
        }
        if (!empty($max_list_price) or ((int)$max_list_price == 0)) {
            $query = $query . "  
                AND (vi.list_price < '$max_list_price' )
            ";
        }
        if (!empty($keyword) && $keyword_is_case_sensitive == 'sensitive') {
            $query = $query . "  
                AND (vi.manufacturer LIKE BINARY '%$keyword%') OR (vi.model_year LIKE BINARY '%$keyword%' ) OR (vi.model_name LIKE BINARY '%$keyword%' ) OR (vi.description LIKE BINARY '%$keyword%')
            ";
        }
        if (!empty($keyword) && $keyword_is_case_sensitive == 'insensitive') {
            $query = $query . "  
                AND (vi.manufacturer LIKE '%$keyword%') OR (vi.model_year LIKE '%$keyword%' ) OR (vi.model_name LIKE '%$keyword%' ) OR (vi.description LIKE '%$keyword%')
            ";
        }
    }

    $query = $query . "))";

    $query = $query . "
        SELECT r.VIN, r.vehicle_type, r.model_year, r.manufacturer, r.model_name, r.list_price, r.color, r.description, IF(r.description_match_indicator=1, 'X', ' ') AS description_match_indicator
        FROM result r
        ORDER BY r.VIN ASC; 
    ";

    $result = mysqli_query($db, $query);

    include('lib/show_queries.php');

    $result_count = mysqli_num_rows($result);
    if ($result_count == 0) {
        array_push($error_msg, "Sorry, it looks like we don’t have that in stock!" . NEWLINE);
    }

}
?>

<?php include("lib/header.php"); ?>
<title>Vehicles Search - Privileged </title>
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
            <div class="title_name"><?php print "Hi!" . $user_name; ?></div>
            <div class="title_name"><?php print "Total number of vehicles for sale: " . $for_sale_count; ?></div>
            <div class="features">

                <div class="profile_section">
                    <div class="subtitle">Search for Vehicles</div>

                    <form name="searchform" action="search_vehicles_privileged.php" method="POST">
                        <table>
                            <tr>
                                <td class="item_label">VIN</td>
                                <td><input type="text" name="VIN"/></td>
                            </tr>
                            <tr>
                                <?php include('lib/selections/vehicle_type_dropdown.php'); ?>
                            </tr>
                            <tr>
                                <?php include('lib/selections/manufacturer_dropdown.php'); ?>
                            </tr>
                            <tr>
                                <?php include('lib/selections/model_year_dropdown.php'); ?>
                            </tr>
                            <tr>
                                <?php include('lib/selections/model_name_dropdown.php'); ?>
                            </tr>
                            <tr>
                                <?php include('lib/selections/color_dropdown.php'); ?>
                            </tr>
                            <tr>
                                <td class="item_label">Min List Price</td>
                                <td><input type="number" name="min_list_price"/></td>
                            </tr>
                            <tr>
                                <td class="item_label">Max List Price</td>
                                <td><input type="number" name="max_list_price"/></td>
                            </tr>
                            <tr>
                                <td class="item_label">Keyword</td>
                                <td><input type="text" name="keyword"/></td>
                            </tr>
                            <tr>
                                <?php include('lib/selections/keyword_case_sensitive_options.php'); ?>
                            </tr>
                            <tr>
                                <?php if (($_SESSION['role_type']) == "Manager") include('lib/selections/sold_status_options.php'); ?>
                                <?php if (($_SESSION['role_type']) == "Owner") include('lib/selections/sold_status_options.php'); ?>
                            </tr>

                        </table>

                        <a href="javascript:searchform.submit();" class="fancy_button">Search</a>
                    </form>
                </div>

                <div class='profile_section'>
                    <div class='subtitle'>Search Results</div>
                    <table>
                        <tr>
                            <td class='heading'>VIN</td>
                            <td class='heading'>Vehicle Type</td>
                            <td class='heading'>Model year</td>
                            <td class='heading'>Manufacturer</td>
                            <td class='heading'>Model Name</td>
                            <td class='heading'>List Price</td>
                            <td class='heading'>Color(s)</td>
                            <td class='heading'>Description Match Indicator</td>
                        </tr>
                        <?php
                        if (isset($result)) {
                            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                $VIN = urlencode($row['VIN']);
                                print "<tr>";
                                print "<td><a href='view_vehicle_detail.php?vin=$VIN'>{$row['VIN']}</a></td>";
                                print "<td>{$row['vehicle_type']}</td>";
                                print "<td>{$row['model_year']}</td>";
                                print "<td>{$row['manufacturer']}</td>";
                                print "<td>{$row['model_name']}</td>";
                                print "<td>{$row['list_price']}</td>";
                                print "<td>{$row['color']}</td>";
                                print "<td>{$row['description_match_indicator']}</td>";
                                print "</tr>";
                            }
                        } ?>
                    </table>
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