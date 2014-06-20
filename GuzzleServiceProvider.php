<?php

namespace Guzzle;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Guzzle service provider for Silex
 *
 * = Parameters:
 *  guzzle.services: (optional) array|string|SimpleXMLElement Data describing
 *      your web service clients.  You can pass the path to a file
 *      (.xml|.js|.json), an array of data, or an instantiated SimpleXMLElement
 *      containing configuration data.  See the Guzzle docs for more info.
 *  guzzle.plugins: (optional) An array of guzzle plugins to register with the
 *      client.
 *
 * = Services:
 *   guzzle: An instantiated Guzzle ServiceBuilder.
 *   guzzle.client: A default Guzzle web service client using a dumb base URL.
 *
 * @author Michael Dowling <michael@guzzlephp.org>
 */
class GuzzleServiceProvider implements ServiceProviderInterface
{
    /**
     * Register Guzzle with Silex
     *
     * @param Application $app Application to register with
     */
    public function register(Application $app)
    {
        $app['guzzle.base_url'] = '/';
        $app['guzzle.api_version'] = 'v1';
        $app['guzzle.default.headers'] = array();
        $app['guzzle.default.query'] = array();
        $app['guzzle.plugins'] = array();

        // Register a simple Guzzle Client object (requires absolute URLs when guzzle.base_url is unset)
        $app['guzzle.client'] = $app->share(function() use ($app) {
            $client = new \GuzzleHttp\Client([
                'base_url' => [$app['guzzle.base_url'] . '{version}', ['version' => $app['guzzle.api_version']]],
                'defaults' => [
                    'headers' => $app['guzzle.default.headers'],
                    'query'   => $app['guzzle.default.query'],
                    'auth'    => [],
                    'proxy'   => ''
                ]
            ]);

            //$client->setDefaultHeaders($app['guzzle.default.headers']);

            // Add apikey to query before every query
            // https://groups.google.com/forum/?hl=en#!topic/guzzle/CTzuOGPdhKE
            $client->getEmitter()->addListener('client.create_request', function (\Guzzle\Common\Event $e) use ($app) {

                $query = $e['request']->getQuery();
                $query->set('api_key', $app['wws.api_key']);
                $query->set('api_instance', $app['wws.api_instance']);
                $e['request']->getCurlOptions()->set(CURLOPT_TCP_NODELAY, 1);
            });

            foreach ($app['guzzle.plugins'] as $plugin) {
                $client->addSubscriber($plugin);
            }

            return $client;
        });
    }

    public function boot(Application $app)
    {
    }
}
