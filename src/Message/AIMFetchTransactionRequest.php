<?php

namespace Omnipay\AuthorizeNet\Message;

/**
 * Authorize.Net AIM Fetch Transaction Request
 */
class AIMFetchTransactionRequest extends AIMAbstractRequest
{
    protected $requestType = 'getTransactionDetailsRequest';
    protected $action = null;

    public function getData()
    {
        $this->validate('transactionReference');
        $data = new \SimpleXMLElement('<' . $this->requestType . '/>');
        $data->addAttribute('xmlns', 'AnetApi/xml/v1/schema/AnetApiSchema.xsd');        
        $this->addAuthentication($data);        
        $data->transId = $this->getTransactionReference()->getTransId();
        
        return $data;
    }

    public function sendData($data)
    {
        $headers = array('Content-Type' => 'text/xml; charset=utf-8');

        $data = $data->saveXml();

        $httpResponse = $this->httpClient->post($this->getEndpoint(), $headers, $data)->send();

        return $this->response = new AIMFetchTransactionResponse($this, $httpResponse->getBody());
    }
}
