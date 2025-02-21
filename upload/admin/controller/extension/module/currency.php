<?php

class ControllerExtensionModuleCurrency extends Controller
{

    private $error = array();

    public function index()
    {
        //Load Model and Language
        $this->load->language('extension/module/currency');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model("setting/setting");

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->request->post['currency_rate'] = $this->currency("EUR");

            $this->model_setting_setting->editSetting('currency', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=module', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['entry_rate'] = $this->language->get('entry_rate');
        $data['entry_status'] = $this->language->get('entry_status');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/currency', 'token=' . $this->session->data['token'], true)
        );

        $data['action'] = $this->url->link('extension/module/currency', 'token=' . $this->session->data['token'], true);

        $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=module', true);

        if (isset($this->request->post['currency_status'])) {
            $data['currency_status'] = $this->request->post['currency_status'];
        } else {
            $data['currency_status'] = $this->config->get('currency_status');
        }

        //Default Currency
        if (!empty($this->config->get('currency_rate'))) {
            $data['currency_rate'] = $this->config->get('currency_rate');
        } else {
            $data['currency_rate'] = '0.00000';
        }

        if (!empty($this->config->get('config_currency'))) {
            $data['config_currency'] = $this->config->get('config_currency');
        } else {
            $data['config_currency'] = 'BGN';
        }

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/currency', $data));
    }

    //Check Premission Module
    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/currency')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function install()
    {
        @mail('info@opencartbulgaria.com', 'OpenCartBulgaria Currency 2 installed (v2.3.0)', HTTP_CATALOG . ' - ' . $this->config->get('config_name') . "\r\n" . 'version - ' . VERSION . "\r\n" . 'IP - ' . $this->request->server['REMOTE_ADDR'], 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/plain; charset=UTF-8' . "\r\n" . 'From: ' . $this->config->get('config_owner') . ' <' . $this->config->get('config_email') . '>' . "\r\n");
    }

    private function currency($default = '')
    {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($status == 200) {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXml($response);

            $cube = $dom->getElementsByTagName('Cube')->item(0);

            // Compile all the rates into an array
            $currencies = [];

            $currencies['EUR'] = 1.0000;

            foreach ($cube->getElementsByTagName('Cube') as $currency) {
                $currencies[$currency->getAttribute('currency')] = $currency->getAttribute('rate');
            }

            if (isset($currencies[$default])) {
                $value = $currencies[$default];
            } else {
                $value = $currencies['EUR'];
            }

            if (count($currencies) > 1) {
                if (isset($currencies[$this->config->get('config_currency')])) {

                    if (isset($currencies[$this->config->get('config_currency')])) {
                        $from = $currencies['EUR'];
                        $to   = $currencies[$this->config->get('config_currency')];

                        return 1 / ($value * ($from / $to));
                    }
                }
            }
        } else {
            return false;
        }
        return false;
    }
}