<?php
// The interface to the CTC members table (or strictly the view_members view)

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Member extends CI_Model
{
    public $id;
    public $firstName;
    public $lastName;
    public $primaryEmail;
    public $homePhone;
    public $address1;
    public $address2;
    public $city;
    public $postcode;   
    
    public function __construct()
    {
        $firstName = NULL;
        $lastName = NULL;
        $primaryEmail = NULL;
        $homePhone = NULL;
        $address1 = NULL;
        $address2 = NULL;
        $city = NULL;
        $postcode = NULL; 

    }

    
    public function loadFromDb($memberId) {
        $members = $this->db->get_where('ctcweb9_ctc.view_members', array('memberId'=>$memberId));
        if ($members->num_rows() != 1) {
            return False;
        }
        $this->id = $memberId;
        $member = $members->row();
        
        // Copy across the fields we need
        foreach (array('firstName', 'lastName', 'primaryEmail', 'homePhone',
            'address1', 'address2', 'city', 'postcode') as $field) {
            $this->$field = $member->$field;
        }
        return True;
    }


    public function set($data) {
        foreach ($data as $key=>$value) {
            $this->$key = $value;
        }
    }

    public function fullName() {
        return $this->firstName . ' ' . $this->lastName;
    }
}
