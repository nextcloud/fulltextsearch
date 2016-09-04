




# Nextant

What does it do right now:
- When uploaded to the cloud, text and pdf file are extracted to the Solr Server.
- It add an owner filter to solr documents so each user search in its own library.
- Use the Solr server when using the searchbox in the files App of your nextcloud.
- You can also extract your current files using ./occ nextant:scan 

What does it do right now that it should not :
- results can be displayed behind an element so it can't be clicked.

What will it be doing in the future:
- search within shared file.
- search within deleted file.
- extract more format (docx, ...)
- have a better display and a better indexing of the results



## Installation

- [You first need to install a Solr servlet](https://github.com/daita/nextant/wiki/Setup-your-local-standalone-Solr)
- Download the .zip from the appstore, unzip and place this app in **nextcloud/apps/** (or clone the github and build the app yourself)
- Enable the app in the app list,
- Edit the settings in the administration page.
- (Optionnal) Extract the current files from your cloud using the **./occ nextant:scan** command 




## Building the app

The app can be built by using the provided Makefile by running:

    make

This requires the following things to be present:
* make
* which
* tar: for building the archive
* curl: used if phpunit and composer are not installed to fetch them from the web
* npm: for building and testing everything JS, only required if a package.json is placed inside the **js/** folder

The make command will install or update Composer dependencies if a composer.json is present and also **npm run build** if a package.json is present in the **js/** folder. The npm **build** script should use local paths for build systems and package managers, so people that simply want to build the app won't need to install npm libraries globally, e.g.:

**package.json**:
```json
"scripts": {
    "test": "node node_modules/gulp-cli/bin/gulp.js karma",
    "prebuild": "npm install && node_modules/bower/bin/bower install && node_modules/bower/bin/bower update",
    "build": "node node_modules/gulp-cli/bin/gulp.js"
}
```





