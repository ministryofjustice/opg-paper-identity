#! /usr/bin/env bash

awslocal dynamodb create-table \
  --table-name identity-verify \
  --attribute-definitions \
    AttributeName=id,AttributeType=S \
  --key-schema \
    AttributeName=id,KeyType=HASH \
  --provisioned-throughput ReadCapacityUnits=1000,WriteCapacityUnits=1000

sleep 5

awslocal dynamodb update-table \
    --table-name identity-verify \
    --attribute-definitions AttributeName=yotiSessionId,AttributeType=S \
    --global-secondary-index-updates \
        "[{\"Create\":{\"IndexName\": \"yotiSessionId-index\",\"KeySchema\":[{\"AttributeName\":\"yotiSessionId\",\"KeyType\":\"HASH\"}], \
        \"ProvisionedThroughput\": {\"ReadCapacityUnits\": 20, \"WriteCapacityUnits\": 10 }, \
        \"Projection\":{\"ProjectionType\":\"ALL\"}}}]"

awslocal secretsmanager create-secret --name local/paper-identity/yoti/certificate \
    --description "PEM certificate for authentication with Yoti API" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/yoti/sdk-client-id \
    --description "ID of Yoti client" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/experian-idiq/certificate \
    --description "Experian IIQ auth certificate" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/experian-idiq/certificate-key \
    --description "Experian IIQ auth certificate private key" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/experian-idiq/certificate-key-passphrase \
    --description "Experian IIQ auth certificate private key passphrase" \
    --secret-string "empty"
