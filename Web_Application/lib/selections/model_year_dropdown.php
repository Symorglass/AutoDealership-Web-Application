<td class="item_label">Model Year</td>
<td>
    <select name="model_year">
        <option disabled selected>-- Select Model Year --</option>
        <?php
        include('lib/db_connection.php');
        $records = mysqli_query($db, "SELECT DISTINCT model_year From vehicle ORDER BY model_year");  // Use select query here

        while ($data = mysqli_fetch_array($records)) {
            echo "<option value='" . $data['model_year'] . "'>" . $data['model_year'] . "</option>";  // displaying data in option menu
        }
        ?>
    </select>
</td>