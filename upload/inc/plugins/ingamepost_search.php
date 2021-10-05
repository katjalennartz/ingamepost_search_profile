<?php
if (!defined("IN_MYBB")) {
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function ingamepost_search_info()
{
  return array(
    "name"      => "Suche Ingameposts eines Users",
    "description"  => "Link im Profil zur Suche der Ingameposts",
    "website"    => "https://github.com/katjalennartz",
    "author"    => "Risuena",
    "authorsite"  => "https://github.com/katjalennartz",
    "version"    => "1.0",
    "compatibility" => "18*"
  );
}

function ingamepost_search_is_installed()
{
  global $mybb;
  if (isset($mybb->settings['ingame_search_fids'])) {
    return true;
  }
  return false;
}

function ingamepost_search_install(){
  global $db;
  // Einstellungen
  $setting_group = array(
    'name' => 'ingame_search',
    'title' => 'Ingame Search Link',
    'description' => 'Einstellungen für den Link im Profil zur Suche von Ingameposts ',
    'disporder' => 3, // The order your setting group will display
    'isdefault' => 0
  );
  $gid = $db->insert_query("settinggroups", $setting_group);
  $setting_array = array(
    'ingame_search_fids' => array(
      'title' => 'Foren Ids',
      'description' => 'Gib hier die Parentforen Ids ein, die berücksichtigt werden sollen (z.B Ingame und Archiv) z.B: 4,24.',
      'optionscode' => 'text',
      'value' => '4,24', // Default
      'disporder' => 1
    ),
    'ingame_search_art' => array(
      'title' => 'Anzeige Suchergebnis',
      'description' => 'Soll das Suchergebnis als Postliste oder Threadliste ausgeben werden?',
      'optionscode' => "select\nthreads=Thread\nposts=Post",
      'value' => "posts",
      'disporder' => 2
    )
);

foreach ($setting_array as $name => $setting) {
  $setting['name'] = $name;
  $setting['gid'] = $gid;
  $db->insert_query('settings', $setting);
}
rebuild_settings();
}
function ingamepost_search_uninstall(){
  global $db;
  // Einstellungen entfernen
  $db->delete_query("settings", "name LIKE 'ingame_search%'");
  $db->delete_query('settinggroups', "name = 'ingame_search'");
  //templates noch entfernen
  rebuild_settings();
}

function ingamepost_search_activate()
{
  global $db;

  require "../inc/adminfunctions_templates.php";
  find_replace_templatesets("member_profile", "#" . preg_quote('{$warning_level}') . "#i", '{$warning_level}{$ingamesearchlink}');

}

function ingamepost_search_deactivate()
{
  global $db;

  require "../inc/adminfunctions_templates.php";
  find_replace_templatesets("member_profile", "#" . preg_quote('{$ingamesearchlink}') . "#i", '');

}


$plugins->add_hook("member_profile_start", "ingamepost_search_link");
function ingamepost_search_link()
{
  global $mybb, $templates, $ingamesearchlink;

  $uid = intval($mybb->input['uid']);
  $ingamesearchlink = "<a href=\"{$mybb->settings['bburl']}/misc.php?action=findingameposts&uid={$uid}\">Suche</a>";
}

$plugins->add_hook("misc_start", "ingamepost_search_start");
function ingamepost_search_start()
{
  global $db, $mybb, $lang, $plugins;

  require_once MYBB_ROOT . "inc/functions_search.php";
  // $fidsstr= $mybb->settings['ingame_search_fids'];
  $fidsarray = array_filter(explode(",", $mybb->settings['ingame_search_fids']));
  $art = $mybb->settings['ingame_search_art'];
  $where ="";
  foreach($fidsarray as $value) {
    $where .= "concat(',',parentlist,',') LIKE '%,{$value},%' OR ";
  }
  //letztes or löschen
  $where = substr($where, 0, -3);
  $posts ="";
  $threads ="";

  if ($mybb->input['action'] == "findingameposts") {
    $uid = intval($mybb->input['uid']);
    $query = $db->write_query("SELECT * from mybb_posts,
    (SELECT fid as fff FROM mybb_forums WHERE {$where}) as f
    where fff = fid and uid = {$uid}");
    while ($result = $db->fetch_array($query)) {
      $posts .= $result['pid'] . ",";
    }
    $posts = substr($posts, 0, -1);

    $query2 = $db->write_query("SELECT DISTINCT(tid) from mybb_posts,
    (SELECT fid as fff FROM ".TABLE_PREFIX."forums WHERE {$where} ) as f
    where fff = fid and uid = {$uid}");
    while ($result = $db->fetch_array($query2)) {
      $threads .= $result['tid'] . ",";
    }
    $threads = substr($threads, 0, -1);


    $sid = md5(uniqid(microtime(), 1));
    $searcharray = array(
      "sid" => $db->escape_string($sid),
      "uid" => intval($mybb->input['uid']),
      "dateline" => time(),
      "ipaddress" => $db->escape_string($session->ipaddress),
      "threads" => $threads,
      "posts" => $posts,
      "resulttype" => $art,
      "querycache" => $db->escape_string($where_sql),
    );

    $plugins->run_hooks("search_do_search_process");
    $db->insert_query("searchlog", $searcharray);
    redirect("search.php?action=results&sid=" . $sid, $lang->redirect_searchresults);
  }
}
