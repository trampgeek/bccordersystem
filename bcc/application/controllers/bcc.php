<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define('DEBUG', 1);

if (defined('DEBUG') && DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors','1');
}

/** The controller for the CTC Backcountry Cuisine (formerly "Foods")
 *  ordering system. Loads the current batch, if there is one,
 *  plus the current member and batch controller models.
 */
class Bcc extends CI_Controller {

    public function __construct()
    {
        global $userData;  // From pre-controller hook (see authenticate.php)

        if (config_item('laptop')) {
            $userData = array('roles'=>array('webmaster'), 'userid'=>595);
        }
        else if ($userData['userid'] == 0) {
            $ctcHome = config_item('joomla_base_url');
            echo '<head><script language="javascript">top.location.href="'.$ctcHome.'";</script></head>';
            die('Not logged in.');
        }

        parent::__construct();
        $this->isOfficer = count($userData['roles']) > 0; // Have roles => officer
        $this->load->database();
        $this->db->query("set time_zone = '+12:00'");
        $memberId = $userData['userid'];
        $this->load->helper(array('html', 'url', 'orderfuncs', 'date'));

        $this->load->model('member', 'member');
        if (!$this->member->loadFromDb($memberId)) {
            die('Non-existent member');
        }
        $this->load->model('member', 'coordinator');
        $this->load->model('batch');
        $this->batch->loadCurrent();
        if ($this->batch->id != 0 &&
             !$this->coordinator->loadFromDb($this->batch->coordinatorMemberId)) {
                die('Coordinator for this round of orders not found in database');
        }
    }


    public function index()
    {
        $this->order();
    }



    // Display the instructions for the current batch
    public function instructions()
    {
        if ($this->_noBatch()) {
            $this->_unavailable();
        }
        else {
            $data = array('coordinator' => $this->coordinator, 'batch' => $this->batch);
            $this->_header('BCC Instructions');
            $this->load->view('instructions', $data);
        }
    }




    // Process an order for the currently-logged-in member
    public function order()
    {
        $this->load->helper('form');
        if ($this->_noBatch()) {
            $this->_unavailable();
        }
        else {
            $this->load->model('catalogue');
            $this->load->model('bccorder', 'order');
            $this->order->load($this->member->id, $this->batch->id);

            if ($this->order->confirmed) {
                $this->_orderAlreadyConfirmed();
            }
            else if ($this->input->post('aborted') == '1') {
                $this->order->cancel();
                $this->_orderCancelled();
            }
            else if ($this->input->post('confirmOrder')) {
                $this->_orderConfirmed();
            }
            else if ($this->input->post('submitOrder')) {
                $this->_buildOrderFromForm();
                if ($this->order->numItemsOrdered() == 0) {
                    $this->_displayOrderForm('***You haven&rsquo;t ordered anything!***');
                }
                else {
                    $this->_requestConfirmation();
                }
            }
            else {
                $this->_displayOrderForm();
            }
        }
    }



    // PRIVATE SUPPORT FUNCTIONS
    // =========================


    // Generate the header
    private function _header($title) {
        $this->load->view('header', array('title'=>$title,
            'isClubOfficer'=>$this->isOfficer));
    }


    // Check if there's a current batch and it's open
    private function _noBatch() {
		return $this->batch->id == 0 || $this->batch->openDate > mdate("%Y-%m-%d", time());
	}


	// Error screen if no batch available
	private function _unavailable() {
		$this->_header('BCC Unavailable');
		$this->load->view('message', array(
			'message' => 'Sorry, but we are not currently processing ' .
				'Backcountry Cuisine orders'));
	}


    // Fill in the current order model from the data in the submitted form.
    private function _buildOrderFromForm()
    {
        $this->order->clearItems();
        foreach (array_keys($this->catalogue->productsMap) as $pid) {
            $qty = $this->input->post($pid);
            if ($qty != 0) {
                $this->order->addItem($pid, $qty);
            }
        }
    }


    // Send an email to the currently-logged in member, confirming their
    // order.
    private function _sendEmail($ok=True)
    {
        $this->load->library('email');
        $params = array('mailtype'=>'html');
        if (config_item('laptop')) {
            $params['protocol'] = 'smtp';
            $params['smtp_host'] = 'smtp.orcon.net.nz';
        }
        $this->email->initialize($params);
        $this->email->from('webmaster@ctc.org.nz', 'CTC Webmaster');
        $this->email->reply_to($this->coordinator->primaryEmail);
        $this->email->to($this->member->primaryEmail);
        $this->email->cc($this->coordinator->primaryEmail);
        $this->email->subject('Confirmation of CTC Backcountry Cuisine order');

        $message = <<<END
<html><head><style>
h2 {font-size: 12pt; }
p.summary {
    font-weight: bold;
}the

th.item, td.item {
    text-align: left;
    padding-left: 15pt;
}

th.qty, td.qty {
    text-align: right;
    padding-right: 10pt;
}

td.money, th.money, td.total {
    text-align: right;
}

td.code, th.code {
    text-align: center;
}
</style>
</head><body>
<p>Your order of Backcountry
Cuisine items via the CTC website is as follows.</p>\n
<p>
END;
        $warning = "<p class='error'>*** Oops. An internal consistency check failed. " .
                   "Please check this order carefully and email ".
                   "<a href='mailto:richard.lobb@canterbury.ac.nz'>the webmaster</a> ".
                   "if you see anything wrong with it.</p>";
        $message .= $ok ? '' : $warning;
        $message .= htmlOrder($this->member, $this->order, $this->catalogue) .
                "</p>\n<hr /><p>" .
                htmlPaymentInstructions($this->coordinator, $this->batch) .
                "</p>\n<p>" .
                htmlAskCoordinator($this->coordinator) . "</p>\n" .
                "</body></html>\n";
        $this->email->message($message);
        if (!$this->email->send()) {
            echo "<p><b>WARNING:</b> an error occurred when trying to send you " .
            "an email confirmation. Please advise the order coordinator ".
            "of this fact.</p>";
        }
        // echo "Email sent to ". $this->member->primaryEmail . ': ' . $this->email->print_debugger();
    }


    // Various order handling phases
    // =============================

    // User has confirmed their order
    // This code contains extra code to confirm that the number of items
    // ordered in the saved confirmed order is the same as the number in the
    // tentative (unconfirmed) order. This shouldn't be necessary but was
    // added because of a strange error that occurred a few times on the
    // main live server, whereby order items vanished from the confirmed order.
    private function _orderConfirmed()
    {
        $this->order->confirmed = True;
        $initialNumItems = $this->order->numItemsOrdered();
        $newOrderId = $this->order->save();
        // Read the order back to confirm all's well (see comment above)
        $this->order->load($this->batch->id, $this->member->id);
        $ok = $initialNumItems === $this->order->numItemsOrdered();
        $this->_sendEmail($ok);
        $this->_header('Order Confirmed');
        $this->load->view('thanks', array(
            'member'      => $this->member,
            'order'       => $this->order,
            'catalogue'   => $this->catalogue,
            'coordinator' => $this->coordinator,
            'batch'       => $this->batch,
            'ok'          => $ok)
            );
    }


    // Member already has a confirmed order
    private function _orderAlreadyConfirmed()
    {
        $this->_header('Order already confirmed');
        $this->load->view("orderAlreadyConfirmed", array(
            'coordinator'   => $this->coordinator,
            'order'         => $this->order,
            'member'        => $this->member,
            'catalogue'     => $this->catalogue)
        );
    }

    // The order form is to be displayed to the user
    private function _displayOrderForm($errorMessage = '')
    {
        $this->_header('CTC Backcountry Cuisine Order');
        $this->load->view('order', array(
            'batch'         => $this->batch,
            'errorMessage'  => $errorMessage,
            'order'         => $this->order,
            'coordinator'   => $this->coordinator,
            'member'        => $this->member,
            'catalogue'     => $this->catalogue)
        );
    }

    // Member has submitted their form. Request confirmation.
    private function _requestConfirmation()
    {
        $this->order->save();
        $this->_header('Backcountry Cuisine Order Confirmation');
        $this->load->view('orderConfirmation', array(
            'order'     => $this->order,
            'member'    => $this->member,
            'catalogue' => $this->catalogue)
        );
    }

    // Member has cancelled their order.
    private function _orderCancelled()
    {
        $this->_header('Order cancelled');
        $this->load->view('message', array('message'=> 'Your order has been cancelled'));
    }

}

