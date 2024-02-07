locals {
  account     = local.sandbox
  environment = lower(terraform.workspace)

  mandatory_moj_tags = {
    business-unit    = "OPG"
    application      = "github-template-repository"
    environment-name = local.environment
    owner            = "WebOps: opg-webops-community@digital.justice.gov.uk"
    is-production    = false
  }

  optional_tags = {
    infrastructure-support = "OPG Webops: opg-webops-community@digital.justice.gov.uk"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
