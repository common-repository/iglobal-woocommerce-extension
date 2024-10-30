<?php

  include( plugin_dir_path( __FILE__ ) . 'Httpful/Bootstrap.php');
  include( plugin_dir_path( __FILE__ ) . 'Httpful/Http.php');
  include( plugin_dir_path( __FILE__ ) . 'Httpful/Request.php');

use \Exception as Exception;
use \Httpful\Request as Request;

class Iglobal
{
    const STATUS_FRAUD = "IGLOBAL_FRAUD_REVIEW"; // The order is currently under fraud review by iGlobal. *Settable by: iGlobal System
    const STATUS_PROCESS = "IGLOBAL_ORDER_IN_PROCESS"; // The order is valid and ready for processing. *Settable by: iGlobal System
    const STATUS_HOLD = "IGLOBAL_ORDER_ON_HOLD"; // The order is currently on a temporary status hold. *Settable by: iGlobal System
    const STATUS_CANCELLED = "IGLOBAL_ORDER_CANCELLED"; // The order has been cancelled in the iGlobal System. *Settable by: iGlobal System
    const STATUS_VENDOR_PREPARE = "VENDOR_PREPARING_ORDER"; // The vendor has marked the order in preparation. *Settable by: Vendor
    const STATUS_VENDOR_READY = "VENDOR_SHIPMENT_READY"; // The vendor has marked the order ready for shipping. *Settable by: Vendor
    const STATUS_VENDOR_LABELS = "VENDOR_LABELS_PRINTED_DATE"; // The vendor has printed shipping labels. *Settable by: Vendor
    const STATUS_VENDOR_CANCEL = "VENDOR_CANCELLATION_REQUEST"; // The order has been requested for cancellation. *Settable by: Vendor
    const STATUS_VENDOR_COMPLETE = "VENDOR_END_OF_DAY_COMPLETE"; // The order is finalized and complete. *Settable by: Vendor

    protected $_entryPoint = 'https://api.iglobalstores.com/v1/';
    protected $_store = null; // default store ID
    protected $_key = '';

    function __construct($store, $key)
    {
        $this->_store = $store;
        $this->_key = $key;
    }

    protected function callApi($path, $data, $entryPoint='https://api.iglobalstores.com/v1/', $headers = array())
    {
        $data = json_encode(array_merge($data, array('store' => $this->_store, 'secret' => $this->_key)));
        //TODO: Logging call for data
        $response = Request::post($entryPoint . $path)
            ->sendsJson()
            ->expectsJson()
            ->body($data)
            ->send();

        //TODO: Logging call for response
        if (!$response->hasErrors()) {
            return $response->body;
        }
        return false;
    }

    public function allOrders()
    {
        return $this->orderNumbers($sinceDate = '20150101');
    }

    public function orderNumbers($sinceOrderId = null, $sinceDate = null, $throughDate = null)
    {
        $data = array();
        if ($sinceOrderId) {
            $data['sinceOrderId'] = $sinceOrderId;
        } else if ($sinceDate) {
            $data['sinceDate'] = $sinceDate;
            if ($throughDate) {
                $data['throughDate'] = $throughDate;
            }
        } else {
            throw new Exception("sinceOrderId or sinceDate is required");
        }
        return $this->callApi('orderNumbers', $data);
    }

    public function orderDetails($orderId = null, $referenceId = null)
    {
        if ($orderId) {
            $data = array('orderId' => $orderId);
        } else if ($referenceId) {
            $data = array('referenceId' => $referenceId);
        } else {
            throw new Exception("orderId or referenceId is required");
        }
        return $this->callApi('orderDetail', $data, $entryPoint="https://api.iglobalstores.com/v2/");
    }

    public function updateMerchantOrderId($orderId, $merchantOrderId)
    {
        $data = array('orderId' => $orderId, 'merchantOrderId' => $merchantOrderId);
        return $this->callApi('updateMerchantOrderId', $data);
    }

    public function updateVendorOrderStatus($orderId, $orderStatus)
    {
        $data = array('orderId' => $orderId, 'orderStatus' => $orderStatus);
        return $this->callApi('updateVendorOrderStatus', $data);
    }

    public function createTempCart(array $data)
    {
        $data = json_encode(array_merge($data, array('storeId' => $this->_store, 'secret' => $this->_key)));
        //TODO: Logging call for data
        $response = Request::post($this->_entryPoint . "createTempCart")
            ->sendsJson()
            ->expectsJson()
            ->body($data)
            ->send();

        //TODO: Logging call for response
        if (!$response->hasErrors()) {
            return $response->body;
        }
        return false;
    }

    public function magentoRegionId($countryId, $region, $orderid)
    {
        $data = array('countryCode' => $countryId, 'region' => $region, 'orderId' => $orderid);
        return $this->callApi(
            'magento-region',
            $data,
            array("serviceToken" => "31ae7155-5b9e-461f-8353-9a8c3f8ae35974ffec3a-88dc-4acb-8420-270df7967338")
        );
    }
}
