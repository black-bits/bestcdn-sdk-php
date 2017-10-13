<?php

namespace BlackBits\BestCdn\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BestCdnException extends \Exception
{
    /**
     * @var array
     */
    private $errors;

    /**
     * @var RequestInterface|null
     */
    private $request;

    /**
     * @var ResponseInterface|null
     */
    private $response;

    /**
     * BestCdnException constructor.
     *
     * @param string $message
     * @param int $code
     * @param array $context
     * @param \Exception|null $previous
     */
    function __construct($message = "", $code = 500, array $context = [], \Exception $previous = null)
    {
        $this->errors    = isset($context['errors'])   ? $context['errors']   : [];
        $this->request   = isset($context['request'])  ? $context['request']  : null;
        $this->response  = isset($context['response']) ? $context['response'] : null;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return null|RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return null|ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }


    /**
     * @return string
     */
    public function __toString()
    {
        if (!$this->getPrevious()) {
            return parent::__toString();
        }

        // PHP strangely shows the innermost exception first before the outer
        // exception message. It also has a default character limit for
        // exception message strings such that the "next" exception (this one)
        // might not even get shown, causing developers to attempt to catch
        // the inner exception instead of the actual exception because they
        // can't see the outer exception's __toString output.
        return sprintf(
            "exception '%s' with message '%s'\n\n%s",
            get_class($this),
            $this->getMessage(),
            parent::__toString()
        );
    }
}
