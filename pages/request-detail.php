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
    $sunetid = $_SERVER['REMOTE_USER'];
    $module->emDebug("*****Global Sunet ID :" . $sunetid) ;
    $sunetid = 'scweber';
    $module->emDebug(" sunet is ".$sunetid);
    if (isset($sunetid)) {
        $include_logic = "[webauth_user]='" . $sunetid . "' and [data_types(5)] = '1'";
        $recordList = REDCap::getData('array', null, $$module->fieldList, null,
            null, null, null, null, $include_logic);
    }
    $returnStruct = array();
    $returnStruct['draw'] = 1;
    $returnStruct["recordsTotal"] = sizeof($recordList);
    $returnStruct["recordsFiltered"] = sizeof($recordList);
    $returnStruct["data"] = array();
    foreach($recordList as $key => $eventData) {
        foreach($eventData as $eventId => $record) {
            $returnStruct["data"][] = $record ;
        }
    }
    $returnStruct["data"][0]['omop'] = 'OMOP';

//    $returnStruct["data"][0]['updatedOn'] = 'N/A';
    $module->emDebug(" * * * * * * OK * * * * * * ");
    $module->emDebug(print_r($returnStruct, TRUE));
    return $returnStruct;
}


echo json_encode(getRequestDetail());
