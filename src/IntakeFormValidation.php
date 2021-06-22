<?php

namespace Stanford\StarrDataDeliveryonDemand;

use \REDCap;

    global $module ;
    $DPA_PID = 10;
    $pid = isset($_GET['pid']) && !empty($_GET['pid']) ? $_GET['pid'] : null;
    if(isset($_GET['doc_id']) && !empty($_GET['doc_id'])) {
        // pick up the style of expected file format from redcap

        //  pick up the doc_id from the query string
        $docId =  $_GET['doc_id'] ; // use 93 for testing
        $ft = $_GET['ft']; // this will be 1, 2, 3 or 4, where
        // 1	I have a list of deid Person_ids with specific date ranges
        // 2	I have a list of deid Person_ids- need data all dates
        // 3	I have a list of Stanford patient MRNs with specific date ranges
        // 4	I have a list of Stanford patient MRNs -need data for all dates

        // e.g. http://localhost/redcap_v10.8.2/ExternalModules/?prefix=redcap-em-starr-d3&page=src%2FIntakeFormValidation&NOAUTH=&pid=33&doc_id=93&ft=4
        $module->emDebug('current doc_id ' . print_r($docId, TRUE) . ' and ft is ' . $ft);

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
        if ($ft === '1' || $ft === '3') { // date ranges
            // uploaded file has mrn or OMOP person_id accompanied by two dates
            // first verify that all dates can be parsed as dates that fall within the last 20 years and not in the future
            // then strip them all off before sending them to the API for validation
            $ret = $module->verifyDates($ar);
            $dateMsg = $ret['msg'];
            $ar = $ret['id_list'];
            $dateStatus = $ret['status'];
        } else {
            $dateMsg = '';
            $dateStatus = 2; // status = 0 means a problem, 1 or 2 means you can carry on
        }
        $module->emDebug('ar is ' . print_r($ar,TRUE));
        // now make an API call to validate the MRNs

        $params = $module->retrieveIdToken();
        if ($ft === '3' | $ft === '1') {
            $newar = array(); // strip dates
            foreach ($ar as &$value) {
                $components = explode(',', $value);
                $newar[] = $components[0];
            }
        } else {
            $newar = $ar;
        }
        $url = ($ft === '1' || $ft === '2') ? str_replace('mrn', 'omopid', $params['url']) : $params['url'];
        $returnedList = $module->idApiPost($pid, $newar, $params['token'], $url, ($ft === '1' || $ft === '2'));
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
            // first check for duplicates. first create a new list with no blanks so we don't confound invalid ids with duplicates
            $instructions = '';
            $filtered = array_filter($returnedList['validatedIds'], function ($element) {
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
                $dedupError = " Also note that $discrepancy duplicate ID$s found in your file.";
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
            // set up a customized error message with lots of information on how to succeed
            $retryInstructions = "Please upload a new version before proceeding. ";
            $type = ($ft === '2' || $ft === '1') ? 'OMOP PersonIds' : 'Stanford MRNs';
            if ($ft === '2' || $ft === '4') {
                $retryInstructions .= "The file should contain a list of $type, which each identifier on its own line in the file. ";
            } else {
                $retryInstructions .= "Please ensure all rows in your file have 3 comma-separated columns, with $type as the first item, the start date as the second, and the end date as the third. Dates may be left blank. All supplied dates should be in the past, and formatted as YYYY-MM-DD, e.g. 2020-12-31. Patients with invalid associated dates will be excluded from the dataset. ";
            }
            $retryInstructions .= "You may want to double check your answer to the 'How do you plan to identify your cohort of interest?' question, above.";

            $nFound = sizeof($dedup);
            $pct = round($m *100 / sizeof($returnedList['validatedMrns']));
            if ($m === 0 && $nFound === 0 && $n === 0) {
                $msg = "Your file appears to be empty. ";
                $instructions = $retryInstructions;
            } else if ($m === 0 && $nFound == 0) {
                $msg = "Server error, most likely a configuration issue. Please contact your REDCap Administrator.";
            } else if ($m === 0 && $nFound > 0) {
                // all supplied mrns were valid
                $msg = "Input validation success: all $nFound supplied IDs are recognized";
            } else if ($n === 0) {
                $msg = "Input validation found 0 matches for the supplied IDs.";
                $instructions = $retryInstructions;
            } else {
                $s1 = $nFound === 1 ? '' : 's';
                $s2 = $m === 1 ? '' : 's';
                $invalidMrnString = implode(", ", $invalidMrns);
                if ($m > 10) {
                    $idMsg = "(e.g. $invalidMrnString)";
                } else {
                    $idMsg = "($invalidMrnString)";
                }
                $msg = "Input validation detected $nFound valid ID$s1 and $m invalid Id$s2: $idMsg. $pct% of your IDs were not recognized.";
                if ($dateStatus == 0) {
                    $instructions = $retryInstructions;
                } else {
                    $instructions = "You can either proceed with the current list or upload a new version, at your discretion.";
                }
            }
            echo trim('<p>' . $msg . $dateMsg . $dedupError . '<p>' . $instructions);
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
