# Primemover

## Documentation

* [Infrastructure](/docs/infrastructure.md)
* [Environment setup](/docs/setup.md)
* [Authentication & Authorization](/docs/authentication.md)
* [Routes](/docs/routes.md)

## Use cases
### Analytics
1. Fetch Jobs/Queues of specific crawlers with `https://url.to.api/api/v1/crawlers/`
   
    Fetch/parse/combine job data for one or several crawler_ids for analysis. 
   
    Implemented in `primemoveR::get_jobs.R`
   

2. Get all agents `https://url.to.api/api/v1/agents/`
3. Get all configurations `https://url.to.api/api/v1/configurations/`
4. Get all proxies `https://url.to.api/api/v1/proxies/`

### Configuration, initialize experiments/bots
1. Get queues not processed through `primemover_py`
   
    ```GET /queues-unprocessed```
---
2. Set `reviewed` flag to 1 for queues processed with success through `primemover_py`
   
   Action:
   ```PUT /queues/<queue_id>```
   Data to send: 
   ```{'reviewed':1}```
---
3. Set `active` flag to 0 to avoid immediate start of queues

   Action:
   ```PUT /queues/<queue_id>```

   Data to send:
   ```{'active':0}```
---
4. Create new empty experiment
   
   Action:
   ```POST /experiments```
---
5. Add preferences to experiment
   
   Action:
   ```POST /experiment/<experiment_id>```
---
6. Get current status of agent. Former to get current Multilogin ID.
   
   Action:
   ```GET /agents/<agent_id>```
---
7. Get current status of crawler.
   
   Action:
   ```GET /crawlers/<crawler_id>```
---
8. Get current status of proxy.
   
   Action:
   ```GET /proxies/<proxy_id>```
---
9. Get current status of configuration.
   
   Action:
   ```GET /configurations/<configuration_id>```
---
10. Get current status of proxy.

   Action:
   ```GET /configurations/<configuration_id>```
---
11. Get current status of experiment.
    
    Action:
    ```GET /experiments/<exp_id>```
---
12. Login (see [Authentication](docs/authentication.md))
    
    Action:
    ```POST /login```
---
13. Initialize crawler(s). Provide crawler data without ID's and queues. Response is used as a template for upcoming loads and updates. Used for new queus and changes of existing. 

    Action:
    ```POST /load```
    
    
    Sende crawler Id und vollständigen crawler mit im Zweifel anderen Werten bei agent, config und proxy.
    Je nach setup werden keine configs, agents und proxies gesendet, insbesondere falls sie sich nicht verändern sondern immer konstant bleiben.
    Response und gesendete Daten werden Temporär gespeichert und dienen zum überprüfen des loads
