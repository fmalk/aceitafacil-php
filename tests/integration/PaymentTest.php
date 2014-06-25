<?php

namespace AceitaFacil\Tests\Integration;

use AceitaFacil\Client,
    AceitaFacil\Entity;


class PaymentTest extends \PHPUnit_Framework_TestCase
{
    public function testMakeCardPayment()
    {
        $client = new Client(true);
        $client->init($_ENV['APPID'], $_ENV['APPSECRET']);
        
        // we need to save a card first
        
        $customer = new Entity\Customer();
        $customer->id = 1;
        $customer->name = 'John Doe';
        $customer->email = 'johndoe@mailinator.com';
        $customer->language = 'EN';
        
        $card = new Entity\Card();
        $card->number = "4111111111111111";
        $card->name = "John Doe";
        $card->exp_date = "205001";
        
        $vendor = new Entity\Vendor();
        $vendor->id = $_ENV['APPID'];
        $vendor->name = 'Acme';
        
        $response = $client->saveCard($customer, $card);
        $this->assertFalse($response->isError(), 'Card was saved');
        
        $cards = $response->getObjects();
        $card = $cards[0];
        $this->assertInstanceOf('AceitaFacil\Entity\Card', $card, 'Card is ok');
        
        // to use a saved card, we must pass its CVV
        $card->cvv = "111";
        
        
        // making a payment
        
        $items = array();
        $item1 = new Entity\Item();
        $item1->id = 10;
        $item1->description = 'Razor blade';
        $item1->amount = 3;
        $item1->vendor = $vendor;
        $item1->fee_split = 1;
        $item1->trigger_lock = false;
        $item2 = new Entity\Item();
        $item2->id = 11;
        $item2->description = 'Band aid';
        $item2->amount = 2;
        $item2->vendor = $vendor;
        $item2->fee_split = 1;
        $item2->trigger_lock = true;
        $items[] = $item1;
        $items[] = $item2;
        
        $description = 'Random purchase';
        $total_amount = $item1->amount + $item2->amount;
        
        $response = $client->makePayment($customer, $description, $total_amount, $items, $card);
        $this->assertFalse($response->isError(), 'Not an error');
        
        $payments = $response->getObjects();
        $payment = $payments[0];
        $this->assertInstanceOf('AceitaFacil\Entity\Payment', $payment, 'Payment is ok');
        $this->assertNotEmpty($payment->id, 'Transaction ID found');
        
        return $payment;
    }

    /**
     * @depends testMakeCardPayment
     */
    public function testGetCardPaymentInfo($original_payment)
    {
        $client = new Client(true);
        $client->init($_ENV['APPID'], $_ENV['APPSECRET']);
        
        $response = $client->getPayment($original_payment->id);
        $this->assertFalse($response->isError(), 'Not an error');
        
        $payments = $response->getObjects();
        $payment = $payments[0];
        $this->assertInstanceOf('AceitaFacil\Entity\Payment', $payment, 'Payment is ok');
        $this->assertEquals($original_payment->id, $payment->id, 'Transaction ID found is the same passed');
    }

    public function testMakeBillPayment()
    {
        $client = new Client(true);
        $client->init($_ENV['APPID'], $_ENV['APPSECRET']);
 
        $customer = new Entity\Customer();
        $customer->id = 1;
        $customer->name = 'John Doe';
        $customer->email = 'johndoe@mailinator.com';
        $customer->language = 'EN';
        
        $vendor = new Entity\Vendor();
        $vendor->id = $_ENV['APPID'];
        $vendor->name = 'Acme';
        
        // making a payment
        
        $items = array();
        $item1 = new Entity\Item();
        $item1->id = 10;
        $item1->description = 'Razor blade';
        $item1->amount = 3;
        $item1->vendor = $vendor;
        $item1->fee_split = 1;
        $item1->trigger_lock = false;
        $item2 = new Entity\Item();
        $item2->id = 11;
        $item2->description = 'Band aid';
        $item2->amount = 2;
        $item2->vendor = $vendor;
        $item2->fee_split = 1;
        $item2->trigger_lock = true;
        $items[] = $item1;
        $items[] = $item2;
        
        $description = 'Random purchase';
        $total_amount = $item1->amount + $item2->amount;
        
        $response = $client->makePayment($customer, $description, $total_amount, $items);
        $this->assertFalse($response->isError(), 'Not an error');
        
        $payments = $response->getObjects();
        $payment = $payments[0];
        $this->assertInstanceOf('AceitaFacil\Entity\Payment', $payment, 'Payment is ok');
        $this->assertNotEmpty($payment->id, 'Transaction ID found');
        $this->assertNotEmpty($payment->bill, 'Payment bill received');
        $this->assertInstanceOf('AceitaFacil\Entity\Bill', $payment->bill, 'Bill is ok');
        $this->assertNotEmpty($payment->bill->url, 'Payment bill URL found');
        
        return $payment;
    }

    /**
     * Payment resulting in a charge error
     * 
     * Sandbox API returns a charge error to every non-integer amount
     * 
     * @depends testMakeCardPayment
     */
    public function testMakePaymentChargeError($first_payment)
    {
        $client = new Client(true);
        $client->init($_ENV['APPID'], $_ENV['APPSECRET']);
 
        $customer = new Entity\Customer();
        $customer->id = 1;
        $customer->name = 'John Doe';
        $customer->email = 'johndoe@mailinator.com';
        $customer->language = 'EN';
        
        $vendor = new Entity\Vendor();
        $vendor->id = $_ENV['APPID'];
        $vendor->name = 'Acme';
        
        // first we need to get a usable Card
        
        $response = $client->getAllCards($customer->id);
        $this->assertFalse($response->isError(), 'Not an error');
        
        $cards = $response->getObjects();
        $card = $cards[0];
        $card->cvv = '111';
        
        // making a payment
        
        $items = array();
        $item1 = new Entity\Item();
        $item1->id = 10;
        $item1->description = 'Razor blade';
        $item1->amount = 2.99;
        $item1->vendor = $vendor;
        $item1->fee_split = 1;
        $item1->trigger_lock = false;
        $item2 = new Entity\Item();
        $item2->id = 11;
        $item2->description = 'Band aid';
        $item2->amount = 1.99;
        $item2->vendor = $vendor;
        $item2->fee_split = 1;
        $item2->trigger_lock = false;
        $items[] = $item1;
        $items[] = $item2;
        
        $description = 'Random purchase';
        $total_amount = $item1->amount + $item2->amount;
        
        $response = $client->makePayment($customer, $description, $total_amount, $items, $card);
        $this->assertTrue($response->isError(), 'Is an error');
        $this->assertEquals(402, $response->getHttpStatus(), 'HTTP Status 402');
        
        $objects = $response->getObjects();
        $this->assertNotEmpty($objects, 'Parsed entities available');
        foreach ($objects as $object) {
            $this->assertInstanceOf('AceitaFacil\Entity\Error', $object, 'Parsed object is an Error');
        }
    }
}