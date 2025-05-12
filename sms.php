<?php
require_once 'vendor/autoload.php';

use AfricasTalking\SDK\AfricasTalking;

class SMS {
    private $sms;

    public function __construct() {
        $username = "sandbox"; 
        $apiKey   = "atsk_1b94c75ee889e25ebef7e456f030ea05b9b6e8ac3bc7f386438d65eeb5d80b779e7d7d80";
        
        $AT = new AfricasTalking($username, $apiKey);
        $this->sms = $AT->sms();
    }

    public function sendTransactionSMS($phoneNumber, $transactionType, $amount, $balance = null) {
        try {
            $message = "";
            
            switch($transactionType) {
                case 'DEPOSIT':
                    $message = "You have deposited $amount Rwf. Your new balance is " . number_format($balance, 2) . " Rwf.";
                    break;
                case 'WITHDRAW':
                    $message = "You have withdrawn $amount Rwf. Your new balance is " . number_format($balance, 2) . " Rwf.";
                    break;
                case 'SEND':
                    $message = "You have sent $amount Rwf. Your new balance is " . number_format($balance, 2) . " Rwf.";
                    break;
                case 'RECEIVE':
                    $message = "You have received $amount Rwf. Your new balance is " . number_format($balance, 2) . " Rwf.";
                    break;
                default:
                    $message = "Transaction of $amount Rwf processed.";
            }
            
            $result = $this->sms->send([
                'to'      => $phoneNumber,
                'message' => $message,
            ]);
            
            return $result;
        } catch (Exception $e) {
            error_log("SMS Error: " . $e->getMessage());
            return false;
        }
    }
}
?>