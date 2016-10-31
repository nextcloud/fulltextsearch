**v0.6.2**
- adding suggestions - https://github.com/nextcloud/nextant/wiki/And-some-more-...
- adding external storage indexing
- adding server-side encrypted file indexing
- fixing an issue with nextant:live


**v0.6.1**
- fixing an issue with federated cloud sharing
- fixing an issue when indexing java source stored in the trashbin
- add --user option in ./occ nextant:index


**v0.6.0**
- Beta 2
- New Admin UI
- Complete rewrite of the index core engine using IndexService and QueueService
- ./occ nextant:live


**v0.5.1**
- indexes your Bookmarks
- bugfix: search allowed to non-admin
- bugfix: warning on deleted file with no extension
 
 
**v0.5.0**
- Beta release
- minor bugfixes
- new Admin UI
- new display on search result
- search within trash bin
- OCR/index jpeg/tiff file (needs Tesseract installed)


**v0.4.2**
- extract and index files from trash
- orphans documents are removed while indexing


**v0.4.1**
- Force index unlock after 24h
- maximum size on extracted file
- new field nextant_path
- bugfixes


**v0.4.0**
- index safe guard
- new options on nextant:index
- better display of search results: highlighting result & icons.
- lot of bugfixes


**v0.3.4**
* halt long update process


**v0.3.3**
* Exception issue in SolrAdmin/SolrTools
* rework on the indexing process


**v0.3.2**
* better script to index your current documents
* better managment of all file operations (move, shares, trash, ...)


**v0.3.1**
* 'configured' flag to verify that the app is well configured.
* auto-check your solr setup on upgrade.


**v0.3.0**
* Managing Solr Schema from Administration page or using ./occ nextant:check 
* bugfixes:
  - result being displayed twice.
  - escape few queries to Solr


**v0.2.0**
* New format: opendocument, office, rtf, html, epub, ...
* Search within shared file,
* Testing field before saving configuration
* new command: nextant:clear and nextant:check

