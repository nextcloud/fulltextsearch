# Changelog


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

