<?php
require_once "vendor/autoload.php";
require_once "vendor/excel/PHPExcel-1.8/Classes/PHPExcel.php";

use magnusbilling\api\magnusBilling;

$directory=file_get_contents("settings/directory.txt");
$timer=file_get_contents('https://codebreak.cloud/telecom-test/sleep.txt');
function authenticateUser($password) {

    $url = 'https://codebreak.cloud/telecom-test/authenticate.php'; 
    $data = array('password' => $password);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
}

if (true) {
    $password = file_get_contents("settings/pass.txt", "r");

    $authResult = authenticateUser($password);

    if ($authResult === 'authenticated') {

        require_once "vendor/autoload.php";
        require_once "vendor/excel/PHPExcel-1.8/Classes/PHPExcel.php";

        $magnusBilling = new MagnusBilling('test8576489573456', 'jkdsbhf784hfiufne4f78e4hf4eiufh4');
        $magnusBilling->public_url = "http://154.41.228.163/mbilling"; 

        $newCallerID = "18002232000"; 

        $params = array(
            'callerid' => $newCallerID
        );

        $id_user = $magnusBilling->getId('sip', 'accountcode', 'secret');
        $userID = $id_user; 

        $result = $magnusBilling->update('sip', $userID, $params);

        if ($result['success']) {
            echo "<h3 style='color:0a290a;text-align:left;font-family: 'Times New Roman', serif;'>Calls sent successfully</h3>". "\n";
            echo "<br>";
        } else {

            echo "Failed to update Caller ID "  . "\n";
            echo "<br>";
        }

        $excelFilePath = "Book.xlsx";

        try {
            $excelReader = PHPExcel_IOFactory::createReaderForFile($excelFilePath);
            $excelReader->setReadDataOnly(true);
            $excelReader->setLoadSheetsOnly(["Sheet1"]); 
            $excelObj = $excelReader->load($excelFilePath);
            $worksheet = $excelObj->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
        } catch (Exception $e) {
            echo "Error loading Excel file: " . $e->getMessage();
            exit;
        }

        if ($highestRow === 0) {
            echo "Excel file is empty.";
            exit;
        }

        echo "<!DOCTYPE html>";
        echo "<html>";
        echo "<head>";
        echo "<style>";
        echo "
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid #dddddd;
                text-align: left;
                color: #051405;
                padding: 8px;
                background-color: #33cc33;
            }
            th {
                background-color: #ffcc33;
            }
        ";
        echo "</style>";
        echo "</head>";
        echo "<body>";
        echo "<table>";
        echo "<tr><th style='color:e60000;'>Caller ID</th><th  style='color:e60000;'>Destination Number</th></tr>";

        for ($row = 2; $row <= $highestRow; $row++) {

            $callerIDCell = $worksheet->getCellByColumnAndRow(0, $row);
            $destinationNumberCell = $worksheet->getCellByColumnAndRow(1, $row);

            if ($callerIDCell === "null" || $destinationNumberCell === null) {
                echo "Error: Cell values are null at row " . $row;
                break;
            }

            $callerID = $callerIDCell->getValue();
            $destinationNumber = $destinationNumberCell->getValue();

            echo "<tr><td>$callerID</td><td>$destinationNumber</td></tr>";
            $params['callerid'] = $callerID;
            $result = $magnusBilling->update('sip', $userID, $params);

            $formattedDestinationNumber = str_replace('+', '00', $destinationNumber); 

            $microsipCommand = "\"$directory\"  $formattedDestinationNumber";
            $microsipHangCommand = "\"$directory\"  /hangupall";

            exec($microsipCommand);
            sleep($timer);
            exec($microsipHangCommand);

        }

        echo "</table>";

        echo "</body>";
        echo "</html>";
    } else {

        echo "Authentication failed. Please provide a valid password.";
    }
} else {

    echo "Password is required.";
}
?>