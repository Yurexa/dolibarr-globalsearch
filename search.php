<?php
/* Copyright (C) 2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

// Load Dolibarr environment from either htdocs/custom/globalsearch or htdocs/globalsearch.
$res = 0;
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) $res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] === $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, $i + 1).'/main.inc.php')) $res = @include substr($tmp, 0, $i + 1).'/main.inc.php';
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, $i + 1)).'/main.inc.php')) $res = @include dirname(substr($tmp, 0, $i + 1)).'/main.inc.php';
if (!$res && file_exists('../../main.inc.php')) $res = @include '../../main.inc.php';
if (!$res && file_exists('../main.inc.php')) $res = @include '../main.inc.php';
if (!$res) die('Include of main fails');

if (!isModEnabled('globalsearch')) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}

$langs->loadLangs(array('main', 'companies', 'products', 'bills', 'globalsearch@globalsearch'));

$term = trim(GETPOST('q', 'restricthtml'));
if ($term === '') {
	$term = trim(GETPOST('search_all', 'restricthtml'));
}
$limit = max(1, min(50, getDolGlobalInt('GLOBALSEARCH_LIMIT', 10)));
$format = GETPOST('format', 'aZ09');

/**
 * Execute a query and return its rows.
 *
 * @param string $sql SQL query
 * @return array<int, object>
 */
function globalsearch_fetch_rows($sql)
{
	global $db;

	$rows = array();
	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog('GlobalSearch query failed: '.$db->lasterror(), LOG_ERR);
		return $rows;
	}
	while ($obj = $db->fetch_object($resql)) {
		$rows[] = $obj;
	}
	return $rows;
}

/**
 * Counts all rows of a limited search query.
 *
 * @param string $sql Search query ending with an ORDER BY clause
 * @return int
 */
/**
 * Converts HTML description to a compact readable text excerpt.
 *
 * @param string $value Source description
 * @param int $length Maximum length
 * @return string
 */
function globalsearch_excerpt($value, $length = 180)
{
	$value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$value = preg_replace('/<br\s*\/?>/iu', ' ', $value);
	$value = preg_replace('/<\/li\s*>/iu', ' · ', $value);
	$value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
	if (function_exists('mb_strwidth') && mb_strwidth($value, 'UTF-8') > $length) {
		return rtrim(mb_strimwidth($value, 0, $length - 1, '', 'UTF-8')).'…';
	}
	return $value;
}

/**
 * Formats product labels and descriptions for concise search results.
 *
 * @param string $sql Product search query
 * @return array<int, object>
 */
function globalsearch_fetch_product_rows($sql)
{
	$rows = globalsearch_fetch_rows($sql);
	foreach ($rows as $row) {
		$description = globalsearch_excerpt(isset($row->description) ? $row->description : '');
		$row->subtitle = trim((string) $row->subtitle).($description !== '' ? ' · '.$description : '');
	}
	return $rows;
}
function globalsearch_count_rows($sql)
{
	global $db;

	$position = strripos($sql, ' ORDER BY ');
	if ($position === false) {
		return 0;
	}
	$resql = $db->query('SELECT COUNT(*) AS total FROM ('.substr($sql, 0, $position).') AS globalsearch_count');
	if (!$resql || !($obj = $db->fetch_object($resql))) {
		dol_syslog('GlobalSearch count query failed: '.$db->lasterror(), LOG_ERR);
		return 0;
	}
	return (int) $obj->total;
}
/**
 * Builds a case-insensitive LIKE condition for trusted SQL field names.
 *
 * @param array<int, string> $fields SQL field names
 * @param string $search Search term
 * @return string
 */
function globalsearch_like_condition($fields, $search)
{
	global $db;

	$search = str_replace('*', '%', $search);
	$pattern = '%'.$db->escape($search).'%';
	$conditions = array();
	foreach ($fields as $field) {
		$conditions[] = "LOWER(".$field.") LIKE LOWER('".$pattern."')";
	}
	return '('.implode(' OR ', $conditions).')';
}

$sections = array();
if ($term !== '') {

	if (isModEnabled('societe') && $user->hasRight('societe', 'lire')) {
		$sql = 'SELECT s.rowid, s.nom AS label, CONCAT_WS(\' · \', s.code_client, s.town, s.email) AS subtitle';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe AS s';
		$sql .= ' WHERE s.entity IN ('.getEntity('societe').')';
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ' AND EXISTS (SELECT sc.fk_soc FROM '.MAIN_DB_PREFIX.'societe_commerciaux AS sc WHERE sc.fk_soc = s.rowid AND sc.fk_user = '.((int) $user->id).')';
		}
		if (!$user->hasRight('fournisseur', 'lire')) {
			$sql .= ' AND (s.fournisseur <> 1 OR s.client <> 0)';
		}
		$sql .= ' AND '.globalsearch_like_condition(array('s.nom', 's.name_alias', 's.code_client', 's.code_fournisseur', 's.code_compta', 's.code_compta_fournisseur', 's.zip', 's.town', 's.email', 's.url', 's.tva_intra', 's.siren', 's.siret', 's.ape', 's.phone', 's.fax', 's.address'), $term);
		$sql .= ' ORDER BY s.nom ASC';
		$sql .= $db->plimit($limit);
		$sections[] = array('title' => 'GlobalSearchThirdParties', 'picto' => 'object_company', 'url' => '/societe/card.php?socid=', 'more_url' => '/societe/list.php?search_all=', 'count' => globalsearch_count_rows($sql), 'rows' => globalsearch_fetch_rows($sql));
	}

	if (isModEnabled('societe') && $user->hasRight('societe', 'contact', 'lire')) {
		$sql = 'SELECT c.rowid, TRIM(CONCAT_WS(\' \', c.firstname, c.lastname)) AS label, CONCAT_WS(\' · \', s.nom, c.email, c.phone) AS subtitle';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'socpeople AS c';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = c.fk_soc';
		$sql .= ' WHERE c.entity IN ('.getEntity('contact').')';
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ' AND (EXISTS (SELECT sc.fk_soc FROM '.MAIN_DB_PREFIX.'societe_commerciaux AS sc WHERE sc.fk_soc = c.fk_soc AND sc.fk_user = '.((int) $user->id).') OR c.fk_soc IS NULL)';
		}
		$sql .= ' AND '.globalsearch_like_condition(array('c.firstname', 'c.lastname', 'c.email', 'c.phone'), $term);
		$sql .= ' ORDER BY c.lastname ASC, c.firstname ASC';
		$sql .= $db->plimit($limit);
		$sections[] = array('title' => 'GlobalSearchContacts', 'picto' => 'object_contact', 'url' => '/contact/card.php?id=', 'more_url' => '/contact/list.php?search_all=', 'count' => globalsearch_count_rows($sql), 'rows' => globalsearch_fetch_rows($sql));
	}

	if ((isModEnabled('product') || isModEnabled('service')) && $user->hasRight('product', 'read')) {
		$sql = 'SELECT p.rowid, p.ref AS label, p.label AS subtitle, p.description';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'product AS p';
		$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
		$sql .= ' AND '.globalsearch_like_condition(array('p.ref', 'p.label', 'p.description'), $term);
		$sql .= ' ORDER BY p.ref ASC';
		$sql .= $db->plimit($limit);
		$sections[] = array('title' => 'GlobalSearchProducts', 'picto' => 'product', 'url' => '/product/card.php?id=', 'more_url' => '/product/list.php?search_all=', 'count' => globalsearch_count_rows($sql), 'rows' => globalsearch_fetch_product_rows($sql));
	}

	if (isModEnabled('facture') && $user->hasRight('facture', 'lire')) {
		$sql = 'SELECT f.rowid, f.ref AS label, CONCAT_WS(\' · \', f.ref_client, s.nom) AS subtitle';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture AS f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = f.fk_soc';
		$sql .= ' WHERE f.entity IN ('.getEntity('invoice').')';
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ' AND EXISTS (SELECT sc.fk_soc FROM '.MAIN_DB_PREFIX.'societe_commerciaux AS sc WHERE sc.fk_soc = f.fk_soc AND sc.fk_user = '.((int) $user->id).')';
		}
		$sql .= ' AND '.globalsearch_like_condition(array('f.ref', 'f.ref_client', 's.nom'), $term);
		$sql .= ' ORDER BY f.datef DESC, f.rowid DESC';
		$sql .= $db->plimit($limit);
		$sections[] = array('title' => 'GlobalSearchCustomerInvoices', 'picto' => 'object_bill', 'url' => '/compta/facture/card.php?facid=', 'more_url' => '/compta/facture/list.php?search_all=', 'count' => globalsearch_count_rows($sql), 'rows' => globalsearch_fetch_rows($sql));
	}

	$canReadSupplierInvoice = (isModEnabled('supplier_invoice') && $user->hasRight('supplier_invoice', 'read')) || (isModEnabled('fournisseur') && $user->hasRight('fournisseur', 'facture', 'lire'));
	if ($canReadSupplierInvoice) {
		$sql = 'SELECT f.rowid, f.ref AS label, CONCAT_WS(\' · \', f.ref_supplier, s.nom) AS subtitle';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn AS f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = f.fk_soc';
		$sql .= ' WHERE f.entity IN ('.getEntity('facture_fourn').')';
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ' AND EXISTS (SELECT sc.fk_soc FROM '.MAIN_DB_PREFIX.'societe_commerciaux AS sc WHERE sc.fk_soc = f.fk_soc AND sc.fk_user = '.((int) $user->id).')';
		}
		$sql .= ' AND '.globalsearch_like_condition(array('f.ref', 'f.ref_supplier', 's.nom'), $term);
		$sql .= ' ORDER BY f.datef DESC, f.rowid DESC';
		$sql .= $db->plimit($limit);
		$sections[] = array('title' => 'GlobalSearchSupplierInvoices', 'picto' => 'object_bill', 'url' => '/fourn/facture/card.php?facid=', 'more_url' => '/fourn/facture/list.php?search_all=', 'count' => globalsearch_count_rows($sql), 'rows' => globalsearch_fetch_rows($sql));
	}

	if (isModEnabled('commande') && $user->hasRight('commande', 'lire')) {
		$sql = 'SELECT c.rowid, c.ref AS label, CONCAT_WS(\' · \', c.ref_client, s.nom) AS subtitle';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'commande AS c';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = c.fk_soc';
		$sql .= ' WHERE c.entity IN ('.getEntity('commande').')';
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ' AND EXISTS (SELECT sc.fk_soc FROM '.MAIN_DB_PREFIX.'societe_commerciaux AS sc WHERE sc.fk_soc = c.fk_soc AND sc.fk_user = '.((int) $user->id).')';
		}
		$sql .= ' AND '.globalsearch_like_condition(array('c.ref', 'c.ref_client', 's.nom'), $term);
		$sql .= ' ORDER BY c.date_commande DESC, c.rowid DESC';
		$sql .= $db->plimit($limit);
		$sections[] = array('title' => 'GlobalSearchCustomerOrders', 'picto' => 'object_order', 'url' => '/commande/card.php?id=', 'more_url' => '/commande/list.php?search_all=', 'count' => globalsearch_count_rows($sql), 'rows' => globalsearch_fetch_rows($sql));
	}

	if (isModEnabled('propal') && $user->hasRight('propal', 'lire')) {
		$sql = 'SELECT p.rowid, p.ref AS label, CONCAT_WS(\' · \', p.ref_client, s.nom) AS subtitle';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'propal AS p';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = p.fk_soc';
		$sql .= ' WHERE p.entity IN ('.getEntity('propal').')';
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ' AND EXISTS (SELECT sc.fk_soc FROM '.MAIN_DB_PREFIX.'societe_commerciaux AS sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = '.((int) $user->id).')';
		}
		$sql .= ' AND '.globalsearch_like_condition(array('p.ref', 'p.ref_client', 's.nom'), $term);
		$sql .= ' ORDER BY p.datep DESC, p.rowid DESC';
		$sql .= $db->plimit($limit);
		$sections[] = array('title' => 'GlobalSearchProposals', 'picto' => 'object_propal', 'url' => '/comm/propal/card.php?id=', 'more_url' => '/comm/propal/list.php?search_all=', 'count' => globalsearch_count_rows($sql), 'rows' => globalsearch_fetch_rows($sql));
	}

	if (isModEnabled('contrat') && $user->hasRight('contrat', 'lire')) {
		$sql = 'SELECT c.rowid, c.ref AS label, CONCAT_WS(\' · \', c.ref_customer, s.nom) AS subtitle';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'contrat AS c';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = c.fk_soc';
		$sql .= ' WHERE c.entity IN ('.getEntity('contract').')';
		$sql .= ' AND '.globalsearch_like_condition(array('c.ref', 'c.ref_customer', 'c.ref_supplier', 's.nom'), $term);
		$sql .= ' ORDER BY c.date_contrat DESC, c.rowid DESC';
		$sql .= $db->plimit($limit);
		$sections[] = array('title' => 'GlobalSearchContracts', 'picto' => 'object_contract', 'url' => '/contrat/card.php?id=', 'more_url' => '/contrat/list.php?search_all=', 'count' => globalsearch_count_rows($sql), 'rows' => globalsearch_fetch_rows($sql));
	}

	if (isModEnabled('project') && $user->hasRight('projet', 'lire')) {
		$authorizedProjects = '';
		if (!$user->hasRight('projet', 'all', 'lire')) {
			include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			$projectstatic = new Project($db);
			$authorizedProjects = $db->sanitize($projectstatic->getProjectsAuthorizedForUser($user, 0, 1, 0) ?: '0');
		}

		$sql = 'SELECT p.rowid, p.ref AS label, CONCAT_WS(\' · \', p.title, s.nom) AS subtitle';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'projet AS p';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = p.fk_soc';
		$sql .= ' WHERE p.entity IN ('.getEntity('project').')';
		if ($authorizedProjects !== '') {
			$sql .= ' AND p.rowid IN ('.$authorizedProjects.')';
		}
		$sql .= ' AND '.globalsearch_like_condition(array('p.ref', 'p.title', 'p.description', 's.nom'), $term);
		$sql .= ' ORDER BY p.dateo DESC, p.rowid DESC';
		$sql .= $db->plimit($limit);
		$sections[] = array('title' => 'GlobalSearchProjects', 'picto' => 'project', 'url' => '/projet/card.php?id=', 'more_url' => '/projet/list.php?search_all=', 'count' => globalsearch_count_rows($sql), 'rows' => globalsearch_fetch_rows($sql));

		$sql = 'SELECT t.rowid, t.ref AS label, CONCAT_WS(\' · \', t.label, p.ref, p.title) AS subtitle';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_task AS t';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'projet AS p ON p.rowid = t.fk_projet';
		$sql .= ' WHERE p.entity IN ('.getEntity('project').')';
		if ($authorizedProjects !== '') {
			$sql .= ' AND p.rowid IN ('.$authorizedProjects.')';
		}
		$sql .= ' AND '.globalsearch_like_condition(array('t.ref', 't.label', 't.description', 't.note_public'), $term);
		$sql .= ' ORDER BY t.dateo DESC, t.rowid DESC';
		$sql .= $db->plimit($limit);
		$sections[] = array('title' => 'GlobalSearchTasks', 'picto' => 'projecttask', 'url' => '/projet/tasks/task.php?id=', 'more_url' => '/projet/tasks/list.php?search_all=', 'count' => globalsearch_count_rows($sql), 'rows' => globalsearch_fetch_rows($sql));
	}
}

if ($format === 'json') {
	header('Content-Type: application/json; charset=UTF-8');
	$payload = array('term' => $term, 'sections' => array());
	foreach ($sections as $section) {
		$rows = array();
		foreach ($section['rows'] as $row) {
			$rows[] = array('label' => $row->label, 'subtitle' => $row->subtitle, 'url' => DOL_URL_ROOT.$section['url'].((int) $row->rowid));
		}
		$payload['sections'][] = array('title' => $langs->trans($section['title']), 'count' => (int) $section['count'], 'rows' => $rows, 'more_url' => (!empty($section['more_url']) ? DOL_URL_ROOT.$section['more_url'].urlencode($term) : ''));
	}
	print json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}
$title = $langs->trans('GlobalSearchResults');
llxHeader('', $title);

print load_fiche_titre($title, '', 'search');
print '<form action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method="GET" class="searchform">';
print '<input type="text" class="minwidth300" name="q" value="'.dol_escape_htmltag($term).'" autofocus>';
print ' <button class="button" type="submit">'.$langs->trans('Search').'</button>';
print '</form>';

if ($term === '') {
	print '<div class="opacitymedium paddingtop">'.$langs->trans('GlobalSearchNoTerm').'</div>';
} else {
	print '<div class="opacitymedium paddingtop">'.dol_escape_htmltag($langs->trans('GlobalSearchMoreResults', $limit)).'</div>';
	$hasResults = false;
	foreach ($sections as $section) {
		if (empty($section['rows'])) {
			continue;
		}
		$hasResults = true;
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="2">'.img_picto('', $section['picto']).' '.$langs->trans($section['title']).' ('.((int) $section['count']).')</th></tr>';
		foreach ($section['rows'] as $row) {
			$url = DOL_URL_ROOT.$section['url'].((int) $row->rowid);
			print '<tr class="oddeven"><td class="nowrap">';
			print '<a href="'.dol_escape_htmltag($url).'">'.dol_escape_htmltag($row->label).'</a>';
			print '</td><td class="opacitymedium">'.dol_escape_htmltag($row->subtitle).'</td></tr>';
		}
		print '</table></div>';
		if (!empty($section['more_url']) && $section['count'] > count($section['rows'])) {
			$moreurl = DOL_URL_ROOT.$section['more_url'].urlencode($term);
			print '<div class="right paddingtop paddingbottom"><a class="button button-small" href="'.dol_escape_htmltag($moreurl).'">'.$langs->trans('GlobalSearchSeeAll').'</a></div>';
		}
		print '<br>';
	}
	if (!$hasResults) {
		print '<div class="opacitymedium paddingtop">'.$langs->trans('GlobalSearchNoResult').'</div>';
	}
}

llxFooter();
$db->close();


