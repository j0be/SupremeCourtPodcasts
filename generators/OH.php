<?php
    require('./func.php');
    // BASE OAUTH OBJECTS
    require('./OAuth2/Client.php');
    require('./OAuth2/GrantType/IGrantType.php');
    require('./OAuth2/GrantType/AuthorizationCode.php');
    $userAgent = 'SupremeCourt/0.1 by j0be';
    $client = new OAuth2\Client('', '', OAuth2\Client::AUTH_TYPE_AUTHORIZATION_BASIC);
    $client->setCurlOption(CURLOPT_USERAGENT, $userAgent);
    $client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);

    // $feedUrl = 'https://ohiochannel.org/collections/supreme-court-of-ohio?collections=108262&dir=DESC&keywords=Search+Collection&pageSize=2000&sort=CreationDate&start=3001';
    $feedUrl = 'https://ohiochannel.org/collections/supreme-court-of-ohio?collections=108262&dir=DESC&keywords=Search+Collection&pageSize=20&sort=CreationDate&start=1';
    $response = $client->fetch($feedUrl);

    $dom = new DomDocument();
    $dom->loadHTML($response['result']);
    $xpath = new DOMXpath($dom);

    $nodes = $xpath->query("//div[contains(@class,'mediaContainer')]");
    $urls = [];

    $baseUrl = explode('/', $feedUrl);
    array_pop($baseUrl);
    $baseUrl = implode('/', $baseUrl) . '/';

    $feedinfo_raw = file_get_contents('./generators/OH.json');
    $feedinfo = json_decode($feedinfo_raw ? $feedinfo_raw : '{}');

    foreach($nodes as $node) {
        foreach ($xpath->query(".//div[contains(@class,'mediaTitle')]//a", $node) as $child) {
            $text = $child->nodeValue;
            $some_exclusions = false;
            $exclusions = [
                'Court News'
            ];
            foreach ($exclusions as $exclusion) {
                $some_exclusions = $some_exclusions || strpos($text, $exclusion) !== false;
            }

            $some_inclusions = false;
            $inclusions = [
                'Case ',
                'State of the Judiciary',
                'Commission ',
                'Opinion Summary'
            ];
            foreach ($inclusions as $inclusion) {
                $some_inclusions = $some_inclusions || strpos($text, $inclusion) !== false;
            }

            if (!$some_exclusions && $some_inclusions) {
                $childUrl = $child->getAttribute('href');
                $url = removeDotPathSegments($baseUrl . $childUrl);

                echo $url, PHP_EOL;

                if (!isset($feedinfo->$childUrl)) {
                    $pageBaseUrl = explode('/', $url);
                    array_pop($pageBaseUrl);
                    $pageBaseUrl = implode('/', $pageBaseUrl) . '/';

                    $page_response = $client->fetch($url);

                    $page_dom = new DomDocument();
                    $page_dom->loadHTML($page_response['result']);
                    $page_xpath = new DOMXpath($page_dom);

                    $feed_item = json_decode('{}');
                    $feed_item->link = $url;

                    $id = '';
                    foreach ($page_xpath->query("//a[contains(@class,'miniButton')][contains(@href,'mp3')]") as $audio_link) {
                        preg_match_all("/\\d+/", $audio_link->getAttribute('href'), $id);
                        $id = $id[0][count($id[0]) - 2]; // minus two for "mp3"
                        $feed_item->id = $id;
                        $feed_item->enclosure_link = removeDotPathSegments($pageBaseUrl . $audio_link->getAttribute('href'));
                    }

                    $feed_item->title = trim(preg_split("/[\\r\\n]+/", trim($page_dom->getElementById("mediaProfileTitle")->nodeValue))[0]);

                    foreach ($page_xpath->query("//div[contains(@class,'primaryMediaInfoHeader')]//div[contains(@class,'desktopView')]") as $date) {
                        $feed_item->pubdate = trim($date->nodeValue);
                    }

                    foreach ($page_xpath->query("//div[contains(@class,'descriptionContainer')]//div[contains(@class,'panelBody')]") as $description) {
                        $feed_item->description = trim($description->nodeValue);
                    }

                    if (!empty($id)) {
                        preg_match("/http[^\\s]*?" . $id . "\\.(jpg|JPG)/", $page_response['result'], $image_match);

                        if ($image_match) {
                            $feed_item->image = $image_match[0];
                        }
                    }

                    foreach ($xpath->query(".//div[contains(@class,'durationDisplay')]", $node) as $duration) {
                        $feed_item->duration = $duration->nodeValue;
                    }


                    $feedinfo_raw = file_get_contents('./generators/OH.json');
                    $feedinfo = json_decode($feedinfo_raw ? $feedinfo_raw : '{}');

                    $feedinfo->$childUrl = $feed_item;

                    $myfile = fopen("./generators/OH.json", "w") or die("Unable to open feedinfo");
                    fwrite($myfile, json_encode($feedinfo));
                    fclose($myfile);
                }
            }
        }
    }

    print_r($feedinfo);
?>