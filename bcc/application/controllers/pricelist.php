<?php

/** This controller exists only to support the uploading of the
 *  weird and wonderful Backcountry Cuisine price list, which is a
 *  spreadsheet supplied by Analise Petas (Analise@backcountrycuisine.co.nz).
 *
 */
class Pricelist extends CI_Controller
{

    public function __construct() {
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
        $this->load->model('catalogue');
        $this->load->model('batch');
        $this->load->helper('html');
        $this->load->helper(array('url', 'html', 'orderfuncs'));

        $this->itemCosts = array(); // Array of items and their costs.
        $this->rowNum = 0;
        $this->sheet = NULL;
    }


    public function upload() {

        $this->_header("Price list upload");
        if ($this->batch->currentExists()) {
            $this->load->view('message', array('message'=>
  'Price list uploading not permitted when current or future batches exist'));

        } else {
            $isPost = False;
            $error = '';
            if ($this->input->post('submit')) {
                $isPost = True;
                if ($_FILES["file"]["error"] > 0) {
                    $error = $_FILES["file"]["error"];
                } else {
                    try {
                        $this->processCsvFile($_FILES["file"]["tmp_name"]);
                    } catch (Exception $e) {
                       $error = "Error processing spreadsheet row {$this->rowNum}: " . $e->getMessage();
                    }
                }
            }

            if (!$isPost || $error) {
                $this->load->view('pricelistupload', array(
                    'error' => $error,
                    'items' => $this->itemCosts));
            } else {
                $this->catalogue->reload($this->itemCosts);
                $this->load->view('pricelistuploadsuccess', array(
                    'items' => $this->itemCosts));
            };
        }
    }


    private function processCsvFile($filename)
    {
        $this->sheet = $this->readCsv($filename);
        $this->rowNum = 0;
        $categoryNum = 1;
        $skippingHeader = true;
        while ($this->rowNum < count($this->sheet)) {
            $row = $this->nextRow();
            if (isEmptyRow($row) || ($skippingHeader && $row[0] != 'Main Course')) {
                continue;
            } else if (startsWith($row[0], 'Freight Charges')) {
                break;
            } else {
                $skippingHeader = false;
                $this->processCategory($categoryNum, $row);
                $categoryNum++;
            }
        }

    }


    private function readCsv($filename)
    {
        $file = fopen($filename, "r");
        $cells = array();
        while ($row = fgetcsv($file, 0, ',')) {
            $cells[] = $row;
        }
        return $cells;
    }


    /**
     * Process a whole category of items from the sheet, starting at the
     * given row number.
     * Updates rowNumber attribute on each row.
     * A category terminates when all sheet rows are used or when an
     * empty row is encountered.
     */
    function processCategory($categoryNum, $row)  {
        $category = $row[0];
        if ($category == '') {
            throw new Exception("Missing category name");
        }
        $serveSizes = null;
        $secondCol = count($row) > 1 ? $row[1] : '';
        if (preg_match('#[1-9] ?[sS]erve \$([0-9.]+) *\+ *(GST|gst)#', $secondCol)) {
            $cellType = 2; // Cell value is code, price is in header row
            $matches = array();
            $i = 1;
            $prices = array();
            $serveSizes = array();
            while ($i < count($row) &&
                  preg_match('#([1-9] ?[sS]erve) (\$([0-9.]+) *\+ *(GST|gst))#',
                                $row[$i], $matches)) {
                $serveSizes[] = $matches[1];
                $prices[] = extractPrice($matches[2]);
                $i++;
            }
        }
        else if (preg_match('/(Single Serve)|(1 ?[sS]erve).*/', $secondCol)) {
            $cellType = 2; // Cell value is code, price is in subheader row
            $serveSizesRaw = $row;
            array_shift($serveSizesRaw);
            $prices = $this->nextRow();
            array_shift($prices);
            $prices = extractPrices($prices);
            $serveSizes = array();
            foreach($serveSizesRaw as $ss) {
                if (empty($ss)) {
                    break;
                }
                $serveSizes[] = extractServeSize($ss);
            }
        }
        else if (startsWith($secondCol, '$')) {
            // Cell value is code. Prices are in category header row.
            // Serve sizes are not-applicable
            $cellType = 2;
            $prices = $row;
            array_shift($prices);
            $prices = extractPrices($prices);
            $serveSizes = array('');
        }
        else  {
            // For all remaining types, it's assumed that price and code are
            // either in columns 1 and 2 or space-separated in column 1.
            $cellType = 1;
            $prices = null;
        }


        while (!isEmptyRow($row = $this->nextRow())) {
            if ($row[0] == '') {  // Price row in middle of category
                $prices = $row;
                array_shift($prices);
                $prices = extractPrices($prices);
            }
            else {
                $name = trim($row[0]);
                array_shift($row);
                $this->processProductRow($name, $row, $category,
                        $categoryNum, $cellType, $prices, $serveSizes);
            }
        }
    }


    private function recordPrice($name, $serve, $category, $price, $code)
    {
        $this->itemCosts[] = array(
                'name'      => $name,
                'serve'     => $serve,
                'category'  => $category,
                'price'     => $price,
                'code'      => $code
            );
    }



    /**
     * Process a row of the sheet, which must be a product row.
     */
    private function processProductRow($name, $row, $category, $categoryNum,
                                 $cellType, $prices, $serveSizes)
    {
        if ($cellType == 1) {
            $n = count($row);
            if ($n > 2) {
                throw new Exception("Too many columns found");
            }
            $matches = array();
            $priceAndCode = $row[0];
            if ($n == 2) {
                $priceAndCode .= ' ' . $row[1];
            }
            if (!preg_match('/\$([0-9.]+)( ?\+ ?gst)? +([0-9]+)/', $priceAndCode, $matches)) {
                throw new Exception("Unexpected price and or code format");
            }
            $price = $matches[1];
            $code = $matches[3];
            $serve = empty($serveSizes) ? '' : $serveSizes[0];
            $this->recordPrice($name, $serve, $category, $price, $code);
        }
        else {
            $n = count($prices);
            if ($n != count($serveSizes)) {
                throw new Exception("Serve size count doesn't match count of prices");
            }
            for ($col = 0; $col < min($n, count($row)); $col++) {
                $cell = $row[$col];

                $serve = $serveSizes == null ? '' : $serveSizes[$col];

                if ($cell == '') {  // Skip empty cells
                }
                else {
                    $price = $prices[$col];
                    $code = $cell;
                    $this->recordPrice($name, $serve, $category, $price, $code);
                }
            }
        }
    }

    // Return the next unprocessed spreadsheet row, purging trailing empty cells
    private function nextRow()
    {
        if ($this->rowNum >= count($this->sheet)) {
            return array();
        }
        else {
            $row = $this->sheet[$this->rowNum++];
            while (count($row) > 0 && $row[count($row) - 1] == '') {
               array_pop($row);  // Purge trailing empty columns
            }
            return $row;
        }

    }



    // Generate the header
    private function _header($title, $otherParams = array()) {
        $params = array('title'=>$title, 'isClubOfficer'=>True);
        $params = array_merge($params, $otherParams);
        $this->load->view('header', $params);
    }

}


//============ UTILITY FUNCTION ===================

function isEmptyRow($row) {
    foreach ($row as $cell) {
        if ($cell != '') {
            return false;
        }
    }
    return true;
}

function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}


function extractServeSize($serveString)
{
    $pos = stripos($serveString, 'serve');
    if ($pos === FALSE) {
        throw new Exception("Expected serve size, got $serveString");
    }
    return trim(substr($serveString, 0, $pos) . ' Serve');
}


function extractPrice($priceWithGST) {
    $matches = array();
    if(!preg_match('#\$([0-9.]+) *\+ *(GST|gst).*#', $priceWithGST, $matches)) {
        throw new Exception('Expected item price, got ' . $priceWithGST);
    }
    return $matches[1];
}


// Given an array of strings containing prices plus other stuff, return
// an array of just the prices.
function extractPrices($pricesWithGST) {
    $ans = array();
    foreach ($pricesWithGST as $price) {
        if ($price == '') {
            break;
        }
        $ans[] = extractPrice($price);
    }
    return $ans;
}
