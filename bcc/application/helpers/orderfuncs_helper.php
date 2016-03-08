<?php
/**
 * Utility function to return an HTML string describing the given order for the given
 * member.
 */

 define('FREIGHT_PERCENT', 2.5);  // Freight charge per order
 define('GST_FACTOR', 1.15);

function htmlOrder($member, $order, $catalogue, $includeFreight=True) {
    $name = htmlspecialchars($member->fullName());
    $result = "\n<h2>Order for " . $name . "</h2>\n<p class='indent'>";
    $result .=  "Phone: {$member->homePhone}<br />\n";
    $result .=  "Email: {$member->primaryEmail}</p>\n";

    $result .=  "\n<h2>Items Ordered</h2>\n";
    $result .=  "<table class='orderTable'>";
    $result .=  "<tr>" .
                "<th class='code'>Code</th>" .
                "<th class='item'>Item</th> " .
                "<th class='money'>Qty</th> " .
                "<th class='money'>@</th> " .
                "<th class='money'>Cost</th> " .
                "</tr>\n";

    $total = 0;

    // Process order in catalogue order
    foreach ($catalogue->productsMap as $pid=>$item) {
        if (isset($order->orderLines[$pid])) {
            $qty = $order->orderLines[$pid];
            $item = $catalogue->productsMap[$pid];
            $priceIncGst = round($item->price * GST_FACTOR, 2);
            $amt = $qty * $priceIncGst;
            $total += $amt;
            $priceToDisplay = sprintf("%.2f", $priceIncGst);
            $description = htmlspecialchars($item->description);
            $amtLayout = sprintf("%.2f", $amt);
            $result .=  "<tr>" .
                  "<td class='code'>{$item->code}</td> " .
                  "<td class='item'>$description</td> " .
                  "<td class='qty'>$qty</td> " .
                  "<td class='money'>\$$priceToDisplay</td> " .
                  "<td class='money'>\$$amtLayout</td> " .
                 "</tr>\n";
        }
    }

    $total = round($total, 2);
    $totalDisplay = sprintf('%.2f', $total);
    $result .=  "\n<tr><td></td><td class='total'>Total incl. GST</td> <td></td><td></td><td class='total'>\$$totalDisplay</td></tr>\n";
    $result .=  "</table>";

    $result .=  "\n<p class='summary'>Amount to pay, including GST";
    if ($includeFreight) {
        $freight = round($total * FREIGHT_PERCENT / 100.0, 2);
        $freightDisplay = sprintf('%.2f', $freight);
        $result .= " and " . FREIGHT_PERCENT . "% freight charge (\$$freightDisplay)";
        $total = $total + $freight;
    }
    $totalDisplay = sprintf('%.2f', $total);
    $result .= ": \$$totalDisplay</p>\n";
    return $result;
}


function htmlPaymentInstructions($coordinator, $batch) {
    $closeDateString = date('l j F', strtotime($batch->closeDate));
    $name = $coordinator->firstName . ' ' . $coordinator->lastName;
    $address1 = $coordinator->address1;
    $address2 = $coordinator->address2;
    $city = $coordinator->city;
    $postcode = $coordinator->postcode;

    return "<p>We ask that you pay for your order before " .
    $closeDateString . ", please. Payment should be ".
    "by Internet banking to: </p>\n".
    "<p class='indent'>Payee Name: $name<br />\n" .
    "Payee account number: {$batch->accountNum}</p>\n\n" .
    "<p>When filling out the <em>details to appear on the payee statement</em> ".
    "part of the  payment form, please put <em>Backcountry</em> as the " .
    "Particulars and your name (abbreviated as necessary to fit) as the " .
    "Code and/or Reference.</p>\n\n" .
    "<p>If you are unable to make an Internet banking payment, please " .
    "contact $name to see if any alternative payment method is " .
    "acceptable.</p>";
}


function htmlAskCoordinator($coordinator) {
    return "If you wish to make changes, or enquire " .
           "about this order, please contact the coordinator: " .
           $coordinator->fullName() . ' ph ' .
           $coordinator->homePhone . ', email ' . $coordinator->primaryEmail;
}


/** Output an HTML table of the items, costs etc.
 *  Used only during pricelist upload. Prices are GST-exclusive.
 *
 * @param type $items The items to output
 * @param type $class The css class to apply to the output table
 */
function printItemTable($items, $class='processeditemstable') {
    echo "<p class='error'>\n";
    echo "<Items processed prior to error, as follows:</p>";
    echo "<table class='$class'>\n";
    echo "<tr><th>Name</th><th>Serve</th><th>Category</th><th>Price</th><th>Code</th></tr>\n";
    foreach ($items as $item) {
        echo "<tr><td>{$item['name']}</td>";
        echo "<td>{$item['serve']}</td>";
        echo "<td>{$item['category']}</td>";
        echo "<td>{$item['price']}</td>";
        echo "<td>{$item['code']}</td></tr>";
    }
    echo "</table>";
}

