<?php

namespace AceitaFacil\Entity;


/**
 * Payment response information
 * 
 * @author Fernando Piancastelli
 * @link https://github.com/Cellide/aceitafacil-php
 * @license MIT
 */
class Payment
{
    /**
     * Transaction ID for the payment
     * 
     * @var string
     */
    public $id;
    
    /**
     * Payment description
     * 
     * @var string
     */
    public $description;
    
    /**
     * Customer who made the payment
     * 
     * @var Customer
     */
    public $customer;
    
    /**
     * Organization receiving the payment
     * 
     * @var Vendor
     */
    public $organization;
    
    /**
     * Payment method
     * 
     * @var string
     */
    public $payment_method;
    
    /**
     * Charge type
     * 
     * @var string
     */
    public $charge_type;
    
    /**
     * Total amount
     * 
     * @var float
     */
    public $total_amount;
    
    /**
     * If payment was already made
     * 
     * @var bool
     */
    public $paid;
    
    /**
     * If payment is closed
     * 
     * @var bool
     */
    public $closed;
    
    /**
     * If payment was attempted already
     * 
     * @var bool
     */
    public $attempted;
    
    /**
     * Number of times payment was attempted
     * 
     * @var int
     */
    public $attempt_count;
    
    /**
     * When the next payment charge attempt will be made
     * 
     * @var DateTime
     */
    public $next_charge_attempt;
    
    /**
     * Start payment attempts
     * 
     * @var DateTime
     */
    public $period_start;
    
    /**
     * End payment attempts
     * 
     * @var DateTime
     */
    public $period_end;
    
    /**
     * Items purchased by this payment
     * 
     * The amount on each Item is the actual amount transfered to that Vendor,
     * therefore an "extra" Item regarding AceitaFacil's tax will appear
     * 
     * @var Item[]
     */
    public $items;
    
    /**
     * Parses a JSON object into a Payment response
     * 
     * @param  mixed[]  $json
     * @return Payment
     */
    public static function parse($json)
    {
        $entity = new Payment();
        $entity->id = isset($json['id']) ? $json['id'] : null;
        $entity->description = isset($json['description']) ? $json['description'] : null;
        $entity->charge_type = isset($json['chargetype']) ? $json['chargetype'] : null;
        $entity->payment_method = isset($json['paymentmethod']) ? $json['paymentmethod'] : null;
        $entity->attempt_count = isset($json['attempt_count']) ? intval($json['attempt_count']) : 0;
        $entity->attempted = isset($json['attempted']) ? ($json['attempted'] == true) : false;
        $entity->closed = isset($json['closed']) ? ($json['closed'] == true) : false;
        $entity->paid = isset($json['paid']) ? ($json['paid'] == true) : false;
        
        $entity->period_start = isset($json['period_start']) ? \DateTime::createFromFormat('Y-m-d H:i:s', $json['period_start'], new \DateTimeZone('America/Sao_Paulo')) : null;
        $entity->period_end = isset($json['period_end']) ? \DateTime::createFromFormat('Y-m-d H:i:s', $json['period_end'], new \DateTimeZone('America/Sao_Paulo')) : null;
        $entity->next_charge_attempt = isset($json['next_charge_attempt']) ? \DateTime::createFromFormat('Y-m-d H:i:s', $json['next_charge_attempt'], new \DateTimeZone('America/Sao_Paulo')) : null;
        
        $entity->organization = new Vendor();
        $entity->organization->id = isset($json['organization_id']) ? $json['organization_id'] : null;
        $entity->organization->name = isset($json['organization_name']) ? $json['organization_name'] : null;
        
        $entity->customer = new Customer();
        $entity->customer->id = isset($json['customer_id']) ? $json['customer_id'] : null;
        $entity->customer->name = isset($json['customer_name']) ? $json['customer_name'] : null;
        $entity->customer->email = isset($json['customer_email']) ? $json['customer_email'] : null;
        
        $entity->items = array();
        if (isset($json['items']) && !empty($json['items'])) {
            foreach ($json['items'] as $item_data) {
                $entity->items[] = Item::parse($item_data);
            }
        }

        return $entity;
    }
}