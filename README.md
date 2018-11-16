# Google Analytics Popular Posts
Uses a Google Analytics account to select and display the most recent posts on a site. 

Features:
* Updates via CRON in the brackground
* Requires no delay on the user side
* no extra database work for Wordpress to log every page, and then do analytics on it

# Usage
## Install via Composer
See [Composer documentation on using a private repo](https://getcomposer.org/doc/05-repositories.md#using-private-repositories)

Edit the composer.json in the root of the project 

Add to the repositories section:
```
"repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:65/ga-popular-posts.git"
    }
  ],
```

Add to the require section:
```
"require": {
    "sixfive/ga-popular-posts": "*",    
 },
```
Then run ``` composer update ``` in the root of your project

## Update

* Download premium code from website above
* Commit to root of this master repository
* Push
* In the project it is included in run ```composer update``` to bring in changes
* FTP or deploy to the site you are working on when you want it live
