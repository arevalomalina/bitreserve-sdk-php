<?php

namespace Bitreserve\HttpClient\Message;

use GuzzleHttp\Message\Response as BaseResponse;

/**
 * Response.
 */
class Response extends BaseResponse
{
    /**
     * Get API rate limits from the response headers.
     *
     * @return array
     */
    public function getApiRateLimit()
    {
        return array(
            'limit' => (string) $this->getHeader('X-RateLimit-Limit'),
            'remaining' => (string) $this->getHeader('X-RateLimit-Remaining'),
            'reset' => (string) $this->getHeader('X-RateLimit-Reset'),
        );
    }

    /**
     * Get the decoded body from the response.
     *
     * @return mixed
     */
    public function getContent()
    {
        $body = $this->getBody(true);
        $content = json_decode($body, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            return $body;
        }

        return $content;
    }

    /**
     * Get parsed content range header.
     *
     * @return array
     */
    public function getContentRange()
    {
        $contentRange = (string) $this->getHeader('Content-Range');

        if (!$contentRange) {
            return null;
        }

        $matches = array();
        preg_match('/^.*\ (\d*)-(\d*)\/(\d*)$/', $contentRange, $matches);

        return array(
            'count' => (int) $matches[3],
            'end' => (int) $matches[2],
            'start' => (int) $matches[1],
        );
    }

    /**
     * Get response error.
     *
     * @return string
     */
    public function getError()
    {
        $content = $this->getContent();

        if (!is_array($content)) {
            return 'unknown_error';
        }

        if (!empty($content['error'])) {
            return $content['error'];
        }

        if (!empty($content['code'])) {
            return $content['code'];
        }

        return 'unknown_error';
    }

    /**
     * Get error description.
     *
     * @return string
     */
    public function getErrorDescription()
    {
        $content = $this->getContent();

        if (!is_array($content)) {
            return 'An unknown error occurred';
        }

        if (!empty($content['errors'])) {
            return sprintf('Error List: %s', print_r($content['errors'], 1));
        }

        if (!empty($content['error_description'])) {
            return $content['error_description'];
        }

        if (!empty($content['message'])) {
            return $content['message'];
        }

        return 'An unknown error occurred';
    }

    /**
     * Checks if the response is a client error.
     *
     * @return boolean
     */
    public function isClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Checks if the response is a server error.
     *
     * @return boolean
     */
    public function isServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }
}
