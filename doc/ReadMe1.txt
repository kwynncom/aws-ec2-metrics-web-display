This is a web application to get and display Amazon Web Services (AWS) EC2 CPU credit 
balances, outgoing network usage, and disk usage.

2020/01/21 4:00pm EST (GMT -5, America/New_York timezone)

A version of this is running at https://kwynn.com/t/9/10/cpu/

Right now that version is behind this.

ReadMe2.txt with dates in November, 2019 was meant for my (potential, sometimes) apprentices.  This is written more 
with posting to GitHub in mind.

With that said, I'm in more of a hurry to get this posted to GitHub rather than make any doc or anything else more perfect.  I'm on a 
roll with GitHub posts, so I want to keep that going.

ASSUMPTIONS / PREREQUISITE SOFTWARE

* You need AWS CLI (command line interface)

https://aws.amazon.com/cli/

$ sudo apt install awscli
[...]
awscli is already the newest version (1.14.44-1ubuntu1).  [ for reference as of 2020/01/21, Ubuntu 18.04 ]

* If you are running this code on EC2, you have the option of giving the role proper permissions.  This script works remotely--off 
ec2--in which case the relevant aws users need proper permissions.  Specifically, the permission needed is

{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "VisualEditor0",
            "Effect": "Allow",
            "Action": [
                "cloudwatch:GetMetricStatistics"
            ],
            "Resource": "*"
        }
    ]
}

* You need MongoDB and MDB-PHP installed by composer (or else adapt this to how you have it installed)

* In theory, if you are running this on EC2, you do not need the following, but I am not at all certain that I removed this 
requirement for running on EC2.  Given that a remote aws cli client doesn't have an easy way to know what instance you're 
querying, and you might want to override default AWS CLI security creds, I manually installed the following in MongoDB 
database aws_cpu and the "config" collection.  Not all of the fields are used; some are for your own reference:

{
    "_id" : ObjectId("1abcd..."),
    "creds" : "aws_access_key_id = AKIA... aws_secret_access_key = CB/x/ABC...",
    "creds_created" : "2019-11-03 23:36 EST",
    "creds_user" : "the-morelity-2-cpu-metric-itself-1",
    "creds_policy_action" : "cloudwatch:GetMetricStatistics",
    "iid" : "i-12345678",
    "reg" : "us-east-1",
    "name" : "aws_cpu_creds_2_2019_11_themorelity_1"
}

REGARDING "PCONTROL"

I wrote the process control stuff after the previous readme.  Fetching the CPU balance and network output can take something like 
2 seconds.  Now the system immediately displays what it fetched previously and then fetches the new data asychronously.  

If 2 calls come within aws_cpu_pcontrol::timeout (currently 30s), the system will not make a new call.  This is in part due to my 
crashing my session several times.  More on that below.

Note that you can't easily use PHP process control functions in "web" mode.  It is not at all obvious that they won't work, but 
reading up on the situation shows they won't work, even if you seemingly enable them.  I found a decent solution to that problem.

Part of the idea with the async processes is to have crontab check the balances every 30 - 45 minutes.  Then when a user 
checks via the web, there is recent data displayed immediately.


FUTURE WORK / TO DO

I haven't tested the latest version running on EC2, only from my local machine querying EC2.  At the moment I feel it's 
more important to post this to GitHub.  

I need to look into whether you need the database entry above on ec2 and fix it if you do.

After I wrote this, I found that the following gets the EC2 metadata more easily.  I should integrate this:

$ /usr/bin/wget -q -O - http://169.254.169.254/latest/dynamic/instance-identity/document

I should probably write some notes on installing MongoDB with MongoDB's ppa and such.

I have multiple "magic numbers" that are based around 72 CPU credits, for t2.nano instances and perhaps others.  I need to at least
consolidate those numbers and then calculate them from the instance info.

Clean up the process control process (sic).  

Document the process control process.

A couple of changes are needed to my running get.php directly:

1. I need to convert from local time to GMT because AWS runs on GMT.  
2. I need to calculate a default period just like I do for the whole web version.  Otherwise you get a message to the effect of 
"too many periods" if you try to get a month of data.

In hindsight, I don't like how I do the HTML in template.php: in a string.  Change that.


MOTIVATION FOR THIS PROJECT / "RESULTS"

kwynn.com's t2.nano instance almost never goes below 70 credits out of a max of 72.  Right now I'm looking at a minimum of 71.42
from the 30 days starting 12/23/2019.  I don't think I've seen lower than 70 since I've run this, although I have seen lower 
than 71, I think.  (I think I have this data still in my database.  Maybe I'll look / display it one day.)

Over the years I've known I don't have a CPU issue based on the AWS account web displays.  However, I'm running an application 
that does (basic) image processing, and I may expand that.  I'm concerend about its cpu, net, and disk usage.  So I wanted 
this working before I heavily delved into that.

Then, of course, this took on a life of its own.


INFINITE LOOPS AND SESSION CRASHES

Given that it's so 1990s, some of you might be amusued to know that my first attempts a process control / the asynchronous call 
resulted in an ininite loop that crashed my system to the point of needing to turn the power off.  

I made the silly mistake of differentiating the web call from the async cli call by something not existing rather than existing.  
Specifically, I forgot that $argv and $argc need to be "global" within a function.  I was checking for their existence and assuming 
I'd see it.  Thus, I was spinning off many processes a second.  Even a modern OS apparently can't stand up to that.
