<?php

// Einrichtung:
// String-Variable mit Profil "~HTML-Box" anlegen, VariablenID weitern unter eintragen
//
// Konfiguration (aller Kameras) ergänzen
// - dieses Script als 'new_event_script' ("neue Ereignisse ... Script") eintragen
// - das Script '.../docs/processStremURL.php' als 'webhook_script' ("Webhook ... Script") eintragen
//
// die Einstellungen im Script nach Belieben anpassen

// HTML-Box
$varID = xxxx;

/* Einstellungen ************************************/

// max. Benachrichtigungen
$max_lines = 20;

// Angabe zum Video-Player passen zu processStreamURL.php als 'webhook_script'!

// Größe des Video-Fensters
$video_iframe_width = 630; // 1260;
$video_iframe_height = 360; // 720;

// Größe des Video-Players
$video_player_width = ''; // automatisch
$video_player_height = ($video_iframe_height - 20);

// Video-Player unter oder über der Liste?
$video_bottom = false;

// Größe der Vignetten
$vignette_width = 60;
$vignette_height = 60;

// Größe der Icons
$icon_width = 40;
$icon_height = 40;

/****************************************************/

$scriptName = IPS_GetName($_IPS['SELF']) . '(' . $_IPS['SELF'] . ')';
IPS_LogMessage($scriptName, '_IPS=' . print_r($_IPS, true));

$instID = $_IPS['InstanceID'];

$base_url = NetatmoSecurity_GetServerUrl($instID);
IPS_LogMessage($scriptName, 'base_url=' . $base_url);

/* Löschen unerwünschter Events *********************/

/*
$events = json_decode($_IPS['new_events'], true);
foreach ($events as $event) {
    $event_id = $event['id'];
    $event_types = [];
    if (isset($event['subevents'])) {
        $subevents = $event['subevents'];
        foreach ($subevents as $subevent) {
            $event_type = $subevent['event_type'];
            if (!in_array($event_type, $event_types)) {
                $event_types[] = $event_type;
            }
        }
    }
    $event_type = implode($event_types, '+');
    // Keine Fahrzeuge im Garten melden
    if ($event_type == 'vehicle' && $instID == xx) {
        $r = NetatmoSecurity_DeleteEvent($instID, $event_id);
        IPS_LogMessage($scriptName, 'delete event ' . $event_id . ' => ' . ($r ? 'ok' : 'fail'));
    }
}
*/

/****************************************************/

// Auslesen der Timelines aller aktiven Kameras
$timeline = '';
$instIDs = IPS_GetInstanceListByModuleID('{06D589CF-7789-44B1-A0EC-6F51428352E6}');
foreach ($instIDs as $instID) {
    if (IPS_GetInstance($instID)['InstanceStatus'] != 102) {
        continue;
    }
    $data = NetatmoSecurity_GetTimeline($instID, false);
    $timeline = NetatmoSecurity_MergeTimeline($instID, $timeline, $data, $instID);
}
$timeline = json_decode($timeline, true);

/****************************************************/

$html = '';

$html .= '<script type="text/javascript">' . PHP_EOL;
$html .= 'function set_video(url) {' . PHP_EOL;
$html .= '   document.getElementById("event_video").src = url;' . PHP_EOL;
$html .= '   console.log("url=" + url);' . PHP_EOL;
$html .= '}' . PHP_EOL;
$html .= '</script>' . PHP_EOL;

$html .= '<style>' . PHP_EOL;
$html .= 'th, td { padding: 2px 10px; text-align: left; }' . PHP_EOL;
$html .= '</style>' . PHP_EOL;

if ($video_bottom == false) {
    $html .= '<iframe id="event_video" ';
    $html .= 'width="' . $video_iframe_width . '" height="' . $video_iframe_height . '" ';
    $html .= 'frameborder="0" src="">';
    $html .= '</iframe>' . PHP_EOL;
}

$html .= '<table>' . PHP_EOL;
$html .= '<tr>' . PHP_EOL;
$html .= '<th>Zeitpunkt</th>' . PHP_EOL;
$html .= '<th>Typ</th>' . PHP_EOL;
$html .= '<th>Meldung</th>' . PHP_EOL;
$html .= '</tr>' . PHP_EOL;

$cur_date = 0;

$n_timeline = count($timeline);
for ($n = 0, $i = $n_timeline - 1; $n < $max_lines && $i >= 0; $i--) {
    $item = $timeline[$i];

    $event_id = $item['id'];
    $tstamp = $item['tstamp'];

    if (isset($item['push_type']) && isset($item['event_type'])) {
        // 'Bewegung erkannt' aber ohne das ein Ereignis draus wird
        if ($item['event_type'] == 'movement' && $event_id == '') {
            continue;
        }
    }

    $dt = new DateTime(date('d.m.Y 00:00:00', $tstamp));
    $ts = $dt->format('U');
    if ($cur_date != $ts) {
        if ($cur_date != 0) {
            $html .= '<tr>' . PHP_EOL;
            $html .= '<td>&nbsp;</td>' . PHP_EOL;
            $html .= '<td colspan=2>' . strftime('%A, %e. %B', $tstamp) . '</td>' . PHP_EOL;
            $html .= '</tr>' . PHP_EOL;
        }
        $cur_date = $ts;
    }

    $html .= '<tr>' . PHP_EOL;
    $html .= '<td>' . date('H:i', $tstamp) . '</td>' . PHP_EOL;

    $instID = $item['tag'];
    $hook = IPS_GetProperty($instID, 'hook');
    $img_path = $hook . '/imgs/';
    $hook_url = $base_url . $hook;

    $instName = IPS_GetName($instID);

    $message = isset($item['message']) ? $item['message'] : '';
    if (isset($item['push_type'])) {
        // Benachrichtigungen

        $event_type = $item['event_type'];
        $html .= '<td>';
        $event_type_icon = NetatmoSecurity_EventType2Icon($instID, $event_type, true);
        $event_type_text = NetatmoSecurity_EventType2Text($instID, $event_type);
        if ($event_type_icon != '') {
            $html .= '<img src=' . $event_type_icon . ' width="' . $icon_width . '" height="' . $icon_height . '" title="' . $event_type_text . '">';
        } elseif ($message != '') {
            $html .= $message;
        } elseif ($event_type_text != '') {
            $html .= $event_type_text;
        } else {
            $html .= '&nbsp;';
        }
        $html .= '</td>' . PHP_EOL;

        $hasMsg = false;
        if ($event_id != '') {
            $vignette_url = NetatmoSecurity_GetVignetteUrl4Notification($instID, $event_id, false);
            if ($vignette_url == false) {
                $vignette_url = NetatmoSecurity_GetSnapshotUrl4Notification($instID, $event_id, false);
            }
            if ($vignette_url != false) {
                $snapshot_url = $hook_url . '/snapshot?notification_id=' . $event_id . '&result=custom';
                $snapshot_url .= '&refresh=0';
                $snapshot_url .= '&width=' . $video_player_width;
                $snapshot_url .= '&height=' . $video_player_height;
                $html .= '<td onclick="set_video(\'' . $snapshot_url . '\')">' . PHP_EOL;
                $html .= '<img src=' . $vignette_url . ' width="' . $vignette_width . '" height="' . $vignette_height . '">';
                $hasMsg = true;
            }
        }
        if (!$hasMsg) {
            $html .= '<td>' . $instName . ': ' . $message . '</td>' . PHP_EOL;
        }
    } else {
        // Ereignisse

        $html .= '<td>';
        if (isset($item['event_types'])) {
            $event_types = $item['event_types'];
            foreach ($event_types as $event_type) {
                $event_type_icon = NetatmoSecurity_EventType2Icon($instID, $event_type, true);
                if ($event_type_icon != '') {
                    $event_type_text = NetatmoSecurity_EventType2Text($instID, $event_type);
                    $html .= '<img src=' . $event_type_icon . ' width="' . $icon_width . '" height="' . $icon_height . '" title="' . $event_type_text . '">';
                    $html .= '&nbsp;';
                }
            }
        } else {
            $html .= '&nbsp;';
        }
        $html .= '</td>' . PHP_EOL;
        if (isset($item['video_id'])) {
            $video_status = isset($item['video_status']) ? $item['video_status'] : 'available';
            switch ($video_status) {
                case 'recording':
                case 'available':
                    $hasMsg = false;
                    $video_url = $hook_url . '/video?event_id=' . $event_id . '&result=custom';
                    $video_url .= '&refresh=0';
                    $video_url .= '&width=' . $video_player_width;
                    $video_url .= '&height=' . $video_player_height;
                    $html .= '<td onclick="set_video(\'' . $video_url . '\')">' . PHP_EOL;
                    if (isset($item['subevents'])) {
                        $subevents = $item['subevents'];
                        foreach ($subevents as $subevent) {
                            $subevent_id = $subevent['id'];
                            $vignette_url = NetatmoSecurity_GetVignetteUrl4Subevent($instID, $subevent_id, false);
                            if ($vignette_url != false) {
                                if (!$hasMsg) {
                                    $html .= '<img src=' . $vignette_url . ' width="' . $vignette_width . '" height="' . $vignette_height . '">';
                                }
                                $html .= '&nbsp;&nbsp;';
                                $hasMsg = true;
                            }
                        }
                    }
                    if (!$hasMsg && isset($item['snapshot'])) {
                        $vignette_url = NetatmoSecurity_GetSnapshotUrl4Event($instID, $event_id, false);
                        if ($vignette_url != false) {
                            if (!$hasMsg) {
                                $html .= '<img src=' . $vignette_url . ' width="' . $vignette_width . '" height="' . $vignette_height . '">';
                            }
                            $html .= '&nbsp;&nbsp;';
                            $hasMsg = true;
                        }
                    }
                    switch ($video_status) {
                        case 'recording':
                            $html .= '&nbsp;&nbsp;Aufzeichnung läuft!';
                            break;
                        default:
                            if (!$hasMsg) {
                                $html .= $message;
                            }
                            break;
                    }
                    $html .= '<td>' . PHP_EOL;
                    break;
                default:
                    $html .= '<td>';
                    if ($message != '') {
                        $html .= $instName . ': ' . $message;
                    }
                    $html .= '</td>' . PHP_EOL;
                    break;
            }
        } elseif (isset($item['snapshot'])) {
            $snapshot_url = $hook_url . '/snapshot?event_id=' . $id . '&result=custom';
            $snapshot_url .= '&refresh=0';
            $snapshot_url .= '&width=' . $video_player_width;
            $snapshot_url .= '&height=' . $video_player_height;
            $html .= '<td onclick="set_video(\'' . $snapshot_url . '\')">' . PHP_EOL;

            $vignette_url = NetatmoSecurity_GetVignetteUrl4Event($instID, $event_id, false);
            if ($vignette_url != false) {
                $vignette_url = NetatmoSecurity_GetSnapshotUrl4Event($instID, $event_id, false);
            }
            if ($vignette_url != false) {
                $html .= '<img src=' . $vignette_url . ' width="' . $vignette_width . '" height="' . $vignette_height . '">';
            } else {
                if ($message != '') {
                    $html .= $message;
                }
            }
            $html .= '<td>' . PHP_EOL;
        } else {
            $html .= '<td>';
            if ($message != '') {
                $html .= $message;
            }
            $html .= '</td>' . PHP_EOL;
        }
    }

    $html .= '</tr>' . PHP_EOL;

    $n++;
}
$html .= '</table>' . PHP_EOL;

if ($video_bottom) {
    $html .= '<iframe id="event_video" ';
    $html .= 'width="' . $video_iframe_width . '" height="' . $video_iframe_height . '" ';
    $html .= 'frameborder="0" src="">';
    $html .= '</iframe>' . PHP_EOL;
}

SetValueString($varID, $html);
