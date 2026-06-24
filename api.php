<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "sankalp_shop");
if ($conn->connect_error) {
    ob_end_clean();
    die(json_encode(['status'=>'error','message'=>'DB Connection Failed']));
}

$action = $_GET['action']?? '';

if($action == 'get_stock') {
    $res = $conn->query("SELECT * FROM stock_master ORDER BY item_name");
    ob_end_clean();
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    exit;
}

elseif($action == 'save_bill') {
    $data = json_decode(file_get_contents('php://input'), true);
    if(!$data) {
        ob_end_clean();
        die(json_encode(['status'=>'error','message'=>'Invalid JSON']));
    }

    $conn->begin_transaction();
    try {
        $sub = $cgst = $sgst = $grand = 0;
        foreach($data['items'] as $i) {
            $sub += $i['taxable'];
            $cgst += $i['cgst'];
            $sgst += $i['sgst'];
            $grand += $i['total'];
        }

        $stmt = $conn->prepare("INSERT INTO bills (bill_type,invoice_no,invoice_date,from_party,to_party,sub_total,total_cgst,total_sgst,total_gst,grand_total) VALUES (?,?,?,?,?,?,?,?,?,?)");
        if(!$stmt) throw new Exception($conn->error);

        $total_gst = $cgst + $sgst;
        $stmt->bind_param("ssssssdddd", $data['bill_type'], $data['invoice_no'], $data['invoice_date'], $data['from_party'], $data['to_party'], $sub, $cgst, $sgst, $total_gst, $grand);
        if(!$stmt->execute()) throw new Exception($stmt->error);

        $stmt = $conn->prepare("INSERT INTO bill_items (invoice_no,item_name,hsn_code,quantity,rate,taxable_amount,gst_percent,cgst_amount,sgst_amount,item_total) VALUES (?,?,?,?,?,?,?,?,?,?)");
        if(!$stmt) throw new Exception($conn->error);

        foreach($data['items'] as $i) {
            $stmt->bind_param("sssddddddd", $data['invoice_no'], $i['name'], $i['hsn'], $i['qty'], $i['rate'], $i['taxable'], $i['gst'], $i['cgst'], $i['sgst'], $i['total']);
            if(!$stmt->execute()) throw new Exception($stmt->error);

            if($data['bill_type'] == 'Purchase') {
                $sql = "UPDATE stock_master SET current_stock = current_stock + {$i['qty']}, purchase_rate = {$i['rate']}, gst_percent = {$i['gst']} WHERE item_name =?";
                $stmt2 = $conn->prepare($sql);
                $stmt2->bind_param("s", $i['name']);
                $stmt2->execute();
                if($stmt2->affected_rows == 0) {
                    $conn->query("INSERT INTO stock_master (item_name,hsn_code,current_stock,purchase_rate,gst_percent) VALUES ('{$conn->real_escape_string($i['name'])}','{$conn->real_escape_string($i['hsn'])}',{$i['qty']},{$i['rate']},{$i['gst']})");
                }
            } else {
                $sql = "UPDATE stock_master SET current_stock = current_stock - {$i['qty']}, sale_rate = {$i['rate']} WHERE item_name =?";
                $stmt2 = $conn->prepare($sql);
                $stmt2->bind_param("s", $i['name']);
                $stmt2->execute();
            }
        }
        $conn->commit();
        ob_end_clean();
        echo json_encode(['status'=>'success']);
    } catch(Exception $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
    exit;
}

elseif($action == 'save_payment') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO payments (payment_date,payment_mode,to_party,amount,ref_invoice_no) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssds", $data['payment_date'], $data['payment_mode'], $data['to_party'], $data['amount'], $data['ref_invoice_no']);
    ob_end_clean();
    echo json_encode(['status'=>$stmt->execute()? 'success' : 'error','message'=>$stmt->error]);
    exit;
}

elseif($action == 'save_receipt') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO receipts (receipt_date,receipt_mode,from_party,amount,ref_invoice_no) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssds", $data['receipt_date'], $data['receipt_mode'], $data['from_party'], $data['amount'], $data['ref_invoice_no']);
    ob_end_clean();
    echo json_encode(['status'=>$stmt->execute()? 'success' : 'error','message'=>$stmt->error]);
    exit;
}

elseif($action == 'get_invoices') {
    $inv = $conn->query("SELECT DISTINCT invoice_no FROM bills ORDER BY id DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
    $party = $conn->query("SELECT DISTINCT from_party as p FROM bills UNION SELECT DISTINCT to_party FROM bills")->fetch_all(MYSQLI_ASSOC);
    ob_end_clean();
    echo json_encode(['invoices'=>array_column($inv,'invoice_no'),'parties'=>array_column($party,'p')]);
    exit;
}

elseif($action == 'upload') {
    $type = $_POST['type'];
    $file = $_FILES['file']['tmp_name'];
    $count = 0;
    $error = '';

    if(!$file) {
        ob_end_clean();
        die(json_encode(['status'=>'error','message'=>'No file uploaded']));
    }

    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

    try {
        // CSV Upload
        if($ext == 'csv') {
            if(($handle = fopen($file, "r"))!== FALSE) {
                fgetcsv($handle); // Skip header
                while(($data = fgetcsv($handle))!== FALSE) {
                    if($type == 'stock_master' && count($data) >= 7) {
                        $stmt = $conn->prepare("INSERT INTO stock_master (item_name,hsn_code,current_stock,min_stock_alert,purchase_rate,sale_rate,gst_percent) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE current_stock=?,purchase_rate=?,sale_rate=?,gst_percent=?");
                        $stmt->bind_param("ssdddddd ddd", $data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[2],$data[4],$data[5],$data[6]);
                        if($stmt->execute()) $count++;
                    }
                    elseif($type == 'bills' && count($data) >= 6) {
                        // CSV format: bill_type,invoice_no,invoice_date,from_party,to_party,grand_total
                        $stmt = $conn->prepare("INSERT IGNORE INTO bills (bill_type,invoice_no,invoice_date,from_party,to_party,grand_total) VALUES (?,?,?,?,?,?)");
                        $stmt->bind_param("sssssd", $data[0],$data[1],$data[2],$data[3],$data[4],$data[5]);
                        if($stmt->execute()) $count++;
                    }
                }
                fclose($handle);
            }
        }
        // XML Upload
        elseif($ext == 'xml') {
            $xml = simplexml_load_file($file);
            if($xml === false) throw new Exception('Invalid XML file');

            if($type == 'stock_master') {
                foreach($xml->item as $item) {
                    $name = (string)$item->item_name;
                    $hsn = (string)$item->hsn_code;
                    $stock = (float)$item->current_stock;
                    $min = (float)$item->min_stock_alert;
                    $prate = (float)$item->purchase_rate;
                    $srate = (float)$item->sale_rate;
                    $gst = (float)$item->gst_percent;

                    $stmt = $conn->prepare("INSERT INTO stock_master (item_name,hsn_code,current_stock,min_stock_alert,purchase_rate,sale_rate,gst_percent) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE current_stock=?,purchase_rate=?,sale_rate=?,gst_percent=?");
                    $stmt->bind_param("ssdddddd ddd", $name,$hsn,$stock,$min,$prate,$srate,$gst,$stock,$prate,$srate,$gst);
                    if($stmt->execute()) $count++;
                }
            }
            elseif($type == 'bills') {
                foreach($xml->bill as $bill) {
                    $stmt = $conn->prepare("INSERT IGNORE INTO bills (bill_type,invoice_no,invoice_date,from_party,to_party,grand_total) VALUES (?,?,?,?,?,?)");
                    $stmt->bind_param("sssssd", $bill->bill_type, $bill->invoice_no, $bill->invoice_date, $bill->from_party, $bill->to_party, $bill->grand_total);
                    if($stmt->execute()) $count++;
                }
            }
        } else {
            throw new Exception('Only CSV or XML files allowed');
        }

        ob_end_clean();
        echo json_encode(['status'=>'success','message'=>"Uploaded $count records successfully"]);

    } catch(Exception $e) {
        ob_end_clean();
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
    exit;
}