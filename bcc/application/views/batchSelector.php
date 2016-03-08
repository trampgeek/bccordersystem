<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

<script type='text/javascript'>
//<![CDATA[
function submitForm(selector)
{
    var formId = document.getElementById('batchSelector');
    formId.action += "/" + selector.value;
    formId.submit();
}
//]]>
</script>
<div class='batchselector'>
    
<?php
echo form_open($postback, array('id'=>'batchSelector'));
echo "<h1>$title for batch: ";
echo "<select id='comboBox' onchange='submitForm(this)'>\n";
foreach ($batches as $batch) {
    $checked = $batch->id == $currBatchId ? "selected='selected'" : '';
    echo "<option value='{$batch->id}' $checked>{$batch->id}: {$batch->closeDate}</option>\n";
}
echo "</select></h1>\n";
echo form_close();
?>

</div>
