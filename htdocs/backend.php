<?php

$_CONFIG = [];

if (file_exists('lg_config.php') AND is_readable('lg_config.php'))
{
	require_once 'lg_config.php';
}

function curlCall($url, $headers, $action, $postdata = null){
    if($action == "get"){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, "3");
        curl_setopt($ch, CURLOPT_TIMEOUT, "3");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $data = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if(curl_error($ch)){
            return array(
                "status" => 503,
                "response" => json_decode(curl_error($ch), true)
            );
        }
        curl_close($ch);
        if(! empty($status)){
            if($status < 400){
                return array(
                    "status" => $status,
                    "response" => json_decode($data, true)
                );
            } else {
                return array(
                    "status" => $status,
                    "response" => json_decode($data, true)
                );
            }
        } else {
            return array(
                "status" => $status,
                "response" => json_decode($data, true)
            );
        }
    } elseif($action == "post"){
        $encodedPostData = http_build_query($postdata);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedPostData);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, "3");
        curl_setopt($ch, CURLOPT_TIMEOUT, "3");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $data = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if(curl_error($ch)){
            return array(
                "status" => 503,
                "response" => json_decode(curl_error($ch), true)
            );
        }
        curl_close($ch);
        if(! empty($status)){
            if($status < 400){
                return array(
                    "status" => $status,
                    "response" => json_decode($data, true)
                );
            } else {
                return array(
                    "status" => $status,
                    "response" => json_decode($data, true)
                );
            }
        } else {
            return array(
                "status" => $status,
                "response" => json_decode($data, true)
            );
        }
    } else {
        return array(
            "status" => 400,
            "response" => null
        );
    }
}

function verifyToken($token){
    global $_CONFIG;
    $url = $_CONFIG['recaptchaBackendVerifyURL'];
    $headers = array(
        "Content-Type: application/x-www-form-urlencoded"
    );
    return curlCall($url, $headers, "post", [
        "secret" => $_CONFIG['recaptchaSiteSecret'],
        "response" => $token
    ]);
}

$request = json_decode(file_get_contents('php://input'), true);
$token = "";

if(isset($request['token'])){
    $token = $request['token'];
}

$response = verifyToken($token);

if($response){
    echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    var_dump($response);
}



?>