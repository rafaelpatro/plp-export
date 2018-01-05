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

class Quack_Export_ExportController extends Mage_Adminhtml_Controller_Action
{
	public function plpAction()
	{
		$post = $this->getRequest()->getParams();
		if ( isset($post['internal_order_ids']) ) {
			$orders = explode(',', $post['internal_order_ids']);
			$model = Mage::getModel('qcxport/plp');
			$file = $model->exportOrders($orders);
			$file = iconv("UTF-8", "WINDOWS-1252", $file);
			$date = date("Ymd_His");
			$this->_prepareDownloadResponse("plp_export_$date.txt", $file);
		}
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
