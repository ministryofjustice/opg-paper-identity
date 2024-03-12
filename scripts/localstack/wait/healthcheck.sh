#!/usr/bin/env bash

# DynamoDB
tables=$(awslocal dynamodb list-tables)
echo $tables | grep '"identity-verify"' || exit 1