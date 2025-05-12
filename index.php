<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load dependencies
require_once 'config.php';
require_once 'menu.php';
require_once 'sms.php';

// Get USSD parameters
$sessionId   = $_POST['sessionId'] ?? '';
$phoneNumber = $_POST['phoneNumber'] ?? '';
$serviceCode = $_POST['serviceCode'] ?? '';
$text        = $_POST['text'] ?? '';

try {
    // Initialize Menu system
    $menu = new Menu($text, $sessionId, $phoneNumber);
    $isRegistered = $menu->isRegistered($phoneNumber);
    $isAgent = $menu->isAgent($phoneNumber);

    // Route USSD flow
    if($text == "") {
        // Show appropriate main menu
        if(!$isRegistered && !$isAgent) {
            $menu->mainMenuUnregistered();
        } elseif($isRegistered) {
            $menu->mainMenuRegistered();
        } elseif($isAgent) {
            $menu->mainMenuAgent();
        }
    } 
    else {
        $textArray = explode("*", $text);
        $choice = $textArray[0] ?? null;

        // Unregistered user flows
        if(!$isRegistered && !$isAgent) {
            switch($choice) {
                case '1': $menu->menuRegister($textArray); break;
                case '2': $menu->menuRegisterAgent($textArray); break;
                default: echo "END Invalid option. Please try again.";
            }
        }
        // Registered user flows
        elseif($isRegistered) {
            switch($choice) {
                case '1': $menu->menuSendMoney($textArray); break;
                case '2': $menu->menuWithdrawMoney($textArray); break;
                case '3': $menu->menuCheckBalance($textArray); break;
                case '4': $menu->menuDepositMoney($textArray); break;
                default: echo "END Invalid choice. Please start again.";
            }
        }
        // Agent flows
        elseif($isAgent) {
            switch($choice) {
                case '1': $menu->menuAgentDeposit($textArray); break;
                case '2': $menu->menuAgentWithdraw($textArray); break;
                case '3': $menu->menuAgentCheckBalance($textArray); break;
                default: echo "END Invalid agent option.";
            }
        }
    }

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo "END System error. Please try again later.";
} catch(Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    echo "END Service temporarily unavailable.";
}
?>