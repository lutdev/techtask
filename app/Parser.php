<?php
namespace App;

use Exception;
use simple_html_dom;
use simple_html_dom_node;

class Parser
{
    protected $forumURL;

    protected $curl;

    protected $defaultUserAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 '.
        '(KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36';

    protected $userAgent;

    protected $logger;

    protected $htmlDom;

    public function __construct()
    {
        $this->forumURL = getenv('FORUM_URL');
        $this->userAgent = getenv('USER_AGENT') ?: $this->defaultUserAgent;

        $this->logger = new Logger();

        $this->htmlDom = new simple_html_dom();
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        if(!$this->forumURL){
            throw new Exception('Forum URL is empty');
        }

        #region Auth action
        $authAction = $this->authAction();
        $cookies = '';

        if($authAction){
            $this->curl = curl_init($authAction);
            curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_COOKIESESSION, false);
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_HEADER, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, [
                'vb_login_username' => getenv('AUTH_USERNAME'),
                'vb_login_md5password' => md5(getenv('AUTH_PASSWORD')),
                'vb_login_md5password_utf' => md5(getenv('AUTH_PASSWORD')),
                'securitytoken' => 'guest',
                'cookieuser' => 1,
                'do' => 'login',
                'vb_login_password' => ''
            ]);

            curl_setopt($this->curl, CURLOPT_USERAGENT, $this->userAgent);

            $result = curl_exec($this->curl);

            preg_match_all('/Set-Cookie:\s*([^;]*)/', $result, $matches);

            foreach($matches[1] as $item) {
                $cookies .= $cookies ? '; '.$item : $item;
            }
        } else{
            $this->logger->addWarning('Auth action is empty');
        }
        #endregion

        if(!getenv('THEME_URL')){
            throw new Exception('Theme URL is empty');
        }

        curl_setopt($this->curl, CURLOPT_URL, getenv('THEME_URL'));
        curl_setopt($this->curl, CURLOPT_HTTPGET, true);
        curl_setopt($this->curl, CURLOPT_COOKIESESSION, false);
        curl_setopt($this->curl, CURLOPT_COOKIE, $cookies);

        $result = curl_exec($this->curl);

        curl_close($this->curl);

        $html = $this->htmlDom->load($result);

        if(!getenv('MESSAGE_POST_CLASS')){
            throw new Exception('Message post class is empty');
        }

        $messages = $html->find('.'.getenv('MESSAGE_POST_CLASS'));

        $theme = $html->find('title', 0)->text();

        if($messages){
            /** @var simple_html_dom_node $message */
            foreach($messages as $message){
                $this->saveMessage($message, $theme);
            }
        } else{
            $this->logger->addWarning('Messages are empty');
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function authAction(): string
    {
        if(getenv('AUTH_URL')) {
            return getenv('AUTH_URL');
        }

        $curl = curl_init($this->forumURL);

        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);

        $result = curl_exec($curl);

        curl_close($curl);

        $html = $this->htmlDom->load($result);

        $formID = getenv('AUTH_FORM_ID');

        /** @var simple_html_dom_node $form */
        $form = $html->find('#'.$formID, 0);

        if(!$form){
            $this->logger->addError('Form is empty on the page');

            return '';
        }

        return $this->forumURL.$form->attr['action'];
    }

    protected function postsFolder(): string
    {
        return __DIR__.'/../posts';
    }

    protected function saveMessage(simple_html_dom_node $message, string $theme)
    {
        if(!isset($message->attr['id'])){
            return $this;
        }

        $isMessage = preg_match('/^post/', $message->attr['id']);

        if($isMessage === false){
            return $this;
        }

        $postDate = $message->find('.'.getenv('MESSAGE_DATE_CLASS'), 0);

        $date = $postDate
            ? preg_replace('/[&nbsp;]+/', ' ', trim($postDate->find('.date', 0)->text()))
            : null;

        $clearDate = $postDate
            ? preg_replace('/[ \.:]+/', '-', trim($date))
            : null;

        $fileName = $theme.'-'.($clearDate ?: time()).'.txt';

        $titleDOM = $message->find('h2.title', 0);

        /** @var simple_html_dom_node $userNameDOM */
        $userNameDOM = $message->find('.userinfo', 0)->find('.username', 0);

        /** @var simple_html_dom_node $postContentDOM */
        $postContentDOM = $message->find('.postbody', 0)->find('blockquote.postcontent', 0);

        $messageTitle = $titleDOM ? $titleDOM->text() : '';
        $messageAuthor = $userNameDOM ? $userNameDOM->text() : '';
        $messageText = $postContentDOM ? $postContentDOM->text() : '';

        $fileContent = trim($messageTitle).PHP_EOL.$messageAuthor.PHP_EOL.$date.PHP_EOL.trim($messageText);

        file_put_contents($this->postsFolder().DIRECTORY_SEPARATOR.$fileName, $fileContent);

        return $this;
    }
}