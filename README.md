# Bizuno Portal (Self-Hosting)

Bizuno is a powerful open source ERP library. This project creates a portal used to access the library for users that want to host on a local server. PhreeSoft also offers hosting options utilizing the PhreSoft cloud.

## Authors

- [@phreesoft](https://www.phreesoft.com/)

## License

[AGPL](https://www.gnu.org/licenses/agpl-3.0.txt)

## Installation

The Bizuno library is best installed using composer.

Prior to installtion, you need to create a database and have the credentials to access it. For security, your data files may be located outside of your web server

1. Download latest release from this repository.
2. Upload files to your server.
3. Rename the file portalCFG-sample.php to portalCFG.php
4. Edit portalCFG.php and enter the credentials for the site following standard PHP syntax. If you want to locate your data outside of the web server space, make sure the full path to the private folder is specified. Don't forget the trailing slashes in folders.
5. Install the Bizuno Library

```bash
  composer install
```

6. Navigate in your browser to your server home page. You should be taken to the installation page.
7. Enter a username, password and any other necessary changes and press install.
8. After a few seconds, you should be redirected the Bizuno home page.

## Usage

1. The Home page has a dashboard listing recommended items that should be addressed sooner than later. Some items, such as the chart of accounts, cannot be changed after a journal post has been made. Others are just to help get your business set up.
2. Please visit the PhreeSoft website for additional dosumentation and support.

## FAQ

#### Where can I find the Bizuno library?

The Bizuno library is another project @GitHub named bizuno (https://github.com/phreesoft/bizuno)

## Support

For support, visit the PhreeSoft support page (https://www.phreesoft.com/support/).

