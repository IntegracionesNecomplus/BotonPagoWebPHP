<?php
    include 'config.inc.php';

    function generateToken() {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => VISA_URL_SECURITY,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
            "Accept: */*",
            'Authorization: '.'Basic '.base64_encode(VISA_USER.":".VISA_PWD)
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    function generateSesion($amount, $token) {
        $session = array(
            'channel' => 'web',
            'amount' => $amount,
            'antifraud' => array(
              'clientIp' => $_SERVER['REMOTE_ADDR'], // Obtener la IP del cliente en forma dinámica.
              'merchantDefineData' => array(
                //Todos los MDDs, tienen que ser dinámicos
                'MDD4' => "integraciones.guillermo@necomplus.com", //correo electrónico del cliente
                'MDD32' => '250376', // ID del cliente. Puede ser DNI, email.
                'MDD75' => 'Registrado', // Invitado | Registrado | Empleado
                'MDD77' => '7' // Se calculan los días transcurridos desde el registro del cliente a la fecha que hace el pago.
              ),
            ),
            'dataMap' => array(
                //La información deberá ser del cliente, en caso no la tenga, colocar la información del comercio
              'cardholderCity' => 'Lima',
              'cardholderCountry' => 'PE',
              'cardholderAddress' => 'Av Principal A-5. Campoy',
              'cardholderPostalCode' => '15046',
              'cardholderState' => 'LIM',
              'cardholderPhoneNumber' => '986322205' //Campo que si puede ser llenado con datos del cliente
            )
        );
        $json = json_encode($session);
        $response = json_decode(postRequest(VISA_URL_SESSION, $json, $token));
        return $response->sessionKey;
    }

    function generateAuthorization($amount, $purchaseNumber, $transactionToken, $token) {
        $data = array(
            'captureType' => 'manual',
            'channel' => 'web',
            'countable' => true, //True: Liquidación Automática | False: Liquidación Manual
            'order' => array(
                'amount' => $amount,
                'currency' => 'PEN',
                'purchaseNumber' => $purchaseNumber,
                'tokenId' => $transactionToken
            ),

            'dataMap' => array(
                'urlAddress' => 'https://desarrolladores.niubiz.com.pe/', //Debe capturar la url del comercio, no debe ir en duro.
                'partnerIdCode' => '', //deberá viajar en blanco | Si el comercio lo posee, deberá de colocarlo.
                'serviceLocationCityName' => 'LIMA',
                'serviceLocationCountrySubdivisionCode' => 'LIMA',
                'serviceLocationCountryCode' => 'PER',
                'serviceLocationPostalCode' => '15074'
            )
        );
        $json = json_encode($data);
        $session = json_decode(postRequest(VISA_URL_AUTHORIZATION, $json, $token));
        return $session;
    }

    function postRequest($url, $postData, $token) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                'Authorization: '.$token,
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => $postData
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    function generatePurchaseNumber(){
        $archivo = "assets/purchaseNumber.txt"; 
        $purchaseNumber = 222;
        $fp = fopen($archivo,"r"); 
        $purchaseNumber = fgets($fp, 100);
        fclose($fp); 
        ++$purchaseNumber; 
        $fp = fopen($archivo,"w+"); 
        fwrite($fp, $purchaseNumber, 100); 
        fclose($fp);
        return $purchaseNumber;
    }