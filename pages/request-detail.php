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
    \Authentication::authenticate();
    $sunetid  = $_SERVER['REMOTE_USER'];
    $module->emDebug("*****Global Sunet ID :" . $sunetid) ;
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
    $i = 0;
    foreach($recordList as $key => $eventData) {
//    $returnStruct["data"][$i]['updatedOn'] = 'N/A';
        $returnStruct["data"][$i++]['omop'] = 'OMOP';
        foreach($eventData as $eventId => $record) {
            $returnStruct["data"][] = $record ;
        }
    }

    $module->emDebug(print_r($returnStruct, TRUE));
    return $returnStruct;
}


echo json_encode(getRequestDetail());
