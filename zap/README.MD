# Zap Scanning Through Cypress

## Purpose

This suite can be used for any Service that already has Cypress tests written for it.  It assumes that you're running your service in Docker Compose, and running Cypress via Docker.  It will by default run a Passive scan of the service whilst the Cypress tests are running.  You can enable an Active scan of your Service **THIS WILL POST DATA SO DON'T RUN AGAINST A LIVE SERVICE** with some sensible defaults, you can configure these further.  Once the scan(s) are complete two reports will be generated, a human readable HTML Report and machine readable Sarif JSON to pass to other tooling.


## Requirements

- Service Stack running in a Docker Compose managed environment
- Cypress tests running Dockerised against this Service


## Usage

You can either run this with a Makefile target that will stand up your service and run the required scripts, or you can choose you orchestrate it yourself via docker compose directly.

#### Assumptions
- Your Cypress Docker Compose service is called `cypress`
- Your primary `docker-compose.yml` lives in the root of your project
- You'll put all your Zap related config in a directory called `zap`

### Makefile
A Makefile snippet with the required variables is provided below, this will need customising for your service.  If you don't want to use a Makefile you'll need to run through each of the steps below with whatever tooling you're using.

```export ACTIVE_SCAN ?= false
export ACTIVE_SCAN_TIMEOUT ?= 300
export SERVICE_NAME ?= Service
export SCAN_URL = ${SCAN_URL}
cypress-zap:
	docker compose -f docker-compose.yml -f zap/docker-compose.zap.yml run --rm cypress # Start your service stack and run cypress tests
	docker compose -f docker-compose.yml -f zap/docker-compose.zap.yml exec -u root zap-proxy bash -c "apt update && apt install -y jq" # As root install jq into the running zap container
	docker compose -f docker-compose.yml -f zap/docker-compose.zap.yml exec zap-proxy bash -c "/zap/wrk/scan.sh" # Run the script
	docker compose -f docker-compose.yml -f zap/docker-compose.zap.yml down # Stop the stack
```

### Reports 
Two reports will be generated in the `zap/` directory.

#### HTML Report
Default report will be `ServiceReport.html` with supporting CSS/Images in a directory called `ServiceReport`.

#### Sarif JSON
Machine readable json report called `ServiceReport.json`

### Default Configuration
Either via the Makefile or directly via Docker Compose there are some variables exposed that allow you configure the default scan.

- `ACTIVE_SCAN` - (Default `false`) Whether or not to perform an active scan of your service either `true` or `false`
- `ACTIVE_SCAN_TIMEOUT` - (Default `300`) The maximum time in seconds the activate scan will run for before being shut down
- `SERVICE_NAME` - (Default `Service`) The name of the service
- `SCAN_URL` - The Docker Compose endpoint for your service, only required for the Active Scan, needs to be set either in Makefile or as an Environment Variable.

### Advanced Configuration
There are some advanced configurations that require you to dig into the result of previous scans using the Zap API and get some config that you might want to change, and then edit the execution script yourself.

#### Active Scanners
You can disable specific rulesets of the Active scan to improve the scan speed if those rulesets don't apply to your service, ie you aren't using a SQL Database, you aren't using Apache, etc.

You can find a list of the current available scanners by docker execing onto the running zap-proxy instance and calling the `/JSON/ascan/view/scanners/` endpoint.

`docker exec {CONTAINER ID} curl localhost:8080/JSON/ascan/view/scanners/ | jq`

Alternatively you can run the scan in full once, and then check the `Plugin id` in the reports to determine which scanners to disable.

Then you can add the ids to the `disable_scanner_rules` function in `scan.sh`


#### False Positives

There is also an ability to filter out false positives that may have been detected because of the  nature of running your service as a small component within Docker Compose.   The method implemented here in the `filter_false_positives` function is to Globally set at Alert Filter, which sets the alert level of an issue result to `-1` or false positive to complete exclude all results of that type from the reporting.   

If you want to be more specific you can add URL regex to target specific instance of issues, so new issues of this type will be picked up by the reports.

Details of these can be found in the reports, alerts are referenced as `Plugin id`.


## Resources

All the interactions with Zap in the `scan.sh` script are performed by interacting with Zap API using cURL, documentation for the API is [here](https://www.zaproxy.org/docs/api/#api-catalogue)

