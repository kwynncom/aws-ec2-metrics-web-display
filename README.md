# aws-ec2-metrics-web-display
Get and display via web Amazon Web Services EC2 metrics (cpu, network bytes out)

Running at https://kwynn.com/t/9/10/cpu/
****
2022/07/16 note: looks like upon a new instance type, you have to update get.php and awsConfig.php AND 
aws_cpu.instances and set a field max_possible_cpu to the max credits (144 for t3a.nano and 288 for t3a.micro)
****
See detailed readmes in the doc folder, although as of 2020/07, those are older.  More recent updates below:
********
2022/01/19

I removed reference to the "creds" database.  I created a simple JSON file with an iid for instance ID.
sudo apt install awscli
create a config and credentials file as described here:
https://docs.aws.amazon.com/cli/latest/userguide/cli-configure-files.html
The config file won't easily work for the web / the www-data user, so I had to create a file with a very 
specific format for the info
The given user needs 2 permissions:

{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "VisualEditor0",
            "Effect": "Allow",
            "Action": "ec2:DescribeInstances",
            "Resource": "*"
        }
    ]
}

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


******
11:58pm

I check the AWS billing console for the number of metric requests I'm doing.  As of the very end of the day, my time, on August 11, I'd made 
1,058 checks.  That's out of 1 million for free.  So I'm using 0.11% of my quota and we're over 1/3rd through the month.  The cron job for 
my "sysadmin-email-alerts-for-aws" project runs every 30 minutes.  Then there are web checks by myself or anyone.  The checks will only run every 
5 minutes at most, so that still would not get anywhere near the quota.

The 5 minutes is the "regulated" get in awsMRegGet()--AWS metrics regulated get.

Note that most checks are 2 checks--one for CPU and one for net.  But I still get nowhere near the quota.  

Previously the recursive call could be another check, but that is unlikely to be called, given the frequent cron job calls.

I'm unclear how often the billing console updates, but it doesn't matter so much in this case, given that I'm way, way away from the quota.



2020/08/11 11:48pm EDT (GMT -4)

I raised the network level to 3.98 GB / month before showing a separate row in the filtered output.  The previous setting of 0.98 was way, way 
too cluttered.

I also created getAWSNetMonth() to confirm the weighted average calcuation of GB / month.  The new function checks for the whole month directly 
rather than by calculation.  

Moments ago, the calculation shows 0.6 (rounded to the 0.1 place) and the check shows 0.76.  That's close enough for my purposes.  

Given that the calculation is close enough, the new function is inert, in that it's not being called by anything.  It's also protected against 
web calls by kwcod.php ("Kwynn's CLI or die").

I should add that the new function doesn't echo or return anything.  I'm using the NetBeans debugger on the "return" line to read the data.  
In fact, I put the return line there because the debugger needs a statement to attach a breakpoint to.  You may also see "$x = 2;" sometimes in my 
code.  That's there for the same purpose--to give a breakpoint attachment.  

The $x thing is all well and good until I'm dealing with coordinates (x,y).  Then it really messes me up.  I need to create some sort of null 
statement, or think of something close enough.

***********
9:53pm

Although the fields are not needed, I also added the AWS username and the date the creds were created to the database.  Although you can 
eventually figure out the AWS username from the aws_access_key_id, it's esier to know the name.  When I say username, I mean an IAM user 
that only has very limited programmatic access, not a "console" user and certainly not a "root" user.  


2020/07/26 9:31pm

I reworked the creds.  

In getCreds.php and getAWSCreds(), the label needed is arbitrary but it has to match the creds/creds database entry, as below.  The minimal 
contents of a non-AWS machine are

{
    "type" : "aws_cpu_creds_2020_1_series",
    "creds" : "aws_access_key_id = AKIA... aws_secret_access_key = tkHX...",
    "iid" : "i-069c...",
    "reg" : "us-east-1"
}

Note 2024/04: https://aws.amazon.com/blogs/security/defense-in-depth-open-firewalls-reverse-proxies-ssrf-vulnerabilities-ec2-instance-metadata-service/

No creds are needed in the aws_cpu database.  No creds are needed anywhere on an AWS system because the instance can "figure out" its instance ID and 
region through the IMDS queries.

My AWS EC2 instance (kwynn.com) is getting its permission through the "IAM role" that it runs under.  The "IAM role" and my separately created user 
for my local machine both need this policy, or something very similar:

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

A local (non AWS) machine needs both that policy and something similar to this:

{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "VisualEditor0",
            "Effect": "Allow",
            "Action": "ec2:DescribeInstances",
            "Resource": "*"
        }
    ]
}

That's what allows the local machine to get the instance type (such as t3a.nano).  I think I'm getting AWS account ID (billing account) from that, too.


*****
same day, 7:45pm

I'm removing the "arch" files because I am seeing "used" functions in NetBeans that are not in fact used.  


Update: 2020/07/05 4:43pm EDT / GMT -4 / New York, Atlanta

Looks like I'm going to disable all the async stuff and do something *much* simpler.  I'm glad to have experimented with the async stuff because 
I may need that at some point, but it got out of hand.  


******
Update: 06/22 11:28pm (next day relative to below)

*NOW* I am nearly certain that my async process FINALLY works consistently.

Those "final" / working changes were made several hours and commits ago.  In this latest commit, I am fleshing out the function:
get/get.php/public static function aws_cpu::cliGet

I am about to add a new project to set alerts for Ubuntu updates needed, AWS metrics, disk space, etc.  I needed to change cliGet to make that easier.

*****
Update: same day, 11:36pm - I think it works now.


****
Update: same day as below, 9:52pm

I'm already cleaning up some of my false starts with the new but failed async attempt.

*****
Update: 2020/06/21 9:31pm EDT / GMT -4 / America/New_York.  

I'm going to save this version.  That's part of what GitHub is for.  This is what is live now, but my process control stuff is turned off for now.  That is, 
my intent is to display the latest stored information and then have JavaScript fetch the latest asynchronously.  That has sort of worked in the past, but 
it never really worked right.  Worse yet, given that I'm running a separate exec, debugging is difficult.  I tried to solve this with FIFOs, but that isn't working
well either due to complicated dependencies around blocking and whatnot.  

So I have a new idea on how to get it to work.  Hopefully it will come soon.

Put another way, the process control stuff is a mess right now.

I also dealt with some issues around my upgrade of kwynn.com from Ubuntu 18.04 to 20.04.  I also moved it from t2.nano to t3a.nano.  One surprise was that my previous 
version of is[]AWS() doesn't work anymore.  
