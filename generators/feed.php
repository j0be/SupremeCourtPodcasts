<?php

setlocale(LC_CTYPE, "C.UTF-8");

header('Content-type: application/rss+xml; charset=utf-8');

$feeditems_raw = file_get_contents('./OH.json');
$feeditems = json_decode($feeditems_raw ? $feeditems_raw : '[]');
$media_base_path = './download/';
$date_fmt = 'D, d M Y H:i:s T';
$exts = array(
    'flac' => 'audio/flac',
    'm4a'  => 'audio/mp4',
    'm4b'  => 'audio/mp4',
    'mp3'  => 'audio/mp3',
    'mp4'  => 'audio/mp4',
    'oga'  => 'audio/ogg',
    'ogg'  => 'audio/ogg'
);

$xml = new DOMDocument('1.0', 'utf-8');

$xmlstr = '<?xml version="1.0" encoding="UTF-8"?><rss/>';
$rss = new SimpleXMLElement($xmlstr);
$rss->addAttribute('version', '2.0');
$rss->addAttribute('xmlns:xmlns:atom', 'http://www.w3.org/2005/Atom');
$rss->addAttribute('xmlns:xmlns:itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
$channel = $rss->addChild('channel');
$channel->addChild('title', 'Ohio Supreme Court');
$channel->addChild('link', 'https://ohiochannel.org/collections/supreme-court-of-ohio');
$channel->addChild('pubDate', date($date_fmt));
$channel->addChild('lastBuildDate', date($date_fmt));
$atomlink = $channel->addChild('atom:atom:link');
$atomlink->addAttribute('rel', 'self');
$atomlink->addAttribute('type', 'application/rss+xml');

$image = $channel->addChild('image');
$image->addChild('url', 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Seal_of_the_Supreme_Court_of_Ohio.svg/1200px-Seal_of_the_Supreme_Court_of_Ohio.svg.png');
$image->addChild('title', 'Ohio Supreme Court');
$image->addChild('link', 'https://ohiochannel.org/collections/supreme-court-of-ohio');


foreach ($feeditems as $feeditem) {
    if (!empty($feeditem->enclosure_link)) {

        $item = $channel->addChild('item');
        $item->addChild('title', htmlspecialchars($feeditem->title));
        $guid = $item->addChild('guid', $feeditem->id);
        $guid->addAttribute('isPermalink', 'false');
        $enclosure = $item->addChild('enclosure');
        $enclosure->addAttribute('url', $feeditem->enclosure_link);
        $enclosure->addAttribute('type', $exts['mp3']);
        $item->addChild('description', htmlspecialchars($feeditem->description));
        $item->addChild('link', $feeditem->link);

        $pubdate = $feeditem->pubdate . ' 12:00:00 EST';
        $date = DateTime::createFromFormat('F j, Y h:i:s T', $pubdate);
        if ($date !== false) {
            $item->addChild('pubDate', $date->format(DateTime::RSS));
        }

        if (!empty($feeditem->duration)) {
            $item->addChild('xmlns:itunes:duration', $feeditem->duration);
        }

        if (!empty($feeditem->image)) {
            $thumb = $item->addChild('xmlns:itunes:image');
            $thumb->addAttribute('href', $feeditem->image);
        }
    }
}

/**
 * Output feed
 */
echo $rss->asXML();

ob_start();
include("OH.php");
ob_end_clean();
?>