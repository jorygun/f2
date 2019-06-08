#!/bin/bash
export AWS_CONFIG_FILE="/usr/home/digitalm/.aws/config"
/usr/home/digitalm/bin/aws s3 sync /usr/home/digitalm/public_html/amdflames.org/assets s3://flames_backups/assets  --profile aws-web

