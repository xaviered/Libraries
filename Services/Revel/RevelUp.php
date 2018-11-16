<?php

namespace ixavier\Libraries\Services\Revel;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\ResponseInterface;

class RevelUp
{
    /**
     * @var array Options that will be sent for each request
     */
    protected $requestOptions = [
        'http_errors' => false,
        'synchronous' => false,
        'timeout' => 60,
        'headers' => [
            'Connection' => 'close',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Cache-Control' => 'no-cache',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36',
        ],
        'cookies' => null,
    ];

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var RevelUpMapper
     */
    private $revelUpUrl;

    // https://donatospol.revelup.com/login/?next=/export-import/product/
    public function __construct(RevelUpMapper $revelUpUrl)
    {
        $this->revelUpUrl = $revelUpUrl;
        $this->httpClient = new HttpClient();
    }

    public function getProducts()
    {
        $this->login();

        $products = $this->apiCall($this->revelUpUrl->url('export-products'));

        print_r([
            $this->requestOptions['cookies'],
            $products->getStatusCode(),
            $products->getHeaders(),
            $products->getBody()->getContents(),
        ]);

        return [];
    }

    /**
     * @param $url
     * @param string $method
     * @return ResponseInterface
     * @throws RevelUpException
     */
    private function apiCall($url, $method = 'get')
    {
        $this->login();
        $r = $this->httpClient->{$method}($url, $this->requestOptions);
        // try to login again because session might have expired
        if ($r->getStatusCode() < 200 || $r->getStatusCode() > 299) {
            $this->login(null, true);
            $r = $this->httpClient->{$method}($url, $this->requestOptions);

            // failed to make request
            if ($r->getStatusCode() < 200 || $r->getStatusCode() > 299) {
                throw new RevelUpException(
                    sprintf('Login failed with code %s', $r->getStatusCode()),
                    RevelUpException::LOGIN_FAILED
                );
            }
        }

        return $r;
    }

    protected function login(?array $nextParams = null, $force = false)
    {
        if (empty($this->requestOptions['cookies']) || $force) {
//            $this->requestOptions['cookies'] = new CookieJar();
//            $r = $this->httpClient->get($this->revelUpUrl->url('login', $nextParams), $this->requestOptions);
//
//            $page = simplexml_load_string($r->getBody()->getContents());
//            $page->xpath('/html/body')
//            print_r([
//                $r->getStatusCode(),
//                $r->getHeaders(),
//                $r->getBody()->getContents(),
//            ]);
//            // error
//            if ($r->getStatusCode() < 200 || $r->getStatusCode() > 299 || !$r->hasHeader('Set-Cookie')) {
//                throw new RevelUpException(
//                    sprintf('Login failed with code %s', $r->getStatusCode()),
//                    RevelUpException::LOGIN_FAILED
//                );
//            }
//            $this->session = $r->getHeader('Set-Cookie');
        }

        return $this;
    }

}
