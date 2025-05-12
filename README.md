# EmmanuelNiyonkuru-Group30_22RP03844_22RP02259_USSD_APP

#Developers:
1.SHYAKA Aimable 22RP03844
2.NIYONKURU Emmanuel 22RP2259


A complete USSD-based mobile money system with agent functionality, SMS notifications, and transaction processing.

## Features

-  *User Transactions*
  - Send money to other users
  - Deposit/withdraw cash via agents
  - Check account balance
  

-  *Agent Services*
  - Process customer deposits
  - Handle cash withdrawals
  - Check customer balances
  

- *USSD Interface*

  - Interactive menu system
  - 99 - Main Menu navigation
  - 98 - Back navigation
  - PIN authentication

-  *SMS Notifications*
  - Transaction confirmations
  - Balance message
  - Agent activities

## System Architecture
 config.php # Database configuration
 index.php # USSD request 
 menu.php # All USSD menu logic
 sms.php # SMS notification service
 vendor/ # Composer dependencies

## Prerequisites

- PHP 7.4+
- MySQL 5.7+
- Composer
- Africa's Talking API account
- Web server (Apache)

## Installation

1. Clone the repository:
   
   git clone https://github.com/yourusername/ussd-momo.git
   cd ussd-momo

2.Install dependencies:

composer require africastalking/africastalking-sdk



