#! /usr/bin/env bash


awslocal dynamodb create-table \
  --table-name identity-verify \
  --attribute-definitions \
    AttributeName=id,AttributeType=N \
  --key-schema \
    AttributeName=id,KeyType=HASH \
  --provisioned-throughput ReadCapacityUnits=1000,WriteCapacityUnits=1000

awslocal dynamodb update-table \
    --table-name identity-verify \
    --attribute-definitions AttributeName=name,AttributeType=S \
    --global-secondary-index-updates \
        "[{\"Create\":{\"IndexName\": \"name-index\",\"KeySchema\":[{\"AttributeName\":\"name\",\"KeyType\":\"HASH\"}], \
        \"ProvisionedThroughput\": {\"ReadCapacityUnits\": 10, \"WriteCapacityUnits\": 5 }, \
        \"Projection\":{\"ProjectionType\":\"ALL\"}}}]"

awslocal dynamodb update-table \
    --table-name identity-verify \
    --attribute-definitions AttributeName=id_number,AttributeType=S \
    --global-secondary-index-updates \
        "[{\"Create\":{\"IndexName\": \"id_number-index\",\"KeySchema\":[{\"AttributeName\":\"name\",\"KeyType\":\"HASH\"}], \
        \"ProvisionedThroughput\": {\"ReadCapacityUnits\": 10, \"WriteCapacityUnits\": 5 }, \
        \"Projection\":{\"ProjectionType\":\"ALL\"}}}]"

# awslocal dynamodb create-table --table-name last-login-local --attribute-definitions AttributeName=email,AttributeType=S --key-schema AttributeName=email,KeyType=HASH --provisioned-throughput ReadCapacityUnits=1000,WriteCapacityUnits=1000
# awslocal dynamodb update-time-to-live --table-name events-dedupe --time-to-live-specification "Enabled=true, AttributeName=expires"


