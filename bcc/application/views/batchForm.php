<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$isNew = $batch->id == 0;
$title = $isNew ?  "New Batch" : "View/Edit Batch";
?>

<div class="batchform">
<h1><?php echo $title; ?></h1>
<h2>Coordinator</h2>
<p class='indent'>
<table>
<tr><td>Name:</td><td><?php echo $coordinator->fullName(); ?></td></tr>
<tr><td>Email:</td><td><?php echo $coordinator->primaryEmail; ?></td></tr>
<tr><td>Phone:</td><td><?php echo $coordinator->homePhone; ?></td></tr>
</table>
</p>
<p>
You are the coordinator for this batch. Your name, phone number
and email address will be supplied to all members using the system.
</p><p>
You must provide a bank account number into which members can make
internet payments for their orders. MAKE SURE YOU GET THIS RIGHT!!
</p>
<h2>Batch details</h2>
<?php
echo validation_errors();


$postbackUrl = 'bccadmin/' . ($batch->id == 0 ? 'newBatch' : 'editBatch');
echo form_open($postbackUrl, array(
        'id'=>'batchform',
        'onsubmit'=>'submitForm()'));
?>

<table>
    <tr><td>Open date</td>
        <td><?php echo form_input('openDate',
            set_value('openDate', $batch->openDate)); ?> </td>
    </tr>
    <tr><td>Close date</td>
        <td><?php echo form_input('closeDate',
            set_value('closeDate', $batch->closeDate)); ?> </td>
    </tr>
    <tr><td>Account number</td>
        <td><?php echo form_input('accountNum',
            set_value('accountNum', $batch->accountNum)); ?> </td>
    </tr>
</table>
<p>
<?php echo form_submit('savebatch', 'Save batch'); ?>
</p>
<?php echo form_close();
?>

</div>
<?php include('footer.php'); ?>


