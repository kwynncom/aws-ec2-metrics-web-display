# aws-ec2-metrics-web-display
Get and display via web Amazon Web Services EC2 metrics (cpu, network bytes out)

See detailed readmes in the doc folder.

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
