<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$closeDateString = date('l j F', strtotime($batch->closeDate));
$firstName = $coordinator->firstName;
$name = $coordinator->firstName . ' ' . $coordinator->lastName;
?>

<div class='instructions'>
<h1>INSTRUCTIONS</h1>

<p>Welcome to the CTC Backcountry Cuisine web order system. For information
about Backcountry Cuisine products, see
<a href="http://www.backcountrycuisine.co.nz/">
their website</a>.</p>
<p>If you wish to participate in the next bulk order, please submit the
order form
before 5pm on <?php echo $closeDateString;?>.
</p>
<p>This order is being
coordinated by <?php echo "$name";?>,
ph <?php echo $coordinator->homePhone;?>,
email <?php echo $coordinator->primaryEmail;?>.</p>

<p>To make an order you will use drop-down menus to select the quantities
of the items you wish to purchase, then
click a &quot;Submit Order&quot; button at the bottom. You will be presented
with a summary of your order and given the option of confirming your order or
backing-up to edit the original form. If you click <em>Confirm</em> you will
receive an email detailing your order and how to pay.</p>
<p>A bulk order will be placed within a
day or two of the close date (<?php echo $closeDateString; ?>) and is
typically received within a week.
<?php echo $firstName;?> will organise sorting of the order
and will email you when
the order is ready to be collected (probably from <?php echo $firstName;?>'s
home or from the Wednesday club night).
</p>
<p>We ask that you pay for your order before the close date. Payment
should be by Internet Banking, unless you can negotiate an alternative
method with <?php echo $firstName; ?>. Payment instructions
will be provided in the order
confirmation web page and email.</p>

<p>
Feel free to email or phone <?php echo "$name";?> if you have any queries
about this process or if any problems occur while you are making
your order.
</p>

</div>
<?php include("footer.php"); ?>
