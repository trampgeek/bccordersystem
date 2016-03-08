<?php
// Interface to the Batches table. A Batch has an id, an open date (when
// members can start ordering), a close date (when ordering ceases) the id
// in the CTC members table of the coordinating committee member and the
// bank account number to be used for Internet payments.
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define('ONE_WEEK', 60 * 60 * 24 * 7); // One week in seconds

class Batch extends CI_Model
{
    public $id;         // ID of this batch
    public $openDate;   // When it open
    public $closeDate;  // When it closes
    public $coordinatorMemberId; // ID for the member who's coordinating this order
    public $accountNum;  // Account number for internet payment


    public function __construct()
    {
        $this->id = 0;
        $this->id = 0;
        $this->openDate = date("Y-m-d", time());
        $this->closeDate = date("Y-m-d", time() + ONE_WEEK);
        $this->accountNum = "00-0000-0000000-000";
    }


    // Load the current batch (i.e. the batch whose open and close
    // dates include today) into this. ** CHANGED: SEE BELOW **
    // If there is no current batch, 'this' will be left in its
    // new batch state with openDate today and closeDate one week on
    // and id = 0.
    // 11/10/11 RJL: Changed definition of "current batch" to "the
    // batch whose close date is any time in the future". This ensures
    // that there can be only one such batch and solves the problem
    // that a batch could be set up with an open date in the future
    // and a close date even further in the future. This didn't satisfy
    // the definition of "current batch", allowing a new batch to be
    // created. However, that new batch was then the one returned by
    // 'loadMostRecent', which was the only one eligible for editing.
    // BUT: the real problem is with the concept of 'openDate',
    // which really doesn't make much sense. Probably should refactor
    // to remove openDate altogether, but it's too much work right now!

    public function loadCurrent()
    {
        // $this->db->where('openDate <= CURDATE() and closeDate >= CURDATE()');
        $this->db->where('closeDate >= CURDATE()');
        $batches = $this->db->get('Batches');
        if ($batches->num_rows() > 1) {
            die("Multiple current batches! Please report this error.");
        }
        else if ($batches->num_rows() == 1) {
            $batch = $batches->row();
            foreach (array('id', 'openDate', 'closeDate', 'accountNum',
                            'coordinatorMemberId') as $prop) {
                $this->$prop = $batch->$prop;
            }
        }
        return True;
    }


    // Return true if there is a current batch, i.e. any batch with a
    // close date in the future.
    public function currentExists()
    {
        $this->db->where('closeDate >= CURDATE()');
        $batches = $this->db->get('Batches');
        return $batches->num_rows() > 0;
    }


    // Load the most recent batch i.e. the batch with the largest
    // id value into this. If the database is empty 'this' will be
    // the default new record with id = 0 and the return value
    // will be false. Otherwise this will be a defined value and
    // the return value will be True.
    public function loadMostRecent()
    {
        $this->db->select_max('id');
        $query = $this->db->get('Batches');
        if ($query->num_rows() != 1) {
            return False; // Database empty
        }
        $id = $query->row()->id;
        $this->load($id);
        return True;
    }


    // Load the specified batch. Dies if no such batch exits.
    public function load($id)
    {
        $batches = $this->db->get_where('Batches', array('id'=>$id));
        if ($batches->num_rows() != 1) {
            die('Database error. Please report.');
        }
        else {
            $batch = $batches->row();
            foreach (array('id', 'openDate', 'closeDate', 'accountNum',
                            'coordinatorMemberId') as $prop) {
                $this->$prop = $batch->$prop;
            }
        }
        return True;
    }


    public function save()
    {
        if ($this->id == 0) {
            $this->db->insert('Batches', $this);
        }
        else {
            $this->db->where('id', $this->id);
            $this->db->update('Batches', $this);
        }
    }

    // Returns a list of all batches.
    public function listAll()
    {
        $this->db->order_by('id');
        $query = $this->db->get('Batches');
        return $query->result();
    }


}
