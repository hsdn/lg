<?php
$urlProtocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$url = $urlProtocol . "://" . $_SERVER['SERVER_NAME'] . "/";
?>
<html>
    <head>
        <Body>
            <h1>403 Forbidden</h1>
            <p>reCAPTCHA was unsuccessful or expired, redirecting...</p>
        </Body>
    </head>
    <Script>
        setTimeout(function () {
            window.location.href = "<?php echo $url; ?>";
        }, 2200);
    </Script>
</html>