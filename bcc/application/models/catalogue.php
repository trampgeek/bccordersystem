<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// An object representing the Backcountry Cuisine catalogue.
// The products field is a map from category ('Main Course',
// 'Breakfast Cereals/Desserts' etc) to a map from
// product class ('Beef and Pasta Hotpot', 'Classic Beef Curry' etc)
// to an array of actual products in that class (typically single serve,
// two serve etc). Each product is an object with a product ID, a serve
// description (Single Serve etc), a price and the BCC code for that product.

// $productsMap is a map from productId to an object with fields
// description (the product class plus serve description), price and code.

class Catalogue extends CI_Model {
    public $products;
    public $productsMap;

    public function __construct()
    {
        $productsMap = array();
        $cats = $this->db->order_by('id')->get('Categories');
        $this->products = array();
        foreach ($cats->result() as $cat) {
            $catId = $cat->id;
            $category = $cat->category;
            $productsInCategory = array();
            $this->db->order_by('id');
            $prodClasses = $this->db->get_where('ProductClasses',
                            array('categoryId' => $catId));
            foreach ($prodClasses->result() as $pc) {
                $this->db->order_by('id');
                $prods = $this->db->get_where('Products',
                            array('productClassId' => $pc->id));
                $serves = array();
                foreach ($prods->result() as $prod) {
                    $serves[] = $prod;
                    $prodDesc = $pc->description;
                    if ($prod->serve != '') {
                        $prodDesc .= " ({$prod->serve})";
                    }
                    $this->productsMap[$prod->id] = (object) array(
                        'description'=>$prodDesc,
                        'price'=>$prod->price,
                        'code'=>$prod->code);
                }
                $productsInCategory[$pc->description] = $serves;
            }
            $this->products[$category] = $productsInCategory;
        }
    }


    /**
     * Reload the entire catalogue, discarding all previous information.
     * DON'T CALL THIS WHEN THERE IS AN ACTIVE BATCH!
     * @param type $items an array of associative arrays, each containing
     * fields name, serve, category, price and code.
     */
    public function reload($items)
    {
        $this->db->empty_table('Categories');
        $this->db->empty_table('ProductClasses');
        $this->db->empty_table('Products');
        $lastCategory = '';
        $lastName = '';
        $categoryId = 0;
        $nameId = 0;
        foreach ($items as $item) {
            extract($item);
            if ($category != $lastCategory) {
                $this->db->insert('Categories', array('category' => $category));
                $categoryId = $this->db->insert_id();
                $lastCategory = $category;
            }

            if ($name != $lastName) {
                $this->db->insert('ProductClasses',
                    array('description' => $name, 'categoryId' => $categoryId));
                $nameId = $this->db->insert_id();
                $lastName = $name;
            }

            $this->db->insert('Products',
                    array('productClassId' => $nameId,
                          'serve' => $serve,
                          'price' => $price,
                          'code'  => $code));
        }
    }
}
