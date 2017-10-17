


# 1. Elasticsearch

Install on Debian is done using this documentation : https://www.elastic.co/guide/en/elasticsearch/reference/5.6/deb.html

(Please keep me updated of any other documentation about installation of the last version (5.6) on other OS)

>     wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -

>     sudo apt-get install apt-transport-https

>     echo "deb https://artifacts.elastic.co/packages/5.x/apt stable main" | sudo tee -a /etc/apt/sources.list.d/elastic-5.x.list

>     sudo apt-get update && sudo apt-get install elasticsearch




## security:

This step will add Basic Authentication (with a login/password) to your ElasticSearch. This step can be bypassed if you have no issue leaving ElasticSearch running with no authentication.

Leaving ElasticSearch without authentication is **NOT** advised if those case:

- you bind the daemon to a network,
- user have ssh access to the server,
- malicious script can be uploaded and executed (PHP for example).

There is 2 ways to implement Basic Authentication to your ElasticSearch:

- The official plugin: [x-pack](https://www.elastic.co/guide/en/x-pack/current/xpack-introduction.html) with a 30 day trial license.
- An open-source (GPLv3) plugin: [ReadonlyREST](https://github.com/sscarduzio/elasticsearch-readonlyrest-plugin)


### setup ReadonlyREST

[Installation of ReadonlyREST](https://readonlyrest.com/documentation/index.html#Overview--Installing)

_Note: If you cannot find ReadonlyREST available for your current version of elasticsearch, you can downgrade using:_
>     apt-get install elasticsearch=5.6.1

### Restart the service


This is a simple configuration so you can setup multiple clouds on the same server, each one using it's own credentials:
```
readonlyrest:


  access_control_rules:

  - name: Accept requests from cloud1 on my_index
    groups: ["cloud1"]
    indices: ["my_index"]

  - name: Accept requests from cloud2 on another_index
    groups: ["cloud2"]
    indices: ["another_index"]


  users:

  - username: username
    auth_key: username:password
    groups: ["cloud1"]

  - username: test
    auth_key_sha1: a94a8fe5ccb19ba61c4c0873d391e987982fbbd3
    groups: ["cloud2"]
```

Add and **EDIT** those lines into _elasticsearch.yml_ (usually in /etc/elasticsearch/) with **your very own credentials**

This is where you define the login and password for your nextcloud, and the index to reach. In the lines above, we have 2 clouds (cloud1 and cloud2), each one having its own credentials to access its own index (my_index and another_index)

_Note: yaml can be really sensitive to copypasta, [if you have issue, please use this link](https://raw.githubusercontent.com/nextcloud/nextant/fullnextsearch/docs/elasticsearch-readonlyrest.yml)_



### ingest-attachment

If you want to index non-plaintext content (PDF, by example) you will need:

>     sudo bin/elasticsearch-plugin install ingest-attachment

Usually, the elasticsearch-plugin executable is in _/usr/share/elasticsearch/bin/_




### Restart the service

Restart elasticsearch.

# 2. FullNextSearch

install all 3 apps (from git or from appstore):

- [FullNextSearch](https://github.com/nextcloud/nextant/tree/fullnextsearch)  
- [FullNextSearch_ElasticSearch](https://github.com/daita/fullnextsearch_elasticsearch)
- [Files_FullNextSearch](https://github.com/daita/files_fullnextsearch)



 

### basic (but needed) configuration of the apps:

- set the search platform to ElasticSearch:
>     ./occ config:app:set --value 'OCA\FullNextSearch_ElasticSearch\Platform\ElasticSearchPlatform' fullnextsearch search_platform

- set the address to reach the ElasticSearch and the credentials for this nextcloud:
>     ./occ config:app:set --value 'http://username:password@localhost:9200' fullnextsearch_elasticsearch elastic_host

- set the index:
>     ./occ config:app:set --value 'my_index' fullnextsearch_elasticsearch elastic_index


c
# enjoy.

You will need to initiate a first index manually:

>     ./occ fullnextsearch:index

To reset the index:

>     ./occ fullnextsearch:reset

