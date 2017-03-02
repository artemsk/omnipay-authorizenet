<?php

namespace Omnipay\AuthorizeNet\Message;

/**
 * Authorize.Net AIM getHostedPaymentPageRequest Request
 */
class AIMGetHostedPaymentPageRequest extends AIMPurchaseRequest
{
    protected $requestType = 'getHostedPaymentPageRequest';

    public function getData()
    {
        $this->validate('amount');
        $data = new \SimpleXMLElement('<' . $this->requestType . '/>');
        $data->addAttribute('xmlns', 'AnetApi/xml/v1/schema/AnetApiSchema.xsd');

        $this->addAuthentication($data);
        $this->addTransactionType($data);
        $data->transactionRequest->amount = $this->getAmount();

        $data->transactionRequest->order->invoiceNumber = $this->getInvoiceNumber();
        $data->transactionRequest->order->description = $this->getDescription();
        $this->addItems($data);

        $data->transactionRequest->poNumber = $this->getPoNumber();
        $this->addBillingData($data);        
        $this->addCustomerIP($data);
        $this->addTransactionSettings($data);

        $data->addChild('hostedPaymentSettings');
        foreach($this->getHostedPaymentSettings() ?: [] as $settingName => $settingValue) {
            $this->addHostedPaymentSettings($data, $settingName, $settingValue);
        }

        return $data;
    }

    protected function addItems(\SimpleXMLElement $data)
    {
        $items = $this->getItems();
        if($items) {
            $xmlLineItems = $data->transactionRequest->addChild('lineItems');
            foreach($items as $n => $item) {                
                $xmlLineItem = $xmlLineItems->addChild('lineItem');
                $xmlLineItem->itemId = $n;
                $xmlLineItem->name = $item->getName();
                $xmlLineItem->description = $item->getDescription();
                $xmlLineItem->quantity = $item->getQuantity();
                $xmlLineItem->unitPrice = $this->formatCurrency($item->getPrice());
            }
        }
    }

    protected function addHostedPaymentSettings($data, $name, $value)
    {
        $setting = $data->hostedPaymentSettings->addChild('setting');
        $setting->settingName = $name;
        $setting->settingValue = json_encode($value);
    }

    public function setHostedPaymentSettings($value)
    {
        return $this->setParameter('hostedPaymentSettings', $value);
    }

    public function getHostedPaymentSettings()
    {
        return $this->getParameter('hostedPaymentSettings');
    }

    public function sendData($data)
    {
        $headers = array('Content-Type' => 'text/xml; charset=utf-8');

        $data = $data->saveXml();
        $httpResponse = $this->httpClient->post($this->getEndpoint(), $headers, $data)->send();

        return $this->response = new AIMGetHostedPaymentPageResponse($this, $httpResponse->getBody());
    }

    public function getPoNumber()
    {
        return $this->getParameter('poNumber');
    }

    public function setPoNumber($value)
    {
        return $this->setParameter('poNumber', $value);
    }
}
