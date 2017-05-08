




# Nextant

>      Navigate through your cloud using Solr

**Nextant** performs fast and concise _Full-Text Search_ within:

- your own files,
- shared files,
- federated cloud shares,
- external storage,
- server-side encrypted storage,
- your bookmarks.
 

### Recognized file format: 
- plain text, 
- rtf, 
- pdf,
- jpeg & tiff (will requiert Tesseract)
- html, 
- openoffice, 
- microsoft office, 
 


## Installation

- [You first need to install a Solr servlet](https://github.com/nextcloud/nextant/wiki)
- Download the .tar.gz from the [appstore](https://apps.nextcloud.com/apps/nextant), unzip and place this app in **nextcloud/apps/** (or clone the github and build the app yourself)
- Enable the app in the app list,
- Edit the settings in the administration page.
- Enable Nextant using the **./occ app:enable nextant** command
- Test your Solr installation and save the configuration to Nextant using the **./occ nextant:test http://127.0.0.1:8983/solr/ nextant --save** command
- Extract the current files from your cloud using the **./occ nextant:index** command 
- Have a look to this [explanation on how Nextant works](https://github.com/nextcloud/nextant/wiki/Extracting,-Live-Update)
- _(Optional)_ [Installing Tesseract](https://github.com/tesseract-ocr/tesseract/wiki) ([Optical Character Recognition](https://en.wikipedia.org/wiki/Optical_character_recognition) (OCR) Engine) will allow Nextant to extract text from image file and pdfs without a text layer.

## Scripted installation (Ubuntu)
The developers of the [Nextcloud VM](https://github.com/nextcloud/vm) has made a [script](https://raw.githubusercontent.com/nextcloud/vm/master/apps/nextant.sh) that you can use.
Please note that you must change the variables in the script to suit your config before you run it.

To get the script, please type the folloing command: `wget https://github.com/nextcloud/vm/blob/master/apps/nextant.sh` and then run the script with `sudo bash nextant.sh`.

Please report any issues regarding the script in the [Nextcloud VM repo](https://github.com/nextcloud/vm/issues).

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





