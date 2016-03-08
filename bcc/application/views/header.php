<?php
// The standard header for all views. Takes $title as parameter.
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// LET THE OUTPUT BEGIN ....
echo doctype('xhtml1-strict');
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo $title; ?></title>
<?php $now=date('zGi'); ?>
<!--<link rel="stylesheet" type="text/css" media="screen"
  href="http://www.ctc.org.nz/templates/ctcnew/css/template_css.css?version=<?php echo $now?>" /> -->
<?php echo link_tag('css/styles.css?version='.$now); ?>
</head>
<body>

<div class="banner">
&nbsp;
</div>

<hr class='navbar' />
<div class="navbar">
    <a href="http://www.ctc.org.nz">CTC Home</a>
 |   <?php echo anchor('bcc/order', 'Order'); ?>
 |   <?php echo anchor('bcc/instructions', 'Instructions'); ?>
 |   <a href="http://backcountrycuisine.co.nz/bcc/">Backcountry Cuisine</a>
<?php if ($isClubOfficer) { ?>
 |   <?php echo anchor('bccadmin/printall', 'PrintAll'); ?>
 |   <?php echo anchor('bccadmin/collate', 'Collate'); ?>
 |   <?php echo anchor('bccadmin/editBatch/', 'Edit Batch'); ?>
 |   <?php echo anchor('bccadmin/newBatch/', 'New Batch'); ?>
 |   <?php echo anchor('pricelist/upload/', 'Pricelist upload'); ?>
<?php } ?>
</div>
<hr class='navbar' />

<?php
if (isset($divclass)) {
    echo "<div class='$divclass'>\n";
}
?>
