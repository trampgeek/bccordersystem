<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
if (!isset($includeFreight)) {
    $includeFreight = True;
}
echo htmlOrder($member, $order, $catalogue, $includeFreight);

