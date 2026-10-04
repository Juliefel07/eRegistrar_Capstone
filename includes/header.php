<?php
// Determine root path for localhost vs production (Render)
$isLocalhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
$baseUrl = $isLocalhost ? '/eRegistrar' : '';
$domainUrl = $isLocalhost ? 'http://localhost/eRegistrar' : 'https://eregistrar-consolatrix.onrender.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eRegistrar | Online Document Request System</title>

    <!-- Dynamic Favicon / Tab Logo -->
    <link rel="icon" type="image/png" href="<?php echo $baseUrl; ?>/assets/images/logooo.png?v=4">

    <!-- Open Graph Link Preview Tags (Facebook / Messenger Sharing) -->
    <meta property="og:title" content="eRegistrar | Online Document Request System">
    <meta property="og:description" content="Manage your document requests, monitor progress, and receive real-time updates from the Registrar's Office.">
    <meta property="og:image" content="<?php echo $domainUrl; ?>/assets/images/logooo.png?v=4">
    <meta property="og:url" content="<?php echo $domainUrl; ?>/">
    <meta property="og:type" content="website">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css?v=2">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/student.css?v=2">
</head>
<body>