<?php
namespace Mavlyan;

#Coupon
$cardNumber = '0123456789';
$wsdlUrl    = 'http://stage.magento.shop/api/v2_soap?wsdl=1';
$apiUser    = 'userapi';
$apiKey     = 'apikey';

$arData['login']    = 'BAA_login';
$arData['password'] = 'BAA_password';
$arData['trace']    = 'true';

$proxy = new \SoapClient($wsdlUrl, $arData);

//common object to store all data for order creation
$total = new \stdClass();

//Check your store ID
$total->storeId = 1;

try {
    $sessionId = connect();
    createCart($proxy, $sessionId);
    addAddress();
    addPayment();
    $couponResult = applyCoupon();
    $orderId      = placeOrder();

    echo '<pre>';
    var_dump($orderId);
    var_dump($couponResult);
    var_dump($total);
    echo '<pre>';
} catch (\Exception $e) {
    echo '<h1>Fail</h1>';
    echo '<p>' . $e->getMessage() . '</p>';
    echo '<p>' . $e->getTraceAsString() . '</p>';
    var_dump($total);
}

function connect()
{
    global $apiUser, $apiKey, $proxy;
    $sessionId = $proxy->login((object)
    [
        'username' => $apiUser,
        'apiKey'   => $apiKey
    ]
    );
    $sessionId->sessionId = $sessionId->result;

    return $sessionId;
}

function createCart($proxy, $sessionId)
{
    global $total;

    $cartId            = $proxy->shoppingCartCreate($sessionId);
    $total->quoteId    = $cartId->result;
    $total->sessionId  = $sessionId->sessionId;

    //add product to cart
    $total->productId  = 365733;//371034;
    $total->filters    = [
        ['sku' => 'N252010000', 'id' => 365733]
    ];
    $product        = $proxy->catalogProductInfo($total);
    $product        = (array)$product->result;
    $product['qty'] = 1;

    $total->productsData = [$product];
    $proxy->shoppingCartProductAdd($total);

    return $total;
}

function addAddress() {
    global $total, $proxy;

    //load existing customer with id = 370
    $total->customerId = 370;

    $customer = $proxy->customerCustomerInfo($total);
    $customer->result->mode = 'customer';
    $customer->mode = 'customer';
    $total->customerData = (array)$customer->result;

    $proxy->shoppingCartCustomerSet($total);

    $address = [
        [
            'mode'                => 'shipping',
            'firstname'           => $customer->result->firstname,
            'lastname'            => $customer->result->lastname,
            'street'              => 'street address',
            'city'                => 'Moscow',
            'region'              => 'region',
            'telephone'           => '123123123123',
            'postcode'            => '123123',
            'country_id'          => '6',
            'is_default_shipping' => 0,
            'is_default_billing'  => 0
        ],
        [
            'mode'                => 'billing',
            'firstname'           => $customer->result->firstname,
            'lastname'            => $customer->result->lastname,
            'street'              => 'street address',
            'city'                => 'city',
            'region'              => 'region',
            'telephone'           => 'phone number',
            'postcode'            => 'postcode',
            'country_id'          => 'country ID',
            'is_default_shipping' => 0,
            'is_default_billing'  => 0
        ],
    ];

    $total->customerAddressData = $address;

    // add customer address
    $proxy->shoppingCartCustomerAddresses($total);

    //TODO: Load here all shipping methods and select 1st one

    $total->shippingMethod = 'flatrate_flatrate';
    //    $total->shippingMethod = 'cdek_1';
    //    $total->shippingMethod = 'dpd_error';
    //    $total->shippingMethod = 'flatrate_error';

    // add shipping method
    $proxy->shoppingCartShippingMethod($total);
}

function addPayment() {
    global $total, $proxy;

    $paymentMethod = [
        'po_number'    => null,
        'method'       => 'checkmo',
        'title'       => 'Payment',
        'cc_cid'       => null,
        'cc_owner'     => null,
        'cc_number'    => null,
        'cc_type'      => null,
        'cc_exp_year'  => null,
        'cc_exp_month' => null
    ];

    $total->paymentData = $paymentMethod;

    // add payment method
    $proxy->shoppingCartPaymentMethod($total);
}

function placeOrder() {
    global $total, $proxy;
    // place the order
    $orderId = $proxy->shoppingCartOrder($total);

    return $orderId;
}

function applyCoupon() {
    global $cardNumber, $total, $proxy;

    $total->couponCode = $cardNumber;

    $couponResult = $proxy->shoppingCartCouponAdd($total);

    return $couponResult;
}