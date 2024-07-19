<td class="item_label">Color</td>
<td>
<select name="color">
    <option disabled selected>-- Select Color --</option>
    <?php
    include('lib/db_connection.php');
    $records = mysqli_query($db, "SELECT DISTINCT color From vehicle_color");  // Use select query here

    while ($data = mysqli_fetch_array($records)) {
        echo "<option value='" . $data['color'] . "'>" . $data['color'] . "</option>";  // displaying data in option menu
    }
    ?>
</select>
</td>