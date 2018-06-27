<?php
/**
 * Instagram APIs library
 *
 * @author khanhkid@vinareseach.net
 * @since 20180306 9:40
 */
//https://github.com/facebook/php-graph-sdk
class Helper_GraphAPI
{
    /*
     * Create an array of the urls to be used in api calls
     * The urls contain conversion specifications that will be replaced by sprintf in the functions
     * @var string
     */
    protected $_api_urls = array(
        'url' => 'https://graph.facebook.com/v3.0/',
        'access_token_long_time' => '/oauth/access_token?',
        'access_token_redeeming' => '/oauth/client_code?',
        'information_user' => '/me',
        'information_page' => '/me/accounts',
        'associate_app_page' => '/%s/subscribed_apps',
        'get_data_mention' => '%s?fields=mentioned_media.media_id(%s){caption,media_type,media_url,owner,timestamp,username,permalink,children{media_url}}',
        'get_data_mention_comments' => '%s?fields=mentioned_media.media_id(%s){username,comments{username,timestamp,text,id,replies{username,text}}}&access_token=%s',
        'comment_mention' => '%s/mentions',
    );
    
    public function graphapi_contruct() 
    {
        $fb = new \Facebook\Facebook([
            'app_id' => getenv("INSTAGRAM_APP_ID"),
            'app_secret' => getenv("INSTAGRAM_APP_SECRET"),
            'default_graph_version' => 'v2.12',
            //'default_access_token' => $params['access_token'], // optional
        ]);
        return $fb;
    }
    
    public function explainData($response)
    {

        switch ($response->getHttpStatusCode()) {
            case 400:
                $data = $response->getResponseData();
                break;
            default:
                $data = $response->getDecodedBody();
                break;
        }
        return $data;
    }
    public function get_long_access_token($params)
    {

        $arrParams = array("grant_type=fb_exchange_token","client_id=".getenv("INSTAGRAM_APP_ID"),"client_secret=".getenv("INSTAGRAM_APP_SECRET"),"fb_exchange_token=".$params['access_token']);
        // echo '<pre>',var_dump($arrParams),'</pre>';die();
        $response = self::_callAPI(
            $this->_api_urls['access_token_long_time'].implode("&",$arrParams),
            $params['access_token']
        );
        return self::explainData($response);
    }

    public function get_redeem_code($params)
    {
        $arrParams = array("redirect_uri=","client_id=".getenv("INSTAGRAM_APP_ID"),"client_secret=".getenv("INSTAGRAM_APP_SECRET"),"access_token=".$params['access_token']);

        $response = self::_callAPI(
            $this->_api_urls['access_token_redeeming'].implode("&",$arrParams),
            $params['access_token']
        );

        return $response;
    }
    public function get_redeem_access_token($params)
    {
        $machine_id = (isset($params['machine_id']) && !is_null($params['machine_id']))?$params['machine_id']:"";
        $arrParams = array("code=".$params['code'],"client_id=".getenv("INSTAGRAM_APP_ID"),"redirect_uri=","machine_id=".$machine_id);

        $response = self::_callAPI(
            $this->_api_urls['access_token_long_time'].implode("&",$arrParams),
            $params['access_token']
        );

        return $response;
    }
    public function get_infomation_user($params)
    {
        $response = self::_callAPI(
            $this->_api_urls['information_user'],
            $params['access_token']
        );
        return self::explainData($response);
    }

    // GraphAPI return data through webhook 
    public function get_infomation_mention_media($params)
    {
        $url = sprintf($this->_api_urls['get_data_mention'], $params['instagram_id'], $params['media_id']);
        $response = self::_callAPI(
            $url,
            $params['access_token']
        );
        return self::explainData($response);
    }
    public function get_infomation_mention_comment($params)
    {
        $url = $this->_api_urls['url'].sprintf($this->_api_urls['get_data_mention_comments'], $params['instagram_id'], $params['media_id'],$params['access_token']);
        return $url;
    }
    public function get_infomation_pageList($params)
    {
        $response = self::_callAPI(
            $this->_api_urls['information_page'],
            $params['access_token']
        );
        return self::explainData($response);
    }
    public function get_infomation_pageDetail($params)
    {
        $response = self::_callAPI(
            "/".$params['facebook_page_id']."?fields=subscribed_apps,instagram_business_account{username,followers_count,follows_count,media_count}",
            $params['access_token']
        );
        return self::explainData($response);
    }
    // 1: reading sub; 2 register a subscription 3. delete a subsciption 
    public function get_subscription($params,$type = 1)
    {
        $url = sprintf($this->_api_urls['associate_app_page'],$params['facebook_page_id']);
        $response = self::_callAPI(
            $url,
            $params['access_token'],
            $type
        );
        return self::explainData($response);
    }
    public function get_comment_mention($params)
    {
        $url = sprintf($this->_api_urls['comment_mention'],$params['instagram_id']);
        $response = self::_callAPI(
            $url,
            $params['access_token'],
            2,
            $params
        );
        return self::explainData($response);
    }

    // Type : 1-> Get, 2 Post
    public function _callAPI($url, $accessToken, $type = 1, $arrParams = array()) {
        $fb = self::graphapi_contruct();
        $error = "";
        try {
            switch ($type) {
                case 2:
                     $response = $fb->post($url, $arrParams , $accessToken);
                    break;
                case 3:
                     $response = $fb->delete($url, $arrParams , $accessToken);
                    break;
                default:
                    $response = $fb->get($url, $accessToken);
                    break;
            }
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            $error = 'Graph returned an error: ' . $e->getMessage();
            $response = $e;
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            $error = 'Facebook SDK returned an error: ' . $e->getMessage();
            $response = $e;
        }
        return $response;
    }
}
