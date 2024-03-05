#! /usr/bin/env bash


awslocal dynamodb create-table \
  --table-name identity-verify \
  --attribute-definitions AttributeName=id,AttributeType=S \
  --key-schema AttributeName=id,KeyType=HASH \
  --provisioned-throughput ReadCapacityUnits=1000,WriteCapacityUnits=1000

# awslocal dynamodb create-table --table-name last-login-local --attribute-definitions AttributeName=email,AttributeType=S --key-schema AttributeName=email,KeyType=HASH --provisioned-throughput ReadCapacityUnits=1000,WriteCapacityUnits=1000
# awslocal dynamodb update-time-to-live --table-name events-dedupe --time-to-live-specification "Enabled=true, AttributeName=expires"


