<hr />
<h2>Email addresses for all orderers</h2>
<p>
<?php
$emailList = '';
foreach($emails as $email) {
    if ($emailList != '') {
        $emailList .= ',';
    }
    $emailList .= $email;
}
echo $emailList;
?>
</p>


