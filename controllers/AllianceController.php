<?php

namespace Modules\Alliance\Controllers;

use App\Core\AbstractController;
use App\Core\Request;
use App\Core\Response;
use Modules\Alliance\Repositories\AllianceRepository;
use Modules\Alliance\Services\AllianceService;

final class AllianceController extends AbstractController
{
    public function __construct(
        private readonly AllianceRepository $alliances = new AllianceRepository(),
    ) {
    }

    public function indexAction(Request $request): Response
    {
        $this->bootLegacy();

        $user = $this->user();
        $lang = $this->lang();

        if (empty($user['id'])) {
            echo '<script language="javascript">';
            echo 'parent.location="../";';
            echo '</script>';
        }

        $mode = ($_GET['mode'] ?? null);
        $yes = ($_GET['yes'] ?? null);
        $edit = ($_GET['edit'] ?? null);
        $allyid = intval($_GET['allyid'] ?? 0);
        $show = intval($_GET['show'] ?? 0);
        $sort = intval($_GET['sort'] ?? 0);
        $sendmail = intval($_GET['sendmail'] ?? 0);
        $t = ($_GET['t'] ?? null);
        $a = intval($_GET['a'] ?? 0);
        $tag = mysql_escape_string(($_GET['tag'] ?? null));
        $rank = ($_GET['rank'] ?? null);
        $kick = ($_GET['kick'] ?? null);
        $d = ($_GET['d'] ?? null);

        $this->includeLang('alliance');
        $lang = $this->lang();

        if (($_GET['mode'] ?? null) == 'ainfo') {
            return $this->showInfo($a, $tag);
        }

        if ($user['ally_id'] == 0) {
            return $this->withoutAlliance($mode, $yes, $allyid, $tag, $t);
        }

        if ($user['ally_id'] != 0 && $user['ally_request'] == 0) {
            return $this->withAlliance($mode, $yes, $edit, $show, $sort, $sendmail, $t, $rank, $kick, $d);
        }

        return Response::html('');
    }


    public function infoAction(Request $request): Response
    {
        $this->bootLegacy();

        $a = $_GET["a"] ?? null;

        if (!is_numeric($a) || !$a) {
            return $this->renderMessage("Identifiant d'alliance invalide", "Erreur");
        }

        $allyrow = $this->alliances->findInfoById((int) $a);

        if (!$allyrow) {
            return $this->renderMessage("Alliance non trouv&eacute;e", "Erreur");
        }

        $count = $this->alliances->countMembers((int) $a);
        $ally_member_scount = (int) ($count['n'] ?? 0);

        $ally_image_html = "";
        if ($allyrow["ally_image"] != "") {
            $ally_image_html = $this->partial('alliance_info_image', array(
                'ally_image_url' => htmlspecialchars((string) $allyrow['ally_image'], ENT_QUOTES),
                'ally_tag' => htmlspecialchars((string) $allyrow['ally_tag']),
            ));
        }

        $ally_web_html = "";
        if ($allyrow["ally_web"] != "") {
            $ally_web_html = $this->partial('alliance_info_web', array(
                'ally_web_label' => 'Site internet',
                'ally_web_url' => htmlspecialchars((string) $allyrow['ally_web'], ENT_QUOTES),
            ));
        }

        $parse = array(
            'ally_image' => $ally_image_html,
            'ally_tag' => $allyrow["ally_tag"],
            'ally_name' => $allyrow["ally_name"],
            'ally_members' => $ally_member_scount,
            'ally_description' => nl2br($this->bbcodeToHtml((string) $allyrow["ally_description"])),
            'ally_web' => $ally_web_html,
            'info_title' => "Information sur l'alliance [" . $allyrow["ally_name"] . "]",
        );

        $page = $this->parse($this->template('alliance_info'), $parse);

        return $this->renderPage($page, $parse['info_title'], false);
    }

    /**
     * Marqueur d'activité d'un membre : la classe porte l'état, le libellé vient du jeu.
     */
    private function onlineBadge(string $class, string $label): string
    {
        return $this->partial('alliance_member_online', array(
            'online_class' => $class,
            'online_label' => $label,
        ));
    }

    private function showInfo(int $a, string $tag): Response
    {
        $user = $this->user();
        $lang = $this->lang();

        $lang['Alliance_information'] = "Information Alliance";

        if (isset($_GET['tag'])) {
            $allyrow = $this->alliances->findByTagFull($tag);
        } elseif (is_numeric($a) && $a != 0) {
            $allyrow = $this->alliances->findByIdRaw($a);
        } else {
            return $this->renderMessage("Cette alliance n'existe pas !", "Information Alliance (1)");
        }

        if (!$allyrow) {
            return $this->renderMessage("Cette alliance n'existe pas !", "Information Alliance (1)");
        }

        extract($allyrow);

        $ally_image = ($allyrow['ally_image'] ?? '');
        $ally_description = ($allyrow['ally_description'] ?? '');
        $ally_web = ($allyrow['ally_web'] ?? '');

        $lang['ally_image'] = ($ally_image != "")
            ? $this->partial('alliance_info_image', array(
                'ally_image_url' => htmlspecialchars((string) $ally_image, ENT_QUOTES),
                'ally_tag' => htmlspecialchars((string) $allyrow['ally_tag']),
            ))
            : '';

        if ($ally_description == "") {
            $ally_description = "Il n'y as aucune descriptions de cette alliance.";
        }
        $lang['ally_description'] = nl2br($this->bbcodeToHtml((string) $ally_description));

        $lang['ally_web'] = ($ally_web != "")
            ? $this->partial('alliance_info_web', array(
                'ally_web_label' => (string) $lang['Main_Page'],
                'ally_web_url' => htmlspecialchars((string) $ally_web, ENT_QUOTES),
            ))
            : '';

        $lang['ally_member_scount'] = $allyrow['ally_members'];
        $lang['ally_name'] = $allyrow['ally_name'];
        $lang['ally_tag'] = $allyrow['ally_tag'];

        if ($user['ally_id'] == 0) {
            $lang['bewerbung'] = $this->partial('alliance_info_apply', array(
                'ally_apply_label' => 'Candidature',
                'ally_apply_url' => '/game/alliance?mode=apply&amp;allyid=' . (int) $allyrow['id'],
                'ally_apply_text' => 'Cliquer ici pour ecrire votre candidature',
            ));
        } else {
            $lang['bewerbung'] = '';
        }

        $page = $this->parse($this->template('alliance_ainfo'), $lang);

        return $this->renderPage($page, $lang['Alliance_information'] . ' [' . $allyrow['ally_name'] . ']');
    }

    private function withoutAlliance($mode, $yes, $allyid, $tag, $t): Response
    {
        $user = $this->user();
        $lang = $this->lang();

        if ($mode == 'make' && $user['ally_request'] == 0) {
            if ($yes == 1 && $_POST) {
                if (!($_POST['atag'] ?? null)) {
                    return $this->renderMessage($lang['have_not_tag'], $lang['make_alliance']);
                }
                if (!($_POST['aname'] ?? null)) {
                    return $this->renderMessage($lang['have_not_name'], $lang['make_alliance']);
                }
                $_POST['aname'] = addslashes($_POST['aname']);
                $_POST['atag'] = addslashes($_POST['atag']);

                $tagquery = $this->alliances->findByTagFull($_POST['atag']);

                if ($tagquery) {
                    return $this->renderMessage(str_replace('%s', $_POST['atag'], $lang['always_exist']), $lang['make_alliance']);
                }

                $this->alliances->insert($_POST['aname'], $_POST['atag'], (int) $user['id'], time());

                $allyquery = $this->alliances->findByTagFull($_POST['atag']);

                $this->alliances->updateUserAlly((int) $user['id'], (int) $allyquery['id'], $allyquery['ally_name'], time());

                $page = MessageForm(str_replace('%s', $_POST['atag'], $lang['ally_maked']), str_replace('%s', $_POST['atag'], $lang['alliance_has_been_maked']) . "<br><br>", "", $lang['Ok']);
            } else {
                $page = $this->parse($this->template('alliance_make'), $lang);
            }

            return $this->renderPage($page, $lang['make_alliance']);
        }

        if ($mode == 'search' && $user['ally_request'] == 0) {
            $parse = $lang;
            $lang['searchtext'] = ($_POST['searchtext'] ?? null);
            $page = $this->parse($this->template('alliance_searchform'), $lang);
            $parse['result'] = null;

            if ($_POST) {
                $rows = $this->alliances->searchByNameOrTag(($_POST['searchtext'] ?? null));

                if (count($rows) != 0) {
                    $template = $this->template('alliance_searchresult_row');

                    foreach ($rows as $s) {
                        $entry = array();
                        $entry['ally_tag'] = "[<a href=\"/game/alliance?mode=apply&allyid={$s['id']}\">{$s['ally_tag']}</a>]";
                        $entry['ally_name'] = $s['ally_name'];
                        $entry['ally_members'] = $s['ally_members'];

                        $parse['result'] .= $this->parse($template, $entry);
                    }

                    $page .= $this->parse($this->template('alliance_searchresult_table'), $parse);
                }
            }

            return $this->renderPage($page, $lang['search_alliance']);
        }

        if ($mode == 'apply' && $user['ally_request'] == 0) {
            if (!is_numeric(($_GET['allyid'] ?? null)) || !($_GET['allyid'] ?? null) || $user['ally_request'] != 0 || $user['ally_id'] != 0) {
                return $this->renderMessage($lang['it_is_not_posible_to_apply'], $lang['it_is_not_posible_to_apply']);
            }

            $allyrow = $this->alliances->findTagAndRequestById(intval($_GET['allyid']));

            if (!$allyrow) {
                return $this->renderMessage($lang['it_is_not_posible_to_apply'], $lang['it_is_not_posible_to_apply']);
            }

            extract($allyrow);
            $ally_tag = ($allyrow['ally_tag'] ?? null);
            $ally_request = ($allyrow['ally_request'] ?? null);

            if (($_POST['further'] ?? null) == $lang['Send']) {
                $this->alliances->setUserRequest((int) $user['id'], $allyid, mysql_escape_string(strip_tags(($_POST['text'] ?? null))), time());
                return $this->renderMessage($lang['apply_registered'], $lang['your_apply']);
            }

            $text_apply = ($ally_request) ? $ally_request : $lang['There_is_no_a_text_apply'];

            $parse = $lang;
            $parse['allyid'] = intval($_GET['allyid']);
            $parse['chars_count'] = strlen($text_apply);
            $parse['text_apply'] = $text_apply;
            $parse['Write_to_alliance'] = str_replace('%s', $ally_tag, $lang['Write_to_alliance']);

            $page = $this->parse($this->template('alliance_applyform'), $parse);

            return $this->renderPage($page, $parse['Write_to_alliance']);
        }

        if ($user['ally_request'] != 0) {
            $allyquery = $this->alliances->findTagById(intval($user['ally_request']));

            extract($allyquery);
            $ally_tag = ($allyquery['ally_tag'] ?? null);

            if (($_POST['bcancel'] ?? null)) {
                $this->alliances->clearUserRequest((int) $user['id']);

                $lang['request_text'] = str_replace('%s', $ally_tag, $lang['Canceled_a_request_text']);
                $lang['button_text'] = $lang['Ok'];
                $page = $this->parse($this->template('alliance_apply_waitform'), $lang);
            } else {
                $lang['request_text'] = str_replace('%s', $ally_tag, $lang['Waiting_a_request_text']);
                $lang['button_text'] = $lang['Delete_apply'];
                $page = $this->parse($this->template('alliance_apply_waitform'), $lang);
            }

            return $this->renderPage($page, $lang['your_apply']);
        }

        $page = $this->parse($this->template('alliance_defaultmenu'), $lang);

        return $this->renderPage($page, $lang['alliance']);
    }

    private function withAlliance($mode, $yes, $edit, $show, $sort, $sendmail, $t, $rank, $kick, $d): Response
    {
        $user = $this->user();
        $lang = $this->lang();
        $dpath = $this->skinPath();

        $ally = $this->alliances->findFullById((int) $user['ally_id']);

        $allianz_raenge = AllianceService::ranks($ally['ally_ranks'] ?? '');

        if (($allianz_raenge[$user['ally_rank_id'] - 1]['onlinestatus'] ?? null) == 1 || $ally['ally_owner'] == $user['id']) {
            $user_can_watch_memberlist_status = true;
        } else {
            $user_can_watch_memberlist_status = false;
        }

        if (($allianz_raenge[$user['ally_rank_id'] - 1]['memberlist'] ?? null) == 1 || $ally['ally_owner'] == $user['id']) {
            $user_can_watch_memberlist = true;
        } else {
            $user_can_watch_memberlist = false;
        }

        if (($allianz_raenge[$user['ally_rank_id'] - 1]['mails'] ?? null) == 1 || $ally['ally_owner'] == $user['id']) {
            $user_can_send_mails = true;
        } else {
            $user_can_send_mails = false;
        }

        if (($allianz_raenge[$user['ally_rank_id'] - 1]['kick'] ?? null) == 1 || $ally['ally_owner'] == $user['id']) {
            $user_can_kick = true;
        } else {
            $user_can_kick = false;
        }

        if (($allianz_raenge[$user['ally_rank_id'] - 1]['rechtehand'] ?? null) == 1 || $ally['ally_owner'] == $user['id']) {
            $user_can_edit_rights = true;
        } else {
            $user_can_edit_rights = false;
        }

        if (($allianz_raenge[$user['ally_rank_id'] - 1]['delete'] ?? null) == 1 || $ally['ally_owner'] == $user['id']) {
            $user_can_exit_alliance = true;
        } else {
            $user_can_exit_alliance = false;
        }

        if (($allianz_raenge[$user['ally_rank_id'] - 1]['bewerbungen'] ?? null) == 1 || $ally['ally_owner'] == $user['id']) {
            $user_bewerbungen_einsehen = true;
        } else {
            $user_bewerbungen_einsehen = false;
        }

        if (($allianz_raenge[$user['ally_rank_id'] - 1]['bewerbungenbearbeiten'] ?? null) == 1 || $ally['ally_owner'] == $user['id']) {
            $user_bewerbungen_bearbeiten = true;
        } else {
            $user_bewerbungen_bearbeiten = false;
        }

        if (($allianz_raenge[$user['ally_rank_id'] - 1]['administrieren'] ?? null) == 1 || $ally['ally_owner'] == $user['id']) {
            $user_admin = true;
        } else {
            $user_admin = false;
        }

        if (!$ally) {
            $this->alliances->resetMissingAlly((int) $user['id']);
            return $this->renderMessage($lang['ally_notexist'], $lang['your_alliance'], '/game/alliance');
        }

        if ($mode == 'exit') {
            if ($ally['ally_owner'] == $user['id']) {
                return $this->renderMessage($lang['Owner_cant_go_out'], $lang['Alliance']);
            }

            if (($_GET['yes'] ?? null) == 1) {
                $this->alliances->clearUserAlly((int) $user['id']);
                $lang['Go_out_welldone'] = str_replace("%s", (string) $ally['ally_name'], $lang['Go_out_welldone']);
                $page = MessageForm($lang['Go_out_welldone'], "", "/game/alliance", $lang['Ok']);
            } else {
                $lang['Want_go_out'] = str_replace("%s", (string) $ally['ally_name'], $lang['Want_go_out']);
                $page = MessageForm($lang['Want_go_out'], "", "/game/alliance?mode=exit&yes=1", "Oui");
            }
            return $this->renderPage($page, $lang['Alliance']);
        }

        if ($mode == 'memberslist') {
            $allianz_raenge = AllianceService::ranks($ally['ally_ranks'] ?? '');

            if ($ally['ally_owner'] != $user['id'] && !$user_can_watch_memberlist) {
                return $this->renderMessage($lang['Denied_access'], $lang['Members_list']);
            }

            if (($_GET['sort2'] ?? null)) {
                $sort1 = intval($_GET['sort1'] ?? 0);
                $sort2 = intval($_GET['sort2'] ?? 0);

                if ($sort1 == 1) {
                    $sort = " ORDER BY `username`";
                } elseif ($sort1 == 2) {
                    $sort = " ORDER BY `username`";
                } elseif ($sort1 == 4) {
                    $sort = " ORDER BY `ally_register_time`";
                } elseif ($sort1 == 5) {
                    $sort = " ORDER BY `onlinetime`";
                } else {
                    $sort = " ORDER BY `id`";
                }

                if ($sort2 == 1) {
                    $sort .= " DESC;";
                } elseif ($sort2 == 2) {
                    $sort .= " ASC;";
                }

                $rows = $this->alliances->listMembers((int) $user['ally_id'], $sort);
            } else {
                $sort2 = null;
                $rows = $this->alliances->listMembers((int) $user['ally_id']);
            }

            $i = 0;
            $template = $this->template('alliance_memberslist_row');
            $page_list = '';
            foreach ($rows as $u) {
                $UserPoints = $this->statsRow((int) $u['id']);

                $i++;
                $u['i'] = $i;

                if (!$user_can_watch_memberlist_status) {
                    $u["onlinetime"] = $this->onlineBadge('text-body-secondary', '-');
                } elseif ($u["onlinetime"] + 60 * 10 >= time()) {
                    $u["onlinetime"] = $this->onlineBadge('text-success', (string) $lang['On']);
                } elseif ($u["onlinetime"] + 60 * 20 >= time()) {
                    $u["onlinetime"] = $this->onlineBadge('text-warning', (string) $lang['15_min']);
                } else {
                    $u["onlinetime"] = $this->onlineBadge('text-danger', (string) $lang['Off']);
                }

                if ($ally['ally_owner'] == $u['id']) {
                    $u["ally_range"] = ($ally['ally_owner_range'] == '') ? $lang['Founder'] : $ally['ally_owner_range'];
                } elseif ($u['ally_rank_id'] != 0 && isset($allianz_raenge[$u['ally_rank_id'] - 1]['name'])) {
                    $u["ally_range"] = $allianz_raenge[$u['ally_rank_id'] - 1]['name'];
                } else {
                    $u["ally_range"] = $lang['Novate'];
                }

                $u["dpath"] = $dpath;
                $u['points'] = "" . pretty_number($UserPoints['total_points']) . "";

                if ($u['ally_register_time'] > 0) {
                    $u['ally_register_time'] = date("Y-m-d h:i:s", $u['ally_register_time']);
                } else {
                    $u['ally_register_time'] = "-";
                }

                $page_list .= $this->parse($template, $u);
            }

            if (($sort2 ?? null) == 1) {
                $s = 2;
            } elseif (($sort2 ?? null) == 2) {
                $s = 1;
            } else {
                $s = 1;
            }

            if ($i != $ally['ally_members']) {
                $this->alliances->updateMembersCount((int) $ally['id'], $i);
            }

            $parse = $lang;
            $parse['i'] = $i;
            $parse['s'] = $s;
            $parse['list'] = $page_list;

            $page = $this->parse($this->template('alliance_memberslist_table'), $parse);

            return $this->renderPage($page, $lang['Members_list']);
        }

        if ($mode == 'circular') {
            $allianz_raenge = AllianceService::ranks($ally['ally_ranks'] ?? '');

            if ($ally['ally_owner'] != $user['id'] && !$user_can_send_mails) {
                return $this->renderMessage($lang['Denied_access'], $lang['Send_circular_mail']);
            }

            if (($_GET['sendmail'] ?? null) == 1) {
                $_POST['r'] = intval($_POST['r'] ?? 0);
                $_POST['text'] = mysql_escape_string(strip_tags(($_POST['text'] ?? null)));

                $rows = $this->alliances->listMemberIds((int) $user['ally_id'], (int) $_POST['r']);

                $list = '';
                foreach ($rows as $u) {
                    $this->alliances->insertMessage(array(
                        'owner' => $u['id'],
                        'sender' => $user['id'],
                        'time' => time(),
                        'from' => $ally['ally_tag'],
                        'subject' => $user['username'],
                        'text' => $_POST['text'],
                    ));
                    $list .= "<br>{$u['username']} ";
                }

                $this->alliances->incrementMessages((int) $user['ally_id'], (int) $_POST['r']);

                $page = MessageForm($lang['Circular_sended'], "Les membres suivants ont re&ccedil;u un message :" . $list, "/game/alliance", $lang['Ok'], true);
                return $this->renderPage($page, $lang['Send_circular_mail']);
            }

            $lang['r_list'] = "<option value=\"0\">{$lang['All_players']}</option>";
            if ($allianz_raenge) {
                foreach ($allianz_raenge as $id => $array) {
                    $lang['r_list'] .= "<option value=\"" . ($id + 1) . "\">" . $array['name'] . "</option>";
                }
            }

            $page = $this->parse($this->template('alliance_circular'), $lang);

            return $this->renderPage($page, $lang['Send_circular_mail']);
        }

        if ($mode == 'admin' && $edit == 'rights') {
            $allianz_raenge = AllianceService::ranks($ally['ally_ranks'] ?? '');
            $ally_ranks = $allianz_raenge;

            if ($ally['ally_owner'] != $user['id'] && !$user_can_edit_rights) {
                return $this->renderMessage($lang['Denied_access'], $lang['Members_list']);
            } elseif (!empty(($_POST['newrangname'] ?? null))) {
                $name = mysql_escape_string(strip_tags($_POST['newrangname']));

                $allianz_raenge[] = array(
                    'name' => $name,
                    'mails' => 0,
                    'delete' => 0,
                    'kick' => 0,
                    'bewerbungen' => 0,
                    'administrieren' => 0,
                    'bewerbungenbearbeiten' => 0,
                    'memberlist' => 0,
                    'onlinestatus' => 0,
                    'rechtehand' => 0
                );

                $ranks = serialize($allianz_raenge);

                $this->alliances->updateRanks((int) $ally['id'], $ranks);

                return $this->redirect('/game/alliance?mode=admin&edit=rights');
            } elseif (($_POST['id'] ?? '') != '' && is_array(($_POST['id'] ?? null))) {
                $ally_ranks_new = array();

                foreach ($_POST['id'] as $id) {
                    $name = $allianz_raenge[$id]['name'];

                    $ally_ranks_new[$id]['name'] = $name;

                    foreach (array(0 => 'delete', 1 => 'kick', 2 => 'bewerbungen', 3 => 'memberlist', 4 => 'bewerbungenbearbeiten', 5 => 'administrieren', 6 => 'onlinestatus', 7 => 'mails', 8 => 'rechtehand') as $rIdx => $key) {
                        $checked = isset($_POST['u' . $id . 'r' . $rIdx]);
                        if ($rIdx == 1) {
                            $checked = $checked && $ally['ally_owner'] == $user['id'];
                        }
                        $ally_ranks_new[$id][$key] = $checked ? 1 : 0;
                    }
                }

                $ranks = serialize($ally_ranks_new);

                $this->alliances->updateRanks((int) $ally['id'], $ranks);

                return $this->redirect('/game/alliance?mode=admin&edit=rights');
            } elseif (isset($d) && isset($ally_ranks[$d])) {
                unset($ally_ranks[$d]);
                $ally['ally_rank'] = serialize($ally_ranks);

                $this->alliances->updateRanks((int) $ally['id'], $ally['ally_rank']);

                return $this->redirect('/game/alliance?mode=admin&edit=rights');
            }

            if (count($ally_ranks) == 0 || $ally_ranks == '') {
                $list = $this->partial('alliance_admin_laws_empty', array(
                    'ally_range_empty' => (string) $lang['There_is_not_range'],
                ));
            } else {
                $list = $this->parse($this->template('alliance_admin_laws_head'), $lang);
                $template = $this->template('alliance_admin_laws_row');

                $rights = array(
                    1 => array('kick', $lang['Expel_users']),
                    2 => array('bewerbungen', $lang['See_the_requests']),
                    3 => array('memberlist', $lang['See_the_list_members']),
                    4 => array('bewerbungenbearbeiten', $lang['Check_the_requests']),
                    5 => array('administrieren', $lang['Alliance_admin']),
                    6 => array('onlinestatus', $lang['See_the_online_list_member']),
                    7 => array('mails', $lang['Make_a_circular_message']),
                    8 => array('rechtehand', $lang['Left_hand_text']),
                );

                foreach ($ally_ranks as $a => $b) {
                    $isOwner = ($ally['ally_owner'] == $user['id']);

                    $lang['id'] = $a;
                    $lang['r0'] = $b['name'];
                    $lang['a'] = $a;
                    $lang['delete'] = $isOwner
                        ? '<a class="btn btn-sm btn-outline-danger" href="/game/alliance?mode=admin&amp;edit=rights&amp;d=' . $a . '" title="' . $lang['Delete_range'] . '"><i class="bi bi-trash" aria-hidden="true"></i></a>'
                        : '';

                    $lang['r1'] = $isOwner
                        ? $this->rankCheckbox((int) $a, 0, $b['delete'] == 1, $lang['Alliance_dissolve'])
                        : '<span class="text-body-secondary">&ndash;</span>';

                    foreach ($rights as $idx => $def) {
                        $lang['r' . ($idx + 1)] = $this->rankCheckbox((int) $a, $idx, $b[$def[0]] == 1, $def[1]);
                    }

                    $list .= $this->parse($template, $lang);
                }

                $list .= $this->parse($this->template('alliance_admin_laws_feet'), $lang);
            }

            $lang['list'] = $list;
            $lang['dpath'] = $dpath;
            $page = $this->parse($this->template('alliance_admin_laws'), $lang);

            return $this->renderPage($page, $lang['Law_settings']);
        }

        if ($mode == 'admin' && $edit == 'ally') {
            if ($t != 1 && $t != 2 && $t != 3) {
                $t = 1;
            }

            if (($_POST['options'] ?? null)) {
                $ally['ally_owner_range'] = mysql_escape_string(htmlspecialchars(strip_tags(($_POST['owner_range'] ?? null))));
                $ally['ally_web'] = mysql_escape_string(htmlspecialchars(strip_tags(($_POST['web'] ?? null))));
                $ally['ally_image'] = mysql_escape_string(htmlspecialchars(strip_tags(($_POST['image'] ?? null))));
                $ally['ally_request_notallow'] = intval(($_POST['request_notallow'] ?? null));

                if ($ally['ally_request_notallow'] != 0 && $ally['ally_request_notallow'] != 1) {
                    return $this->renderMessage("Aller dans \"Candidature\" et choisir une option dans le formulaire !", "Erreur");
                }

                $this->alliances->updateSettings((int) $ally['id'], $ally['ally_owner_range'], $ally['ally_web'], $ally['ally_image'], (int) $ally['ally_request_notallow']);
            } elseif (($_POST['t'] ?? null)) {
                if ($t == 3) {
                    $ally['ally_request'] = mysql_escape_string(strip_tags(($_POST['text'] ?? null)));
                    $this->alliances->updateText((int) $ally['id'], 'ally_request', $ally['ally_request']);
                } elseif ($t == 2) {
                    $ally['ally_text'] = mysql_escape_string(strip_tags(($_POST['text'] ?? null)));
                    $this->alliances->updateText((int) $ally['id'], 'ally_text', $ally['ally_text']);
                } else {
                    $ally['ally_description'] = mysql_escape_string(strip_tags(stripslashes(($_POST['text'] ?? null))));
                    $this->alliances->updateText((int) $ally['id'], 'ally_description', $ally['ally_description']);
                }
            }
            $lang['dpath'] = $dpath;

            if ($t == 3) {
                $lang['request_type'] = $lang['Show_of_request_text'];
            } elseif ($t == 2) {
                $lang['request_type'] = $lang['Internal_text_of_alliance'];
            } else {
                $lang['request_type'] = $lang['Public_text_of_alliance'];
            }

            if ($t == 2) {
                $lang['text'] = $ally['ally_text'];
                $lang['Texts'] = "Interner Text";
                $lang['Show_of_request_text'] = "Internet Allianz Text";
            } else {
                $lang['text'] = $ally['ally_description'];
            }

            $lang['t'] = $t;

            $lang['ally_web'] = $ally['ally_web'];
            $lang['ally_image'] = $ally['ally_image'];
            $lang['ally_request_notallow_0'] = (($ally['ally_request_notallow'] == 1) ? ' SELECTED' : '');
            $lang['ally_request_notallow_1'] = (($ally['ally_request_notallow'] == 0) ? ' SELECTED' : '');
            $lang['ally_owner_range'] = $ally['ally_owner_range'];
            $lang['Transfer_alliance'] = MessageForm("Abandonner / Transf&eacute;rer L'alliance", "", "?mode=admin&edit=give", $lang['Continue']);
            $lang['Disolve_alliance'] = MessageForm("Dissoudre L'alliance", "", "?mode=admin&edit=exit", $lang['Continue']);

            $page = $this->parse($this->template('alliance_admin'), $lang);

            return $this->renderPage($page, $lang['Alliance_admin']);
        }

        if ($mode == 'admin' && $edit == 'give') {
            if (($_POST["id"] ?? null)) {
                $this->alliances->transferOwnership((int) $user['ally_id'], intval(($_POST["id"] ?? 0)));

                return $this->redirect('/game/alliance?mode=admin&edit=ally');
            }

            $options = '';
            foreach ($this->alliances->listMembers((int) $user['ally_id']) as $data) {
                $options .= $this->partial('alliance_admin_give_option', array(
                    'member_id' => (int) $data['id'],
                    'member_name' => htmlspecialchars((string) $data['username']),
                ));
            }

            $page = $this->partial('alliance_admin_give', array('ally_give_options' => $options));

            return $this->renderPage($page, $lang['Alliance_admin']);
        }

        if ($mode == 'admin' && $edit == 'members') {
            if ($ally['ally_owner'] != $user['id'] && !$user_can_kick) {
                return $this->renderMessage($lang['Denied_access'], $lang['Members_list']);
            }

            if (isset($kick)) {
                if ($ally['ally_owner'] != $user['id'] && !$user_can_kick) {
                    return $this->renderMessage($lang['Denied_access'], $lang['Members_list']);
                }

                $u = $this->alliances->findUserByIdRaw($kick);

                if ($u['ally_id'] == $ally['id'] && $u['id'] != $ally['ally_owner']) {
                    $this->alliances->clearMemberAlly((int) $u['id']);
                }
            } elseif (isset($_POST['newrang'])) {
                $q = $this->alliances->findUserByIdRaw($rank);

                if ((isset($allianz_raenge[($_POST['newrang'] ?? null) - 1]) || ($_POST['newrang'] ?? null) == 0) && ($q['id'] ?? null) != $ally['ally_owner']) {
                    $this->alliances->setUserRank(intval($rank), mysql_escape_string(strip_tags($_POST['newrang'])));
                }
            }

            $template = $this->template('alliance_admin_members_row');
            $f_template = $this->template('alliance_admin_members_function');

            if (($_GET['sort2'] ?? null)) {
                $sort1 = intval($_GET['sort1'] ?? 0);
                $sort2 = intval($_GET['sort2'] ?? 0);

                if ($sort1 == 1) {
                    $sort = " ORDER BY `username`";
                } elseif ($sort1 == 2) {
                    $sort = " ORDER BY `username`";
                } elseif ($sort1 == 4) {
                    $sort = " ORDER BY `ally_register_time`";
                } elseif ($sort1 == 5) {
                    $sort = " ORDER BY `onlinetime`";
                } else {
                    $sort = " ORDER BY `id`";
                }

                if ($sort2 == 1) {
                    $sort .= " DESC;";
                } elseif ($sort2 == 2) {
                    $sort .= " ASC;";
                }

                $rows = $this->alliances->listMembers((int) $user['ally_id'], $sort);
            } else {
                $sort2 = null;
                $rows = $this->alliances->listMembers((int) $user['ally_id']);
            }

            $i = 0;
            $page_list = '';
            $lang['memberzahl'] = count($rows);

            foreach ($rows as $u) {
                $UserPoints = $this->statsRow((int) $u['id']);
                $i++;
                $u['i'] = $i;
                $u['points'] = "" . pretty_number($UserPoints['total_points']) . "";
                $days = (int) floor(round(time() - $u["onlinetime"]) / 3600 % 24);
                $u["onlinetime"] = $this->onlineBadge('text-body-secondary', str_replace("%s", (string) $days, "%s d"));

                if ($ally['ally_owner'] == $u['id']) {
                    $ally_range = ($ally['ally_owner_range'] == '') ? $lang['Founder'] : $ally['ally_owner_range'];
                } elseif ($u['ally_rank_id'] == 0 || !isset($allianz_raenge[$u['ally_rank_id'] - 1]['name'])) {
                    $ally_range = $lang['Novate'];
                } else {
                    $ally_range = $allianz_raenge[$u['ally_rank_id'] - 1]['name'];
                }

                if ($ally['ally_owner'] == $u['id'] || $rank == $u['id']) {
                    $u["functions"] = '';
                } elseif (($allianz_raenge[$user['ally_rank_id'] - 1][5] ?? null) == 1 || $ally['ally_owner'] == $user['id']) {
                    $f['dpath'] = $dpath;
                    $f['Expel_user'] = $lang['Expel_user'];
                    $f['Set_range'] = $lang['Set_range'];
                    $f['You_are_sure_want_kick_to'] = htmlspecialchars(str_replace("%s", $u['username'], $lang['You_are_sure_want_kick_to']), ENT_QUOTES);
                    $f['id'] = $u['id'];
                    $u["functions"] = $this->parse($f_template, $f);
                } else {
                    $u["functions"] = '';
                }
                $u["dpath"] = $dpath;

                if ($rank != $u['id']) {
                    $u['ally_range'] = $ally_range;
                } else {
                    $u['ally_range'] = '';
                }
                $u['ally_register_time'] = date("Y-m-d h:i:s", $u['ally_register_time']);
                $page_list .= $this->parse($template, $u);

                if ($rank == $u['id']) {
                    $r['Rank_for'] = str_replace("%s", $u['username'], $lang['Rank_for']);
                    $r['options'] = '';
                    $r['options'] .= "<option value=\"0\">{$lang['Novate']}</option>";

                    foreach ($allianz_raenge as $a => $b) {
                        $r['options'] .= "<option value=\"" . ($a + 1) . "\"";
                        if ($u['ally_rank_id'] - 1 == $a) {
                            $r['options'] .= ' selected=selected';
                        }
                        $r['options'] .= ">{$b['name']}</option>";
                    }
                    $r['id'] = $u['id'];
                    $r['Save'] = $lang['Save'];
                    $page_list .= $this->parse($this->template('alliance_admin_members_row_edit'), $r);
                }
            }

            if (($sort2 ?? null) == 1) {
                $s = 2;
            } elseif (($sort2 ?? null) == 2) {
                $s = 1;
            } else {
                $s = 1;
            }

            if ($i != $ally['ally_members']) {
                $this->alliances->updateMembersCount((int) $ally['id'], $i);
            }

            $lang['memberslist'] = $page_list;
            $lang['s'] = $s;
            $page = $this->parse($this->template('alliance_admin_members_table'), $lang);

            return $this->renderPage($page, $lang['Members_administrate']);
        }

        if ($mode == 'admin' && $edit == 'requests') {
            if ($ally['ally_owner'] != $user['id'] && !$user_bewerbungen_bearbeiten) {
                return $this->renderMessage($lang['Denied_access'], $lang['Check_the_requests']);
            }

            $this->alliances->setRequestContext((int) $user['id'], (string) $ally['ally_tag'], (string) $ally['ally_name']);

            if (($_POST['action'] ?? null) == "Accepter") {
                $_POST['text'] = mysql_escape_string(strip_tags(($_POST['text'] ?? null)));

                $this->alliances->acceptRequest((int) $ally['id'], (int) $ally['id'], (string) $ally['ally_name'], $show, $_POST['text']);

                return $this->redirect('/game/alliance?mode=admin&edit=requests');
            } elseif (($_POST['action'] ?? null) == "Refuser" && ($_POST['action'] ?? null) != '') {
                $_POST['text'] = mysql_escape_string(strip_tags(($_POST['text'] ?? null)));

                $this->alliances->refuseRequest((int) $ally['id'], $show, $_POST['text']);

                return $this->redirect('/game/alliance?mode=admin&edit=requests');
            }

            $row = $this->template('alliance_admin_request_row');
            $i = 0;
            $parse = $lang;
            $parse['list'] = '';
            $rows = $this->alliances->findRequests((int) $ally['id']);
            $s = array();
            foreach ($rows as $r) {
                if (isset($show) && $r['id'] == $show) {
                    $s['username'] = $r['username'];
                    $s['ally_request_text'] = nl2br($r['ally_request_text']);
                    $s['id'] = $r['id'];
                }

                $r['time'] = date("Y-m-d h:i:s", $r['ally_register_time']);
                $parse['list'] .= $this->parse($row, $r);
                $i++;
            }
            if ($parse['list'] == '') {
                $parse['list'] = $this->partial('alliance_admin_request_empty', array(
                    'request_empty' => 'Il ne reste plus aucune candidature',
                ));
            }

            if (isset($show) && $show != 0 && $parse['list'] != '') {
                $s['Request_from'] = str_replace('%s', ($s['username'] ?? null), $lang['Request_from']);
                $parse['request'] = $this->parse($this->template('alliance_admin_request_form'), $s);
                $parse['request'] = $this->parse($parse['request'], $lang);
            } else {
                $parse['request'] = '';
            }

            $parse['ally_tag'] = $ally['ally_tag'];
            $parse['Back'] = $lang['Back'];
            $parse['There_is_hanging_request'] = str_replace('%n', $i, $lang['There_is_hanging_request']);

            $page = $this->parse($this->template('alliance_admin_request_table'), $parse);

            return $this->renderPage($page, $lang['Check_the_requests']);
        }

        if ($mode == 'admin' && $edit == 'name') {
            if ($ally['ally_owner'] != $user['id'] && !$user_admin) {
                return $this->renderMessage($lang['Denied_access'], $lang['Members_list']);
            }

            if (($_POST['newname'] ?? null)) {
                $ally['ally_name'] = mysql_escape_string(strip_tags($_POST['newname']));
                $this->alliances->updateName((int) $user['ally_id'], $ally['ally_name']);
            }

            $parse['question'] = str_replace('%s', $ally['ally_name'], $lang['How_you_will_call_the_alliance_in_the_future']);
            $parse['New_name'] = $lang['New_name'];
            $parse['Change'] = $lang['Change'];
            $parse['name'] = 'newname';
            $parse['Return_to_overview'] = $lang['Return_to_overview'];
            $page = $this->parse($this->template('alliance_admin_rename'), $parse);

            return $this->renderPage($page, $lang['Alliance_admin']);
        }

        if ($mode == 'admin' && $edit == 'tag') {
            if ($ally['ally_owner'] != $user['id'] && !$user_admin) {
                return $this->renderMessage($lang['Denied_access'], $lang['Members_list']);
            }

            if (($_POST['newtag'] ?? null)) {
                $ally['ally_tag'] = mysql_escape_string(strip_tags($_POST['newtag']));
                $this->alliances->updateTag((int) $user['ally_id'], $ally['ally_tag']);
            }

            $parse['question'] = str_replace('%s', $ally['ally_tag'], $lang['How_you_will_call_the_alliance_in_the_future']);
            $parse['New_name'] = $lang['New_name'];
            $parse['Change'] = $lang['Change'];
            $parse['name'] = 'newtag';
            $parse['Return_to_overview'] = $lang['Return_to_overview'];
            $page = $this->parse($this->template('alliance_admin_rename'), $parse);

            return $this->renderPage($page, $lang['Alliance_admin']);
        }

        if ($mode == 'admin' && $edit == 'exit') {
            if ($ally['ally_owner'] != $user['id'] && !$user_can_exit_alliance) {
                return $this->renderMessage($lang['Denied_access'], $lang['Members_list']);
            }

            $this->alliances->clearUserAlly((int) $user['id']);
            $this->alliances->delete((int) $ally['id']);

            return $this->redirect('/game/alliance');
        }

        $allyRanks = null;
        if ($ally['ally_owner'] != $user['id']) {
            $allyRanks = AllianceService::ranks($ally['ally_ranks'] ?? '');
        }

        if ($ally['ally_owner'] == $user['id']) {
            $range = ($ally['ally_owner_range'] != '') ? $ally['ally_owner_range'] : $lang['Founder'];
        } elseif ($user['ally_rank_id'] != 0 && isset($allyRanks[$user['ally_rank_id'] - 1]['name'])) {
            $range = $allyRanks[$user['ally_rank_id'] - 1]['name'];
        } else {
            $range = $lang['member'];
        }

        if ($ally['ally_owner'] == $user['id'] || ($allyRanks[$user['ally_rank_id'] - 1]['memberlist'] ?? 0) != 0) {
            $lang['members_list'] = " <a href=\"?mode=memberslist\">({$lang['Members_list']})</a>";
        } else {
            $lang['members_list'] = '';
        }

        if ($ally['ally_owner'] == $user['id'] || ($allyRanks[$user['ally_rank_id'] - 1]['administrieren'] ?? 0) != 0) {
            $lang['alliance_admin'] = " <a href=\"?mode=admin&amp;edit=ally\">({$lang['Alliance_admin']})</a>";
        } else {
            $lang['alliance_admin'] = '';
        }

        if ($ally['ally_owner'] == $user['id'] || ($allyRanks[$user['ally_rank_id'] - 1]['mails'] ?? 0) != 0) {
            $lang['send_circular_mail'] = "<tr><td class=\"xnova-label\">{$lang['Circular_message']}</td><td><a href=\"?mode=circular\">{$lang['Send_circular_mail']}</a></td></tr>";
        } else {
            $lang['send_circular_mail'] = '';
        }

        $lang['requests'] = '';
        $request_count = $this->alliances->countRequests((int) $ally['id']);
        if ($request_count != 0) {
            if ($ally['ally_owner'] == $user['id'] || ($allyRanks[$user['ally_rank_id'] - 1]['bewerbungen'] ?? 0) != 0) {
                $lang['requests'] = "<tr><td class=\"xnova-label\">{$lang['Requests']}</td><td><a href=\"/game/alliance?mode=admin&amp;edit=requests\">{$request_count} {$lang['XRequests']}</a></td></tr>";
            }
        }

        $lang['ally_owner'] = ($ally['ally_owner'] != $user['id'])
            ? MessageForm($lang['Exit_of_this_alliance'], "", "?mode=exit", $lang['Continue'])
            : '';

        $lang['ally_image'] = ($ally['ally_image'] != '')
            ? '<img class="xnova-ally-image" src="' . $ally['ally_image'] . '" alt="' . htmlspecialchars((string) $ally['ally_tag']) . '">'
            : '';

        $lang['ally_web'] = ($ally['ally_web'] != '')
            ? "<tr><td class=\"xnova-label\">{$lang['Main_Page']}</td><td><a href=\"" . $ally['ally_web'] . "\" target=\"_blank\" rel=\"noopener\">" . $ally['ally_web'] . "</a></td></tr>"
            : '';

        $lang['range'] = $range;

        $lang['ally_description'] = nl2br($this->bbcodeToHtml((string) $ally['ally_description']));
        $lang['ally_text'] = nl2br($this->bbcodeToHtml((string) $ally['ally_text']));

        $lang['ally_tag'] = $ally['ally_tag'];
        $lang['ally_members'] = $ally['ally_members'];
        $lang['ally_name'] = $ally['ally_name'];

        $page = $this->parse($this->template('alliance_frontpage'), $lang);

        return $this->renderPage($page, $lang['your_alliance']);
    }

    private function statsRow(int $ownerId): array|false
    {
        return (new \App\Repositories\StatsRepository())->findUserStatRow($ownerId);
    }

    /**
     * Case a cocher de droit d'alliance (name = u<rang>r<droit>, attendu par le POST).
     */
    private function rankCheckbox(int $rankId, int $rightIndex, bool $checked, string $label): string
    {
        $id = 'u' . $rankId . 'r' . $rightIndex;

        return '<input class="form-check-input" type="checkbox" id="' . $id . '" name="' . $id . '"'
            . ($checked ? ' checked' : '')
            . ' aria-label="' . htmlspecialchars($label, ENT_QUOTES) . '">';
    }

    /**
     * Convertit le mini-BBCode des textes d'alliance ([fc]..[/fc]..[/f] et [img]..[/img])
     * en HTML compatible avec le theme courant.
     */
    private function bbcodeToHtml(string $text): string
    {
        $patterns = array(
            "#\[fc\]([a-z0-9\#\ \[\]]+)\[/fc\](.*?)\[/f\]#Ssi",
            '#\[img\](.*?)\[/img\]#Smi',
            "#\[fc\]([a-z0-9\#\ \[\]]+)\[/fc\]#Ssi",
            "#\[/f\]#Ssi",
        );

        $replacements = array(
            '<span style="color:\1">\2</span>',
            '<img class="xnova-ally-inline-image" src="\1" alt="">',
            '<span style="color:\1">',
            '</span>',
        );

        return preg_replace($patterns, $replacements, $text);
    }
}
