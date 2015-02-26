<?php

class PMXE_Import_List extends PMXE_Model_List {
	public function __construct() {
		parent::__construct();
		//$this->setTable(PMXE_Plugin::getInstance()->getTablePrefix() . 'imports');
	}
}