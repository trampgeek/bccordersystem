<h2>Horrible hacky pricelist upload facility</h2>
<p>This page is for administrators to upload the weird
Backcountry Cuisine pricelist, as supplied by
Analise Petas (Analise@backcountrycuisine.co.nz).
It is a csv file that changes with the day of the month, the wind direction and
the phase of the moon. It almost certainly needs some manual massaging before
being uploaded here.
</p><p>
Key attributes of the (expected) .csv file:
<ol>
<li>All rows prior to a row with 'Main Course' in the first column can be ignored</li>
<li>The spreadsheet is in 'Category' blocks with at least one blank
    line between blocks. All food items in a particular category
    appear with the category block.</li>
<li>The first line of a category block specifies the category in the first column.</li>
<li>Category blocks take one of the following forms:
    <ul>
    <li>There are multiple columns for different serving sizes.
        The second column of the first row starts with 'Single serve' or '1 serve'
        Serve prices are in the second row.
        The product name is in the first column.
        The Product codes are in the cells of the main body.</li>
    <li>A variant of (a) where the header row contains the price in
        the second column. There is no serving size for such blocks.</li>
    <li>All other blocks, which have neither a price nor a serve size in the
        second column of the header row. For these, the product name is in
        the first column. The price and product code are either in
        the second and third columns respectively or are space separated in
        the second column.
    </li>
    </ul>
    </li>
</ol>

</p>
<?php
    if ($error) {
        echo "<p class='error'>\n**** $error ****\n</p>\n";
        if (count($items) > 0) {
            echo "<p class='error'>Processed items, up until error:</p>";
            printItemTable($items, 'processeditemstable error');
        }
    }
?>
<form action="upload" method="post" enctype="multipart/form-data">
    <label for="file">CSV filename:</label>
    <input type="file" name="file" id="file" />
    <br />
    <input type="submit" name="submit" value="Submit" />
</form>

