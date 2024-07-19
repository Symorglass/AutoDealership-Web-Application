<td class="item_label">Colors</td>
<td>
    <form name="color_form" action="" method="post">

        <select name="color_selected_list[]" multiple>
            <option value="" disabled selected>-- Select Color --</option>
            <?php
                include('lib/db_connection.php');
                $records = mysqli_query($db, "SELECT DISTINCT color From color");  // Use select query here

                while ($data = mysqli_fetch_array($records)) {
                    echo "<option value='" . $data['color'] . "'>" . $data['color'] . "</option>";  // displaying data in option menu
                }

            ?>
        </select>


        <input type="submit" name="internal_selected_color" value="Choose colors">

    </form>
</td>


<td class="item_label">Selected Colors</td>
<td>
    <input type="text" name="selected_colors" readonly
           value="<?php
                $list_string = "";
                if (isset($_POST['internal_selected_color'])) {
                    if (!empty($_POST['color_selected_list'])) {
                        foreach ($_POST['color_selected_list'] as $selected) {
                            $list_string = $list_string . ',' . $selected;
                        }
                    } else {
                        echo 'Please select color(s)';
                    }
                }


                // remove extra ,
                $arr = explode(',', $list_string);
                $arr = array_filter($arr);
                $out = implode(',', $arr);

                $selected_colors_string = trim($out);
                print_r($selected_colors_string)

           ?>"/>
</td>
