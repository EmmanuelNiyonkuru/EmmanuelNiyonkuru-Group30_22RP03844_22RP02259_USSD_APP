<?php
require_once 'config.php';
require_once 'sms.php'; 

class Menu {
    protected $text;
    protected $sessionId;
    protected $pdo;
    protected $phoneNumber;

    function __construct($text, $sessionId, $phoneNumber = null) {
        global $pdo;
        $this->text = $text;
        $this->sessionId = $sessionId;
        $this->pdo = $pdo;
        $this->phoneNumber = $phoneNumber;
    }

    public function isRegistered($phoneNumber) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
        $stmt->execute([$phoneNumber]);
        return $stmt->rowCount() > 0;
    }

    public function isAgent($phoneNumber) {
        $stmt = $this->pdo->prepare("SELECT * FROM agents WHERE phone_number = ? AND status = 'ACTIVE'");
        $stmt->execute([$phoneNumber]);
        return $stmt->rowCount() > 0;
    }

    public function mainMenuUnregistered() {
        $response = "CON Welcome to XYZ MOMO \n";
        $response .= "1. Register as User\n";
        $response .= "2. Register as Agent\n";
        echo $response;
    }

    public function mainMenuAgent() {
        $response = "CON Welcome Agent\n";
        $response .= "1. Process Deposit\n";
        $response .= "2. Process Withdrawal\n";
        $response .= "3. Check Balance\n";
        echo $response;
    }

    public function menuRegister($textArray) {
        $level = count($textArray);

        if($level == 1) {
            echo "CON Enter your fullname \n";
        }
        else if($level == 2) {
            echo "CON Enter your PIN \n";
        }
        else if($level == 3) {
            echo "CON Re-enter your PIN \n";
        }
        else if($level == 4) {
            $name = $textArray[1];
            $pin = $textArray[2];
            $confirm_pin = $textArray[3];
            
            if($pin != $confirm_pin) {
                echo "END PINs do not match, Retry";
            } else {
                try {
                    $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
                    $stmt = $this->pdo->prepare("INSERT INTO users (phone_number, full_name, pin) VALUES (?, ?, ?)");
                    $stmt->execute([$this->phoneNumber, $name, $hashed_pin]);
                    
                    // Send welcome SMS
                    $sms = new SMS();
                    $sms->sendTransactionSMS($this->phoneNumber, 'WELCOME Dear user your Balance is ', 0, 0);
                    
                    echo "END Dear $name, you have successfully registered as a user \n SMS will come shortly";
                } catch(PDOException $e) {
                    echo "END Registration failed. Please try again.";
                }
            }
        }
    }

    public function menuRegisterAgent($textArray) {
        $level = count($textArray);

        if($level == 1) {
            echo "CON Enter your fullname \n";
        }
        else if($level == 2) {
            echo "CON Enter your Agent Code \n";
        }
        else if($level == 3) {
            echo "CON Enter your PIN \n";
        }
        else if($level == 4) {
            echo "CON Re-enter your PIN \n";
        }
        else if($level == 5) {
            $name = $textArray[1];
            $agent_code = $textArray[2];
            $pin = $textArray[3];
            $confirm_pin = $textArray[4];
            
            if($pin != $confirm_pin) {
                echo "END PINs do not match, Retry";
            } else {
                try {
                    $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
                    $stmt = $this->pdo->prepare("INSERT INTO agents (agent_code, full_name, phone_number, pin) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$agent_code, $name, $this->phoneNumber, $hashed_pin]);
                    
                    // Send welcome SMS to agent
                    $sms = new SMS();
                    $sms->sendTransactionSMS($this->phoneNumber, 'AGENT_WELCOME', 0, 0);
                    
                    echo "END Dear $name, you have successfully registered as an agent with code: $agent_code \n SMS will come shortly";
                } catch(PDOException $e) {
                    echo "END Agent registration failed. Please try again.";
                }
            }
        }
    }

    public function menuAgentDeposit($textArray) {
        $level = count($textArray);
        if($level == 1) {
            echo "CON Enter customer phone number";
        } else if($level == 2) {
            echo "CON Enter amount";
        } else if($level == 3) {
            echo "CON Enter your agent PIN";
        } else if($level == 4) {
            $customer_phone = $textArray[1];
            $amount = $textArray[2];
            $agent_pin = $textArray[3];

            // Verify agent PIN
            $stmt = $this->pdo->prepare("SELECT * FROM agents WHERE phone_number = ?");
            $stmt->execute([$this->phoneNumber]);
            $agent = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$agent || !password_verify($agent_pin, $agent['pin'])) {
                echo "END Invalid agent PIN";
                return;
            }

            // Check if customer exists
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
            $stmt->execute([$customer_phone]);
            if($stmt->rowCount() == 0) {
                echo "END Customer not registered";
                return;
            }

            try {
                $this->pdo->beginTransaction();

                // Add amount to customer's balance
                $stmt = $this->pdo->prepare("UPDATE users SET balance = balance + ? WHERE phone_number = ?");
                $stmt->execute([$amount, $customer_phone]);

                // Record transaction
                $stmt = $this->pdo->prepare("INSERT INTO transactions (recipient_phone, amount, transaction_type, agent_code) VALUES (?, ?, 'DEPOSIT', ?)");
                $stmt->execute([$customer_phone, $amount, $agent['agent_code']]);

                $this->pdo->commit();
                
                // Get updated balance for SMS
                $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE phone_number = ?");
                $stmt->execute([$customer_phone]);
                $customerBalance = $stmt->fetchColumn();

                // Send SMS notification to customer
                $sms = new SMS();
                $sms->sendTransactionSMS($customer_phone, 'DEPOSIT', $amount, $customerBalance);
                
                echo "END Deposit of $amount processed successfully for customer $customer_phone \n SMS will come shortly";
                            // Send SMS to agent
            $sms->sendTransactionSMS(
                $this->phoneNumber,
                'AGENT_DEPOSIT',
                $amount,
                // $agentBalance,
                $customer_phone // Additional reference
            );

            echo "END Deposit processed successfully";
            } catch(PDOException $e) {
                $this->pdo->rollBack();
                echo "END Deposit failed. Please try again.";
            }
        }
    }

    public function menuAgentWithdraw($textArray) {
        $level = count($textArray);
        if($level == 1) {
            echo "CON Enter customer phone number";
        } else if($level == 2) {
            echo "CON Enter amount";
        } else if($level == 3) {
            echo "CON Enter your agent PIN";
        } else if($level == 4) {
            $customer_phone = $textArray[1];
            $amount = $textArray[2];
            $agent_pin = $textArray[3];

            // Verify agent PIN
            $stmt = $this->pdo->prepare("SELECT * FROM agents WHERE phone_number = ?");
            $stmt->execute([$this->phoneNumber]);
            $agent = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$agent || !password_verify($agent_pin, $agent['pin'])) {
                echo "END Invalid agent PIN";
                return;
            }

            // Check if customer exists and has sufficient balance
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
            $stmt->execute([$customer_phone]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$customer) {
                echo "END Customer not registered";
                return;
            }

            if($customer['balance'] < $amount) {
                echo "END Customer has insufficient balance";
                return;
            }

            try {
                $this->pdo->beginTransaction();

                // Deduct amount from customer's balance
                $stmt = $this->pdo->prepare("UPDATE users SET balance = balance - ? WHERE phone_number = ?");
                $stmt->execute([$amount, $customer_phone]);

                // Record transaction
                $stmt = $this->pdo->prepare("INSERT INTO transactions (sender_phone, amount, transaction_type, agent_code) VALUES (?, ?, 'WITHDRAW', ?)");
                $stmt->execute([$customer_phone, $amount, $agent['agent_code']]);

                $this->pdo->commit();
                
                // Get updated balance for SMS
                $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE phone_number = ?");
                $stmt->execute([$customer_phone]);
                $customerBalance = $stmt->fetchColumn();

                // Send SMS notification to customer
                $sms = new SMS();
                $sms->sendTransactionSMS($customer_phone, 'WITHDRAW', $amount, $customerBalance);
                
                echo "END Withdrawal of $amount processed successfully for customer $customer_phone \n SMS will come shortly";
            } catch(PDOException $e) {
                $this->pdo->rollBack();
                echo "END Withdrawal failed. Please try again.";
            }
        }
    }

    public function menuAgentCheckBalance($textArray) {
        $level = count($textArray);
        if($level == 1) {
            echo "CON Enter customer phone number";
        } else if($level == 2) {
            echo "CON Enter your agent PIN";
        } else if($level == 3) {
            $customer_phone = $textArray[1];
            $agent_pin = $textArray[2];

            // Verify agent PIN
            $stmt = $this->pdo->prepare("SELECT * FROM agents WHERE phone_number = ?");
            $stmt->execute([$this->phoneNumber]);
            $agent = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$agent || !password_verify($agent_pin, $agent['pin'])) {
                echo "END Invalid agent PIN";
                return;
            }

            // Get customer balance
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
            $stmt->execute([$customer_phone]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$customer) {
                echo "END Customer not registered";
                return;
            }
            $sms = new SMS();
            $sms->sendTransactionSMS($this->phoneNumber, 'Your balance'. number_format($customer['balance'], 2) . "Rwf  you will receive an sms shortly");
            echo "END Customer balance: " . number_format($customer['balance'], 2) . "Rwf";
        }
    }

    public function mainMenuRegistered() {
        $response = "CON Welcome back to XYZ MOMO.\n";
        $response .= "1. Send Money\n";
        $response .= "2. Withdraw Money\n";
        $response .= "3. Check balance\n";
        $response .= "4. Deposit Money";
        echo $response;
    }

    public function menuSendMoney($textArray) {
        $level = count($textArray);
        if($level == 1) {
            echo "CON Enter recipient phone number";
        } else if($level == 2) {
            echo "CON Enter amount";
        } else if($level == 3) {
            echo "CON Enter PIN";
        } else if($level == 4) {
            $recipient = $textArray[1];
            $amount = $textArray[2];
            $pin = $textArray[3];

            // Verify PIN
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
            $stmt->execute([$this->phoneNumber]);
            $sender = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$sender || !password_verify($pin, $sender['pin'])) {
                echo "END Invalid PIN";
                return;
            }

            // Check if recipient exists
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
            $stmt->execute([$recipient]);
            if($stmt->rowCount() == 0) {
                echo "END Recipient not registered";
                return;
            }

            // Check balance
            if($sender['balance'] < $amount) {
                echo "END Insufficient balance";
                return;
            }

            try {
                $this->pdo->beginTransaction();

                // Deduct from sender
                $stmt = $this->pdo->prepare("UPDATE users SET balance = balance - ? WHERE phone_number = ?");
                $stmt->execute([$amount, $this->phoneNumber]);

                // Add to recipient
                $stmt = $this->pdo->prepare("UPDATE users SET balance = balance + ? WHERE phone_number = ?");
                $stmt->execute([$amount, $recipient]);

                // Record transaction
                $stmt = $this->pdo->prepare("INSERT INTO transactions (sender_phone, recipient_phone, amount, transaction_type) VALUES (?, ?, ?, 'SEND')");
                $stmt->execute([$this->phoneNumber, $recipient, $amount]);

                $this->pdo->commit();
                
                // Get updated balances for SMS
                $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE phone_number = ?");
                $stmt->execute([$this->phoneNumber]);
                $senderBalance = $stmt->fetchColumn();
                
                $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE phone_number = ?");
                $stmt->execute([$recipient]);
                $recipientBalance = $stmt->fetchColumn();

                // Send SMS notifications
                $sms = new SMS();
                $sms->sendTransactionSMS($this->phoneNumber, 'SEND', $amount, $senderBalance);
                $sms->sendTransactionSMS($recipient, 'RECEIVE', $amount, $recipientBalance);
                
                echo "END You have sent amount of $amount to $recipient successfully \n SMS will come shortly";
            } catch(PDOException $e) {
                $this->pdo->rollBack();
                echo "END Transaction failed. Please try again.";
            }
        }
    }

    public function menuWithdrawMoney($textArray) {
        $level = count($textArray);
        if($level == 1) {
            echo "CON Enter amount";
        } else if($level == 2) {
            echo "CON Enter Agent code";
        } else if($level == 3) {
            echo "CON Enter PIN";
        } else if($level == 4) {
            $amount = $textArray[1];
            $agent_code = $textArray[2];
            $pin = $textArray[3];

            // Verify PIN
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
            $stmt->execute([$this->phoneNumber]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$user || !password_verify($pin, $user['pin'])) {
                echo "END Invalid PIN";
                return;
            }

            // Check balance
            if($user['balance'] < $amount) {
                echo "END Insufficient balance";
                return;
            }

            try {
                $this->pdo->beginTransaction();

                // Deduct amount
                $stmt = $this->pdo->prepare("UPDATE users SET balance = balance - ? WHERE phone_number = ?");
                $stmt->execute([$amount, $this->phoneNumber]);

                // Record transaction
                $stmt = $this->pdo->prepare("INSERT INTO transactions (sender_phone, amount, transaction_type, agent_code) VALUES (?, ?, 'WITHDRAW', ?)");
                $stmt->execute([$this->phoneNumber, $amount, $agent_code]);

                $this->pdo->commit();
                
                // Get updated balance for SMS
                $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE phone_number = ?");
                $stmt->execute([$this->phoneNumber]);
                $newBalance = $stmt->fetchColumn();

                // Send SMS notification
                $sms = new SMS();
                $sms->sendTransactionSMS($this->phoneNumber, 'WITHDRAW', $amount, $newBalance);
                
                echo "END Dear, you have successfully withdrawn amount of $amount with $agent_code agent code. Collect your money";
            } catch(PDOException $e) {
                $this->pdo->rollBack();
                echo "END Withdrawal failed. Please try again.";
            }
        }
    }

    public function menuCheckBalance($textArray) {
        $level = count($textArray);
        
        if ($level == 1) {
            echo "CON Enter PIN";
        } 
        else if ($level == 2) {
            $pin = $textArray[1];
    
            try {
                // Verify PIN and get balance
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
                $stmt->execute([$this->phoneNumber]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
                if (!$user || !password_verify($pin, $user['pin'])) {
                    echo "END Invalid PIN";
                    return;
                }
    
                $formattedBalance = number_format($user['balance'], 2);
                
                // USSD response
                echo "END Balance: " . $formattedBalance . " Rwf\nThank you for using our service you will receive sms shortly ";
                
                // Send SMS notification
                $sms = new SMS();
                $smsMessage = "Your current balance: " . $formattedBalance . " Rwf";
                $sms->sendTransactionSMS(
                    $this->phoneNumber, 
                    'BALANCE_CHECK', 
                    0,  // Amount is 0 for balance check
                    $user['balance']  // Current balance
                );
                
            } catch (PDOException $e) {
                error_log("Balance check error: " . $e->getMessage());
                echo "END System error. Please try again.";
            }
        }
    }

    public function menuDepositMoney($textArray) {
        $level = count($textArray);
        if($level == 1) {
            echo "CON Enter amount to deposit";
        } else if($level == 2) {
            echo "CON Enter Agent code";
        } else if($level == 3) {
            echo "CON Enter PIN";
        } else if($level == 4) {
            $amount = $textArray[1];
            $agent_code = $textArray[2];
            $pin = $textArray[3];

            // Verify PIN
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
            $stmt->execute([$this->phoneNumber]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$user || !password_verify($pin, $user['pin'])) {
                echo "END Invalid PIN";
                return;
            }

            try {
                $this->pdo->beginTransaction();

                // Add amount to user's balance
                $stmt = $this->pdo->prepare("UPDATE users SET balance = balance + ? WHERE phone_number = ?");
                $stmt->execute([$amount, $this->phoneNumber]);

                // Record transaction
                $stmt = $this->pdo->prepare("INSERT INTO transactions (sender_phone, amount, transaction_type, agent_code) VALUES (?, ?, 'DEPOSIT', ?)");
                $stmt->execute([$this->phoneNumber, $amount, $agent_code]);

                $this->pdo->commit();
                
                // Get updated balance for SMS
                $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE phone_number = ?");
                $stmt->execute([$this->phoneNumber]);
                $newBalance = $stmt->fetchColumn();

                // Send SMS notification
                $sms = new SMS();
                $sms->sendTransactionSMS($this->phoneNumber, 'DEPOSIT', $amount, $newBalance);
                
                echo "END Dear, you have successfully deposited amount of $amount with $agent_code agent code";
            } catch(PDOException $e) {
                $this->pdo->rollBack();
                echo "END Deposit failed. Please try again.";
            }
        }
    }
}