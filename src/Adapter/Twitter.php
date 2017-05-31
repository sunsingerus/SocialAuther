<?php

/**
 * @author: Vladlen Grachev (https://github.com/gwer)
 */
namespace SocialAuther\Adapter;

class Twitter extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $provider = 'twitter';

    /**
     * {@inheritDoc}
     */
    protected $responseType = 'oauth_token';

    /**
     * {@inheritDoc}
     */
    protected $socialFieldsMap = array(
        // local property name => external property name
        'socialId' => 'id',
        'name'     => 'name',
        'email'    => 'email',
        'sex'      => 'sex',
        'birthday' => 'bdate'
    );

    /**
     * Get user social id or null if it is not set
     *
     * @return string|null
     */
    public function getSocialPage()
    {
        if (isset($this->userInfo['screen_name'])) {
            return 'http://twitter.com/' . $this->userInfo['screen_name'];
        }

        return null;
    }

    /**
     * Get url of user's avatar or null if it is not set
     *
     * @return string|null
     */
    public function getAvatar()
    {
        if (isset($this->userInfo['profile_image_url'])) {
            return implode('', explode('_normal', $this->userInfo['profile_image_url']));
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate()
    {
        $result = false;

        if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])) {
            $authUrl = 'https://api.twitter.com/oauth/access_token';
            $params = array(
                'oauth_token'    => $_GET['oauth_token'],
                'oauth_verifier' => $_GET['oauth_verifier'],
            );
            $params = $this->appendServiceUrlParams($authUrl, $params);

            // Perform auth
            $authInfo = $this->get($authUrl, $params, false);
            parse_str($authInfo, $authInfo);

            if (isset($authInfo['oauth_token'])) {
                // Auth OK, can fetch additional info
                $getDataUrl = 'https://api.twitter.com/1.1/users/show.json';
                $params = array(
                    'oauth_token'      => $authInfo['oauth_token'],
                    'screen_name'      => $authInfo['screen_name'],
                    'include_entities' => 'false',
                );
                $params = $this->appendServiceUrlParams($getDataUrl, $params, $authInfo['oauth_token_secret']);

                // Fetch additional info
                $userInfo = $this->get($getDataUrl, $params);
                if (isset($userInfo['id'])) {
                    $this->userInfo = $userInfo;
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * NB
     * IMPORTANT
     * Twitter makes GET request to https://api.twitter.com/oauth/request_token in order to get preliminary token
     */
    public function getAuthUrlComponents()
    {
        $requestTokenUrl = 'https://api.twitter.com/oauth/request_token';
        $params = $this->appendServiceUrlParams($requestTokenUrl, array(
            'oauth_callback' => $this->redirectUri)
        );
        $requestTokens = $this->get($requestTokenUrl, $params, false);
        parse_str($requestTokens, $requestTokens);

        return array(
            'auth_url'    => 'https://api.twitter.com/oauth/authorize',
            'auth_params' => array(
                'oauth_token' => isset($requestTokens['oauth_token']) ? $requestTokens['oauth_token'] : null
            ),
        );
    }

    /**
     * Sign url params
     *
     * @param $url
     * @param array $params
     * @param string $oauth_token
     * @param string $type
     * @return array
     */
    private function appendServiceUrlParams($url, array $params = array(), $oauth_token = '', $type = 'GET')
    {
        $params += array(
            'oauth_consumer_key'     => $this->clientId,
            'oauth_nonce'            => md5(uniqid(rand(), true)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => time(),
            'oauth_token'            => $oauth_token,
            'oauth_version'          => '1.0',
        );
        ksort($params);
        $sigBaseStr = $type . '&' . urlencode($url) . '&' . urlencode(http_build_query($params));
        $key = $this->clientSecret . '&' . $oauth_token;
        $params['oauth_signature'] = base64_encode(hash_hmac("sha1", $sigBaseStr, $key, true));
        $params = array_map('urlencode', $params);

        return $params;
    }
}
