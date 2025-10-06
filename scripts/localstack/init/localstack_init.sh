#! /usr/bin/env bash

aws configure set cli_follow_urlparam false

awslocal dynamodb create-table \
  --table-name identity-verify \
  --attribute-definitions \
    AttributeName=id,AttributeType=S \
  --key-schema \
    AttributeName=id,KeyType=HASH \
  --provisioned-throughput ReadCapacityUnits=1000,WriteCapacityUnits=1000

sleep 5

awslocal dynamodb update-time-to-live --table-name identity-verify --time-to-live-specification "Enabled=true, AttributeName=ttl"

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

awslocal ssm put-parameter --name "service-availability" --type "String" --value '{"EXPERIAN":true,"NATIONAL_INSURANCE_NUMBER":true,"DRIVING_LICENCE":true,"PASSPORT":true,"POST_OFFICE":true}' --overwrite

awslocal secretsmanager create-secret --name local/paper-identity/dwp/oauth-client-secret \
    --description "DWP Oauth2 client secret" \
    --secret-string "clientsecret" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/dwp/oauth-client-id \
    --description "DWP Oauth2 client ID" \
    --secret-string "clientid" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/dwp/opg-certificate-bundle \
    --description "DWP OPG certificate bundle" \
    --secret-string "-----BEGIN OPENSSH PRIVATE KEY-----ThisIsntARealKeySoDontWorryvbmUAAAAEbm9uZQAAAADADADAAAAAMwAAAAtzc2gtZQ-----END OPENSSH PRIVATE KEY----" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/dwp/opg-certificate-private-key \
    --description "DWP OPG private key" \
    --secret-string "-----BEGIN OPENSSH PRIVATE KEY-----ThisIsntARealKeySoDontWorryvbmUAAAAEbm9uZQAAAADADADAAAAAMwAAAAtzc2gtZQ-----END OPENSSH PRIVATE KEY----" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/dwp/dwp-policy-id \
    --description "DWP Policy ID" \
    --secret-string "policy-id" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/dwp/dwp-context \
    --description "DWP Context" \
    --secret-string "context" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/hmpo/api-key \
    --description "HMPO X-API-Key" \
    --secret-string "X-API-Key-X-API-Key-X-API-Key-X-API-Key-X-API-Key-X-API-Key-X-API-Key" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/hmpo/user-agent \
    --description "HMPO User-Agent" \
    --secret-string "hmpo-opg-client" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/hmpo/auth-client-id \
    --description "HMPO clientId" \
    --secret-string "client-id" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/hmpo/auth-client-secret \
    --description "HMPO secret" \
    --secret-string "client-secret" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/hmpo/grant-type \
    --description "HMPO grantType" \
    --secret-string "grant-type" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/hmpo/opg-private-cert-key \
    --description "HMPO private cert key" \
    --secret-string "private-cert-key" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/hmpo/opg-private-cert \
    --description "HMPO private cert" \
    --secret-string "private-cert" \
    --region "eu-west-1"

awslocal secretsmanager create-secret --name local/paper-identity/hmpo/opg-private-cert-key-passphrase \
    --description "HMPO private cert key passphrase" \
    --secret-string "private-cert-key-passphrase" \
    --region "eu-west-1"