<?php

namespace Omnipay\AuthorizeNet\Message;

/**
 * Authorize.Net AIM Fetch Transaction Response
 */
class AIMFetchTransactionResponse extends AIMGetHostedPaymentPageResponse
{
    protected $requestType = 'getTransactionDetailsResponse';

    /**
     * For Error codes: @see https://developer.authorize.net/api/reference/responseCodes.html
     */
    const ERROR_RESPONSE_CODE_CANNOT_ISSUE_CREDIT = 54;

    /**
     * The overall transaction result code.
     */
    const TRANSACTION_RESULT_CODE_APPROVED = 1;
    const TRANSACTION_RESULT_CODE_DECLINED = 2;
    const TRANSACTION_RESULT_CODE_ERROR    = 3;
    const TRANSACTION_RESULT_CODE_REVIEW   = 4;
    
    public function isSuccessful()
    {
        return static::TRANSACTION_RESULT_CODE_APPROVED === $this->getResultCode();
    }

    /**
     * Status of the transaction. This field is also known as "Response Code" in Authorize.NET terminology.
     * A result of 0 is returned if there is no transaction response returned, e.g. a validation error in
     * some data, or invalid login credentials.
     *
     * @return int 1 = Approved, 2 = Declined, 3 = Error, 4 = Held for Review
     */
    public function getResultCode()
    {
        // If there is a transaction response, then we get the code from that.
        if (isset($this->data->transaction[0]->responseCode)) {
            return intval((string)$this->data->transaction[0]->responseCode);
        }

        // No transaction response, so return 3 aka "error".
        return static::TRANSACTION_RESULT_CODE_ERROR;
    }

    /**
     * A more detailed version of the Result/Response code.
     *
     * @return int|null
     */
    public function getReasonCode()
    {
        $code = null;

        if (isset($this->data->transaction[0]->messages)) {
            // In case of a successful transaction, a "messages" element is present
            $code = intval((string)$this->data->transaction[0]->messages[0]->message->code);

        } elseif (isset($this->data->transaction[0]->errors)) {
            // In case of an unsuccessful transaction, an "errors" element is present
            $code = intval((string)$this->data->transaction[0]->errors[0]->error->errorCode);

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

        if (isset($this->data->transaction[0]->messages)) {
            // In case of a successful transaction, a "messages" element is present
            $message = (string)$this->data->transaction[0]->messages[0]->message->description;

        } elseif (isset($this->data->transaction[0]->errors)) {
            // In case of an unsuccessful transaction, an "errors" element is present
            $message = (string)$this->data->transaction[0]->errors[0]->error->errorText;

        } elseif (isset($this->data->messages[0]->message)) {
            // In case of invalid request, the top-level message provides details.
            $message = (string)$this->data->messages[0]->message->text;
        }

        return $message;
    }

    public function getAuthorizationCode()
    {
        if (isset($this->data->transaction[0]->authCode)) {
            return (string)$this->data->transaction[0]->authCode;
        } else {
            return '';
        }
    }

    /**
     * Returns the Address Verification Service return code.
     *
     * @return string A single character. Can be A, B, E, G, N, P, R, S, U, X, Y, or Z.
     */
    public function getAVSCode()
    {
        if (isset($this->data->transaction[0]->AVSResponse)) {
            return (string)$this->data->transaction[0]->AVSResponse;
        } else {
            return '';
        }
    }
}
