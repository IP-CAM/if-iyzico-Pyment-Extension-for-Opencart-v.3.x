<?php

class ControllerExtensionPaymentIfIyzico extends Controller
{
    public function index()
    {
        $this->load->language('extension/payment/if_iyzico');

        $data['payment_method'] = $this->config->get('payment_if_iyzico_payment_method');

        switch ($data['payment_method']) {

            case 'CHECKOUT_FORM':

                // settings not required

                break;

            default:

                return '<div class="text-center">Error: payment method not defined: ' . $this->config->get('payment_if_iyzico_payment_method') . '</div>';

        }

        return $this->load->view('extension/payment/if_iyzico', $data);
    }

    public function send()
    {
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $order_products = $this->model_checkout_order->getOrderProducts($this->session->data['order_id']);

        $orderId = (int)$order_info['order_id'];

        $paymentMethod = $this->config->get('payment_if_iyzico_payment_method');

        $items = [];

        foreach ($order_products as $order_product) {

            $items[] = [
                'id'        => $order_product['product_id'],
                'name'      => $order_product['name'],
                'price'     => $order_product['total'],
                'category1' => $order_product['model']
            ];

        }

        $customFields = isset($order_info['custom_field']) ? array_values((array)$order_info['custom_field']) : [];

        $tryIdentityNumber = isset($customFields[0]) ? trim($customFields[0]) : '';

        $identityNumber = strlen($tryIdentityNumber) === 11 ? $tryIdentityNumber : '11111111111';

        $paymentZipCode = urlencode(($order_info['payment_iso_code_2'] != 'US') ? $order_info['payment_zone'] : $order_info['payment_zone_code']);

        $shippingZipCode = urlencode(($order_info['shipping_iso_code_2'] != 'US') ? $order_info['shipping_zone'] : $order_info['shipping_zone_code']);

        $paymentAddressArray = [];

        if ( ! empty($order_info['payment_city'])) {
            $paymentAddressArray[] = $order_info['payment_city'];
        }

        if ( ! empty($order_info['payment_address_1'])) {
            $paymentAddressArray[] = $order_info['payment_address_1'];
        }

        if ( ! empty($order_info['payment_address_2'])) {
            $paymentAddressArray[] = $order_info['payment_address_2'];
        }

        $paymentAddress = implode(' ', $paymentAddressArray);

        $shippingAddressArray = [];

        if ( ! empty($order_info['shipping_city'])) {
            $shippingAddressArray[] = $order_info['shipping_city'];
        }

        if ( ! empty($order_info['shipping_address_1'])) {
            $shippingAddressArray[] = $order_info['shipping_address_1'];
        }

        if ( ! empty($order_info['shipping_address_2'])) {
            $shippingAddressArray[] = $order_info['shipping_address_2'];
        }

        $shippingAddress = implode(' ', $shippingAddressArray);

        $moduleData = [

            'secret_key'      => urlencode($this->config->get('payment_if_iyzico_secret_key')),
            'locale'          => in_array($order_info['language_code'], ['tr', 'tr-tr', 'TR-tr']) ? 'tr' : 'en',
            'price'           => $order_info['total'],
            'paid_price'      => $order_info['total'],
            'currency'        => $order_info['currency_code'],
            'installment'     => 1,
            'basket_id'       => $orderId,
            'payment_channel' => 'WEB',
            'payment_group'   => 'PRODUCT',

            'buyer' => [
                'id'              => urlencode($order_info['customer_id']),
                'first_name'      => urlencode($order_info['payment_firstname']),
                'last_name'       => urlencode($order_info['payment_lastname']),
                'email'           => urlencode($order_info['email']),
                'identity_number' => $identityNumber,
                'country'         => urlencode($order_info['payment_country']),
                'city'            => urlencode($order_info['payment_zone']),
                'address'         => urlencode($paymentAddress),
                'ip'              => empty($order_info['forwarded_ip']) ? $order_info['ip'] : $order_info['forwarded_ip'],
                'phone_number'    => urlencode($order_info['telephone']),
                'zip_code'        => $paymentZipCode,
            ],

            'billing' => [
                'name'     => urlencode($order_info['payment_firstname']) . ' ' . urlencode($order_info['payment_lastname']),
                'country'  => urlencode($order_info['payment_country']),
                'city'     => urlencode($order_info['payment_zone']),
                'address'  => urlencode($paymentAddress),
                'zip_code' => $paymentZipCode
            ],

            'items' => $items

        ];

        switch ($paymentMethod) {

            case 'CHECKOUT_FORM':

                // eklenecek başka bilgi yok

                break;

        }

        if ($this->cart->hasShipping()) {

            $moduleData['shipping'] = [
                'name'     => urlencode($order_info['shipping_firstname']) . ' ' . urlencode($order_info['shipping_lastname']),
                'country'  => urlencode($order_info['shipping_country']),
                'city'     => urlencode($order_info['shipping_zone']),
                'address'  => urlencode($shippingAddress),
                'zip_code' => $shippingZipCode
            ];

        } else {

            $moduleData['shipping'] = [
                'name'     => urlencode($order_info['payment_firstname']) . ' ' . urlencode($order_info['payment_lastname']),
                'country'  => urlencode($order_info['payment_country']),
                'city'     => urlencode($order_info['payment_zone']),
                'address'  => urlencode($paymentAddress),
                'zip_code' => $paymentZipCode
            ];

        }

        $data = [
            'module'      => 'IYZICO',
            'method'      => $paymentMethod,
            'licence_key' => urlencode($this->config->get('payment_if_iyzico_licence_key')),
            'ok_url'      => urlencode($this->url->link('extension/payment/if_iyzico/callback', ['status' => 'ok'], true)),
            'fail_url'    => urlencode($this->url->link('extension/payment/if_iyzico/callback', ['status' => 'fail'], true)),
            'test'        => ( ! ! $this->config->get('payment_if_iyzico_test')),
            'extra_info'  => [
                'order_id' => $orderId
            ],
            'data'        => $moduleData
        ];

        $responseObject = $this->curl_request('INIT', $data);

        if ($responseObject === false) {

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'error' => 'Bilinmeyen bir hata meydana geldi.'
            ]));

        } else {

            if ($responseObject->success) {

                switch ($responseObject->type) {

                    case 'response':

                        $transactionId = $responseObject->transaction_id;
                        $transactionHash = $responseObject->transaction_hash;

                        $validate_response = $this->validate_transaction($paymentMethod, $transactionId, $transactionHash);

                        if ($validate_response === false) {

                            $this->response->addHeader('Content-Type: application/json');
                            $this->response->setOutput(json_encode([
                                'error' => 'Bilinmeyen bir hata meydana geldi.'
                            ]));

                        } else {

                            if ($validate_response->success) {

                                $this->model_checkout_order->addOrderHistory($orderId, $this->config->get('payment_if_iyzico_order_status_id'));

                                $this->response->addHeader('Content-Type: application/json');
                                $this->response->setOutput(json_encode([
                                    'redirect' => $this->url->link('checkout/success', '', true)
                                ]));

                            } else {

                                $this->response->addHeader('Content-Type: application/json');
                                $this->response->setOutput(json_encode([
                                    'error' => $validate_response->message ?? 'Bilinmeyen bir hata meydana geldi.'
                                ]));

                            }

                        }

                        break;

                    case 'redirect':

                        $this->response->addHeader('Content-Type: application/json');
                        $this->response->setOutput(json_encode([
                            'redirect' => $responseObject->url
                        ]));

                        break;

                    default:

                        $this->response->addHeader('Content-Type: application/json');
                        $this->response->setOutput(json_encode([
                            'error' => 'Bilinmeyen bir hata meydana geldi.'
                        ]));

                        break;

                }

            } else {

                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode([
                    'error' => $responseObject->message
                ]));

            }

        }
    }

    public function callback()
    {
        $this->load->model('checkout/order');

        $status = isset($this->request->get['status']) ? $this->request->get['status'] : null;

        $this->log->write('if_iyzico callback started: ' . $status);

        switch ($status) {

            case 'ok':

                $this->log->write('if_iyzico callback ok started: ' . var_export($this->request->post, true));

                $transactionId = $this->request->post['transaction_id'];
                $transactionHash = $this->request->post['transaction_hash'];

                $validate_response = $this->validate_transaction($this->config->get('payment_if_iyzico_payment_method'), $transactionId, $transactionHash);

                if ($validate_response === false) {

                    $this->log->write('if_iyzico callback validate hatalı gerçekleşti.');

                    $this->session->data['error'] = 'Bilinmeyen bir hata meydana geldi.';

                    $this->response->redirect($this->url->link('checkout/checkout', '', true));

                } else {

                    if ($validate_response->success) {

                        $this->log->write('if_iyzico callback validate başarılı oldu: ' . var_export($validate_response, true));

                        $this->model_checkout_order->addOrderHistory($validate_response->extra_info->order_id, $this->config->get('payment_if_iyzico_order_status_id'));

                        $this->response->redirect($this->url->link('checkout/success', '', true));

                    } else {

                        $this->log->write('if_iyzico callback validate başarısız oldu: ' . var_export($validate_response, true));

                        $this->session->data['error'] = isset($validate_response->message) ? $validate_response->message : 'Bilinmeyen bir hata meydana geldi.';

                        $this->response->redirect($this->url->link('checkout/checkout', '', true));

                    }

                }

                break;

            case 'fail':

                $this->log->write('if_iyzico callback fail started: ' . var_export($this->request->post, true));

                $this->session->data['error'] = isset($this->request->post['message']) ? $this->request->post['message'] : 'Bilinmeyen bir hata meydana geldi.';

                $this->response->redirect($this->url->link('checkout/checkout', '', true));

                break;

            default:

                $this->session->data['error'] = 'Bilinmeyen bir hata meydana geldi.';

                $this->response->redirect($this->url->link('checkout/checkout', '', true));

                break;

        }
    }

    private function validate_transaction($paymentMethod, $id, $hash)
    {
        return $this->curl_request('VALIDATE', [
            'module'           => 'IYZICO',
            'method'           => $paymentMethod,
            'licence_key'      => urlencode($this->config->get('payment_if_iyzico_licence_key')),
            'test'             => ( ! ! $this->config->get('payment_if_iyzico_test')),
            'transaction_id'   => $id,
            'transaction_hash' => $hash
        ]);
    }

    private function curl_request($action, $data)
    {
        $curl = curl_init('https://backend.ifyazilim.com/payment/process');

        curl_setopt($curl, CURLOPT_PORT, 443);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', [
            'action=' . $action,
            'data=' . json_encode($data)
        ]));

        $response = curl_exec($curl);

        curl_close($curl);

        if ( ! $response) {

            $this->log->write('IfIyzicoPayment failed: ' . curl_error($curl) . '(' . curl_errno($curl) . ')');

            return false;

        }

        return json_decode($response);
    }
}