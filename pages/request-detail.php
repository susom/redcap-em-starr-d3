<?php
namespace Stanford\StarrDataDeliveryonDemand;

use \REDCap;

global $module ;

/*
 * DataTables server-side processing script.
 *
 * See http://datatables.net/usage/server-side for full details on the server-
 * side processing requirements of DataTables.
 *
 * @license MIT - http://datatables.net/license_mit
 */

function getRequestDetail() {
    global $module;

    $sunetid  =  $_SERVER['REMOTE_USER'];
    $module->emDebug("*****Global Sunet ID :" . $sunetid) ;
    if (isset($sunetid)) {
        $include_logic = "[webauth_user]='" . $sunetid . "' and [data_types(5)] = '1'";
        $recordList = REDCap::getData('array', null, $$module->fieldList, null,
            null, null, null, null, $include_logic);
    }
    $project_id = $_GET['pid'];
    $returnStruct = array();
    $returnStruct['draw'] = 1;
    $returnStruct["recordsTotal"] = sizeof($recordList);
    $returnStruct["recordsFiltered"] = sizeof($recordList);
    $returnStruct["data"] = array();

    // if user is listed on this project, also include the URL e.g.
    // https://redcap.stanford.edu/redcap_v11.1.0/DataEntry/record_home.php?pid=22082&arm=1&id=108
    $projectUsers = REDCap::getUsers();
//    $module->emDebug(print_r($projectUsers, TRUE));

    foreach($recordList as $key => $eventData) {
        foreach($eventData as $eventId => $record) {
            if (in_array($sunetid, $projectUsers)) {
                $redcapURL = APP_PATH_WEBROOT_FULL . APP_PATH_WEBROOT . "DataEntry/record_home.php?pid=$project_id" . "&arm=1&id=" . $record['record_id'];
                $record['url'] = "<a href='$redcapURL'>View in REDCap</a>";
            } else {
                $record['url'] = '';
            }
            $record['omop'] = 'OMOP';
            // $record['updatedOn'] = ...
//            $module->emDebug(print_r($record, TRUE));
            $returnStruct["data"][] = $record ;
        }
    }
    return $returnStruct;
}


echo json_encode(getRequestDetail());
