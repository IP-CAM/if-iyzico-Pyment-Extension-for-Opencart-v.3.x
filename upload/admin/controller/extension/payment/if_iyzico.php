<?php

class ControllerExtensionPaymentIfIyzico extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/if_iyzico');

        $this->document->setTitle($this->language->get('IF Iyzico'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_if_iyzico', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['payment_method'])) {
            $data['error_payment_method'] = $this->error['payment_method'];
        } else {
            $data['error_payment_method'] = '';
        }

        if (isset($this->error['licence_key'])) {
            $data['error_licence_key'] = $this->error['licence_key'];
        } else {
            $data['error_licence_key'] = '';
        }

        if (isset($this->error['secret_key'])) {
            $data['error_secret_key'] = $this->error['secret_key'];
        } else {
            $data['error_secret_key'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/if_iyzico', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/if_iyzico', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_if_iyzico_payment_method'])) {
            $data['payment_if_iyzico_payment_method'] = $this->request->post['payment_if_iyzico_payment_method'];
        } else {
            $data['payment_if_iyzico_payment_method'] = $this->config->get('payment_if_iyzico_payment_method');
        }

        if (isset($this->request->post['payment_if_iyzico_licence_key'])) {
            $data['payment_if_iyzico_licence_key'] = $this->request->post['payment_if_iyzico_licence_key'];
        } else {
            $data['payment_if_iyzico_licence_key'] = $this->config->get('payment_if_iyzico_licence_key');
        }

        if (isset($this->request->post['payment_if_iyzico_secret_key'])) {
            $data['payment_if_iyzico_secret_key'] = $this->request->post['payment_if_iyzico_secret_key'];
        } else {
            $data['payment_if_iyzico_secret_key'] = $this->config->get('payment_if_iyzico_secret_key');
        }

        if (isset($this->request->post['payment_if_iyzico_test'])) {
            $data['payment_if_iyzico_test'] = $this->request->post['payment_if_iyzico_test'];
        } else {
            $data['payment_if_iyzico_test'] = $this->config->get('payment_if_iyzico_test');
        }

        if (isset($this->request->post['payment_if_iyzico_transaction'])) {
            $data['payment_if_iyzico_transaction'] = $this->request->post['payment_if_iyzico_transaction'];
        } else {
            $data['payment_if_iyzico_transaction'] = $this->config->get('payment_if_iyzico_transaction');
        }

        if (isset($this->request->post['payment_if_iyzico_total'])) {
            $data['payment_if_iyzico_total'] = $this->request->post['payment_if_iyzico_total'];
        } else {
            $data['payment_if_iyzico_total'] = $this->config->get('payment_if_iyzico_total');
        }

        if (isset($this->request->post['payment_if_iyzico_order_status_id'])) {
            $data['payment_if_iyzico_order_status_id'] = $this->request->post['payment_if_iyzico_order_status_id'];
        } else {
            $data['payment_if_iyzico_order_status_id'] = $this->config->get('payment_if_iyzico_order_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_if_iyzico_geo_zone_id'])) {
            $data['payment_if_iyzico_geo_zone_id'] = $this->request->post['payment_if_iyzico_geo_zone_id'];
        } else {
            $data['payment_if_iyzico_geo_zone_id'] = $this->config->get('payment_if_iyzico_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_if_iyzico_status'])) {
            $data['payment_if_iyzico_status'] = $this->request->post['payment_if_iyzico_status'];
        } else {
            $data['payment_if_iyzico_status'] = $this->config->get('payment_if_iyzico_status');
        }

        if (isset($this->request->post['payment_if_iyzico_sort_order'])) {
            $data['payment_if_iyzico_sort_order'] = $this->request->post['payment_if_iyzico_sort_order'];
        } else {
            $data['payment_if_iyzico_sort_order'] = $this->config->get('payment_if_iyzico_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/if_iyzico', $data));
    }

    protected function validate()
    {
        if ( ! $this->user->hasPermission('modify', 'extension/payment/if_iyzico')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ( ! $this->request->post['payment_if_iyzico_payment_method']) {
            $this->error['payment_method'] = $this->language->get('error_payment_method');
        }

        if ( ! $this->request->post['payment_if_iyzico_licence_key']) {
            $this->error['licence_key'] = $this->language->get('error_licence_key');
        }

        if ( ! $this->request->post['payment_if_iyzico_secret_key']) {
            $this->error['secret_key'] = $this->language->get('error_secret_key');
        }

        return ! $this->error;
    }
}