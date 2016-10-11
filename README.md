




# Nextant

      Navigate through your cloud using Solr

**Nextant** indexes your documents and your shared files to perform fast and concise _Full-Text Search_. 

### Recognized file format: 
- plain text, 
- rtf, 
- pdf, old pdf will requiert Tesseract installed
- jpeg & tiff (will also requiert Tesseract)
- html, 
- openoffice, 
- microsoft office, 
 


## Installation

- [You first need to install a Solr servlet](https://github.com/daita/nextant/wiki)
- Download the .zip from the appstore, unzip and place this app in **nextcloud/apps/** (or clone the github and build the app yourself)
- Enable the app in the app list,
- Edit the settings in the administration page.
- Extract the current files from your cloud using the **./occ nextant:index** command 
- Have a look to this [explanation on how Nextant works](https://github.com/daita/nextant/wiki/Extracting,-Live-Update)
- _(Optional)_ [Installing Tesseract](https://github.com/tesseract-ocr/tesseract/wiki) ([Optical Character Recognition](https://en.wikipedia.org/wiki/Optical_character_recognition) (OCR) Engine) will allow Nextant to extract text from image file and old pdf.


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





