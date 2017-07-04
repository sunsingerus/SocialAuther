<?php

/**
 * SocialAuther
 *
 * @author: sunsingerus (https://github.com/sunsingerus)
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace SocialAuther\Provider;


class AuthProviderFactory
{
    /**
     * @var array mapping provider name => provider class for Factory
     */
    protected static $map = array(
        // provider name => provider class
        AuthProviderBase::PROVIDER_FACEBOOK      => Facebook::class,
        AuthProviderBase::PROVIDER_GOOGLE        => Google::class,
        AuthProviderBase::PROVIDER_MAILRU        => Mailru::class,
        AuthProviderBase::PROVIDER_ODNOKLASSNIKI => Odnoklassniki::class,
        AuthProviderBase::PROVIDER_TWITTER       => Twitter::class,
        AuthProviderBase::PROVIDER_VKONTAKTE     => Vkontakte::class,
        AuthProviderBase::PROVIDER_YANDEX        => Yandex::class,
    );

    /**
     * @var array of all available providers
     */
    protected static $providers = array();

    /**
     * Instantiate all available providers into static array
     *
     * @param $config array of provider configuations
     * @return array instances of all available providers
     */
    public static function providers(array $config = array())
    {
        // build array of providers
        static::$providers = array();
        foreach ($config as $provider => $settings) {
            // config can contain whatever provider names, so need to check its valid
            if (AuthProviderBase::isProviderValid($provider)) {
                static::$providers[$provider] = static::create($provider, $settings);
            }
        }

        return static::$providers;
    }

    /**
     * Get provider by its name/id (One of AuthProviderBase::PROVIDER_*)
     *
     * @param null $provider name/id of the provider (One of AuthProviderBase::PROVIDER_*)
     * @return mixed|null provider instance, if available
     */
    public static function provider($provider = null)
    {
        return array_key_exists($provider, static::$providers) ? static::$providers[$provider] : null;
    }

    /**
     * Create instance of provider by its name/id (One of AuthProviderBase::PROVIDER_*)
     *
     * @param $provider provider name/id (One of AuthProviderBase::PROVIDER_*)
     * @param array $config configuration for provider to be created
     * @return mixed|null provider, if able to instansiate
     */
    public static function create($provider, array $config = array())
    {
        if (isset(static::$map[$provider])) {
            $class = static::$map[$provider];
            return new $class($config);
        }

        return null;
    }
}