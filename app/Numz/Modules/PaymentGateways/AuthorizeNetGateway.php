<?php

namespace App\Numz\Modules\PaymentGateways;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class AuthorizeNetGateway implements PaymentGatewayInterface
{
    private $merchantAuthentication;
    private $sandbox;

    public function __construct()
    {
        $this->merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $this->merchantAuthentication->setName(config('numz.gateways.authorizenet.api_login_id'));
        $this->merchantAuthentication->setTransactionKey(config('numz.gateways.authorizenet.transaction_key'));

        $this->sandbox = config('numz.gateways.authorizenet.sandbox', true);
    }

    public function getName(): string
    {
        return 'Authorize.Net';
    }

    public function processPayment(array $data): array
    {
        try {
            // Create credit card object
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber($data['card_number']);
            $creditCard->setExpirationDate($data['expiry_date']);
            $creditCard->setCardCode($data['cvv']);

            // Create payment type
            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);

            // Create transaction request
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($data['amount']);
            $transactionRequestType->setPayment($paymentOne);

            // Create request
            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);
            $request->setTransactionRequest($transactionRequestType);

            // Execute request
            $controller = new AnetController\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse(
                $this->sandbox
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
            );

            if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getMessages() != null) {
                    return [
                        'success' => true,
                        'transaction_id' => $tresponse->getTransId(),
                        'auth_code' => $tresponse->getAuthCode(),
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $tresponse->getErrors()[0]->getErrorText(),
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => $response->getMessages()->getMessage()[0]->getText(),
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function handleWebhook(array $payload): array
    {
        // Authorize.Net uses Silent Post
        return [
            'success' => isset($payload['x_response_code']) && $payload['x_response_code'] == 1,
            'transaction_id' => $payload['x_trans_id'] ?? null,
            'amount' => $payload['x_amount'] ?? 0,
        ];
    }

    public function refund(string $transactionId, float $amount): array
    {
        try {
            // Create payment type for refund
            $paymentOne = new AnetAPI\PaymentType();
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber("XXXX"); // Last 4 digits
            $creditCard->setExpirationDate("XXXX");
            $paymentOne->setCreditCard($creditCard);

            // Create transaction request
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("refundTransaction");
            $transactionRequestType->setAmount($amount);
            $transactionRequestType->setPayment($paymentOne);
            $transactionRequestType->setRefTransId($transactionId);

            // Create request
            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);
            $request->setTransactionRequest($transactionRequestType);

            // Execute request
            $controller = new AnetController\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse(
                $this->sandbox
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
            );

            if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();
                return [
                    'success' => true,
                    'refund_id' => $tresponse->getTransId(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->getMessages()->getMessage()[0]->getText(),
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
