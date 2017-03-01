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
        
        $this->addBillingData($data);        
        $this->addCustomerIP($data);
        $this->addTransactionSettings($data);

        foreach(['hostedPaymentReturnOptions', 'hostedPaymentButtonOptions', 'hostedPaymentSecurityOptions',
            'hostedPaymentShippingAddressOptions', 'hostedPaymentBillingAddressOptions',
            'hostedPaymentCustomerOptions'] as $settingName => $settingValue) {
            
            $this->addMoreTransactionSettings($data, $settingName, $settingValue);
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

    protected function addMoreTransactionSettings($data, $name, $value)
    {
        $setting = $data->transactionRequest->transactionSettings->addChild('setting');
        $setting->settingName = $name;
        $setting->settingValue = json_encode($value);
    }

    public function sendData($data)
    {
        $headers = array('Content-Type' => 'text/xml; charset=utf-8');

        $data = $data->saveXml();
        $httpResponse = $this->httpClient->post($this->getEndpoint(), $headers, $data)->send();

        return $this->response = new AIMGetHostedPaymentPageResponse($this, $httpResponse->getBody());
    }
}
