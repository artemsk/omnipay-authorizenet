<?php

namespace Omnipay\AuthorizeNet\Message;

use Omnipay\AuthorizeNet\Model\CardReference;
use Omnipay\AuthorizeNet\Model\TransactionReference;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\AbstractResponse;

/**
 * Authorize.Net AIM getHostedPaymentPageRequest Response
 */
class AIMGetHostedPaymentPageResponse extends AbstractResponse
{
    public function __construct(AbstractRequest $request, $data)
    {
        // Strip out the xmlns junk so that PHP can parse the XML
        $xml = preg_replace('/<getHostedPaymentPageResponse[^>]+>/', '<getHostedPaymentPageResponse>', (string)$data);

        try {
            $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOWARNING);
        } catch (\Exception $e) {
            throw new InvalidResponseException();
        }

        if (!$xml) {
            throw new InvalidResponseException();
        }

        parent::__construct($request, $xml);
    }

    public function isSuccessful()
    {
        return $this->getResultCode() == 'Ok' && $this->getReasonCode() == 'I00001';
    }

    /**
     *
     * @return string Ok, Error
     */
    public function getResultCode()
    {
        return isset($this->data->messages->resultCode) ? $this->data->messages->resultCode : 'Error';
    }

    /**
     * A more detailed version of the Result/Response code.
     *
     * @return int|null
     */
    public function getReasonCode()
    {
        $code = null;

        if (isset($this->data->transactionResponse[0]->messages)) {
            // In case of a successful transaction, a "messages" element is present
            $code = intval((string)$this->data->transactionResponse[0]->messages[0]->message->code);

        } elseif (isset($this->data->transactionResponse[0]->errors)) {
            // In case of an unsuccessful transaction, an "errors" element is present
            $code = intval((string)$this->data->transactionResponse[0]->errors[0]->error->errorCode);

        } elseif (isset($this->data->messages[0]->message)) {
            // In case of invalid request, the top-level message provides details.
            $code = (string)$this->data->messages[0]->message->code;
        }

        return $code;
    }

    /**
     * Text description of the status.
     *
     * @return string|null
     */
    public function getMessage()
    {
        $message = null;

        if (isset($this->data->transactionResponse[0]->messages)) {
            // In case of a successful transaction, a "messages" element is present
            $message = (string)$this->data->transactionResponse[0]->messages[0]->message->description;

        } elseif (isset($this->data->transactionResponse[0]->errors)) {
            // In case of an unsuccessful transaction, an "errors" element is present
            $message = (string)$this->data->transactionResponse[0]->errors[0]->error->errorText;

        } elseif (isset($this->data->messages[0]->message)) {
            // In case of invalid request, the top-level message provides details.
            $message = (string)$this->data->messages[0]->message->text;
        }

        return $message;
    }

    public function getToken()
    {
        return isset($this->data->token) ? (string)$this->data->token : null;
    }
}
