<?php
namespace Stanford\IntakeForm;

use \REDCap;

global $module ;

$module->emDebug("***** I am in jobStatus Page****");
$fieldList = array('record_id', 'datetime_1','datetime_2','datetime_3','datetime_4','datetime_5','datetime_6','nero_gcp_name', 'irb_number', 'project_title', 'webauth_user', 'dpa_omop', 'dataset_name_omop', 'data_types', 'status') ;

$module->emDebug("*****username :" . $_GET["uname"]) ;

$module->emDebug("*****Global Username :" . $userid) ;
$sunetid = $_SERVER['REMOTE_USER'];
$module->emDebug("*****Global Sunet ID :" . $sunetid) ;

if(isset($_GET['uname']) && !empty($_GET['uname'])){
    $module->emDebug("Printing results for ".$_GET["uname"]." ***********") ;
    $include_logic = "[webauth_user]='" . $_GET["uname"] . "'" ;
} else {
    $module->emDebug("Printing results for scweber ***********") ;
    $include_logic = "[webauth_user]='scweber'" ;
}

$recordList = REDCap::getData('array', null, $fieldList, null,
    null, null, null, null, $include_logic);

$hideTutorial = sizeof($recordList) > 0 ? 'hidden' : '';
$hideDt = sizeof($recordList) === 0 ?  'hidden' : '';
$toggleBtnLabel = sizeof($recordList) > 0 ? 'Show' : 'Hide';

foreach($recordList as $key => $eventData) {
    foreach($eventData as $eventId => $record) {
        $module->emDebug(" Record Id ". $key . " event id ". $eventId) ;
        $module->emDebug($record) ;
        $module->emDebug("IRB ".$record->irb_number . " Title :" . $record["project_title"]) ;
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" />

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>

    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <style>
        dt {
            font-weight: bolder;
        }
        .h1, h2, h3, h4, h5 {
            color:darkslategrey;
            font-weight:lighter;
        }
        .card-title {
            background-color:rgb(131, 19, 16);
            color:white;
        }
        #jobsTable {
            width:100%;
        }
        #toggleTutorialBtn {
            height: 32px;
        }
        .hidden {
            display: none;
        }

    </style>
    <title>Clinical Data Dashboard</title>
    <script>
        function toggleTutorial() {
            if ($('#tutorial').hasClass('hidden')) {
                $('#tutorial').removeClass('hidden');
                $('#toggleTutorialBtn').text("Hide Tutorial")
            } else {
                $('#tutorial').addClass('hidden');
                $('#toggleTutorialBtn').text("Show Tutorial")
            }

        }
        $(document).ready(function() {
            $('#jobsTable').DataTable({
            });


        } );
    </script>
</head>
<body>
<nav class="navbar navbar-dark bg-white">
    <a class="navbar-brand" >
        <img height="50px" src="<?php echo $module->getUrl('images/rit_logo.png') ?>" class="d-inline-block align-bottom" alt="">
        <span class="h1 m-3" >Clinical Research Data Delivery on Demand</span>
    </a>
</nav>

<div class="col-8 mx-auto <?php echo $hideTutorial ?>"  id="tutorial" >
    <h2 > Welcome to Research IT's Data Delivery on Demand ("D3") service.
    </h2>
    <p></p>
    <p>The <a href="https://starr.stanford.edu/"><b>Sta</b>nford Medicine <b>R</b>esearch Data <b>R</b>epository</a>
        contains a wide range of clinical data for research purposes drawn from both hospitals. In addition to
        Electronic Medical Record (EMR) data we have an ever growing portfolio of clinical ancillary data, including
        DICOM, Philips Bedside Monitoring, as well as research datasets from clinical systems used in Oncology and Cardiology.
    </p>
    <p>
        In order to streamline the process of obtaining clinical data for research, we have established the D3 service
        to faciliate repeated downloads of data from well defined datasets. The specified dataset is delivered to your
        <a href="https://med.stanford.edu/nero.html">Nero</a> project as a <a href="https://cloud.google.com/bigquery">BigQuery</a> dataset.

    </p>
    <p>
        At this time our only service offering is OMOP, but we plan to expand this to other popular datasets.
    </p>
    <h3>Pre-requisites</h3>
    <p>There are three prerequisites to using D3: compliance, cohort definition, and data delivery destination.
    </p>
    <ol>
        <li><p>
            Compliance: if you will be requesting any PHI, you must have a valid IRB with associated
                <a href="http://med.stanford.edu/starr-tools/data-compliance/data-privacy-attestation.html">STARR Data Privacy Attestation</a>.
                <br/>If you are not requesting any PHI, you must still complete a <a href="https://med.stanford.edu/starr-omop/access.html">Data Privacy Attestation</a>.
            </p>
        </li>
        <li><p>
            Cohort Definition:    In order to use D3 to get an OMOP dataset you must supply us with a list of patients of interest to your research project.
            </p>
            <p>We currently support two methods of specifying patients: PersonIDs from our de-id OMOP, and Stanford MRNs.
                The process to gain access to Stanford's de-id OMOP is described on the <a href="https://med.stanford.edu/starr-omop/access.html">Stanford OMOP website</a>,
                and the process to download MRNs is described in this <a href="#starr-tools">quick-start guide</a>.
            </p>
        </li>
        <li><p>
            Data Delivery: If you do not yet have a Nero project, please contact the <a href="https://med.stanford.edu/nero.html">Nero</a> support team to get one.
            </p>
        </li>
    </ol>
    <h3>Getting Started</h3>

    <p>
        To get started, please <span style="font-size: larger">first review the documentation below,</span> then navigate to <a href="https://redcap.stanford.edu/plugins/gethelp/ric.php">this intake form</a> and fill it out.
    </p>
    <p>
        To trigger an automated download of OMOP, you must tick the box labeled "Custom / identified OMOP" in the
        question labeled "Please indicate which (if any) of these data types you are interested in"
    </p>
    <p>The remainder of this document explains the meaning of the questions that appear once you tick the OMOP checkbox.</p>
    <h4>How do you plan to identify your cohort of interest?</h4>

    <p>Options 1 and 2 are both variants on specifying your patients using Patient IDs from Stanford's de-id OMOP. In the first case
        you have already associated with each patient a pair of dates specific to that patient, and wish to trim the returned data to stay
        within the specified date ranges. For example, if the cohort is of surgery cases, you may only be interested in data 6 months prior and up to
        2 years after the surgery of interest. In this case, the file should have three columns, such that the first column is the patient ID,
        the second column the start date, and the third column the end date.</p>
    <p>
        Options 3 and 4 are similar to 1 and 2 respectively, but based on Stanford MRNs rather than de-id OMOP Person IDs.
        See below for a <a href="#starr-tools">quick start guide to obtaining Stanford MRNs</a>.
    </p>
    <p>
        In each of the first 4 options you are prompted to upload a file. Shortly after upload, you will see a message appear
        with the results of a data validation process. If there is bad data in the file you uploaded you can keep uploading
        new versions of the file until you are satisfied that your list of patients is good enough to proceed.
    </p>
    <p>
        Options 5 and 6 are not yet supported, and Option 7 is merely informational, advising you to follow the process of either gaining
        access to <a href="https://med.stanford.edu/starr-omop/access.html">de-id OMOP</a> or to the <a href="http://med.stanford.edu/starr-tools.html">STARR tools</a>.
    </p>
    <h4>Nero GCP Project</h4>
    <p>
    As there can be associated costs with using data on Google Big Query, you must provide your Nero project so we can deliver the data
        to a Google project supported by your research PTA.
    </p>
    <h4>Nero Dataset Name</h4>
    <p>
        Big Query dataset names consist of letters, numbers, and underscores and should be kept relatively short.
    </p>
    <p>
        You should specify a new name that is not yet in use in your project. In order to prevent data loss, if you re-use an existing dataset name
        we will append the current date as a suffix for the new dataset so your existing data is not overwritten.
    </p>
    <h4>OMOP Tables</h4>
    <p>
        All datasets will include Observation, Observation_period, and Fact tables; check the boxes for the additional tables of interest.
    </p>
    <h4>Protected Health Information (HIPAA Identifiers)</h4>
    <p>
        In order to receive identified data you must have a valid IRB research protocol
        with associated STARR <a href="http://med.stanford.edu/starr-tools/data-compliance/data-privacy-attestation.html">Data Privacy Attestation</a>
        that documents the PHI elements the IRB and Privacy Office have approved for use in your research study.
    </p>
    <p>
        The IRB protocol number is entered  higher up on the form, under the question "Is this research". If you are interested
        in PHI, you must respond "Yes" to the "Is this research" question and enter your IRB number.
    </p>
    <p>
        Stanford OMOP does not contain addresses, phone numbers, or other means of locating or contacting patients. If your
        intent is study recruitment, please connect with the <a href="http://med.stanford.edu/spectrum/b1_8_rec.html">Research Participation Team</a>
        by emailing <a href="mailto:engageparticipants@stanford.edu">engageparticipants@stanford.edu</a>.
    </p>
    <h4>OMOP DPA Number</h4>
    <p>
        If you are not requesting any PHI, you have the option of completing a self-signed Data Privacy Attestation
        as described in "Get access to STARR-OMOP-deid dataset" on <a href="https://med.stanford.edu/starr-omop/access.html">this page</a>.
        If you answered "No" to the "Is this research" question and left the IRB number blank, this question will pop up
        prompting you for your DPA number.
    </p>
    <h4> <a id="starr-tools"></a>Postscript: Using STARR Tools to get a list of Stanford MRNs</h4>
    <p>As noted above, in order to use identified data for research at Stanford, you must have a valid IRB protocol with associated
        STARR Data Privacy Attestation (DPA). Specifically, in order to use the STARR Tools to download a list of MRNs needed for self-service
        access to OMOP, you must have a DPA that documents your intent to use MRNs in your research. If you do not yet have an IRB with DPA,
        you can  <a href="http://med.stanford.edu/starr-tools/data-compliance/data-privacy-attestation.html">follow this process</a> to get one. </p>
    <p>
        Once you have an approved IRB with associated DPA allowing use of MRNs, the steps to obtain a list of MRNs are:
        <ol>
        <li>
            Log into the <a href="https://stride-service.stanford.edu/stride/web">STARR Cohort Discovery Tool</a>
        </li>
        <li>
            Define your cohort of interest by dragging constraints into the middle from the left hand side. Bear in mind that clinical data
            when reviewed for research purposes <a href="https://med.stanford.edu/starr-tools/data-inventory.html">can be surprisingly messy</a>, as clinical data is intended for use in clinical care,
            and people are extremely good at filtering out irrelevant information, normalizing inconsistent information,
            and drawing inferences when information is missing entirely. In general the approach used by the STARR Cohort Discovery Tool
            works reasonably well, but when using this tool don't be surprised if you find some patients you were not expecting
            and find you are missing others that you were expecting.
        </li>
        <li>
            Once you are satisfied with the number of patients matching your query, click the "Searches" icon in the upper left,
            and select 'Save Patients for Chart Review' from the menu that pops up
        </li>
        <li>
            A form will pop up prompting you to input your IRB number and specify the PHI you want. Don't forget to check
            the MRNs checkbox. Click 'Create Cohort'.
        </li>
        <li>
            Navigate to your newly created chart review. If it does not appear in the list simply refresh your browser and try again.
        </li>
        <li>
            In the upper right, there is a panel listing the various types of clinical data associated with your cohort. Tick the "Patient Info" box
            and click "Create Download Files"
        </li>
        <li>
            In a matter of minutes your file should become available for download. Using Excel or similar editor you can trim everything but the MRNs from the file,
            leaving you with a file that can be used to specify the patients for your custom OMOP dataset.
        </li>
    </ol>
    </p>
    <p></p>
    <hr/>
    <p></p>
</div>

<div id="dashboard" class="card col-11 mx-auto <?php echo $hideDt ?>" >
    <div class="card-body">
        <h5 class="card-title px-2 py-2" >My Clinical Research Datasets <button id="toggleTutorialBtn" class="btn btn-secondary float-right mt-n1" onclick="toggleTutorial()"><?php echo $toggleBtnLabel ?> Tutorial</button></h5>
        <div class="card-text">
            <table id="jobsTable" class="table table-striped table-bordered" >
                <thead>
                <tr>
                    <th>Job #</th>
                    <th>Requested On</th>
                    <th>Project</th>
                    <th>Data Type</th>
                    <th>Destination</th>
                    <th>Status</th>
                    <th>Updated On</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach($recordList as $key => $eventData) {
                    foreach($eventData as $eventId => $record) {
                        ?>
                        <tr>
                            <td><?php echo $record["record_id"] ?></td>
                            <td><?php echo $record["datetime_1"] ?></td>
                            <td><?php echo $record["project_title"] .' ('. (!empty($record["irb_number"])?'IRB# '.$record["irb_number"]:'DPA-'.$record["dpa_omop"]) .')' ?></td>
                            <td><?php echo 'OMOP' ?></td>
                            <td><?php echo $record["nero_gcp_name"] . ':' .$record["dataset_name_omop"] ?></td>
                            <td><?php echo $record["status"] ?></td>
                            <td><?php if ( ! empty($record["datetime_6"])) {echo $record["datetime_6"];}
                                else if (! empty($record["datetime_5"])) {echo $record["datetime_5"];}
                                else if (! empty($record["datetime_4"])) {echo $record["datetime_4"];}
                                else if (! empty($record["datetime_3"])) {echo $record["datetime_3"];}
                                else if (! empty($record["datetime_2"])) {echo $record["datetime_2"];}
                                ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </table>
        </div>
    </div>
</div>

<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>

</body>
</html>
