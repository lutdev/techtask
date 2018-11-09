<?php
include_once 'config.php';
include_once 'simple_html_dom.php';

if(!AUTH_URL){
    $curl = curl_init('http://forumodua.com/');

    curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36');

    $result = curl_exec($curl);

    curl_close($curl);

    $html = str_get_html($result);

    /** @var simple_html_dom_node $form */
    $form = $html->find('#navbar_loginform', 0);
    $authAction = 'http://forumodua.com/'.$form->attr['action'];
} else{
    $authAction = AUTH_URL;
}

$curl = curl_init($authAction);
curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_COOKIESESSION, false);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HEADER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, [
    'vb_login_username' => USERNAME,
    'vb_login_md5password' => md5(PASSWORD),
    'vb_login_md5password_utf' => md5(PASSWORD),
    'securitytoken' => 'guest',
    'cookieuser' => 1,
    'do' => 'login',
    'vb_login_password' => ''
]);
curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36');

$result = curl_exec($curl);

preg_match_all('/Set-Cookie:\s*([^;]*)/', $result, $matches);

$cookies = '';

foreach($matches[1] as $item) {
    $cookies .= $cookies ? '; '.$item : $item;
}

if(!THEME_URL){
    throw new Error('Theme URL is empty');
}

curl_setopt($curl, CURLOPT_URL, THEME_URL);
curl_setopt($curl, CURLOPT_HTTPGET, true);
curl_setopt($curl, CURLOPT_COOKIESESSION, false);
curl_setopt($curl, CURLOPT_COOKIE, $cookies);

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
        $date = preg_replace('/[&nbsp;]+/', ' ', trim($postDate->find('.date', 0)->text()));
        $clearDate = preg_replace('/[ \.:]+/', '-', trim($date));

        $fileName = $theme.'-'.$clearDate.'.txt';

        $messageTitle = $message->find('h2.title', 0) ? $message->find('h2.title', 0)->text() : '';
        $messageAuthor = $message->find('.userinfo', 0)->find('.username', 0)->text();
        $messageText = $message->find('.postbody', 0)->find('blockquote.postcontent', 0)->text();

        $fileContent = trim($messageTitle).PHP_EOL.$messageAuthor.PHP_EOL.$date.PHP_EOL.trim($messageText);

        file_put_contents($folder.DIRECTORY_SEPARATOR.$fileName, $fileContent);
    }
}