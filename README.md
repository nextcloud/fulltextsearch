<!--
  - SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Full text search

[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/fulltextsearch)](https://api.reuse.software/info/github.com/nextcloud/fulltextsearch)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/fulltextsearch/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/fulltextsearch/?b=master)

_Full text search_ is the core app of a full-text search framework for your Nextcloud. 
To have it operate, and get content indexed, some other apps are needed: 

- Some **Providers Apps** to extract content from your Nextcloud. 
- A **Platform App** that communicate with a search platform _(ie. Elastic Search, Solr, …)_ in order to index the content provided by the **Providers**.   
_Note: There is no limit to the number of platform-apps that can be installed, however only one can be selected from the admin interface_



### Documentation

[Can be found on the wiki](https://github.com/nextcloud/fulltextsearch/wiki)
