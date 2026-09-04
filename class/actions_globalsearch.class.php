<?php
/* Copyright (C) 2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Hooks used by the GlobalSearch external module.
 */
class ActionsGlobalSearch
{
	/** @var DoliDB */
	public $db;
	/** @var array */
	public $results = array();
	/** @var string */
	public $resprints = '';

	/**
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Adds the global-search destination to the native sidebar search dropdown.
	 *
	 * @param array $parameters Hook parameters
	 * @param CommonObject $object Current object
	 * @param string $action Current action
	 * @param HookManager $hookmanager Hook manager
	 * @return int 0 on success
	 */
	public function addSearchEntry($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user;

		if ($user->socid > 0) {
			return 0;
		}


		// Do not expose an empty destination to a user who cannot read any supported object.
		$canReadSomething = $user->hasRight('societe', 'lire')
			|| $user->hasRight('societe', 'contact', 'lire')
			|| $user->hasRight('product', 'read')
			|| $user->hasRight('facture', 'lire')
			|| $user->hasRight('supplier_invoice', 'read')
			|| $user->hasRight('fournisseur', 'facture', 'lire')
			|| $user->hasRight('commande', 'lire')
			|| $user->hasRight('propal', 'lire')
			|| $user->hasRight('contrat', 'lire')
			|| $user->hasRight('projet', 'lire');
		if (!$canReadSomething) {
			return 0;
		}

		$langs->load('globalsearch@globalsearch');
		$this->results['globalsearch'] = array(
			'position' => 1,
			'shortcut' => 'G',
			'img' => 'search',
			'label' => $langs->trans('GlobalSearch'),
			'text' => img_picto('', 'search', 'class="pictofixedwidth"').' '.$langs->trans('GlobalSearch'),
			'url' => dol_buildpath('/globalsearch/search.php', 1),
		);

		return 0;
	}

	/**
	 * Adds the address to the native third-party quick search so that the
	 * full-results page uses the same scope as GlobalSearch.
	 *
	 * @param array $parameters Hook parameters
	 * @param CommonObject $object Current object
	 * @param string $action Current action
	 * @param HookManager $hookmanager Hook manager
	 * @return int 0 on success
	 */
	public function completeFieldsToSearchAll($parameters, &$object, &$action, $hookmanager)
	{
		$contexts = explode(':', $parameters['context']);
		if (in_array('thirdpartylist', $contexts, true)) {
			$this->results['fieldstosearchall'] = array('s.address' => 'Address');
		}
		return 0;
	}}

