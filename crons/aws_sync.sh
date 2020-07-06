#!/bin/bash
export AWS_DEFAULT_PROFILE="aws-web"
export AWS_CONFIG_FILE="/usr/home/digitalm/.aws/config"
export AWS_SHARED_CREDENTIALS_FILE="/usr/home/digitalm/.aws/credentials"
export LC_CTYPE=en_US.UTF-8
export LANG=en_US.UTF-8
export LANGUAGE=en_US
export LC_ALL=en_US.UTF-8

/usr/home/digitalm/bin/aws s3 sync /usr/home/digitalm/Sites/flames/shared/assets s3://amdflames/assets  --profile aws-web --quiet
/usr/home/digitalm/bin/aws s3 sync /usr/home/digitalm/Sites/flames/shared/newsp s3://amdflames/newsp  --profile aws-web



