# Starr Data Delivery on Demand
This REDCap External Module presents a customer facing dashboard that shows
which datasets they have requested, what the delivery status is, and offers new users a tutorial
on how to help themselves to data from Research IT.  In addition to the customer-facing
tutorial/dashboard there is also a hook that helps a customer successfully fill in the
request form, offering validation feedback on the fly.

The tutorial is presented to new users with no outstanding requests.  Repeat customers
are able to access the tutorial, but their initial view is of their requests, both active and completed.

The STARR Core CWL pipeline has associated APIs and automation hooks that update the status and message fields
in this project. The pipeline polls REDCap PID 22082 ("RIC Intake 2021") looking for new complete requests.
When a new request
is found, the information in it is used to launch a custom OMOP dataset pipeline. The initial
release only supports creating OMOP subsets for lists of patients.

The pipeline updates the status and messages fields as it runs
, setting timestamps as appropriate in each of datetime_1 through datetime_4, until
it reaches the manual QA stage. At that point progress pauses.  Then once QA is complete,
the STARR team member goes into the REDCap record and manually updates the status code,
changing it from 4 to 5, and pressing the "Now" button next to the datetime field for "Datetime QA Complete" (datetime_5). The pipeline is also monitoring for this transition, so at some point
after the QA status has been updated, an automated delivery attempt will be made.
Once the attempt completes, the pipeline changes the status code from 5 to 6,
inserts the current timestamp into datetime_6, and appends a suitable status message
to the message field.

If at any point the pipeline encounters an unrecoverable error, it updates the status field to
88 rather than to the next status code in the sequence.  The timestamp corresponding to the
current pipeline stage is also updated with the time the error was encountered.

Current status codes:

    1, Request Received
    2, Process Started
    3, Cohort Validation
    4, Dataset Prepared and Awaiting QA
    5, QA Complete
    6, Data Delivery
    88, Error
