<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

    
<div class='confirmation'>
<h1>CTC Backcountry Cuisine Order</h1>

<?php

echo form_open('bcc/order'); 
echo htmlOrder($member, $order, $catalogue);
?>
<p>
If the above is correct, please click <em>Confirm</em> to place your
order. If not, click <em>Back</em> to alter it.<p>
<p>
<?php
echo form_submit('confirmOrder', 'Confirm');
echo form_submit('back', 'Back');
?>
</p>

</form>

</div>
<?php include("footer.php"); ?>
