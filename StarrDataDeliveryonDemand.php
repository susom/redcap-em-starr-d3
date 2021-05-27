<?php
namespace Stanford\StarrDataDeliveryonDemand;

require_once "emLoggerTrait.php";

class StarrDataDeliveryonDemand extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	}

	// used to pick up the access token needed to invoke the MRN validity API
    function retrieveIdToken() {
        global $module ;
        $module->emError("in retrieveIdToken");
        try {
            $VTM = \ExternalModules\ExternalModules::getModuleInstance('redcap-em-vertx-token-manager');
            $module->emError("success instantiating VTM");
        } catch (Exception $ex) {
            $msg = "The Vertx Token Manager module is not enabled, please contact REDCap support.";
            $module->emError($msg);
            return array("status" => 0,
                "message" => $msg);
        }

        // Get a valid API token from the vertx token manager
        $service = "id";
        $token = $VTM->findValidToken($service);
        if ($token == false) {
            $module->emError("Could not retrieve valid access token for service $service");
            return array("status" => 0,
                "message" => "* Internal Access problem - please contact the REDCap team");
        }

        // Retrieve ID URL from the system settings
        $api_url = $module->getSystemSetting("url_to_id_api");

        return array("status" => 1,
            "token" => $token,
            "url" => $api_url);

    }

    // send the MRNs to the validity API
    function apiPost($pid, $mrns, $token, $url) {
        global $module ;
        // Use the STARR API to see if these MRNs are valid
        $body = array("mrns" => $mrns);
        $timeout = null;
        $content_type = 'application/json';
        $basic_auth_user = null;
        $headers = array("Authorization: Bearer " . $token);

        // Call the API to verify the MRN and retrieve data if valid
        $result = http_post($url, $body, $timeout, $content_type, $basic_auth_user, $headers);
        $this->emDebug(print_r($result,TRUE));
        if (is_null($result)) {
            $module->emError("Problem with API call to " . $url . " for project $pid");
            return array("status" => 0,
                "message" => "* Could not verify MRNs. Please contact REDCap team for help");
        } else {
            $returnData = json_decode($result, true);
            $this->emDebug(print_r($returnData,TRUE));
            $mrnInfo = $returnData["result"];
        }

        return array("status" => 1,
            "validatedMrns" => $mrnInfo);
    }

    /**
     * HOOK FUNCTION on SAVE
     * Check to see if the checkbox for Sync on Save is checked for an EM configuration,
     */
    public function redcap_save_record($project_id, $record = NULL,  $instrument,  $event_id,  $group_id = NULL,  $survey_hash = NULL,  $response_id = NULL, $repeat_instance)
    {

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

        $url = $this->getUrl('src/IntakeFormValidation.php') ;
        $this->emDebug($url) ;

        $script = <<<EOT
            <script>
            $(document).ready(function(){
            window.onload = function () {
                var origStopUpload = stopUpload;
                stopUpload = function(success,this_field,doc_id,doc_name,study_id,doc_size,event_id,download_page,delete_page,doc_id_hash,instance)
                {
                    // console.log("in stopUpload");
                    var newHtml = "<tr id='validation_message-tr'><td colspan='3' class='validationMsg'></td></tr>";
                    $("#validation_message-tr").replaceWith(newHtml);
                    aurl =  "$url"  + "&doc_id=" + doc_id;
                    // console.log( aurl );
                    $.ajax({
                        url: aurl ,
                        timeout: 60000000,
                        type: 'GET',
                        dataType: 'html',
                        success: function (response) {
                            // console.log('success in ajax callback');
                            // console.log(response);
                            var newHtml = "<tr id='validation_message-tr'><td colspan='3' class='validationMsg'>" + response + "</td></tr>";
                            $("#validation_message-tr").replaceWith(newHtml);
                        },
                        error: function (request, error) {
                            var newHtml = "<tr id='validation_message-tr'><td colspan='3' class='validationMsg'>Unable to validate uploaded file content. Server error: " + JSON.stringify(error) + "</td></tr>";
                            $("#validation_message-tr").replaceWith(newHtml);
                            console.log(error);
                        }
                    });
                    return origStopUpload(success,this_field,doc_id,doc_name,study_id,doc_size,event_id,download_page,delete_page,doc_id_hash,instance);
                }
            }});
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




}
