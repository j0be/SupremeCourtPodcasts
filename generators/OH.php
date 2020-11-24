<?php
    // BASE OAUTH OBJECTS
    require('../OAuth2/Client.php');
    require('../OAuth2/GrantType/IGrantType.php');
    require('../OAuth2/GrantType/AuthorizationCode.php');
    $userAgent = 'SupremeCourt/0.1 by j0be';
    $client = new OAuth2\Client(null, null, OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
    $client->setCurlOption(CURLOPT_USERAGENT, $userAgent);
    $client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);

    $feedUrl = 'https://ohiochannel.org/collections/supreme-court-of-ohio?collections=108262&dir=DESC&keywords=Search+Collection&pageSize=96&sort=CreationDate&start=1';
    $response = $client->fetch($feedUrl);

    echo $response;
?>