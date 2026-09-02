<?php
// Load Dolibarr environment from either htdocs/custom/globalsearch or htdocs/globalsearch.
$res = 0;
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) $res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] === $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, $i + 1).'/main.inc.php')) $res = @include substr($tmp, 0, $i + 1).'/main.inc.php';
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, $i + 1)).'/main.inc.php')) $res = @include dirname(substr($tmp, 0, $i + 1)).'/main.inc.php';
if (!$res && file_exists('../../../main.inc.php')) $res = @include '../../../main.inc.php';
if (!$res && file_exists('../../main.inc.php')) $res = @include '../../main.inc.php';
if (!$res) die('Include of main fails');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
$langs->loadLangs(array('admin', 'globalsearch@globalsearch'));
if (!$user->admin) accessforbidden();
$action = GETPOST('action', 'aZ09');
if ($action === 'save') {
	dolibarr_set_const($db, 'GLOBALSEARCH_SPOTLIGHT_ENABLED', GETPOSTINT('spotlight_enabled'), 'yesno', 0, '', $conf->entity);
	dolibarr_set_const($db, 'GLOBALSEARCH_SHORTCUT', strtoupper(GETPOST('shortcut', 'alphanohtml')), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'GLOBALSEARCH_MIN_CHARS', max(1, min(10, GETPOSTINT('min_chars'))), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'GLOBALSEARCH_LIMIT', max(1, min(50, GETPOSTINT('limit'))), 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
}
llxHeader('', $langs->trans('GlobalSearchSetup'));
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>';
print load_fiche_titre($langs->trans('GlobalSearchSetup'), $linkback, 'search');
print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="save"><table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans('GlobalSearchSpotlight').'</td></tr>';
print '<tr><td>'.$langs->trans('GlobalSearchSpotlightEnabled').'</td><td><input type="checkbox" name="spotlight_enabled" value="1"'.(getDolGlobalInt('GLOBALSEARCH_SPOTLIGHT_ENABLED', 1) ? ' checked' : '').'></td></tr>';
print '<tr><td>'.$langs->trans('GlobalSearchShortcut').'</td><td><input class="minwidth200" name="shortcut" value="'.dol_escape_htmltag(getDolGlobalString('GLOBALSEARCH_SHORTCUT', 'CTRL+K')).'"><br><span class="opacitymedium">CTRL+K, ALT+K ou CTRL+SHIFT+K</span></td></tr>';
print '<tr><td>'.$langs->trans('GlobalSearchMinChars').'</td><td><input type="number" min="1" max="10" name="min_chars" value="'.getDolGlobalInt('GLOBALSEARCH_MIN_CHARS', 2).'"></td></tr>';
print '<tr><td>'.$langs->trans('GlobalSearchLimit').'</td><td><input type="number" min="1" max="50" name="limit" value="'.getDolGlobalInt('GLOBALSEARCH_LIMIT', 10).'"></td></tr>';
print '</table><div class="center paddingtop"><input class="button button-save" type="submit" value="'.$langs->trans('Save').'"> </div></form>';
llxFooter(); $db->close();


