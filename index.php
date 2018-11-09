<?php
include_once 'simple_html_dom.php';

$curl = curl_init('http://forumodua.com/showthread.php?t=851487');

curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36');
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Content-Type: text/xml; charset=utf-8",
    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
    "Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,uk;q=0.6"
]);

$result = curl_exec($curl);

curl_close($curl);

$html = str_get_html($result);

$messages = $html->find('.postbitlegacy');
$theme = $html->find('title', 0)->text();
$folder = __DIR__.DIRECTORY_SEPARATOR.'posts';

if($messages){
    /** @var simple_html_dom_node $message */
    foreach($messages as $message){
        if(!isset($message->attr['id'])){
            continue;
        }

        $isMessage = preg_match('/^post/', $message->attr['id']);

        if($isMessage === false){
            continue;
        }

        $postDate = $message->find('.postdate', 0);
        $date = $postDate->find('.date', 0)->text();
        $clearDate = preg_replace('/[ &nbsp;\.:]+/', '-', trim($date));

        $fileName = $theme.'-'.$clearDate.'.txt';

        $messageTitle = $message->find('h2.title', 0) ? $message->find('h2.title', 0)->text() : '';
        $messageAuthor = $message->find('.userinfo', 0)->find('.username', 0)->text();
        $messageText = $message->find('.postbody', 0)->find('blockquote.postcontent', 0)->text();

        $fileContent = $messageTitle.PHP_EOL.$messageAuthor.PHP_EOL.$messageText;

        file_put_contents($folder.DIRECTORY_SEPARATOR.$fileName, $fileContent);
    }
}