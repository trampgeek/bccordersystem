<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>


<div class='message'>
<h1>Order already placed</h1>
<p>
Your order has already been placed, as below, and cannot now be
changed via the website.
<?php echo htmlAskCoordinator($coordinator); ?>
</p>
<hr class="spacer" />

<?php echo htmlOrder($member, $order, $catalogue); ?>
</div>
<?php include("footer.php"); ?>
