<?php
if (isset($_POST["access"])) {
    $data = $_POST["access"];
    $cartTotal = $_POST["cartTotal"];
    $cust_name = $_POST["billing_first_name"];
    $cust_phone = $_POST["billing_phone"];
    $cust_email = $_POST["billing_email"];
    $cust_address = $_POST["billing_address_1"];
    $invoice_number = $_POST["invoice_number"];
    $baseurl = $_POST["baseurl"];
    $returnURL = $_POST["returnURL"];

    $header = array(
        'storeId:' . $data["merchantId"],
        'storePassword:' . $data["password"]
    );

    $url = curl_init("https://api.paystation.com.bd/grant-token");
    curl_setopt($url, CURLOPT_HTTPHEADER, $header);
    curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($url, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
    $tokenData = curl_exec($url);
    curl_close($url);
    $tokenArray = json_decode($tokenData, true);

    if (array_key_exists("status", $tokenArray) && $tokenArray["status"] == "success") {
        $token = $tokenArray["token"];
        $header = array(
            'token:' . $token
        );
        $post_feild = array(
            'invoice_number' => '000'.$invoice_number,
            'currency' => "BDT",
            'payment_amount' => $cartTotal,
            'cust_name' => $cust_name,
            'cust_phone' => $cust_phone,
            'cust_email' => $cust_email,
            'cust_address' => $cust_address,
            'reference' => "Website",
            'callback_url' => $returnURL,
            'checkout_items' => "checkout_items"
        );

        $url = curl_init("https://api.paystation.com.bd/create-payment");
        curl_setopt($url, CURLOPT_HTTPHEADER, $header);
        curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($url, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($url, CURLOPT_POSTFIELDS, $post_feild);
        curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
        $responseData = curl_exec($url);
        curl_close($url);
        $responseArray = json_decode($responseData, true);
        if (array_key_exists("status", $responseArray) && $responseArray["status"] == "success") {
            $rtData["status"] = "success";
            $rtData["statusCode"] = $responseArray["status_code"];
            $rtData["message"] = $responseArray["message"];
            $rtData["payment_url"] = $responseArray["payment_url"];
            echo json_encode($rtData);
        } else {
            $rtData["status"] = "failed";
            // $rtData["statusCode"] = $responseArray["statusCode"];
            $rtData["message"] = $responseArray["message"];
            echo json_encode($rtData);
        }
    } else {
        $rtData["status"] = "failed";
        $rtData["statusCode"] = $tokenArray["statusCode"];
        $rtData["message"] = $tokenArray["message"];
        echo json_encode($rtData);
    }
}
