<td class="item_label">Model Name</td>
<td>
    <select name="model_name">
        <option disabled selected>-- Select Model Name --</option>
        <?php
        include('lib/db_connection.php');
        $records = mysqli_query($db, "SELECT DISTINCT model_name From vehicle ORDER BY model_name");  // Use select query here

        while ($data = mysqli_fetch_array($records)) {
            echo "<option value='" . $data['model_name'] . "'>" . $data['model_name'] . "</option>";  // displaying data in option menu
        }
        ?>
    </select>
</td>