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
    --secret-string "$(openssl genpkey -algorithm RSA -out - -pkeyopt rsa_keygen_bits:2048 -quiet)"

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

awslocal secretsmanager create-secret --name local/paper-identity/experian-crosscore/username \
    --description "Experian Crosscore Username" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/experian-crosscore/password \
    --description "Experian Crosscore Password" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/experian-crosscore/client-id \
    --description "Experian Crosscore ClientId" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/experian-crosscore/client-secret \
    --description "Experian Crosscore Client Secret" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/experian-crosscore/domain \
    --description "Experian Crosscore Assigned Domain" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/experian-crosscore/tenant-id \
    --description "Experian Crosscore Tenant ID" \
    --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/dwp/oauth-token-endpoint \
    --description "DWP authentication API" \
        --secret-string "empty"

awslocal secretsmanager create-secret --name local/paper-identity/dwp/citizen-match-endpoint \
    --description "DWP match api" \
        --secret-string "empty"

awslocal secretsmanager create-secret --name local//paper-identity/dwp/citizen-endpoint \
    --description "DWP citizen details api" \
        --secret-string "empty"

awslocal ssm put-parameter --name "service-availability" --type "String" --value '{"EXPERIAN":true,"NATIONAL_INSURANCE_NUMBER":true,"DRIVING_LICENCE":true,"PASSPORT":true,"POST_OFFICE":true}' --overwrite
