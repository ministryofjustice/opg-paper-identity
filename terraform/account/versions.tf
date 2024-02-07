terraform {

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "3.40.0"
    }
    github = {
      source  = "integrations/github"
      version = "4.11.0"
    }
  }
  required_version = ">= 1.1.0"
}
