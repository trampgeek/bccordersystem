<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// View of a BackcountryFoods order form.
// Input value: $order, the current order (see model class bccorder).


/** Generate a select box for the given product id, for which the
 *  current quantity ordered is $qty.
 */
function selectBox($prodId, $qty) {
    $class = ($qty > 0 ? 'qtynonzero' : 'qty');
    $result = "<select class='$class' name='$prodId' onchange='qtyChanged(this)'>";
    for ($i = 0; $i < 40; $i++) {
        $optVal = $i == 0 ? '-' : $i;
        $selected = ($qty == $i ? " selected='selected'" : '');
        $result .= "<option value='$i'$selected>$optVal</option>\n";
    }
    $result .= "</select>";
    return $result;

}

// Display a BackcountryFoods order form.

function displayOrderForm($order, $catalogue)
{
    foreach ($catalogue->products as $category=>$products) {
        $cat = htmlspecialchars($category);
        echo "<h2>$cat</h2>\n<table class='products'>";
        foreach ($products as $name=>$serves) {
            echo '<tr><td>' . htmlspecialchars($name) . '</td>';
            foreach ($serves as $product) {
                $dispPrice = sprintf('%.2f', $product->price * GST_FACTOR);
                echo "<td class='prod'>{$product->serve} @ \$$dispPrice ";
                $qty = $order->quantity($product->id);
                echo selectBox($product->id, $qty) . '</td>';
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
}

?>


<script type='text/javascript'>
function qtyChanged(selectBox) {
    selectBox.className = selectBox.value == 0 ? 'qty' : 'qtynonzero';
}

function cancelOrder() {
    var cancelling = confirm("Click OK to abort the order, Cancel to return to the form.");
    if (cancelling) {
        var form = document.getElementById('orderform');
        form.aborted.value = '1';
        form.submit();
    }
    return cancelling;
}

</script>

<div class='bccorder'>
<h1>CTC Backcountry Cuisine Order Form</h1>

<p>
Welcome to the CTC Backcountry Cuisine Order form. If you&rsquo;re not
familiar with this, please <?php echo anchor('bcc/instructions', 'read the instructions'); ?> first.
</p>
<p>Order closes: 5pm
<?php
    $closeDateString = date('l j F', strtotime($batch->closeDate));
    echo $closeDateString;
?>
</p>
<h2>Your personal details</h2>
<p class="indent">
Name:  <?php echo $member->fullName();?><br />
Phone: <?php echo $member->homePhone;?><br />
Email: <?php echo $member->primaryEmail;?>
</p><p class='indent'><b>NOTE:</b> If the above details are incorrect please do not proceed with this
order. Instead, please update your membership details via the User Details menu
item at the
<a href="http://www.ctc.org.nz">CTC home page</a>.
Then return to this form to submit your order.
</p>
<hr class='spacer' />
<?php
if ($errorMessage != '') {
    echo "<p class='error'>$errorMessage</p>\n";
}
echo form_open('bcc/order', array('id'=>'orderform'), array('aborted'=>'0'));
?>
<p class='maininstruct'>Use the drop-down menus to select the quantities of
any items you wish to order then click the <em>Submit</em> button at the
bottom. </p>
<p>+ and * beside the product names denote gluten free and vegetarian
respectively.</p>
<p><em>NB: Prices include GST. However there will be a freight charge
        of 2.5% added in at the end.</em></p>

<?php displayOrderForm($order, $catalogue); ?>
<hr />
<p>+ and * beside the product names denote gluten free and vegetarian
respectively.</p>
<p>The above prices include GST but there will be an additional charge of 2.5%
of the total order cost to cover freight charges.</p>
<p>
<?php echo form_submit('submitOrder', 'Submit'),
           form_button('abortOrder', 'Abort order', 'class="abort" onclick="cancelOrder()"'); ?>
</p>
</form>
</div>
<?php include("footer.php"); ?>
