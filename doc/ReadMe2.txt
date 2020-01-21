2020/01/21 3:59pm - I'm likely going to change this to #2 and create another readme.

11/09 - change log at bottom
2019/11/08 4:29pm beginning this doc, EST, GMT -5, Kwynn Buess
This code is running at https://kwynn.com/t/9/10/cpu/

CONTENTS

File overview
The AWS CLI commands themselves
Narration of process flow

******************
FILE OVERVIEW, roughly in order of execution or what is central.

A lucky 13 files:


* THIS FILE

* index.php - Beginning of execution, both for web and CLI.
* dao       - Data Access Object - the MongoDB I/O

GET - AWS INPUT
* get(.php) - This has the actual awscli commands to get data.
* parse     - Processes the awscli results


UTILS (folder) - Stuff that is necessary but would be clutter in the main files.  


AWS SPECIFIC UTILS

* getawsacctid.php - I made this standalone in the sense that it has conditions under which it will run separately.  
This file is all about various ways to get the AWS Account ID.  

* machineInfo.php - determining whether the script is running on AWS or not.  Also gets security credentials if need be.


OTHER UTILS

* utils.php - Small functions that are needed but would be clutter in any other file.
* kwutils - This is a general file I use for several projects.  This is now a "hard Linux file link," which is another story.  
* testMode - logic for whether the script is running live / normally or whether we want to do something different for testing 
purposes.
       

OUTPUT (to screen / HTML)

* output.php - Generates HTML
* template.php - mostly an HTML file
* filterOutput - turns the first round of data into consolidated data

************************
AWS CLI COMMANDS

Note that to run the command you must have creds set up with either export (environment variables) or other means.  See
https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-configure.html

Note that you have to either make the command one line or copy and paste it as 2 lines when bash gives you the > prompt you 
paste the second:

export AWS_ACCESS_KEY_ID=AKIA...
export AWS_SECRET_ACCESS_KEY=4dm09/5di8...
/usr/bin/aws cloudwatch get-metric-statistics --metric-name NetworkOut --namespace AWS/EC2 --statistics Sum --period 3600 \
 --start-time 2019-10-29T19:15:00Z --end-time 2019-10-29T22:14:00Z --dimensions Name=InstanceId,Value=i-12345678 --region us-east-1

from "utils/utils.php":

function getTheCmd($reg, $iid, $begin, $end, $per, $metric, $stat) {
    $cmd  = "/usr/bin/aws cloudwatch get-metric-statistics --metric-name $metric --namespace AWS/EC2 ";
    $cmd .= "--statistics $stat ";
    $cmd .= "--dimensions Name=InstanceId,Value=$iid --region $reg ";
    $cmd .= " --start-time $begin --end-time $end ";
    $cmd .= "--period $per ";
    
    return $cmd;
} // See https://docs.aws.amazon.com/cli/latest/reference/cloudwatch/get-metric-statistics.html

The metric in our case is cpu balance and network bytes-out to the internet / out of AWS.
In theses cases, a statistic can be requested as a minimum or a sum.  We want minimum cpu over a period and the sum of network 
bytes.  $iid is Amazon instance ID, something like i-1234abc.  That's the designator of the virtual machine.  Presumably it's 
unique througout AWS or at least through a region or availabiity zone.

$reg is Amazon region such as us-east-1 (northern Virginia)

Note the $begin and $end time must be specific formats

/usr/bin/aws cloudwatch get-metric-statistics --metric-name NetworkOut --namespace AWS/EC2 --statistics Sum --period 3600 \
 --start-time 2019-10-29T19:15:00Z --end-time 2019-10-29T22:14:00Z --dimensions Name=InstanceId,Value=i-12345678 --region us-east-1

OUTPUT:

{
    "Label": "NetworkOut",
    "Datapoints": [
        {
            "Timestamp": "2019-10-29T20:15:00Z",
            "Sum": 1145822.0,
            "Unit": "Bytes"
        },
        {
            "Timestamp": "2019-10-29T19:15:00Z",
            "Sum": 1491344.0,
            "Unit": "Bytes"
        },
        {
            "Timestamp": "2019-10-29T21:15:00Z",
            "Sum": 1223332.0,
            "Unit": "Bytes"
        }
    ]
}

CPU:
/usr/bin/aws cloudwatch get-metric-statistics --metric-name CPUCreditBalance --namespace AWS/EC2 --statistics Minimum --period 86400  --start-time 2019-10-28T20:15:42Z --end-time 2019-11-01T20:10:42Z --dimensions Name=InstanceId,Value=i-12345678 --region us-east-1

{
    "Label": "CPUCreditBalance",
    "Datapoints": [
        {
            "Timestamp": "2019-10-29T20:15:00Z",
            "Minimum": 71.746636,
            "Unit": "Count"
        },
        {
            "Timestamp": "2019-10-30T20:15:00Z",
            "Minimum": 71.856397,
            "Unit": "Count"
        },
        {
            "Timestamp": "2019-10-28T20:15:00Z",
            "Minimum": 72.0,
            "Unit": "Count"
        },
        {
            "Timestamp": "2019-10-31T20:15:00Z",
            "Minimum": 72.0,
            "Unit": "Count"
        }
    ]
}

The parse.php file parses that JSON

*********************
EXECUTION NARRATION


awsMRegGet - aws metrics regulated get

aws_cpu::awsMRegGet - In other projects I have "regulated get" (regget) and "real get."  Regulated means that it won't execute 
too often.  There are checks on whether to execute before a "real" get.  Get in this case is running AWS CLI commands.

At the moment regGet won't call the actual get doCmds1() unless either 5 minutes has passed or we're in test mode.  This 
prevents too much abuse on the system.  That's the "regulated" part.

I'll come back to the cpuRegGet logic.  It calls:

aws_cpu constants:

    const sind     = 86400; // seconds in a day
    const minInterval = 1200;  // always run at least these seconds of data
    const minDays  = self::minInterval / self::sind; // same as above in days
    const maxIDays = 12; // If running for more than n days of data, break it into 12 day periods
    const maxPer   = self::maxIDays * self::sind; // same in seconds
    const minPer   = 300; // minimum period as in if you run 1200 minutes end - start interval you'll run 4 periods === 1200 / 300
    const defaultDays = 30; // if never run before, run n days
    const rerunAtCPU = 71.98; 

I chose the minInterval from running dozens of commands with various start and end times and such.  I found that you couldn't 
be guaranteed that the command gives you any data with a 5 minute period unless you went 20 minutes back.  This may have been 
early 2018, so that's probably changed.  

1 minute data seems pointless at least for most purposes, and I'm not sure it existed when I was experimenting.

I chose max days due to various storage times.  1-minute data only lasts 15 days in case we want to drill down.  It also had to 
do with how many periods you get to include in various multiples of 30 days and such.  

I do a rerun because the CPU might be down over a "long" interval; then we want to run again with the min interval to check the 
lastest number.

I chose to multiply by 2/3 to make sure I had at least 2 intervals, although they are uneven.  AWS will do the "ceil()" / ceiling 
function, bascially.  If told to run 2/3 of an interview it will run one interval at 2/3 and the other is the rest--
if you want to get 18 hours worth of data, it will run 12 then 6.  I also subtract a bit to make sure I really get all the 
data with no rounding issues.

I unset() vars when I'm done with them because it makes reading the debugger output much easier.

I save all my params to the database before I attempt execution.  I need to know about failed attempts.

    $rarr['begin_ts'] = $beginTS; unset($beginTS); // result array
    $rarr['end_iso'] = $end; unset($end);  // iso as in 'c' time
    $rarr['end_exec_ts'] = $ts; unset($ts); // end of execution, which is just set to the first time() / now the function ran
    $rarr['per_interval_s']    = $per; // period such as 300
    $rarr['per_interval_days'] = $per / self::sind; unset($per);
    $rarr['begin_iso'] = $begin; unset($begin);
    $rarr['interval_days'] = getDaysDBVal($days); unset($days); // getDaysDBVal makes it a useful number rather than 3.3038582329284
    $rarr['exec_n'] = php_uname('n'); // machine name that is running it such as themorelitysmeanmachine
    $rarr['cmd_seq']  = $dao->getSeq('awscmdset'); // 1, 2, 3, 4, 5, ...
    $rarr['status'] = 'pre-Fetch'; // fetch or get or run command
    $dao->put($rarr);

PARSE

parse.php/parseAWSMetric() parses the JSON output of the aws command.  

I put the minimum possible timestamp in 2016 because data only goes back 455 days, so I'm giving that plenty of room.

https://docs.aws.amazon.com/AmazonCloudWatch/latest/monitoring/cloudwatch_concepts.html

kwas($count, 'parse fail count CPUBal'); // Kwynn's assert kwas() - either true or exception

I've gone to great lengths with the minimum interval and period to make sure I get data - this is the final check that there 
is some data.

FILTER / OUTPUT

output/filterOutuput.php/filterOutput()

I'm starting from oldest to newest and combining rows where cpu does not vary much.  I am calculated the average of the network 
usage over these combined rows.  The average is in GB / month.  

***************
SECURITY NOTES

Removing references to my dev machine name. [Update: later removing references to kwynn.com's instance id.]

************
SELF-CRITIQUE / FUTURE WORK

More of this should be in classes even if all the methods are static. That is, I have a bunch of loose functions polluting 
the namespace.  These functions could be in classes.


*********************
OTHER NOTES

Note that date('c') is not precisely what AWS docs show, but it works.  'c' format is 
2004-02-12T15:19:21+00:00 
versus
2019-10-29T22:14:00Z

See https://www.php.net/manual/en/function.date.php 

**********
CHANGES - CHANGE LOG

11/09 10:14pm - starting several changes

More code to identify my local machine or AWS and using different creds depending on the answer.  If it's my machine--
local or AWS--use my creds. Otherwise use theMorelity's.

It would appear I left out iid (AWS EC2 instance ID / VM ID) and region from the database.  Fixing that.

I am abstracting "get.php" some more to allow a simplified by-hand command line version.

2019/11/08, ca 10pm EST was the first morelity version
