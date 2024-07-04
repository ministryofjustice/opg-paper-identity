#! /usr/bin/env bash

awslocal dynamodb create-table \
  --table-name identity-verify \
  --attribute-definitions \
    AttributeName=id,AttributeType=S \
  --key-schema \
    AttributeName=id,KeyType=HASH \
  --provisioned-throughput ReadCapacityUnits=1000,WriteCapacityUnits=1000

awslocal secretsmanager create-secret --name local/paper-identity/yoti/certificate \
    --description "PEM certificate for authentication with Yoti API" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/yoti/sdk-client-id \
    --description "ID of Yoti client" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/yoti/public-key \
    --region "eu-west-1" \
    --description "Local dev public key" \
    --secret-string file:///tmp/public_key.pem

awslocal secretsmanager create-secret --name local/paper-identity/yoti/private-key \
    --region "eu-west-1" \
    --description "Local dev private key" \
    --secret-string file:///tmp/private_key.pem
