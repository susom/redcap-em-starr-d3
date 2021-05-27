<?php

namespace Stanford\StarrDataDeliveryonDemand;

use \REDCap;

    global $module ;
    $DPA_PID = 10;
    $pid = isset($_GET['pid']) && !empty($_GET['pid']) ? $_GET['pid'] : null;
    if(isset($_GET['doc_id']) && !empty($_GET['doc_id'])) {
        //  pick up the doc_id from the query string
        $docId =  $_GET['doc_id'] ; // use 93 for testing
        // e.g. http://localhost/redcap_v10.8.2/ExternalModules/?prefix=redcap-em-starr-d3&page=src%2FIntakeFormValidation&NOAUTH=&pid=33&doc_id=93
        $module->emDebug('current doc_id ' . print_r($docId, TRUE));

        // first look up the actual document filename given the doc_id
        $q = db_query("select doc_name, stored_name from redcap_edocs_metadata where delete_date is null and doc_id = " . db_escape($docId));
        if (!db_num_rows($q)) return false;
        $edoc_orig_filename = db_result($q, 0, 'doc_name');
        $stored_filename = db_result($q, 0, 'stored_name');
        $module->emDebug("***** I am in IntakeFormValidation Page**** " . EDOC_PATH . "  " . $edoc_orig_filename . " " . $stored_filename);
        // open csv file
        $fname = EDOC_PATH . $stored_filename;

        $string = file_get_contents($fname);
        $module->emDebug($string);
        $ar0 = explode("\n", $string);
        // now remove any blank lines that may have resulted from the explode
        $ar = array_filter($ar0, function ($element) {
            return is_string($element) && '' !== trim($element);
        });
        $module->emDebug('ar is ' . print_r($ar,TRUE));
        // now make an API call to validate the MRNs
        $params = $module->retrieveIdToken();
        $returnedList = $module->apiPost($pid, $ar, $params['token'], $params['url']);

        // now process the returned list to see what we got back
        $module->emDebug('returned list ' . print_r($returnedList, TRUE));

        // next step is to compare the two lists and assemble an error report
        // if the two lists are identical, tell the user "Input validation success: all <n> supplied IDs are recognized"
        // where <n> is the length of the de-duplicated list
        // if there were any dropouts, tell the user "Input validation detected <n> valid IDs and <m> invalid Ids. <j>% of your IDs were not recognized.
        // You can either proceed with the current list or upload a new one, at your discretion."
        // if the the input list has duplicates, also tell the user "Please note that <i> duplicate IDs were found in your file"
        if ($returnedList['status'] === '0') {
            echo "We are currently experiencing technical difficulties. Please notify REDCap Help by browsing to <a href='http://med.stanford.edu/researchit.html'>med.stanford.edu/researchit.html</a> and clicking the 'Request a Consultation' button. ";
        } else {
            // first check for duplicates. first create a new list with no blanks so we don't confound invalid mrns with duplicates

            $filtered = array_filter($returnedList['validatedMrns'], function ($element) {
                global $module;
                $isEmpty = ('' !== trim($element));
                $module->emDebug('element in list ' . print_r($element, TRUE). ' is_string: '.is_string($element). ' isempty? '.$isEmpty);

                return is_string($element) && '' !== trim($element);
            });
            $module->emDebug('filtered list ' . print_r($filtered, TRUE));
            $dedup = array_unique($filtered);
            $module->emDebug('dedup list ' . print_r($dedup, TRUE));
            $dedupError = '';
            if (sizeof($dedup) < sizeof($filtered)) {
                $discrepancy = sizeof($filtered) - sizeof($dedup);
                $s = $discrepancy === 1 ? ' was' : 's were';
                $dedupError = " Please note that $discrepancy duplicate ID$s found in your file.";
            }

            // now check for invalid mrns. if more than 10 just show the first 10 to the user in the error message
            // otherwise show them all. Calculate the percentage error rate.
            $invalidMrns = array();
            $n = 0;
            $m = 0;
            for ($i=0, $len=count($returnedList['validatedMrns']); $i<$len; $i++) {
                if ($returnedList['validatedMrns'][$i] === '') {
                    $module->emDebug("found an invalid MRN at position $i : $ar[$i]");
                    $m++;
                    if (sizeof($invalidMrns) < 11) {
                        $invalidMrns[] = $ar[$i];
                    }
                } else {
                    $n++;
                }
            }
            $nFound = sizeof($dedup);
            $pct = round($m *100 / sizeof($returnedList['validatedMrns']));
            if ($m === 0) {
                // all supplied mrns were valid
                $msg = "Input validation success: all $nFound supplied IDs are recognized";
            } else if ($n === 0) {
                $msg = "Input validation found 0 matches for the supplied IDs. Please upload a new version before proceeding. The file should contain a list of identifiers of the specified type, which each identifier on its own line in the file. You may want to double check your answer to the 'How do you plan to identify your cohort of interest?' question, above.";
            } else {
                $s1 = $nFound === 1 ? '' : 's';
                $s2 = $m === 1 ? '' : 's';
                $invalidMrnString = implode(", ", $invalidMrns);
                if ($m > 10) {
                    $idMsg = "(e.g. $invalidMrnString)";
                } else {
                    $idMsg = "($invalidMrnString)";
                }
                $msg = "Input validation detected $nFound valid ID$s1 and $m invalid Id$s2: $idMsg. $pct% of your IDs were not recognized. You can either proceed with the current list or upload a new version, at your discretion.";
            }
            echo trim($msg . $dedupError);
        }

    }

    // the rest of this does not deal with MRN validation
    if(isset($_GET['fn']) && !empty($_GET['fn'])) {
        $fieldList = array('record_id', 'approved') ;
        if ($_GET['fn'] == 'validate_irb') {
            //if (empty($_GET["irb"])) return 'valid' ;
            $filter_logic = "[prj_protocol]='" . $_GET["irb"] . "'" ;
            $recordList = REDCap::getData($DPA_PID, 'array', null, $fieldList, null,
                            null, null, null, null, $filter_logic);
            if (count($recordList) == 0) {
                echo 'Invalid' ;
            } else {
                echo 'valid' ;
            }
        }

        if ($_GET['fn'] == 'validate_dpa') {
            $filter_logic = "[record_id]='" . substr($_GET["dpa"], 4) . "'" ;
            $module->emDebug("filter logic :" . $filter_logic) ;
            $recordList = REDCap::getData($DPA_PID, 'array', null, $fieldList, null,
                            null, null, null, null, $filter_logic);
            if (count($recordList) == 0) {
                echo 'Invalid' ;
            } else {
                echo 'valid' ;
            }
        }

        echo '' ;
        return ;
    }


?>