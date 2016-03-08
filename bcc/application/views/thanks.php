<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

<div class='message'>
<h1>Thank you for your order</h1>
<?php if (!$ok) {
    echo "<p class='error'>*** Oops. An internal consistency check failed. " .
                   "Please check this order carefully and email ".
                   "<a href='mailto:richard.lobb@canterbury.ac.nz'>the webmaster</a> ".
                   "if you see anything wrong with it.</p>";
 } ?>
<p>
Your order has now been placed, as follows.
</p>
<hr class='spacer' />
<p>
<?php echo htmlOrder($member, $order, $catalogue); ?>
</p>
<hr />
<p>
<?php echo htmlPaymentInstructions($coordinator, $batch); ?>
</p>
<p>
<?php echo htmlAskCoordinator($coordinator); ?>
</p>
<hr class='spacer' />
<p>A copy of the above has been emailed to you.</p>
</div>
<?php include("footer.php"); ?>
