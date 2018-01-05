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

class Quack_Export_ImportController extends Mage_Adminhtml_Controller_Action
{
    public function plpAction()
    {
        $upload = new Varien_File_Uploader("plp_file");
        $upload->setAllowedExtensions(array('csv', 'CSV'));
        $upload->setAllowRenameFiles(true);
        $upload->setFilesDispersion(false);
        $upload->save(Mage::getBaseDir('var'), "plp_list.csv");
        $filename = Mage::getBaseDir('var'). DS . $upload->getUploadedFileName();
        $csv = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($csv as $row) {
            $this->saveShipment(explode(';', utf8_encode($row)));
        }
        unlink($filename);
        $this->_redirect('adminhtml/sales_order/index');
    }
    
    /**
     * @param array $row
     * @return Quack_Export_ImportController
     */
    public function saveShipment($row)
    {
        list($numeroPLP,
            $dataCriacao,
            $contrato,
            $cartaoPostagem,
            $remetente,
            $enderecoRemetente,
            $emailRemetente,
            $codigoServico,
            $descricaoServico,
            $codigoObjeto,
            $observacoes,
            $peso,
            $altura,
            $largura,
            $comprimento,
            $destinatario,
            $endereco,
            $numero,
            $complemento,
            $bairro,
            $cidade,
            $uf,
            $cep,
            $email,
            $servicosAdicionais,
            $valorCobrado) = $row;
        
        if (is_numeric($numeroPLP)) {
            $orderId = substr($email, 0, 9);
            /* @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if ($order->canShip()) {
                $shipment = $order->prepareShipment($this->_getItemQtys($order));
                $shipment->register();
                $shipment->addComment("PLP: {$numeroPLP} | Valor Cobrado: {$valorCobrado} | Serviços Adicionais: {$servicosAdicionais}");
                $shipment->addTrack($this->_getTrack(array(
                    'title'        => 'Correios',
                    'number'       => $codigoObjeto,
                    'carrier_code' => 'pedroteixeira_correios'
                )));
                $order->setIsInProcess(true);
                try {
                    $transaction = Mage::getModel('core/resource_transaction')
                        ->addObject($shipment)
                        ->addObject($order)
                        ->save();
                    $order->setStatus(PedroTeixeira_Correios_Model_Sro::ORDER_SHIPPED_STATUS);
                    $order->save();
                    $shipment->sendEmail(true);
                    $this->_getSession()->addSuccess("Tracking saved with success: {$codigoObjeto}");
                } catch (Mage_Exception $e) {
                    $this->_getSession()->addError("{$e->getMessage()} Tracking number: {$codigoObjeto}");
                }
            } else {
                $this->_getSession()->addError("Can't ship order: {$orderId}. Tracking number: {$codigoObjeto}");
            }
        }
        return $this;
    }
    
    /**
     * @param Mage_Sales_Model_Order $order
     * @return int
     */
    protected function _getItemQtys($order)
    {
        $qty = array();
        foreach ($order->getAllItems() as $item) {
            $itemQty = $item->getQtyOrdered()
                - $item->getQtyShipped()
                - $item->getQtyRefunded()
                - $item->getQtyCanceled();
            $qty[$item->getId()] = $itemQty;
        }
        return $qty;
    }
    
    /**
     * @param array $data
     * @return Mage_Sales_Model_Order_Shipment_Track
     */
    protected function _getTrack($data)
    {
        $track = Mage::getModel('sales/order_shipment_track');
        $track->addData($data);
        return $track;
    }
    
    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales');
    }
}
