# aws-ec2-metrics-web-display
Get and display via web Amazon Web Services EC2 metrics (cpu, network bytes out)

Running at https://kwynn.com/t/9/10/cpu/

See detailed readmes in the doc folder, although as of 2020/07, those are older.  More recent updates below:

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
version of isAWS() doesn't work anymore.  
