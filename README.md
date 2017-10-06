# fullnextsearch

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/daita/fullnextsearch/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/daita/fullnextsearch/?branch=master)

Nextant 2.0

### Note:

PRE-ALPHA - DO NOT INSTALL.

### install:

>      make composer

### also:

You will need :

- https://github.com/daita/fullnextsearch_elasticsearch
- https://github.com/daita/files_fullnextsearch

### configuration

Add the app in the top bar (optional)

>     ./occ config:app:set --value '1' fullnextsearch app_navigation

### options

smaller chunk _might_ need less memory (default is 1000)

>     ./occ config:app:set --value '50' fullnextsearch index_chunk
