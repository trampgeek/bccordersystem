<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Administrator functions for the Backcountry Cuisine
// order system.
// This module doesn't include the dreadful hacked-together code that
// takes the ghastly pseudo-spreadsheet of products, prices, codes etc
// that BCC posts to us and builds a database of products from it.
// That's in pricelist.php.

define('DEBUG', 1);

if (defined('DEBUG') && DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors','1');
}

/** The controller for the CTC Backcountry Cuisine (formerly "Foods")
 *  ordering system
 */
class Bccadmin extends CI_Controller {

    /** The constructor for the administration controller.
     *  The pre-controller hook will have called authenticate.php
     *  prior to this, so various CTC user info is already set.
     */
    public function __construct()
    {
        global $userData;
        if (config_item('laptop')) {
            $userData = array('roles'=>array('webmaster'), 'userid'=>595);
        }
        else if ($userData['userid'] == 0) {
            $ctcHome = config_item('joomla_base_url');
            echo '<head><script language="javascript">top.location.href="'.$ctcHome.'";</script></head>';
            die('Not logged in.');
        }


        parent::__construct();
        $this->load->database();
        $this->db->query("set time_zone = '+12:00'");

        $memberId = $userData['userid'];

        $this->load->helper(array('html', 'url', 'orderfuncs', 'date'));

        $this->load->model('member', 'member');
        if (!$this->member->loadFromDb($memberId)) {
            die('Non-existent member');
        }
        $this->load->model('batch');
        $this->load->model('member', 'coordinator');
        $this->load->helper('form', 'date');
    }


    // Create a new batch.
    public function newBatch()
    {
        $this->batch->loadCurrent();
        if ($this->batch->id != 0) {

            // Batch already active; a new one cannot be created

            $this->_header('Batch already active');
            $this->load->view('message', array(
                'message'=>'There can only be one active batch at a time.'));
        }
        else {
            $this->batch->coordinatorMemberId = $this->member->id;
            $this->editBatch2();
        }

    }


    // Edit the most recent batch. Only the batch coordinator can edit
    // it and only the current batch can ever be edited. Once a new
    // one is created. the old batch is history and history cannot be
    // edited.

    public function editBatch()
    {
        $this->batch->loadMostRecent();
        if ($this->batch->id == 0) {
            $this->_header('No batches');
            $this->load->view('message', array(
                'message'=>'No batches to edit.'
            ));
        }
        else if ($this->batch->coordinatorMemberId != $this->member->id) {

            // Batch already active and logged in member not its coordinator

            $this->_header('Not coordinator');
            $this->load->view('message', array(
                'message'=>'Only the coordinator can edit the current batch.'
            ));
        }
        else {
            $this->editBatch2();
        }
    }


    /** Phase2 of editing or creating a batch. Operates on the current
     *  batch. */
    public function editBatch2()
    {
        $this->coordinator->loadFromDb($this->batch->coordinatorMemberId);
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<p class="error">', '</p>');
        $this->form_validation->set_rules('openDate', 'Open date', 'callback_dateCheck');
        $this->form_validation->set_rules('closeDate', 'Close date', 'callback_dateCheck');
        $this->form_validation->set_rules('accountNum', 'Account number', 'callback_acnumCheck');

        if ($this->form_validation->run()) {

            // Successfully created or edited batch

            foreach (array('openDate', 'closeDate', 'accountNum') as $field) {
                $this->batch->$field = $this->input->post($field);
            }
            $this->batch->save();

            $this->_header('Batch saved');
            $this->load->view('message', array('message'=>'Batch saved'));
        }

        else {

            // New batch or bad batch data submitted
            $title = $this->batch->id != 0 ? "Edit batch" : "New batch";
            $this->_header($title);
            $this->load->view('batchForm', array(
                'batch' => $this->batch,
                'coordinator' => $this->coordinator
            ));
        }
    }




    // Print all orders in the most recent batch if $batchId == 0
    // or the nominated one otherwise, for use when sorting the
    // order into member packages after it has been received.
    public function printAll($batchId = 0)
    {
        if ($batchId == 0) {
            $this->batch->loadMostRecent();
        }
        else {
            $this->batch->load($batchId);
        }
        if ($this->batch->id == 0) {
            $this->_header('No batches');
            $this->load->view('message', array('message' => "Can't
                print orders when there are not batches!"));
        }
        else {
            $this->_printAll2();
        }
    }


    // Print all orders in the current (non-empty) batch.
    private function _printAll2()
    {
        $this->load->model('catalogue');
        $this->load->model('bccorder', 'order');
        $this->_header('All Orders', array('divclass' =>'allorders'));
        $allBatches = $this->batch->listAll();
        $this->load->view('batchSelector', array(
            'postback' => 'bccadmin/printAll',
            'title' => 'All orders',
            'currBatchId' => $this->batch->id,
            'batches'=>$allBatches));

        $emails = array();
        $this->order->loadFirst($this->batch);
        while ($this->order->isValid) {
            $this->member->loadFromDb($this->order->memberId);
            $emails[] = $this->member->primaryEmail;
            $this->load->view('orderSeparator');
            $this->load->view('displayOneOrder', array(
                'order'=>$this->order,
                'catalogue'=>$this->catalogue,
                'member'=>$this->member
            ));
            $this->order->next();
        }
        $this->load->view('emaillist', array('emails'=>$emails));
        $this->load->view('footer', array('closeDiv'=>True));
    }


    // Collate all orders in the most recent batch if $batchId == 0
    // or the nominated one otherwise into a single order for
    // sending to Backcountry Cuisine.
    public function collate($batchId = 0)
    {
        if ($batchId == 0) {
            $this->batch->loadMostRecent();
        }
        else {
            $this->batch->load($batchId);
        }
        if ($this->batch->id == 0) {
            $this->_header('No batches');
            $this->load->view('message', array('message' => "Can't
                collate orders when there isn't even a batch!"));
        }
        else {
            $this->_collate2();
        }
    }

    // Collate all orders in the current (non-empty) batch.
    private function _collate2()
    {
        $this->coordinator->loadFromDb($this->batch->coordinatorMemberId);
        $this->load->model('catalogue');
        $this->load->model('bccorder', 'order');

        $allOrders = array();
        $this->order->loadFirst($this->batch);
        while ($this->order->isValid) {
            foreach ($this->order->orderLines as $prodId=>$qty) {
                if (!isset($allOrders[$prodId])) {
                    $allOrders[$prodId] = $qty;
                }
                else {
                    $allOrders[$prodId] += $qty;
                }
            }
            $this->order->next();
        }
        $this->load->model('bccorder', 'collatedOrder');

        foreach ($allOrders as $prodId => $qty) {
            $this->collatedOrder->addItem($prodId, $qty);
        }

        $this->order->loadFirst($this->batch);
        $this->_header('Collated order', array('divclass' => 'collatedorder'));
        $allBatches = $this->batch->listAll();
        $this->load->view('batchSelector', array(
            'title' => 'Collated order',
            'postback' => 'bccadmin/collate',
            'currBatchId' => $this->batch->id,
            'batches'=>$allBatches
            ));
        $this->load->model('member', 'ctc');
        $this->ctc->set(array(
            'firstName' => 'Christchurch',
            'lastName'  => 'Tramping Club Inc',
            'homePhone' => $this->coordinator->homePhone,
            'primaryEmail' => $this->coordinator->primaryEmail
            ));
        $this->load->view('displayOneOrder', array(
            'order'          => $this->collatedOrder,
            'catalogue'      => $this->catalogue,
            'member'         => $this->ctc,
            'includeFreight' => False
            ));
        $this->load->view('footer', array('closeDiv'=>True));
    }



    // PRIVATE SUPPORT FUNCTIONS
    // =========================

    // Generate the header
    private function _header($title, $otherParams = array()) {
        $params = array('title'=>$title, 'isClubOfficer'=>True);
        $params = array_merge($params, $otherParams);
        $this->load->view('header', $params);
    }

    // A oncer to load a subset of view_members into a view_members
    // table for testing (on Richard's laptop). Assumes availability
    // of a view_members export in php format using phpMyAdmin
    public function loadCtcMembers()
    {
        require_once('/home/richard/365/CodeIgniter/CodeIgniter_2.0.2/application/controllers/view_members.php');
        foreach ($view_members as $viewMember) {
            $member = new stdClass();
            foreach (array('memberId', 'firstName', 'lastName', 'primaryEmail',
            'homePhone', 'address1', 'address2', 'city', 'postcode') as $field) {
                $member->$field = $viewMember[$field];
            }
            $this->db->insert('view_members', $member);
        }
    }


    // Validator callbacks (for batches)
    // =================================
    public function dateCheck($str) {
        if (!preg_match("/20[0-9]{2}-[0-9]{2}-[0-9]{2}/", $str)) {
            $this->form_validation->set_message('dateCheck',
              "Dates must be of form 'yyyy-mm-dd'");
            return False;
        }
        else {
            return True;
        }
    }

    public function acnumCheck($str) {
        if (!preg_match("/[0-9]{2}-[0-9]{4}-[0-9]{7}-[0-9]{2,3}/", $str)) {
            $this->form_validation->set_message('acnumCheck',
              "Account numbers must be of form '00-0000-0000000-000'");
            return False;
        }
        else if (preg_match("/^00-.*/", $str)
                || preg_match("/.*-0000-.*/", $str)
                || preg_match("/.*-0000000-.*/", $str)) {
            $this->form_validation->set_message('acnumCheck',
              "Account number fields cannot be zero (except the suffix)");
            return False;
        }
        else {
            return True;
        }
    }
}

