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
        \"ProvisionedThroughput\": {\"ReadCapacityUnits\": 20, \"WriteCapacityUnits\": 10 }, \
        \"Projection\":{\"ProjectionType\":\"ALL\"}}}]"

sleep 5

awslocal dynamodb update-table \
    --table-name identity-verify \
    --attribute-definitions AttributeName=id_number,AttributeType=S \
    --global-secondary-index-updates \
        "[{\"Create\":{\"IndexName\": \"id_number-index\",\"KeySchema\":[{\"AttributeName\":\"id_number\",\"KeyType\":\"HASH\"}], \
        \"ProvisionedThroughput\": {\"ReadCapacityUnits\": 20, \"WriteCapacityUnits\": 10 }, \
        \"Projection\":{\"ProjectionType\":\"ALL\"}}}]"

sleep 5

awslocal dynamodb create-table \
  --table-name cases \
  --attribute-definitions \
    AttributeName=id,AttributeType=S \
  --key-schema \
    AttributeName=id,KeyType=HASH \
  --provisioned-throughput ReadCapacityUnits=1000,WriteCapacityUnits=1000
