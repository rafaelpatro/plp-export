<?php
/**
 * Este arquivo é parte do programa Quack Export
 *
 * Quack Export é um software livre; você pode redistribuí-lo e/ou
 * modificá-lo dentro dos termos da Licença Pública Geral GNU como
 * publicada pela Fundação do Software Livre (FSF); na versão 3 da
 * Licença, ou (na sua opinião) qualquer versão.
 *
 * Este programa é distribuído na esperança de que possa ser útil,
 * mas SEM NENHUMA GARANTIA; sem uma garantia implícita de ADEQUAÇÃO
 * a qualquer MERCADO ou APLICAÇÃO EM PARTICULAR. Veja a Licença
 * Pública Geral GNU para maiores detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral GNU junto
 * com este programa, Se não, veja <http://www.gnu.org/licenses/>.
 *
 * @category   Quack
 * @package    Quack_Export
 * @author     Rafael Patro <rafaelpatro@gmail.com>
 * @copyright  Copyright (c) 2015 Rafael Patro (rafaelpatro@gmail.com)
 * @license    http://www.gnu.org/licenses/gpl.txt
 * @link       https://github.com/rafaelpatro/Quack_Export
 */

class Quack_Export_Model_Plp extends Mage_Core_Model_Abstract {

	private $content = array();
	
    public function exportOrders($orders) {
		$header[] = "1SIGEP DESTINATARIO NACIONAL";
		$body = array();
        foreach ($orders as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            $body[$this->_getSortKey($order)] = $this->getOrderData($order);
        }
        ksort($body);
        $footer[] = "9".str_pad(count($orders), 6, '0', STR_PAD_LEFT);
        $this->content = array_merge($header, $body, $footer);
        $output = implode("\r\n", $this->content);
        return $output;
    }
	
    /**
     * Retrieve order data
     * 
     * @param Mage_Sales_Model_Order $order Order
     * 
     * @return string
     */
    private function getOrderData($order) {
        $shippingAddress = !$order->getIsVirtual() ? $order->getShippingAddress() : null;
        $billingAddress = $order->getBillingAddress();
		$addressLine = array();
		if(strpos($shippingAddress->getData("street"), "\n")){
			$addressLine = explode("\n", $shippingAddress->getData("street"));
		}
		for ($i=0;$i<4;$i++) {
			if (!isset($addressLine[$i])) {
				$addressLine[$i] = '';
			}
		}
		$email = $this->getEmail($order);
        return
			'2'.
			$this->mbStrPad('', 14).
			$this->mbStrPad($shippingAddress->getName(), 50).
			$this->mbStrPad($email, 50).
			$this->mbStrPad($shippingAddress->getName(), 50).
			$this->mbStrPad($billingAddress->getName(), 50).
			$this->mbStrPad(preg_replace("/[^0-9]/", "",$shippingAddress->getData("postcode")), 8).
			$this->mbStrPad(preg_replace("/:/", " ",$addressLine[0]), 50).
			$this->mbStrPad(preg_replace("/[^0-9]/", "",$addressLine[1]), 6).
			$this->mbStrPad($addressLine[2], 30).
			$this->mbStrPad($addressLine[3], 50).
			$this->mbStrPad($shippingAddress->getData("city"), 50).
			$this->mbStrPad($shippingAddress->getTelephone(), 18).
			$this->mbStrPad('', 12).
			$this->mbStrPad('', 12);
    }
    
    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
	private function getEmail(Mage_Sales_Model_Order $order)
	{
	    $orderId = $order->getRealOrderId();
		$service = $order->getShippingMethod(true)->getMethod();
		if (preg_match('/(\d+) volumes/i', $order->getShippingDescription(), $matches)) {
		    $service.= ".{$matches[0]}x";
		}
				
		$domain = preg_replace("/^.+@/", "", Mage::getStoreConfig('trans_email/ident_general/email'));
		return "{$orderId}.{$service}@{$domain}";
	}
	
    public function mbStrPad($str, $int) {
		$size = $int;
		$pattern = '/[[:^ascii:]]/';
		$matches = array();
		if ( preg_match_all($pattern, $str, $matches) ) {
			$size+= count($matches[0])/2;
		}
		$output = str_pad($str, $size);
		return $output;
    }

    private function _getSortKey(Mage_Sales_Model_Order $order)
    {
        $method = $order->getShippingMethod(true)->getMethod();
        $name = $order->getCustomerName();
        $id = $order->getId();
        return "{$method}{$name}{$id}";
    }
}
