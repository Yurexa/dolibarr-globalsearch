<?php
/* Copyright (C) 2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Module descriptor for the GlobalSearch external module.
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modGlobalSearch extends DolibarrModules
{
	/**
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 193970;
		$this->rights_class = 'globalsearch';
		$this->family = 'technic';
		$this->module_position = '90';
		$this->name = 'GlobalSearch';
		$this->description = 'GlobalSearchDescription';
		$this->descriptionlong = 'GlobalSearchDescription';
		$this->editor_name = 'GlobalSearch';
		$this->version = '1.3.1';
		$this->const_name = 'MAIN_MODULE_GLOBALSEARCH';
		$this->picto = 'search';

		// The searchform context is provided by Dolibarr's standard sidebar search.
		$this->module_parts = array(
			'css' => array('/globalsearch/css/spotlight.css'),
			'js' => array('/globalsearch/js/spotlight.js.php'),
			'hooks' => array('searchform', 'thirdpartylist'),
		);

		$this->dirs = array();
		$this->config_page_url = array('setup.php@globalsearch');
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array('globalsearch@globalsearch');
		$this->phpmin = array(7, 0);
		$this->need_dolibarr_version = array(19, 0);
		$this->need_javascript_ajax = 0;
		$this->warnings_activation = array();
		$this->const = array(
			array(0, 'GLOBALSEARCH_SPOTLIGHT_ENABLED', 'yesno', '1', 'Enable GlobalSearch spotlight', 0, 'current', 1),
			array(0, 'GLOBALSEARCH_SHORTCUT', 'chaine', 'CTRL+K', 'Keyboard shortcut for GlobalSearch spotlight', 0, 'current', 1),
			array(0, 'GLOBALSEARCH_MIN_CHARS', 'chaine', '2', 'Minimum characters before searching', 0, 'current', 1),
			array(0, 'GLOBALSEARCH_LIMIT', 'chaine', '10', 'Results per category', 0, 'current', 1),
		);
		$this->tabs = array();
		$this->menus = array();

		if (!isset($conf->globalsearch) || !isset($conf->globalsearch->enabled)) {
			$conf->globalsearch = new stdClass();
			$conf->globalsearch->enabled = 0;
		}
	}
}


