<?php
namespace Stanford\StarrDataDeliveryonDemand;

require_once "emLoggerTrait.php";

class StarrDataDeliveryonDemand extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	}
    protected $url = 'https://api.rit.stanford.edu/rcs/api/v1/data';
    /**
     * HOOK FUNCTION on SAVE
     * Check to see if the checkbox for Sync on Save is checked for an EM configuration,
     */
    public function redcap_save_record($project_id, $record = NULL,  $instrument,  $event_id,  $group_id = NULL,  $survey_hash = NULL,  $response_id = NULL, $repeat_instance)
    {
        // Retrieve the EM Configurations to see if any configurations should run on save
        /*
        $query_configs = $this->getSubSettings('queries');

        $cohort_syncd = false;
        foreach ($query_configs as $query) {

            // Check to see if this data config should run when a record is saved
            if ($query['sync-on-save'] && ($query['data-location'] == 'redcap') && ($query['save-instrument'] == $instrument)) {

                // Make sure the records are sync'd before retrieving any data
                if (!$cohort_syncd) {
                    $this->emDebug("Syncing records for project $project_id");
                    $status = $this->syncRecords($project_id);
                    $cohort_syncd = true;
                }

                // Now call the data retrieval query
                $query_name = $query['query-name'];
                $this->emDebug("Query name to run on save: " . $query_name . " for project " . $project_id);
                $this->syncData($project_id, $query_name);
                $this->emDebug("Back from query " . $query_name . " for project " . $project_id);

            }
        }
        */
        $this->emDebug("REDCap Save Record hook is called") ;
        $this->emDebug($record) ;
    }

    //[internal function]: Stanford\IntakeForm\IntakeForm->redcap_survey_page_top(23, NULL, 'project_summary', 56, NULL, '49N8WWTXXT', '', 1)
    public function redcap_survey_page_top ( int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, string $survey_hash, string $response_id = NULL, int $repeat_instance = 1 )
    {
        $this->emDebug("REDCap Survey Page Top is invoked....") ;
        //print '<h2> I am survey page top</h2>' ;

    }

    public function redcap_survey_page ( int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, string $survey_hash, string $response_id = NULL, int $repeat_instance = 1 )
    {
        $this->emDebug("REDCap Survey Page Bottom is invoked....") ;
        // print '<h2> I am survey page bottom</h2>' ;

        // Instantiate the IRB Lookup module
        /*
        try {

            $IRBL = \ExternalModules\ExternalModules::getModuleInstance('irb_lookup');
            $rtn = $IRBL->getIRBNumsBySunetID("scweber") ;
            $this->emDebug($rtn) ;
            print_r($rtn, true) ;

        } catch (Exception $ex) {
            $msg = "The IRB Lookup module is not enabled, please contact REDCap support.";
            $this->emError($msg);
            return (array("status" => 0, "message" => $msg));
        }

        $irb_select = "<select name=irb_number><option></option><option>123456</option><option>78912</option></select>" ;

        $script = "<Script>" .
            //"     alert($('[name=\"irb_number\"]').html()) ; " .
            "   $('[name=\"irb_number\"]').replaceWith('" . $irb_select . "') ;" .
            "</Script>" ;

        print $script ;
        */
        $url = $this->getUrl('src/IntakeFormValidation.php') ;
        $this->emDebug($url) ;

        $script = <<<EOT
            <script>
                $('[name="irb_number"]').on('change', function(elem) {
                    console.log("In irb number change method") ;
                    //$(this).css("border-color", "") ;
                    //$(".validationMessage.irb").remove() ;
                    $("#irb_error_message").hide() ;

                    if ($(this).val().trim().length > 0) {
                        var url = '$url' + '&NOAUTH=' ;
                        var irbElem = $(this) ;
                        $.get( url + "&fn=validate_irb&irb=" + $(this).val(), function(data) {
                            console.log("got data from ajax call :" + data) ;
                            if (data.toLowerCase() == "invalid") {
                                $("#irb_error_message").html("Unable to locate a DPA associated with IRB " + irbElem.val()
                                        + ". Please verify you entered the correct IRB above, and that this IRB has <a style='font-size:small' target='_blank' href='http://med.stanford.edu/starr-tools/data-compliance/data-privacy-attestation.html'>an associated DPA</a>.") ;
                                $("#irb_error_message").show() ;
                                //irbElem.css("border-color", "red") ;
                                //irbElem.after('<div class="validationMessage irb">Invalid IRB Number</div>') ;
                            }
                        }) ;
                    }
                }) ;

                $('[name="dpa_omop"]').on('change', function(elem) {
                    console.log("In dpa number change method") ;
                    //$(this).css("border-color", "") ;
                    $(".validationMessage.dpa").remove() ;
                    var dpaElem = $(this) ;
                    if ($(this).val().trim().length > 0) {
                        if ($(this).val().toLowerCase().substring(0, 4) != "dpa-") {
                            //dpaElem.css("border-color", "red") ;
                            dpaElem.after('<div class="validationMessage dpa">DPA Number has to start with "DPA-"</div>') ;
                            return ;
                        }
                        var url = '$url'  + '&NOAUTH=' ;
                        $.get( url + "&fn=validate_dpa&dpa=" + $(this).val(), function(data) {
                            console.log("got data from ajax call :" + data) ;
                            if (data.toLowerCase() == "invalid") {
                                var errMsg = "Unable to locate DPA " + dpaElem.val() + " in our database. <a style='font-size:small' target='_blank' href='http://med.stanford.edu/starr-tools/data-compliance/data-privacy-attestation.html'>Click here</a> for more information on DPAs." ;
                                //dpaElem.css("border-color", "red") ;
                                dpaElem.after('<div class="validationMessage dpa">' + errMsg + '</div>') ;
                            }
                        }) ;
                    }
                }) ;

            </script>
            <style>
                .validationMessage {
                    color: red ;
                    font-size: small ;
                    font-weight: bold ;
                }
            </style>
        EOT ;

        print $script ;
    }

    /**
     * This function is called by the cron job to find out which projects have the EM enabled.
     * Each project where the EM is enabled needs to be called through an API call so that REDCap will run through
     * the project setup and be in project context. Then, each configuation will be checked to see if it has
     * crons to run.  This is called every 4 hours.  There is a separate cron that runs 1 x day.
     */
    public function checkFor4hourCrons() {
        $cron4h = true;
        $this->checkAllCronJobs($cron4h);
    }

    /**
     * This function is called by the cron job to find out which projects have the EM enabled.
     * Each project where the EM is enabled needs to be called through an API call so that REDCap will run through
     * the project setup and be in project context. Then, each configuation will be checked to see if it
     * is time to run.  This is called 1 x day.  There is a separate cron that runs every 4 hours.
     */
    public function checkForDailyCrons() {
        $cron4h = false;
        $this->checkAllCronJobs($cron4h);
    }

    /**
     * This function will perform the calls for the cron.  First it will see which crons needs to be run
     * and then runs them.  Before any query is run, we sync the record list.  There are 6 crons setup at
     * midnight, 4am, 8am, noon, 4pm, and 8pm for the 4 hour cron.  There is another cron setup to run at
     * 7pm for the daily cron.
     *
     * @param $cron4h - true if this cron is the every 4 hour cron, otherwise false
     */
    private function checkAllCronJobs($cron4h) {

        // Find all the projects that are using the Redcap to STARR Link EM
        $enabled = ExternalModules::getEnabledProjects($this->PREFIX);

        // Loop over all enabled projects to first sync all the records and then
        // sync all the data.
        while($row = $enabled->fetch_assoc()) {

            $proj_id = $row['project_id'];

            $queries_to_run = $this->checkCronFrequency($proj_id, $cron4h);
            $this->emDebug("This is the list of crons to run now for project $proj_id: " . json_encode($queries_to_run));
            if (!empty($queries_to_run)) {

                $status = $this->syncRecords($proj_id);
                if ($status) {

                    // Loop over the queries that are ready to run for this project
                    foreach ($queries_to_run as $query_name) {
                        $this->emDebug("Starting data sync for project $proj_id, query " . $query_name);
                        $this->syncData($proj_id, $query_name);
                        $this->emDebug("Finished data sync for project $proj_id, query $query_name");
                    }
                }
            } else {
                $this->emDebug("Not running any queries for project $proj_id.");
            }
        }
    }


    /**
     * This little function will return the user who is saving the record. If the user is a survey respondent, change the user
     * to 'survey' otherwise REDCap to STARR link will reject the request.
     *
     * @param $user
     * @return mixed|string|string[]
     */
    private function getUser($user) {
        if (USERID == "[survey respondent]") {
            return "survey";
        } else {
            return USERID;
        }
    }

    /**
     * This function will initiate the sync process for this project.
     *
     * @param $proj_id
     * @return bool|false|\returns|string
     */
    public function syncRecords($proj_id) {

        // Find the user who is saving this record - if it is survey respondent, just use 'survey'
        $user = $this->getUser(USERID);

        // Create the API URL to this project to see if we need to either sync records or pull data
        $projectRecordURL = $this->getUrl('src/RedcapProjectToStarrLink.php?pid=' . $proj_id, true, true);
        $projectRecordURL .= "&action=records&user=" . $user;
        $this->emDebug("Calling cron to sync records and/or data for project $proj_id at URL " . $projectRecordURL);

        // Call the project through the API so it will be in project context
        $response = http_get($projectRecordURL);

        if ($response == false) {
            $this->emDebug("Project $proj_id records were NOT successfully sync'd");
        } else {
            $this->emDebug("Project $proj_id records were successfully sync'd, response: " . $response);
            $response = true;
        }

        return $response;
    }

    /**
     * This function will initiate the data retrieval process for this project.
     *
     * @param $proj_id
     */
    public function syncData($proj_id, $query_name=null) {

        // Find the user who is saving this record - if it is survey respondent, just use 'survey'
        $user = $this->getUser(USERID);

        // Create the API URL to this project to sync data
        $projectDataURL = $this->getUrl('src/RedcapProjectToStarrLink.php?pid=' . $proj_id, true, true);
        $projectDataURL .= "&action=data&user=" . $user;
        $this->emDebug("Data URL: " . $projectDataURL);
        if (!is_null($query_name)) {
            $projectDataURL .= "&query=" . $query_name;
        }
        $this->emDebug("Calling cron to sync records and/or data for project $proj_id at URL " . $projectDataURL);

        // Call the project through the API so it will be in project context
        $response = http_get($projectDataURL);

        if ($response == false) {
            $this->emDebug("Project $proj_id data was NOT successfully sync'd");
        } else {
            $this->emDebug("Project $proj_id data was successfully sync'd");
        }
    }

    /**
     * This function will put together the URL to call to initialize the project context.  Then, each query subsetting
     * will be checked to make sure it is time to run.
     *
     * @param $proj_id - project id where this EM is enabled
     * @param $cron_4h - value to designate if this is the cron that runs every 4 hours vs. 1 x day
     * @return array - array of query_names that are ready to be run
     */
    private function checkCronFrequency($proj_id, $cron_4h) {

        // Find the user who is saving this record - if it is the cron job, just use 'cron'
        $user = $this->getUser(USERID);

        // We need to get into project context so we can retrieve the query configurations.
        // Create the API URL to project to check the cron status
        $projectCronURL = $this->getUrl('src/RedcapProjectToStarrLink.php?pid=' . $proj_id, true, true);
        if ($cron_4h) {
            $projectCronURL .= "&action=cron4h&user=" . $user;
        } else {
            $projectCronURL .= "&action=cron&user=" . $user;
        }

        // Call the page in context mode and it will return the list of queries that should run now.
        $response = http_get($projectCronURL);
        $queries_to_run = json_decode($response, true);

        return $queries_to_run;
    }

    /**
     * This function is called by the TrackCovidConsolidator EM to bring STARR Lab Results to the REDCap temp directory
     *
     * @param $proj_id
     * @return false|string
     */
    public function getStanfordTrackCovidResults($proj_id)
    {
        // Setup parameters.  Any of the TrackCovid projects can be used.  The main reason we need a
        // project id is to retrieve the IRB number to make sure it is valid.
        // We are setting fieldsToRetrieve to null so that we retrieve all the fields
        // named in the query 'all_lab_tests' stored in the Oracle Redcap_to_STARR tables.
        $formData = array('redcap-arm' => '1', 'query-name' => 'all_lab_tests', 'constraints' => null);

        // Retrieve the filename for today
        $filename = $this->dailyFilename('StanfordLabs');

        // Make the request and save the lab data in the temp directory
        $status = $this->sendRequestForTrackCovid($proj_id, $formData, $filename);
        return $status;
    }


    /**
     * Create a file name with today's date to put into the REDCap temp directory. If the file will be
     * deleted after each use, the date is probably fine otherwise maybe I should add a timestamp also.
     *
     * @return string
     */
    private function dailyFilename($name) {

        $filename =  APP_PATH_TEMP . $name . '_' . date('mdY') . '.csv';
        return $filename;
    }


    /**
     * This function is called by the TrackCovidConsolidator EM to bring STARR Appointment Results to the REDCap temp directory
     *
     * @param $proj_id
     * @return false|string
     */
    public function getStanfordTrackCovidAppts($proj_id)
    {
        $this->emDebug("In getStanfordTrackingCovidAppts with project id $proj_id");

        // Setup parameters.  Any of the TrackCovid projects can be used.  The main reason we need a
        // project id is to retrieve the IRB number to make sure it is valid.
        // We are setting fieldsToRetrieve to null so that we retrieve all the fields
        // named in the query 'all_lab_tests' stored in the Oracle Redcap_to_STARR tables.
        $formData = array('redcap-arm' => '1', 'query-name' => 'appointments', 'constraints' => null);

        // Retrieve the filename for today
        $filename = $this->dailyFilename('StanfordAppts');
        $this->emDebug("Filename: " . $filename);

        // Make the request and save the appt data in the temp directory
        $status = $this->sendRequestForTrackCovid($proj_id, $formData, $filename);
        return $status;
    }

    /**
     * This function is called by the TrackCovidConsolidator EM to bring STARR Vaccine Results to the REDCap temp directory
     *
     * @param $proj_id
     * @return false|string
     */
    public function getStanfordTrackCovidVax($proj_id)
    {
        $this->emDebug("In getStanfordTrackCovidVax with project id $proj_id");

        // Setup parameters.  Any of the TrackCovid projects can be used.  The main reason we need a
        // project id is to retrieve the IRB number to make sure it is valid.
        // We are setting fieldsToRetrieve to null so that we retrieve all the fields
        // named in the query 'vaccines' stored in the Oracle Redcap_to_STARR tables.
        $formData = array('redcap-arm' => '1', 'query-name' => 'vaccines', 'constraints' => null);

        // Retrieve the filename for today
        $filename = $this->dailyFilename('StanfordVax');
        $this->emDebug("Filename: " . $filename);

        // Make the request and save the vaccine data in the temp directory
        $status = $this->sendRequestForTrackCovid($proj_id, $formData, $filename);
        return $status;

    }

    private function sendRequestForTrackCovid($proj_id, $formData, $filename) {

        // Check IRB and setup request
        $fieldsToRetrieve = null;
        list($header, $message) = checkIRBAndSetupRequest($proj_id, $formData, $fieldsToRetrieve);
        $message['export_format'] = 'csv';
        unset($message['fields']);

        // If we have a valid token, continue to the API call
        if (!is_null($header) && !empty($header)) {

            // Send an API call and save the results to a file in the REDCap temp directory
            $basic_auth_user = null;
            $timeout = null;
            $content_type = 'application/json';
            $message_to_send = json_encode($message);
            $response = http_post($this->url, $message_to_send, $timeout, $content_type, $basic_auth_user, $header);
            $fileStatus = file_put_contents($filename, $response);
            if (!$fileStatus) {
                $this->emError("Could not create file $filename for TrackCovid vaccine dates");
                $status = false;
            } else {
                $status = $filename;
            }
        }

        return $status;
    }


}
