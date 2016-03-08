<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** The model for a BCC order, represented in the database by the
 *  two tables Orders and OrderLines.
 */


class Bccorder extends CI_Model {
    public $batchId;
    public $memberId;
    public $orderId;
    public $confirmed;
    public $orderLines;
    public $isValid;
    public $allOrders;

    public function __construct()
    {
        parent::__construct();
        $this->batchId = 0;
        $this->memberId = 0;
        $this->orderId = 0;
        $this->confirmed = 0;
        $this->orderLines = array();
        $this->isValid = False;
        $this->allOrders = NULL;
    }


    public function addItem($prodId, $qty)
    {
        $this->orderLines[$prodId] = $qty;
    }


    /** Load the current order from the database for the given member.
     *  Return True if succeeds, Fail otherwise.
     */
    public function load($memberId, $batchId)
    {
        $query = $this->db->get_where('Orders',
                    array(
                    'batchId'=>$batchId,
                    'memberId'=>$memberId,
                    'isActive'=>1
                    )
                );
        if ($query->num_rows() > 1) {
            if (defined('DEBUG') && DEBUG) {
                die("DB error in Bccorder::load: " . $this->db->error_message());
            }
            return False;
        }

        $this->memberId = $memberId;
        $this->batchId = $batchId;

        if ($query->num_rows() == 1) {
            $this->orderId = $query->row()->id;
            $this->confirmed = $query->row()->confirmed;
            $this->_populateOrderItems();
        }
        $this->isValid = True;
        return True;
    }


    /** The quantity of the given product ID ordered in this order.
     *  0 if no such order line exists.
     */
    public function quantity($prodId)
    {
        return isset($this->orderLines[$prodId]) ? $this->orderLines[$prodId] : 0;
    }


    /** The total number of items ordered */
    public function numItemsOrdered()
    {
        $n = 0;
        foreach ($this->orderLines as $id=>$qty) {
            $n += $qty;
        }
        return $n;
    }


    /** Clear the list of ordered items.
     */
    public function clearItems()
    {
        $this->orderLines = array();
    }

    /** Cancel this order, i.e. mark it inactive in the database */
    public function cancel()
    {
        $this->db->where(
            array( 'batchId'    => $this->batchId,
                   'memberId'   => $this->memberId,
                   'isActive'   => 1
                 )
        );
        $this->db->set('isActive', 0);
        $this->db->update('Orders');
    }

    /** Save the current order to the database after first marking
     *  any other order for the same memberId as inactive.
     *  Returns: id of (new) saved order.
     */

    public function save()
    {
        $this->cancel();

        $this->db->insert('Orders',
            array(
                'batchId'   => $this->batchId,
                'memberId'  => $this->memberId,
                'confirmed' => $this->confirmed)
        );
        $orderId = $this->db->insert_id();
        foreach ($this->orderLines as $prodId=>$qty) {
            $this->db->insert('OrderLines',
                array('orderId'     => $orderId,
                      'productId'   => $prodId,
                      'quantity'    => $qty
                      )
            );
        }
        return $orderId;
    }


    /* Load the first order in the given batch. Subsequent
     * orders are loaded via the next method. End is indicated
     * by isValid == False
     */
     public function loadFirst($batch)
     {
        if ($batch->id != 0) {
            $query = $this->db->get_where('Orders',
                 array(
                    'batchId'=>$batch->id,
                    'isActive'=>1,
                    'confirmed'=>1
                    )
                );
             $this->allOrders = $query->result();
             $this->next();
        }
        else {
            $this->isValid = False;
        }
     }


     /* Load the next order in the list of all orders initiated by
      * a call to loadFirst.
      */
     public function next()
     {
         $row = array_shift($this->allOrders);
         if ($row == NULL) {
             $this->isValid = False;
         }
         else {
            $this->orderId = $row->id;
            $this->memberId = $row->memberId;
            $this->batchId = $row->batchId;
            $this->confirmed = $row->confirmed;
            $this->_populateOrderItems();
            $this->isValid = True;
        }
     }


     /* Load the order items for the current order */
     private function _populateOrderItems()
     {
        $this->clearItems();
        $this->db->order_by('id');
        $items = $this->db->get_where('OrderLines', array('orderId'=>$this->orderId));
        foreach ($items->result() as $item) {
            $this->addItem($item->productId, $item->quantity);
        }
    }
}




