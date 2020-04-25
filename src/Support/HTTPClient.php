<?php


namespace RobustTools\SMS\Support;

use RobustTools\SMS\Exceptions\BadRequestException;
use RobustTools\SMS\Exceptions\InternalServerErrorException;
use RobustTools\SMS\Exceptions\UnauthorizedException;
use SimpleXMLElement;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class HTTPClient
{
    /**
     * @var Client
     */
    private $client;

    public function __construct ()
    {
        $this->client = new Client(['http_errors' => false]);
    }

    /**
     * @param string $endpoint
     * @param array $headers
     * @param $requestBody
     * @return GuzzleHttp\Psr7\Response|SimpleXMLElement
     * @throws UnauthorizedException
     * @throws BadRequestException
     * @throws InternalServerErrorException
     */
    public function post (string $endpoint, array $headers, $requestBody)
    {
        $response = $this->client->request('POST', $endpoint, [
            'headers' => $headers,
            $requestBody
        ]);

        if ($response->getStatusCode() == 400) {
            throw new BadRequestException($response->getBody()->getContents());
        }

        if ($response->getstatusCode() == 401) {
            throw new UnauthorizedException('Unauthorized: Access is denied due to invalid credentials');
        }

        if ($response->getStatusCode() == 500) {
            throw new InternalServerErrorException("Internal Server Error");
        }

        if ($this->isXML($response->getHeader("Content-Type"))) {
            return $this->parseResponse($response);
        }

        return $response;
    }

    /**
     * Parse xml response.
     * @param ResponseInterface $response
     * @return SimpleXMLElement
     */
    private function parseResponse (ResponseInterface $response): SimpleXMLElement
    {
        return new SimpleXMLElement($response->getBody()->getContents());
    }

    /**
     * Detect response content type.
     * @param $contentTYpe
     * @return bool
     */
    private function isXML ($contentTYpe): bool
    {
        return array_pop($contentTYpe) == "application/xml";
    }
}
