<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen
require_once __DIR__ . '/../libs/library.php'; // modul-bezogene Funktionen

define('EVENTS_AS_MEDIA', true);

class NetatmoSecurityCamera extends IPSModule
{
    use NetatmoSecurityCommon;
    use NetatmoSecurityLibrary;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('product_type', '');
        $this->RegisterPropertyString('product_id', '');
        $this->RegisterPropertyString('home_id', '');

        $this->RegisterPropertyBoolean('with_last_contact', false);
        $this->RegisterPropertyBoolean('with_last_event', false);
        $this->RegisterPropertyBoolean('with_last_notification', false);

        $this->RegisterPropertyString('hook', '');

        $this->RegisterPropertyInteger('event_max_age', '14');
        $this->RegisterPropertyInteger('notification_max_age', '2');
        if (EVENTS_AS_MEDIA) {
            $this->RegisterPropertyBoolean('events_cached', false);
            $this->RegisterPropertyBoolean('notifications_cached', false);
        }

        $this->RegisterPropertyString('ftp_path', '');
        $this->RegisterPropertyInteger('ftp_max_age', '14');

        $associations = [];
        $associations[] = ['Wert' => CAMERA_STATUS_UNDEFINED, 'Name' => $this->Translate('unknown'), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => CAMERA_STATUS_OFF, 'Name' => $this->Translate('off'), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => CAMERA_STATUS_ON, 'Name' => $this->Translate('on'), 'Farbe' => -1];
        $associations[] = ['Wert' => CAMERA_STATUS_DISCONNECTED, 'Name' => $this->Translate('disconnected'), 'Farbe' => 0xEE0000];
        $this->CreateVarProfile('NetatmoSecurity.CameraStatus', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => CAMERA_STATUS_OFF, 'Name' => $this->Translate('off'), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => CAMERA_STATUS_ON, 'Name' => $this->Translate('on'), 'Farbe' => -1];
        $this->CreateVarProfile('NetatmoSecurity.CameraAction', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => LIGHT_STATUS_UNDEFINED, 'Name' => $this->Translate('unknown'), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => LIGHT_STATUS_OFF, 'Name' => $this->Translate('off'), 'Farbe' => -1];
        $associations[] = ['Wert' => LIGHT_STATUS_ON, 'Name' => $this->Translate('on'), 'Farbe' => -1];
        $associations[] = ['Wert' => LIGHT_STATUS_AUTO, 'Name' => $this->Translate('auto'), 'Farbe' => -1];
        $this->CreateVarProfile('NetatmoSecurity.LightModeStatus', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => LIGHT_STATUS_OFF, 'Name' => $this->Translate('off'), 'Farbe' => -1];
        $associations[] = ['Wert' => LIGHT_STATUS_ON, 'Name' => $this->Translate('on'), 'Farbe' => -1];
        $associations[] = ['Wert' => LIGHT_STATUS_AUTO, 'Name' => $this->Translate('auto'), 'Farbe' => -1];
        $this->CreateVarProfile('NetatmoSecurity.LightAction', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => SDCARD_STATUS_UNDEFINED, 'Name' => $this->Translate('unknown'), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => SDCARD_STATUS_OFF, 'Name' => $this->Translate('off'), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => SDCARD_STATUS_ON, 'Name' => $this->Translate('on'), 'Farbe' => -1];
        $this->CreateVarProfile('NetatmoSecurity.SDCardStatus', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => ALIM_STATUS_UNDEFINED, 'Name' => $this->Translate('unknown'), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => ALIM_STATUS_OFF, 'Name' => $this->Translate('off'), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => ALIM_STATUS_ON, 'Name' => $this->Translate('on'), 'Farbe' => -1];
        $this->CreateVarProfile('NetatmoSecurity.AlimStatus', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $this->ConnectParent('{DB1D3629-EF42-4E5E-92E3-696F3AAB0740}');

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
        $with_last_event = $this->ReadPropertyBoolean('with_last_event');
        $with_last_notification = $this->ReadPropertyBoolean('with_last_notification');

        $vpos = 1;

        $this->MaintainVariable('Status', $this->Translate('State'), VARIABLETYPE_BOOLEAN, '~Alert.Reversed', $vpos++, true);
        $this->MaintainVariable('LastContact', $this->Translate('Last communication'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_contact);
        $this->MaintainVariable('LastEvent', $this->Translate('Last event'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_event);
        $this->MaintainVariable('LastNotification', $this->Translate('Last notification'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_notification);

        $this->MaintainVariable('CameraStatus', $this->Translate('Camera state'), VARIABLETYPE_INTEGER, 'NetatmoSecurity.CameraStatus', $vpos++, true);
        $this->MaintainVariable('SDCardStatus', $this->Translate('SD-Card state'), VARIABLETYPE_INTEGER, 'NetatmoSecurity.SDCardStatus', $vpos++, true);
        $this->MaintainVariable('AlimStatus', $this->Translate('Alim state'), VARIABLETYPE_INTEGER, 'NetatmoSecurity.AlimStatus', $vpos++, true);
        $this->MaintainVariable('LightmodeStatus', $this->Translate('Lightmode state'), VARIABLETYPE_INTEGER, 'NetatmoSecurity.LightModeStatus', $vpos++, true);

        $this->MaintainVariable('CameraAction', $this->Translate('Camera operation'), VARIABLETYPE_INTEGER, 'NetatmoSecurity.CameraAction', $vpos++, true);
        $this->MaintainVariable('LightAction', $this->Translate('Light operation'), VARIABLETYPE_INTEGER, 'NetatmoSecurity.LightAction', $vpos++, true);

        $this->MaintainAction('CameraAction', true);
        $this->MaintainAction('LightAction', true);

        if (!EVENTS_AS_MEDIA) {
            $this->MaintainVariable('Events', $this->Translate('Events'), VARIABLETYPE_STRING, '', $vpos++, true);
            $this->MaintainVariable('Notifications', $this->Translate('Notifications'), VARIABLETYPE_STRING, '', $vpos++, true);
        }

        $product_type = $this->ReadPropertyString('product_type');
        $product_id = $this->ReadPropertyString('product_id');
        $product_info = $product_id . ' (' . $product_type . ')';
        $this->SetSummary($product_info);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $hook = $this->ReadPropertyString('hook');
            if ($hook != '') {
                $this->RegisterHook($hook);
            }
        }

        $this->SetStatus(IS_ACTIVE);
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $hook = $this->ReadPropertyString('hook');
            if ($hook != '') {
                $this->RegisterHook($hook);
            }
        }
    }

    public function GetConfigurationForm()
    {
        $formElements = [];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'module_disable', 'caption' => 'Instance is disabled'];

        $product_type = $this->ReadPropertyString('product_type');
        switch ($product_type) {
            case 'NACamera':
                $product_type_s = 'Netatmo Indoor camera (Welcome)';
                break;
            case 'NOC':
                $product_type_s = 'Netatmo Outdoor camera (Presence)';
                break;
            default:
                $product_type_s = 'Netatmo Camera';
                break;
        }
        $formElements[] = ['type' => 'Label', 'caption' => $product_type_s];

        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'product_type', 'caption' => 'Product-Type'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'product_id', 'caption' => 'Product-ID'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'home_id', 'caption' => 'Home-ID'];
        $formElements[] = ['type' => 'Label', 'caption' => 'optional data'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_last_contact', 'caption' => ' ... last communication with Netatmo'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_last_event', 'caption' => ' ... last event from Netatmo'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_last_notification', 'caption' => ' ... last notification from Netatmo'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'hook', 'caption' => 'WebHook'];
        $formElements[] = ['type' => 'Label', 'caption' => 'Events'];
        $formElements[] = ['type' => 'NumberSpinner', 'name' => 'event_max_age', 'caption' => ' ... max. age', 'suffix' => 'days'];
        if (EVENTS_AS_MEDIA) {
            $formElements[] = ['type' => 'CheckBox', 'name' => 'events_cached', 'caption' => ' ... Media-object cached'];
        }
        $formElements[] = ['type' => 'Label', 'caption' => 'Notifications'];
        $formElements[] = ['type' => 'NumberSpinner', 'name' => 'notification_max_age', 'caption' => ' ... max. age', 'suffix' => 'days'];
        if (EVENTS_AS_MEDIA) {
            $formElements[] = ['type' => 'CheckBox', 'name' => 'notifications_cached', 'caption' => ' ... Media-object cached'];
        }
        $formElements[] = ['type' => 'Label', 'caption' => 'Local copy of videos from Netatmo via FTP'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'ftp_path', 'caption' => ' ... path'];
        $formElements[] = ['type' => 'NumberSpinner', 'name' => 'ftp_max_age', 'caption' => ' ... max. age', 'suffix' => 'days'];

        $formActions = [];
        $formActions[] = ['type' => 'Label', 'caption' => '____________________________________________________________________________________________________'];
        $formActions[] = [
                            'type'    => 'Button',
                            'caption' => 'Module description',
                            'onClick' => 'echo "https://github.com/demel42/IPSymconNetatmoSecurity/blob/master/README.md";'
                        ];

        $formStatus = $this->GetFormStatus();

        return json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
    }

    public function ReceiveData($data)
    {
        if ($this->GetStatus() == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, 'instance is inactive, skip', 0);
            return;
        }

        $jdata = json_decode($data, true);
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($jdata, true), 0);

        $source = $jdata['Source'];
        $buf = $jdata['Buffer'];

        $home_id = $this->ReadPropertyString('home_id');
        $product_id = $this->ReadPropertyString('product_id');

        $event_max_age = $this->ReadPropertyInteger('event_max_age');
        $notification_max_age = $this->ReadPropertyInteger('notification_max_age');

        $now = time();

        $err = '';
        $statuscode = 0;
        $do_abort = false;

        $this->SendDebug(__FUNCTION__, 'source=' . $source, 0);

        if ($buf != '') {
            $jdata = json_decode($buf, true);
            $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

            switch ($source) {
                case 'QUERY':
                    $homes = $this->GetArrayElem($jdata, 'body.homes', '');
                    if ($homes != '') {
                        foreach ($homes as $home) {
                            if ($home_id != $home['id']) {
                                continue;
                            }
                            $cameras = $this->GetArrayElem($home, 'cameras', '');
                            if ($cameras != '') {
                                foreach ($cameras as $camera) {
                                    if ($product_id != $camera['id']) {
                                        continue;
                                    }

                                    $this->SendDebug(__FUNCTION__, 'decode camera=' . print_r($camera, true), 0);

                                    $camera_status = $this->map_camera_status($this->GetArrayElem($camera, 'status', ''));
                                    if (is_int($camera_status)) {
                                        $this->SetValue('CameraStatus', $camera_status);
                                        if ($camera_status == CAMERA_STATUS_ON) {
                                            $v = CAMERA_STATUS_OFF;
                                        } else {
                                            $v = CAMERA_STATUS_ON;
                                        }
                                        $this->SetValue('CameraAction', $v);
                                    }

                                    $sd_status = $this->map_sd_status($this->GetArrayElem($camera, 'sd_status', ''));
                                    if (is_int($sd_status)) {
                                        $this->SetValue('SDCardStatus', $sd_status);
                                    }

                                    $alim_status = $this->map_alim_status($this->GetArrayElem($camera, 'alim_status', ''));
                                    if (is_int($alim_status)) {
                                        $this->SetValue('AlimStatus', $alim_status);
                                    }

                                    $light_mode_status = $this->map_lightmode_status($this->GetArrayElem($camera, 'light_mode_status', ''));
                                    if (is_int($light_mode_status)) {
                                        $this->SetValue('LightmodeStatus', $light_mode_status);
                                        if ($light_mode_status == LIGHT_STATUS_ON) {
                                            $v = LIGHT_STATUS_OFF;
                                        } else {
                                            $v = LIGHT_STATUS_ON;
                                        }
                                        $this->SetValue('LightAction', $v);
                                    }

                                    $vpn_url = $this->GetArrayElem($camera, 'vpn_url', '');
                                    if ($vpn_url != $this->GetBuffer('vpn_url')) {
                                        $this->SetBuffer('vpn_url', $vpn_url);
                                        $this->SetBuffer('local_url', '');
                                    }

                                    $is_local = $this->GetArrayElem($camera, 'is_local', false);
                                    if ($is_local != $this->GetBuffer('is_local')) {
                                        $this->SetBuffer('is_local', $is_local);
                                        $this->SetBuffer('local_url', '');
                                    }
                                }
                            }
                        }
                    }

                    $got_new_event = false;
                    $ref_ts = $now - ($event_max_age * 24 * 60 * 60);

                    $new_events = [];
                    if (EVENTS_AS_MEDIA) {
                        $s = $this->GetMediaData('Events');
                    } else {
                        $s = $this->GetValue('Events');
                    }
                    $old_events = json_decode($s, true);
                    if ($old_events != '') {
                        foreach ($old_events as $old_event) {
                            if ($old_event['tstamp'] < $ref_ts) {
                                continue;
                            }
                            $new_events[] = $old_event;
                        }
                    }

                    $events = $this->GetArrayElem($home, 'events', '');
                    if ($events != '') {
                        foreach ($events as $event) {
                            if ($product_id != $event['camera_id']) {
                                continue;
                            }
                            $this->SendDebug(__FUNCTION__, 'decode event=' . print_r($event, true), 0);

                            $id = $this->GetArrayElem($event, 'id', '');
                            $tstamp = $this->GetArrayElem($event, 'event_list.0.time', 0);

                            $fnd = false;
                            foreach ($new_events as $new_event) {
                                if ($new_event['id'] == $id) {
                                    $fnd = true;
                                    break;
                                }
                            }
                            if ($fnd) {
                                continue;
                            }

                            $new_event = [
                                    'tstamp'      => $tstamp,
                                    'id'          => $id,
                                ];

                            $video_id = $this->GetArrayElem($event, 'video_id', '');
                            if ($video_id != '') {
                                $new_event['video_id'] = $video_id;
                            }

                            $message = $this->GetArrayElem($event, 'message', '');
                            if ($message != '') {
                                $new_event['message'] = $message;
                            }

                            $new_subevents = [];
                            $subevents = $this->GetArrayElem($event, 'event_list', '');
                            if ($subevents != '') {
                                foreach ($subevents as $subevent) {
                                    $id = $this->GetArrayElem($subevent, 'id', '');
                                    $type = $this->GetArrayElem($subevent, 'type', '');
                                    $ts = $this->GetArrayElem($subevent, 'time', 0);
                                    $message = $this->GetArrayElem($subevent, 'message', '');
                                    $snapshot_id = $this->GetArrayElem($subevent, 'snapshot.id', '');
                                    $snapshot_key = $this->GetArrayElem($subevent, 'snapshot.key', '');
                                    $snapshot_filename = $this->GetArrayElem($subevent, 'snapshot.filename', '');
                                    $vignette_id = $this->GetArrayElem($subevent, 'vignette.id', '');
                                    $vignette_key = $this->GetArrayElem($subevent, 'vignette.key', '');
                                    $vignette_filename = $this->GetArrayElem($subevent, 'vignette.filename', '');

                                    $new_subevent = [
                                            'id'        => $id,
                                            'time'      => $ts,
                                            'type'      => $type,
                                            'message'   => $message,
                                            'type'      => $type,
                                        ];

                                    $snapshot = [];
                                    if ($snapshot_id != '') {
                                        $snapshot['id'] = $snapshot_id;
                                    }
                                    if ($snapshot_key != '') {
                                        $snapshot['key'] = $snapshot_key;
                                    }
                                    if ($snapshot_filename != '') {
                                        $snapshot['filename'] = $snapshot_filename;
                                    }
                                    if ($snapshot != []) {
                                        $new_subevent['snapshot'] = $snapshot;
                                    }

                                    $vignette = [];
                                    if ($vignette_id != '') {
                                        $vignette['id'] = $vignette_id;
                                    }
                                    if ($vignette_key != '') {
                                        $vignette['key'] = $vignette_key;
                                    }
                                    if ($vignette_filename != '') {
                                        $vignette['filename'] = $vignette_filename;
                                    }
                                    if ($vignette != []) {
                                        $new_subevent['vignette'] = $vignette;
                                    }

                                    $new_subevents[] = $new_subevent;
                                }
                                $new_event['subevents'] = $new_subevents;
                                $got_new_event = true;
                            }

                            $new_events[] = $new_event;
                        }
                    }

                    if ($new_events != []) {
                        usort($new_events, ['NetatmoSecurityCamera', 'cmp_events']);
                        $s = json_encode($new_events);
                    } else {
                        $s = '';
                    }

                    if (EVENTS_AS_MEDIA) {
                        $events_cached = $this->ReadPropertyBoolean('events_cached');
                        $this->SetMediaData('Events', $s, $events_cached);
                    } else {
                        $this->SetValue('Events', $s);
                    }

                    $status = $this->GetArrayElem($jdata, 'status', '') == 'ok' ? true : false;
                    $this->SetValue('Status', $status);

                    $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
                    if ($with_last_contact) {
                        $tstamp = $this->GetArrayElem($jdata, 'time_server', 0);
                        $this->SetValue('LastContact', $tstamp);
                    }

                    $with_last_event = $this->ReadPropertyBoolean('with_last_event');
                    if ($with_last_event && $got_new_event) {
                        $this->SetValue('LastEvent', $now);
                    }

                    break;
                case 'EVENT':
                    $ref_ts = $now - ($notification_max_age * 24 * 60 * 60);
                    $notification = $jdata;
                    $got_new_notification = false;

                    $new_notifications = [];
                    if (EVENTS_AS_MEDIA) {
                        $s = $this->GetMediaData('Notifications');
                    } else {
                        $s = $this->GetValue('Notifications');
                    }
                    $old_notifications = json_decode($s, true);
                    if ($old_notifications != '') {
                        foreach ($old_notifications as $old_notification) {
                            if ($old_notification['tstamp'] < $ref_ts) {
                                continue;
                            }
                            $new_notifications[] = $old_notification;
                        }
                    }

                    $camera_id = $this->GetArrayElem($notification, 'camera_id', '');
                    if ($camera_id == '' || $product_id == $camera_id) {
                        $this->SendDebug(__FUNCTION__, 'decode notification=' . print_r($notification, true), 0);

                        $push_type = $this->GetArrayElem($notification, 'push_type', '');
                        switch ($push_type) {
                            case 'NOC-human':
                            case 'NOC-animal':
                            case 'NOC-vehicle':
                                $event_id = $this->GetArrayElem($notification, 'event_id', '');
                                $subevent_id = $this->GetArrayElem($notification, 'subevent_id', '');
                                $event_type = $this->GetArrayElem($notification, 'event_type', '');
                                $message = $this->GetArrayElem($notification, 'message', '');
                                $snapshot_id = $this->GetArrayElem($notification, 'snapshot.id', '');
                                $snapshot_key = $this->GetArrayElem($notification, 'snapshot.key', '');
                                $vignette_id = $this->GetArrayElem($notification, 'vignette.id', '');
                                $vignette_key = $this->GetArrayElem($notification, 'vignette.key', '');
                                $new_notification = [
                                        'tstamp'       => $now,
                                        'id'           => $event_id,
                                        'push_type'    => $push_type,
                                        'event_type'   => $event_type,
                                        'message'      => $message,
                                        'subevent_id'  => $subevent_id,
                                        'snapshot_id'  => $snapshot_id,
                                        'snapshot_key' => $snapshot_key,
                                        'vignette_id'  => $vignette_id,
                                        'vignette_key' => $vignette_key,
                                    ];
                                $new_notifications[] = $new_notification;
                                $got_new_notification = true;
                                break;
                            case 'NOC-connection':
                            case 'NOC-disconnection':
                            case 'NOC-light_mode':
                            case 'NOC-movement':
                            case 'NOC-off':
                            case 'NOC-on':
                                $id = $this->GetArrayElem($notification, 'id', '');
                                $message = $this->GetArrayElem($notification, 'message', '');
                                $event_type = $this->GetArrayElem($notification, 'event_type', '');
                                $new_notification = [
                                        'tstamp'       => $now,
                                        'id'           => $id,
                                        'push_type'    => $push_type,
                                        'event_type'   => $event_type,
                                        'message'      => $message,
                                    ];
                                $new_notifications[] = $new_notification;
                                $got_new_notification = true;
                                break;
                            case 'daily_summary':
                            case 'topology_changed':
                            case 'webhook_activation':
                                $err = 'ignore push_type "' . $push_type . '"';
                                $this->SendDebug(__FUNCTION__, $err, 0);
                                $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_MESSAGE);
                                break;
                            default:
                                $err = 'unknown push_type "' . $push_type . '", data=' . print_r($notification, true);
                                $this->SendDebug(__FUNCTION__, $err, 0);
                                $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
                                break;
                        }
                    }

                    if ($new_notifications != []) {
                        usort($new_notifications, ['NetatmoSecurityCamera', 'cmp_events']);
                        $s = json_encode($new_notifications);
                    } else {
                        $s = '';
                    }

                    if (EVENTS_AS_MEDIA) {
                        $notifications_cached = $this->ReadPropertyBoolean('notifications_cached');
                        $this->SetMediaData('Notifications', $s, $notifications_cached);
                    } else {
                        $this->SetValue('Notifications', $s);
                    }

                    $with_last_notification = $this->ReadPropertyBoolean('with_last_notification');
                    if ($with_last_notification && $got_new_notification) {
                        $this->SetValue('LastNotification', $now);
                    }
                    break;
                default:
                    $err = 'unknown source "' . $source . '"';
                    $this->SendDebug(__FUNCTION__, $err, 0);
                    $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
                    break;
            }
        }

        $this->SetStatus(IS_ACTIVE);
    }

    public function RequestAction($Ident, $Value)
    {
        $product_type = $this->ReadPropertyString('product_type');

        switch ($Ident) {
            case 'LightAction':
                if ($product_type == 'NOC') {
                    $this->SendDebug(__FUNCTION__, '$Ident=' . $Value, 0);
                    $this->SwitchLight($Value);
                } else {
                    $this->SendDebug(__FUNCTION__, 'invalid ident ' . $Ident . ' for product ' . $product_type, 0);
                }
                break;
            case 'CameraAction':
                $this->SendDebug(__FUNCTION__, '$Ident=' . $Value, 0);
                $this->SwitchCamera($Value);
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $Ident, 0);
                break;
        }
    }

    public function SwitchLight(int $mode)
    {
        $url = $this->determineUrl();
        if ($url == false) {
            $err = 'no url available';
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            return false;
        }

        switch ($mode) {
            case LIGHT_STATUS_OFF:
                $value = 'off';
                break;
            case LIGHT_STATUS_ON:
                $value = 'on';
                break;
            case LIGHT_STATUS_AUTO:
                $value = 'auto';
                break;
            default:
                $err = 'unknown mode "' . $mode . '"';
                $this->SendDebug(__FUNCTION__, $err, 0);
                $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
                return false;
        }
        $url .= '/command/floodlight_set_config?config=' . urlencode('{"mode":"' . $value . '"}');

        $SendData = ['DataID' => '{2EEA0F59-D05C-4C50-B228-4B9AE8FC23D5}', 'Function' => 'CmdUrl', 'Url' => $url];
        $data = $this->SendDataToParent(json_encode($SendData));

        $this->SendDebug(__FUNCTION__, 'url=' . $url . ', got data=' . print_r($data, true), 0);

        $jdata = json_decode($data, true);
        return $jdata['status'];
    }

    public function DimLight(int $intensity)
    {
        $url = $this->determineUrl();
        if ($url == false) {
            $err = 'no url available';
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            return false;
        }

        $intensity = intval($intensity);
        if ($intensity > 100 or $intensity < 0) {
            $err = 'linght-intensity range from 0 to 100';
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            return false;
        }

        $url .= '/command/floodlight_set_config?intensity=' . urlencode('{"mode":"' . $intensity . '"}');

        $SendData = ['DataID' => '{2EEA0F59-D05C-4C50-B228-4B9AE8FC23D5}', 'Function' => 'CmdUrl', 'Url' => $url];
        $data = $this->SendDataToParent(json_encode($SendData));

        $this->SendDebug(__FUNCTION__, 'url=' . $url . ', got data=' . print_r($data, true), 0);

        $jdata = json_decode($data, true);
        return $jdata['status'];
    }

    public function SwitchCamera(int $mode)
    {
        $url = $this->determineUrl();
        if ($url == false) {
            $err = 'no url available';
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            return false;
        }

        switch ($mode) {
            case CAMERA_STATUS_OFF:
                $value = 'off';
                break;
            case CAMERA_STATUS_ON:
                $value = 'on';
                break;
            default:
                $err = 'unknown mode "' . $mode . '"';
                $this->SendDebug(__FUNCTION__, $err, 0);
                $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
                return false;
        }

        $url .= '/command/changestatus?status=' . $value;

        $SendData = ['DataID' => '{2EEA0F59-D05C-4C50-B228-4B9AE8FC23D5}', 'Function' => 'CmdUrl', 'Url' => $url];
        $data = $this->SendDataToParent(json_encode($SendData));

        $this->SendDebug(__FUNCTION__, 'url=' . $url . ', got data=' . print_r($data, true), 0);

        $jdata = json_decode($data, true);
        return $jdata['status'];
    }

    private function cmp_events($a, $b)
    {
        $a_tstamp = $a['tstamp'];
        $b_tstamp = $b['tstamp'];
        if ($a_tstamp != $b_tstamp) {
            return ($a_tstamp < $b_tstamp) ? -1 : 1;
        }
        $a_id = $a['id'];
        $b_id = $b['id'];
        return (strcmp($a_id, $b_id) < 0) ? -1 : 1;
    }

    public function GetVpnUrl()
    {
        $url = $this->determineVpnUrl();
        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);
        return $url;
    }

    public function GetLocalUrl()
    {
        $url = $this->determineLocalUrl();
        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);
        return $url;
    }

    public function GetLiveVideoUrl(string $resolution)
    {
        if (!in_array($resolution, ['poor', 'low', 'medium', 'high'])) {
            $err = 'unknown resolution "' . $resolution . '"';
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            return false;
        }

        $url = $this->determineUrl();
        if ($url == false) {
            $err = 'no url available';
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            return false;
        }

        $url .= '/live/files/' . $resolution . '/index.m3u8';
        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);
        return $url;
    }

    public function GetLiveSnapshotUrl()
    {
        $url = $this->determineUrl();
        if ($url == false) {
            $err = 'no url available';
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            return false;
        }

        $url .= '/live/snapshot_720.jpg';
        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);
        return $url;
    }

    public function GetVideoUrl(string $video_id, string $resolution)
    {
        if (!in_array($resolution, ['poor', 'low', 'medium', 'high'])) {
            $err = 'unknown resolution "' . $resolution . '"';
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            return false;
        }

        $url = $this->determineUrl();
        if ($url == false) {
            $err = 'no url available';
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            return false;
        }

        $url .= '/vod/' . $video_id . '/files/' . $resolution . '/index.m3u8';
        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);
        return $url;
    }

    public function GetPictureUrl(string $id, string $key)
    {
        $url = 'https://api.netatmo.com/api/getcamerapicture?image_id=' . $id . '&key=' . $key;
        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);
        return $url;
    }

    public function GetPictureUrl4Filename(string $filename)
    {
        $url = $this->determineUrl();
        if ($url == false) {
            $err = 'no url available';
            $this->SendDebug(__FUNCTION__, $err, 0);
            $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            return false;
        }

        $url .= '/' . $filename;
        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);
        return $url;
    }

    public function GetEvents()
    {
        if (EVENTS_AS_MEDIA) {
            $data = $this->GetMediaData('Events');
        } else {
            $data = $this->GetValue('Events');
        }
        return $data;
    }

    public function GetNotifications()
    {
        if (EVENTS_AS_MEDIA) {
            $data = $this->GetMediaData('Notifications');
        } else {
            $data = $this->GetValue('Notifications');
        }
        return $data;
    }

    public function CleanupVideoPath(bool $verboѕe = false)
    {
        $dt = new DateTime(date('d.m.Y 00:00:00', time()));
        $now = $dt->format('U');

        $path = $this->ReadPropertyString('ftp_path');
        $max_age = $this->ReadPropertyInteger('ftp_max_age');

        if ($path == '' || $max_age < 1) {
            $this->SendDebug(__FUNCTION__, 'no path or no max_age', 0);
            return false;
        }

        if (substr($path, 0, 1) != DIRECTORY_SEPARATOR) {
            $path = IPS_GetKernelDir() . $path;
        }
        $this->SendDebug(__FUNCTION__, 'cleanup viedeo_path ' . $path, 0);
        $age = $max_age * 24 * 60 * 60;
        $this->SendDebug(__FUNCTION__, '* cleanup files', 0);

        $n_files_total = 0;
        $n_files_deleted = 0;
        $n_dirs_total = 0;
        $n_dirs_deleted = 0;
        $directory = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $objects = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($objects as $object) {
            $isFile = $object->isFile();
            if (!$isFile) {
                continue;
            }
            $pathname = $object->getPathname();
            $a = $now - filemtime($pathname);
            $too_young = ($a < $age);
            $n_files_total++;
            if (!$verboѕe && $too_young) {
                continue;
            }
            $this->SendDebug(__FUNCTION__, '  name=' . $object->getPathname() . ', age=' . floor(($a / 86400)) . ' => ' . ($too_young ? 'skip' : 'delete'), 0);
            if ($too_young) {
                continue;
            }
            $n_files_deleted++;
            if (!unlink($pathname)) {
                $err = 'unable to delete file ' . $pathname;
                $this->SendDebug(__FUNCTION__, $err, 0);
                $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            }
        }
        $this->SendDebug(__FUNCTION__, '* cleanup dirs', 0);
        $directory = new RecursiveDirectoryIterator($path);
        $objects = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($objects as $object) {
            $pathname = $object->getPathname();
            $basename = basename($pathname);
            if ($basename == '.' || $basename == '..') {
                continue;
            }
            $isDir = $object->isDir();
            if (!$isDir) {
                continue;
            }
            $n_dirs_total++;

            $filenames = scandir($pathname);
            $n_child = count($filenames) - 2; // not only '.' and '..'

            if (!$verboѕe && $n_child > 0) {
                continue;
            }
            $this->SendDebug(__FUNCTION__, '  name=' . $pathname . ', childs=' . $n_child . ' => ' . ($n_child > 0 ? 'skip' : 'delete'), 0);
            if ($n_child > 0) {
                continue;
            }
            $n_dirs_deleted++;
            if (!rmdir($pathname)) {
                $err = 'unable to delete directory ' . $pathname;
                $this->SendDebug(__FUNCTION__, $err, 0);
                $this->LogMessage(__FUNCTION__ . ': ' . $err, KL_NOTIFY);
            }
        }
        $msg = 'files deleted=' . $n_files_deleted . '/' . $n_files_total . ', dirs deleted=' . $n_dirs_deleted . '/' . $n_dirs_total;
        $this->SendDebug(__FUNCTION__, $msg, 0);
        $this->LogMessage(__FUNCTION__ . ': path=' . $path . ', ' . $msg, KL_MESSAGE);
    }

    public function GetVideoFilename(string $video_id, int $tstamp)
    {
        $ftp_path = $this->ReadPropertyString('ftp_path');
        if ($ftp_path == '') {
            $this->SendDebug(__FUNCTION__, '"ftp_path" is not defined', 0);
            return false;
        }

        if ($video_id == '') {
            $this->SendDebug(__FUNCTION__, 'empty video_id "' . $video_id . '"', 0);
            return false;
        }
        $ids = explode('-', $video_id);
        if ($ids == false) {
            $this->SendDebug(__FUNCTION__, 'invalid video_id "' . $video_id . '"', 0);
            return false;
        }
        $id = $ids[0];

        $path = IPS_GetKernelDir() . $ftp_path . DIRECTORY_SEPARATOR;

        for ($i = 0, $ok = false; $i < 2 && !$ok; $i++) {
            $y = date('Y', $tstamp);
            $m = date('m', $tstamp);
            $d = date('d', $tstamp);
            $H = date('H', $tstamp);
            $M = date('i', $tstamp);

            $filename = $path;
            $filename .= $y . DIRECTORY_SEPARATOR . $m . DIRECTORY_SEPARATOR . $d . DIRECTORY_SEPARATOR;
            $filename .= $y . '-' . $m . '-' . $d . '_' . $H . '.' . $M . '_' . $id . '.mp4';

            $ok = is_file($filename);
            if (!$ok) {
                // der zeitpunkt der Erstellung der Datei ist nkcht unbedingt der des Sub-Events
                $tstamp += 30;
            }
        }

        $this->SendDebug(__FUNCTION__, 'tstamp=' . date('d.m.Y H:i:s', $tstamp) . ', video_id=' . $video_id . ', filename=' . $filename . ' => ' . ($ok ? 'exists' : 'MISSING'), 0);

        return $ok ? $filename : false;
    }

    protected function ProcessHookData()
    {
        $this->SendDebug(__FUNCTION__, '_SERVER=' . print_r($_SERVER, true), 0);
        $this->SendDebug(__FUNCTION__, '_GET=' . print_r($_GET, true), 0);

        $root = realpath(__DIR__);
        $uri = $_SERVER['REQUEST_URI'];
        if (substr($uri, -1) == '/') {
            http_response_code(404);
            die('File not found!');
        }
        $hook = $this->ReadPropertyString('hook');
        if ($hook == '') {
            http_response_code(404);
            die('File not found!');
        }
        $path = parse_url($uri, PHP_URL_PATH);
        $basename = substr($path, strlen($hook));

        $this->SendDebug(__FUNCTION__, 'basename=' . $basename, 0);
        switch ($basename) {
            case 'video':
                if (isset($_GET['video_id'])) {
                    $video_id = $_GET['video_id'];
                    $event_id = '';
                } elseif (isset($_GET['video_id'])) {
                    $event_id = $_GET['event_id'];
                    $video_id = '';
                }
                $tstamp = '';

                if (EVENTS_AS_MEDIA) {
                    $data = $this->GetMediaData('Events');
                } else {
                    $data = $this->GetValue('Events');
                }
                $events = json_decode($data, true);
                foreach ($events as $event) {
                    if ($video_id != '') {
                        if (!isset($event['video_id'])) {
                            continue;
                        }
                        if ($event['video_id'] != $video_id) {
                            continue;
                        }
                        $tstamp = $event['tstamp'];
                    }
                    if ($event_id != '') {
                        if ($event['id'] != $event_id) {
                            continue;
                        }
                        $video_id = $event['video_id'];
                        $tstamp = $event['tstamp'];
                        break;
                    }
                }

                if ($video_id == '') {
                    http_response_code(404);
                    die('File not found!');
                }

                if ($tstamp != '') {
                    $filename = $this->GetVideoFilename($video_id, $tstamp);
                    $this->SendDebug(__FUNCTION__, 'filename=' . $filename, 0);
                    if ($filename != '') {
                        $path = IPS_GetKernelDir() . 'webfront';
                        $path = substr($filename, strlen($path));

                        $url = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
                        $url .= '://' . $_SERVER['HTTP_HOST'] . $path;

                        $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);

                        http_response_code(200);

                        echo '<html>';
                        echo '<body>';
                        echo '<video>';
                        echo '  <source src="' . $url . '" type="video/mp4" />';
                        echo '</video>';
                        echo '</body>';
                        echo '</html>';

                        return;
                    }
                }

                $url = $this->GetVideoUrl($video_id, 'high');
                if ($url != false) {
                    $this->SendDebug(__FUNCTION__, 'url=' . $url, 0);

                    http_response_code(200);

                    echo '<html>';
                    echo '<head>';
                    echo '<meta http-equiv="refresh" content="0; url=' . $url . '">';
                    echo '</head>';
                    echo '<body>';
                    echo '</body>';
                    echo '</html>';

                    return;
                }
                break;
            case 'snapshot':
                break;
            default:
                $path = realpath($root . '/' . $basename);
                if ($path === false) {
                    http_response_code(404);
                    die('File not found!');
                }
                if (substr($path, 0, strlen($root)) != $root) {
                    http_response_code(403);
                    die('Security issue. Cannot leave root folder!');
                }
                header('Content-Type: ' . $this->GetMimeType(pathinfo($path, PATHINFO_EXTENSION)));
                readfile($path);
                break;
            }
    }
}