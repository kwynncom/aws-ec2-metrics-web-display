<?php

define('KWYNN_GET_AWS_INSTANCE_TYPE_URL', 'https://kwynn.com/t/9/10/cpu/utils/getInstanceType.php');

function getMaxCPUCreditFromInstanceType($tin) {
    switch($tin) {
	case 't3a.nano' : return 144;
    }
} // https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/burstable-credits-baseline-concepts.html