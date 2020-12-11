# Changelog


### 20.0.0

- database migration
- upgrade deps


### 2.0.0

- compat nc20


### 1.4.2

- compat nc19
- error logs on missing Provider Options


### 1.4.1

- adding ./occ fulltextsearch:document:status
- ability to set the index status to IGNORE


### 1.4.0 (nc18)

- compat nc18


### 1.3.6

- some compat nc17


### 1.3.5

- ignore live index on cron
- live can be run as a service
- simple queries


### 1.3.4

- some improvement in the index comparison


### 1.3.2

- adding a key to the list of indexes for old version of NC
- no crash on missing provider 
- --no-readline can be passed with empty options


### 1.3.1

- issue with some providers.


### 1.3.0

- Chunks (NC 16)

### 1.2.3

- fixing issue with tests.
- adding IndexService->createIndex() (NC 15.0.1)


### 1.2.2

- cleaning


### 1.2.1

- initiating some vars
- adding the and: option


### 1.2.0 (NC15)

- Compat NC15 + full php7.
- breaking index on Ctrl-C.
- non interactive mode available during :index and :live


### 1.0.3

- improvement: display process advancement during compareWithCurrentIndex


### 1.0.2

- improvement: long process while indexing should not timeout (Force Quit).
- misc: removing compat with NC12.


### 1.0.1

improvement: some rework on the process wrapper.


### 1.0.0

First stable release



### 0.99.1 Release Candidate 2

- bugfix: crashing issue during :live
- database: documentId is now a string
- improvement: tags/metatags/subtags
- improvement: no more chunks, documents are indexed one by one.
- improvement: the :index command allow a navigation between results.


### 0.99.0 Release Candidate

Command Line Interface:

- The indexing process is now embedded in a new graphical wrapper, including an interactive interface for both the fulltextsearch:index and fulltextsearch:live commands.
- Errors are now displayed during index/live execution with navigation. Errors can be managed and deleted while indexing.
- new command: ./occ fulltextsearch:test to test the indexing and search platform.
- new command: ./occ fulltextsearch:document:provider to get info about a document from a provider.
- new command: ./occ fulltextsearch:document:platform to get info about a document from the search platform.
- ./occ fulltextsearch:reset can now be done for a specific provider only.
- ./occ fulltextsearch:index now accept users, providers, errors, chunk and paused options.
- fixing some display glitch.


### 0.8.2

- debug, testing tools
- get document
- multi-host


### 0.7.0

- navigation app to search within all content from your providers
- better navigation
- content (index and search) can be splited in Parts 
- rework on the exchange between platform and providers
- bugfixes



### 0.6.1

- bugfix: removing reset of the index on migration
- bugfix: do not retrieve access on ignored document



### 0.6.0

- Nextcloud integration
- Options panel
- bugfixes
 

### 0.5.1

- bugfixes



### 0.5.0

- managing errors from platform
- issues with JS



### v0.4.0

- fullnextsearch -> fulltextsearch
- Pagination
- settings panel



### v0.3.2

- UI: remove personal settings
- DB: fill err field on new indexes



### v0.3.1

- bugfixes.



### BETA v0.3.0

- First Beta

