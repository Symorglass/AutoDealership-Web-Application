<td class="item_label">Manufacturer</td>
<td>
<select name="manufacturer">
    <option disabled selected>-- Select Manufacturer --</option>
    <?php
    include('lib/db_connection.php');
    $records = mysqli_query($db, "SELECT DISTINCT manufacturer From vehicle");  // Use select query here

    while ($data = mysqli_fetch_array($records)) {
        echo "<option value='" . $data['manufacturer'] . "'>" . $data['manufacturer'] . "</option>";  // displaying data in option menu
    }
    ?>
</select>
</td>