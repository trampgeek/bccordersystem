<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	http://codeigniter.com/user_guide/general/hooks.html
|
*/

/*
 * Set up a hook to verify user is already logged in or, alternatively, is logged
 * in to the CTC Joomla site (in which case their contact role is used to determine
 * their privilege level). See hooks/authenticate.php for details.
 */
$hook['pre_controller'] = array(
         'class'    => '',
         'function' => 'authenticate',
         'filename' => 'authenticate.php',
         'filepath' => 'hooks',
         'params'   => array()
        );

?>