


# 1. Elasticsearch

Install on Debian were done using this documentation : https://www.elastic.co/guide/en/elasticsearch/reference/master/deb.html

(Please keep me updated of any other documentation about installation of the last version (5.6) on other OS)


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



# 2. FullNextSearch

install all 3 apps (from git or from appstore):

- [FullNextSearch](https://github.com/nextcloud/nextant/tree/fullnextsearch)  
- [FullNextSearch_ElasticSearch](https://github.com/daita/fullnextsearch_elasticsearch)
- [Files_FullNextSearch](https://github.com/daita/files_fullnextsearch)



 

### basic configuration of the apps:

- set the search platform to ElasticSearch:
>     ./occ config:app:set --value 'OCA\FullNextSearch_ElasticSearch\Platform\ElasticSearchPlatform' fullnextsearch search_platform

- set the address to reach the ElasticSearch and the credentials for this nextcloud:
>     ./occ config:app:set --value 'http://username:password@localhost:9200' fullnextsearch_elasticsearch elastic_host

- set the index:
>     ./occ config:app:set --value 'my_index' fullnextsearch_elasticsearch elastic_index



# enjoy.

You will need to initiate a first index manually:

>     ./occ fullnextsearch:index



