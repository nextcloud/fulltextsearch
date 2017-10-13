# fullnextsearch

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/nextant/badges/quality-score.png?b=fullnextsearch)](https://scrutinizer-ci.com/g/nextcloud/nextant/?b=fullnextsearch)

**ALPHA - DO NOT INSTALL ON PROD ENVIRONMENT.**  

**FullNextSearch** allows you to index and search the content of your cloud.  

This is the core app of the Full Next Search app, and by itself won't do anything. To work, you will need to add few more apps (modules):

- A gateway to a _Search Platform_. It is an app that will communicate to a search platform (like Elastic Search, Solr, ...). right now, only one app is doing this: [FullNextSearch_ElasticSearch](https://github.com/daita/fullnextsearch_elasticsearch)
- Some _Content Provider_. Those apps will browse a specific type of content to generate the index. As of today, only your files can be indexed: [Files_FullNextSearch](https://github.com/daita/files_fullnextsearch)



### Installation

You can download the app from the store, or download the source from the git repository and copy it in **apps/**.

[Complete installation instruction](https://github.com/nextcloud/nextant/blob/fullnextsearch/INSTALL.md)


### Configuration

Add the app in the top bar (optional)

>     ./occ config:app:set --value '1' fullnextsearch app_navigation

smaller chunk _might_ need less memory (default is 1000)

>     ./occ config:app:set --value '50' fullnextsearch index_chunk


**Important:** each module will require few settings, please have a look to those README files to complete your configuration:

- https://github.com/daita/fullnextsearch_elasticsearch/blob/master/README.md
- https://github.com/daita/files_fullnextsearch/blob/master/README.md


