<?php
$conn = new mysqli("localhost", "root", "", "sankalp_shop");
if ($conn->connect_error) die("Connection failed: ". $conn->connect_error);

$type = $_GET['type']?? '';
$inv = $_GET['inv']?? '';
$party = $_GET['party']?? '';

echo '<html><head><title>Invoice - Sankalp Gift Corner</title>
<meta charset="UTF-8">
<style>
    body{font-family:Arial, sans-serif; padding:20px; font-size:14px; color:#000}
    table{width:100%; border-collapse:collapse; margin:15px 0}
    th,td{border:1px solid #333; padding:8px; text-align:left}
    th{background:#f0f0f0}
 .header{text-align:center; border-bottom:2px solid #000; padding-bottom:10px; margin-bottom:15px}
 .header h2{margin:5px 0; color:#2c3e50; font-size:24px}
 .header h3{margin:5px 0; color:#34495e; font-size:18px}
 .header p{margin:3px 0; font-size:13px}
 .details{margin:15px 0}
 .details p{margin:5px 0}
 .total-box{text-align:right; margin-top:15px; font-size:16px}
 .total-box p{margin:3px 0}
 .sign{margin-top:60px; display:flex; justify-content:space-between}
 .no-print{margin-bottom:20px}
 .btn{background:#3498db; color:#fff; border:none; padding:10px 20px; border-radius:4px; cursor:pointer; margin-right:10px}
    @media print{
     .no-print{display:none}
        body{padding:10px}
        @page{margin:0.5in}
    }
</style>
</head><body>';

echo '<div class="no-print"><button class="btn" onclick="window.print()">🖨️ Print</button> <button class="btn" onclick="window.close()">Close</button></div>';

// 1. PURCHASE / SALE INVOICE
if($type == 'Purchase' || $type == 'Sale') {
    $where = $inv? "invoice_no='$inv'" : "(from_party='$party' OR to_party='$party')";
    $bill = $conn->query("SELECT * FROM bills WHERE bill_type='$type' AND $where ORDER BY id DESC LIMIT 1")->fetch_assoc();

    if($bill) {
        echo "<div class='header'>
                <h2>Sankalp Gift Corner</h2>
                <p>Main Road, Bhilai, Chhattisgarh | GSTIN: 22XXXXX1234X1ZX | Mob: 9876543210</p>
                <h3>$type Invoice</h3>
              </div>";

        echo "<div class='details'>
                <p><b>Invoice No:</b> {$bill['invoice_no']} &nbsp;&nbsp; | &nbsp;&nbsp; <b>Date:</b> ". date('d-m-Y', strtotime($bill['invoice_date'])). "</p>
                <p><b>From:</b> {$bill['from_party']}</p>
                <p><b>To:</b> {$bill['to_party']}</p>
              </div>";

        echo "<table>
                <tr>
                    <th>S.No</th><th>Item Name</th><th>HSN</th><th>Qty</th><th>Rate</th>
                    <th>Taxable Value</th><th>GST%</th><th>CGST</th><th>SGST</th><th>Total</th>
                </tr>";

        $items = $conn->query("SELECT * FROM bill_items WHERE invoice_no='{$bill['invoice_no']}'");
        $sno = 1;
        while($i = $items->fetch_assoc()) {
            echo "<tr>
                    <td>$sno</td>
                    <td>{$i['item_name']}</td>
                    <td>{$i['hsn_code']}</td>
                    <td>{$i['quantity']}</td>
                    <td>₹". number_format($i['rate'],2). "</td>
                    <td>₹". number_format($i['taxable_amount'],2). "</td>
                    <td>{$i['gst_percent']}%</td>
                    <td>₹". number_format($i['cgst_amount'],2). "</td>
                    <td>₹". number_format($i['sgst_amount'],2). "</td>
                    <td>₹". number_format($i['item_total'],2). "</td>
                  </tr>";
            $sno++;
        }
        echo "</table>";

        echo "<div class='total-box'>
                <p>Sub Total: ₹". number_format($bill['sub_total'],2). "</p>
                <p>CGST: ₹". number_format($bill['total_cgst'],2). "</p>
                <p>SGST: ₹". number_format($bill['total_sgst'],2). "</p>
                <p><b>Grand Total: ₹". number_format($bill['grand_total'],2). "</b></p>
                <p><i>Amount in Words: ". numberToWords($bill['grand_total']). " Only</i></p>
              </div>";

        echo "<div class='sign'>
                <div><br><br>___________________<br>Receiver's Signature</div>
                <div><br><br>For Sankalp Gift Corner<br>___________________<br>Authorised Signatory</div>
              </div>";
    } else {
        echo "<h3>Invoice Not Found</h3><p>No $type invoice found for Invoice No: '$inv' or Party: '$party'</p>";
    }
}

// 2. PAYMENT VOUCHER
elseif($type == 'Payment') {
    $where = $inv? "ref_invoice_no='$inv'" : "to_party='$party'";
    $pay = $conn->query("SELECT * FROM payments WHERE $where ORDER BY id DESC LIMIT 1")->fetch_assoc();

    if($pay) {
        echo "<div class='header'>
                <h2>Sankalp Gift Corner</h2>
                <h3>Payment Voucher</h3>
              </div>";

        echo "<div class='details'>
                <p><b>Voucher No:</b> PAY-{$pay['id']} &nbsp;&nbsp; | &nbsp;&nbsp; <b>Date:</b> ". date('d-m-Y', strtotime($pay['payment_date'])). "</p>
                <p><b>Paid To:</b> {$pay['to_party']}</p>
                <p><b>Payment Mode:</b> {$pay['payment_mode']}</p>
                <p><b>Ref Invoice No:</b> {$pay['ref_invoice_no']}</p>
              </div>";

        echo "<div class='total-box'>
                <p style='font-size:20px'><b>Amount Paid: ₹". number_format($pay['amount'],2). "</b></p>
                <p>Amount in Words: ". numberToWords($pay['amount']). " Only</p>
              </div>";

        echo "<div class='sign'>
                <div><br><br>___________________<br>Receiver's Signature</div>
                <div><br><br>For Sankalp Gift Corner<br>___________________<br>Authorised Signatory</div>
              </div>";
    } else {
        echo "<h3>Payment Record Not Found</h3><p>No payment found for Invoice: '$inv' or Party: '$party'</p>";
    }
}

// 3. RECEIPT VOUCHER
elseif($type == 'Receipt') {
    $where = $inv? "ref_invoice_no='$inv'" : "from_party='$party'";
    $rec = $conn->query("SELECT * FROM receipts WHERE $where ORDER BY id DESC LIMIT 1")->fetch_assoc();

    if($rec) {
        echo "<div class='header'>
                <h2>Sankalp Gift Corner</h2>
                <h3>Receipt Voucher</h3>
              </div>";

        echo "<div class='details'>
                <p><b>Receipt No:</b> REC-{$rec['id']} &nbsp;&nbsp; | &nbsp;&nbsp; <b>Date:</b> ". date('d-m-Y', strtotime($rec['receipt_date'])). "</p>
                <p><b>Received From:</b> {$rec['from_party']}</p>
                <p><b>Payment Mode:</b> {$rec['receipt_mode']}</p>
                <p><b>Ref Invoice No:</b> {$rec['ref_invoice_no']}</p>
              </div>";

        echo "<div class='total-box'>
                <p style='font-size:20px'><b>Amount Received: ₹". number_format($rec['amount'],2). "</b></p>
                <p>Amount in Words: ". numberToWords($rec['amount']). " Only</p>
              </div>";

        echo "<div class='sign'>
                <div><br><br>___________________<br>Payer's Signature</div>
                <div><br><br>For Sankalp Gift Corner<br>___________________<br>Authorised Signatory</div>
              </div>";
    } else {
        echo "<h3>Receipt Record Not Found</h3><p>No receipt found for Invoice: '$inv' or Party: '$party'</p>";
    }
}

else {
    echo "<h3>Invalid Request</h3><p>Please select Purchase, Sale, Payment or Receipt from Print tab</p>";
}

echo '</body></html>';

// Amount to Words Function
function numberToWords($num) {
    $num = number_format($num, 2, ".", "");
    $num_arr = explode(".", $num);
    $wholenum = $num_arr[0];
    $decnum = $num_arr[1];

    if($wholenum == 0) return "Zero";

    $words = numberToWordsHelper($wholenum);

    if($decnum > 0) {
        $words.= " and ". numberToWordsHelper($decnum). " Paise";
    }
    return trim($words);
}

function numberToWordsHelper($num) {
    $ones = array("", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen");
    $tens = array("", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety");

    $num = (int)$num;
    if($num == 0) return "";

    if($num < 20) {
        return $ones[$num];
    } elseif($num < 100) {
        return $tens[substr($num, 0, 1)]. " ". $ones[substr($num, 1, 1)];
    } elseif($num < 1000) {
        $result = $ones[substr($num, 0, 1)]. " Hundred";
        if(substr($num, 1, 2) > 0) {
            $result.= " ". numberToWordsHelper(substr($num, 1, 2));
        }
        return $result;
    } elseif($num < 100000) {
        $thousand = floor($num / 1000);
        $remainder = $num % 1000;
        $result = numberToWordsHelper($thousand). " Thousand";
        if($remainder > 0) $result.= " ". numberToWordsHelper($remainder);
        return $result;
    } elseif($num < 10000000) {
        $lakh = floor($num / 100000);
        $remainder = $num % 100000;
        $result = numberToWordsHelper($lakh). " Lakh";
        if($remainder > 0) $result.= " ". numberToWordsHelper($remainder);
        return $result;
    } else {
        $crore = floor($num / 10000000);
        $remainder = $num % 10000000;
        $result = numberToWordsHelper($crore). " Crore";
        if($remainder > 0) $result.= " ". numberToWordsHelper($remainder);
        return $result;
    }
}
?>